<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use App\Services\DayEndService;
use App\Services\ReportingService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'sales_rep']);
    }

    private function service(): SaleService
    {
        return app(SaleService::class);
    }

    /**
     * @param  array<int, array{method: string, amount: float|int}>  $tenders
     * @param  array<string, mixed>  $overrides
     */
    private function splitSale(array $tenders, float $itemPrice = 500, array $overrides = []): Sale
    {
        return $this->service()->record(array_merge([
            'payment_method' => PaymentMethod::Split->value,
            'tenders' => $tenders,
            'items' => [
                ['product_name' => 'Inverter', 'quantity' => 1, 'unit_price' => $itemPrice],
            ],
        ], $overrides), $this->user);
    }

    public function test_fully_settled_split_records_tender_lines(): void
    {
        $sale = $this->splitSale([
            ['method' => 'cash', 'amount' => 300],
            ['method' => 'mobile_money', 'amount' => 200],
        ]);

        $this->assertSame(PaymentMethod::Split, $sale->payment_method);
        $this->assertEquals(500, $sale->total_amount);
        $this->assertEquals(0, $sale->amount_due);
        $this->assertEquals(500, $sale->paid_amount);
        $this->assertNull($sale->paid_via);

        $this->assertCount(2, $sale->salePayments);
        $this->assertDatabaseHas('sale_payments', ['sale_id' => $sale->id, 'method' => 'cash', 'amount' => 300]);
        $this->assertDatabaseHas('sale_payments', ['sale_id' => $sale->id, 'method' => 'mobile_money', 'amount' => 200]);
    }

    public function test_underpaid_split_leaves_remainder_outstanding_and_requires_customer(): void
    {
        $sale = $this->splitSale(
            [['method' => 'cash', 'amount' => 300]],
            500,
            ['customer_name' => 'John Banda', 'customer_phone' => '0977123456'],
        );

        $this->assertEquals(200, $sale->amount_due);
        $this->assertEquals(300, $sale->paid_amount);
    }

    public function test_underpaid_split_without_customer_details_is_rejected(): void
    {
        try {
            $this->splitSale([['method' => 'cash', 'amount' => 300]], 500);
            $this->fail('Expected a ValidationException for a split with a balance owing and no customer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_name', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_payments', 0);
    }

    public function test_overpaid_split_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->splitSale([
            ['method' => 'cash', 'amount' => 400],
            ['method' => 'bank', 'amount' => 200],
        ], 500);
    }

    public function test_split_with_invalid_or_zero_tender_is_rejected(): void
    {
        try {
            $this->splitSale([['method' => 'credit', 'amount' => 500]]);
            $this->fail('A credit tender line must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tenders', $e->errors());
        }

        try {
            $this->splitSale([['method' => 'cash', 'amount' => 0]]);
            $this->fail('A zero tender line must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tenders', $e->errors());
        }

        try {
            $this->splitSale([]);
            $this->fail('A split with no tender lines must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tenders', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_tenders_are_ignored_for_single_method_sales(): void
    {
        $sale = $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'tenders' => [['method' => 'bank', 'amount' => 100]],
            'items' => [['product_name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ], $this->user);

        $this->assertCount(0, $sale->salePayments);
        $this->assertDatabaseCount('sale_payments', 0);
    }

    public function test_day_end_summary_counts_split_tenders_per_channel(): void
    {
        // Plain cash sale + fully settled split + split with remainder
        $this->service()->record([
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_name' => 'Bulb', 'quantity' => 1, 'unit_price' => 100]],
        ], $this->user);

        $this->splitSale([
            ['method' => 'cash', 'amount' => 300],
            ['method' => 'mobile_money', 'amount' => 200],
        ]);

        $this->splitSale(
            [['method' => 'bank', 'amount' => 250]],
            400,
            ['customer_name' => 'Mary Phiri', 'customer_phone' => '0966123456'],
        );

        $summary = app(DayEndService::class)->summary(now()->toDateString());

        $this->assertEqualsWithDelta(1000, $summary['gross_sales'], 0.001); // 100 + 500 + 400
        $this->assertEqualsWithDelta(400, $summary['total_cash'], 0.001);   // 100 + 300
        $this->assertEqualsWithDelta(250, $summary['total_bank'], 0.001);
        $this->assertEqualsWithDelta(200, $summary['total_mobile_money'], 0.001);
        $this->assertEqualsWithDelta(150, $summary['total_outstanding'], 0.001); // 400 - 250

        // Reconciliation identity: channels + outstanding = gross
        $settled = $summary['total_cash'] + $summary['total_bank']
            + $summary['total_mobile_money'] + $summary['total_outstanding'];
        $this->assertEqualsWithDelta($summary['gross_sales'], $settled, 0.001);
    }

    public function test_settlement_breakdown_includes_splits_and_credit_partials(): void
    {
        $this->splitSale([
            ['method' => 'cash', 'amount' => 300],
            ['method' => 'mobile_money', 'amount' => 200],
        ]);

        // Credit sale with a partial payment via bank
        $this->service()->record([
            'payment_method' => PaymentMethod::Credit->value,
            'customer_name' => 'John Banda',
            'customer_phone' => '0977123456',
            'paid_amount' => 40,
            'paid_via' => 'bank',
            'items' => [['product_name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ], $this->user);

        $b = app(ReportingService::class)->settlementBreakdown(now()->startOfDay(), now()->endOfDay());

        $this->assertEqualsWithDelta(300, $b['cash'], 0.001);
        $this->assertEqualsWithDelta(40, $b['bank'], 0.001);
        $this->assertEqualsWithDelta(200, $b['mobile_money'], 0.001);
        $this->assertEqualsWithDelta(60, $b['outstanding'], 0.001);
        $this->assertEqualsWithDelta(600, $b['total'], 0.001); // = gross sales
    }

    public function test_split_with_balance_appears_on_debtors_and_takes_repayment(): void
    {
        $sale = $this->splitSale(
            [['method' => 'cash', 'amount' => 300]],
            500,
            ['customer_name' => 'John Banda', 'customer_phone' => '0977123456'],
        );

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('debtors.index'))
            ->assertOk()
            ->assertSee('John Banda')
            ->assertSee($sale->reference);

        $payment = $this->service()->receivePayment($sale, 200.0, 'mobile_money', $admin);

        $this->assertEquals(200, $payment->amount);
        $this->assertEquals(0, (float) $sale->fresh()->amount_due);
    }

    public function test_repayment_rejected_for_fully_settled_split(): void
    {
        $sale = $this->splitSale([
            ['method' => 'cash', 'amount' => 300],
            ['method' => 'bank', 'amount' => 200],
        ]);

        $this->expectException(ValidationException::class);
        $this->service()->receivePayment($sale, 50.0, 'cash', $this->user);
    }

    public function test_approving_day_end_locks_split_sales_with_correct_totals(): void
    {
        $this->splitSale([
            ['method' => 'cash', 'amount' => 300],
            ['method' => 'mobile_money', 'amount' => 200],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $report = app(DayEndService::class)->approve(now()->toDateString(), $admin);

        $this->assertEqualsWithDelta(500, $report->total_sales_value, 0.001);
        $this->assertEqualsWithDelta(300, $report->total_cash, 0.001);
        $this->assertEqualsWithDelta(200, $report->total_mobile_money, 0.001);
        $this->assertEqualsWithDelta(0, $report->total_outstanding, 0.001);
        $this->assertTrue(Sale::first()->isLocked());
    }

    public function test_pos_endpoint_records_a_split_sale(): void
    {
        \App\Models\DayOpening::create([
            'business_date' => now()->toDateString(),
            'opening_balance' => 0,
            'opened_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('pos.store'), [
                'payment_method' => 'split',
                'tenders' => [
                    ['method' => 'cash', 'amount' => 60],
                    ['method' => 'mobile_money', 'amount' => 40],
                ],
                'items' => [
                    ['product_name' => 'Cable', 'quantity' => 1, 'unit_price' => 100],
                ],
            ])
            ->assertRedirect(route('pos.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sales', ['payment_method' => 'split', 'amount_due' => 0]);
        $this->assertDatabaseCount('sale_payments', 2);
    }
}
