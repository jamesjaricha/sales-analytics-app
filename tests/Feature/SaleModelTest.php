<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\DailySalesReport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sale_has_items_a_user_and_a_product(): void
    {
        $user = User::factory()->create(['role' => 'sales_rep']);
        $product = Product::create([
            'name' => 'Test Panel',
            'sku' => 'TP-1',
            'price' => 100,
        ]);

        $sale = $this->makeSale(['user_id' => $user->id]);
        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);

        $this->assertCount(1, $sale->items);
        $this->assertSame($user->id, $sale->user->id);
        $this->assertSame($product->id, $sale->items->first()->product->id);
    }

    public function test_payment_method_is_cast_to_an_enum(): void
    {
        $sale = $this->makeSale(['payment_method' => PaymentMethod::MobileMoney->value]);

        $this->assertInstanceOf(PaymentMethod::class, $sale->payment_method);
        $this->assertSame(PaymentMethod::MobileMoney, $sale->payment_method);
        $this->assertSame('Mobile Money', $sale->payment_method->label());
    }

    public function test_query_scopes_filter_sales(): void
    {
        $this->makeSale(['status' => 'completed', 'business_date' => '2026-06-17']);
        $this->makeSale(['status' => 'void', 'business_date' => '2026-06-17']);
        $this->makeSale(['status' => 'completed', 'business_date' => '2026-06-16']);

        $this->assertSame(2, Sale::completed()->count());
        $this->assertSame(2, Sale::forDate('2026-06-17')->count());
        $this->assertSame(3, Sale::unreconciled()->count());
    }

    public function test_sale_links_to_an_approved_day_end_and_locks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $report = DailySalesReport::create([
            'user_id' => $admin->id,
            'sale_date' => '2026-06-17',
            'total_sales_value' => 500,
            'total_deductions' => 0,
            'cash_at_hand' => 300,
            'status' => 'completed',
            'total_cash' => 300,
            'total_bank' => 100,
            'total_mobile_money' => 100,
            'total_outstanding' => 0,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $sale = $this->makeSale(['day_end_report_id' => $report->id]);

        $this->assertTrue($report->isApproved());
        $this->assertSame($admin->id, $report->approvedBy->id);
        $this->assertCount(1, $report->sales);
        $this->assertTrue($sale->isLocked());

        // Reconciliation identity: cash + bank + mobile + outstanding = gross
        $settled = $report->total_cash + $report->total_bank
            + $report->total_mobile_money + $report->total_outstanding;
        $this->assertEquals($report->total_sales_value, $settled);
    }

    public function test_an_unreconciled_sale_is_not_locked(): void
    {
        $this->assertFalse($this->makeSale()->isLocked());
    }

    public function test_payment_method_options_lists_all_four_settlements(): void
    {
        $options = PaymentMethod::options();

        $this->assertCount(4, $options);
        $this->assertSame(
            ['value' => 'credit', 'label' => 'Outstanding Debt (Credit)'],
            $options[3],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSale(array $overrides = []): Sale
    {
        $userId = $overrides['user_id'] ?? User::factory()->create()->id;

        return Sale::create(array_merge([
            'reference' => 'INV-'.fake()->unique()->numerify('TEST-#####'),
            'user_id' => $userId,
            'business_date' => '2026-06-17',
            'payment_method' => PaymentMethod::Cash->value,
            'total_amount' => 200,
            'status' => 'completed',
        ], $overrides));
    }
}
