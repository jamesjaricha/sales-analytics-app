<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Services\DayEndService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DayEndServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'sales_rep']);
        $this->date = now()->toDateString();
    }

    private function service(): DayEndService
    {
        return app(DayEndService::class);
    }

    private function sale(string $method, float $amount, ?float $due = null): Sale
    {
        return Sale::create([
            'reference' => 'INV-'.fake()->unique()->numerify('TEST-#####'),
            'user_id' => $this->user->id,
            'business_date' => $this->date,
            'payment_method' => $method,
            'total_amount' => $amount,
            'amount_due' => $due ?? ($method === 'credit' ? $amount : 0),
            'status' => 'completed',
        ]);
    }

    public function test_summary_aggregates_by_payment_method(): void
    {
        $this->sale('cash', 100);
        $this->sale('cash', 50);
        $this->sale('bank', 200);
        $this->sale('mobile_money', 30);
        $this->sale('credit', 80);

        $summary = $this->service()->summary($this->date);

        $this->assertSame(5, $summary['invoice_count']);
        $this->assertEqualsWithDelta(460, $summary['gross_sales'], 0.001);
        $this->assertEqualsWithDelta(150, $summary['total_cash'], 0.001);
        $this->assertEqualsWithDelta(200, $summary['total_bank'], 0.001);
        $this->assertEqualsWithDelta(30, $summary['total_mobile_money'], 0.001);
        $this->assertEqualsWithDelta(80, $summary['total_outstanding'], 0.001);
    }

    public function test_approve_creates_report_and_locks_sales(): void
    {
        $a = $this->sale('cash', 100);
        $b = $this->sale('bank', 50);

        $report = $this->service()->approve($this->date, $this->user);

        $this->assertTrue($report->isApproved());
        $this->assertEqualsWithDelta(150, $report->total_sales_value, 0.001);
        $this->assertEqualsWithDelta(100, $report->total_cash, 0.001);
        $this->assertEqualsWithDelta(100, $report->cash_at_hand, 0.001); // no expenses
        $this->assertSame($report->id, $a->fresh()->day_end_report_id);
        $this->assertSame($report->id, $b->fresh()->day_end_report_id);
        $this->assertTrue($a->fresh()->isLocked());
    }

    public function test_expenses_reduce_cash_at_hand(): void
    {
        $this->sale('cash', 300);

        $report = $this->service()->approve($this->date, $this->user, [
            ['description' => 'Transport', 'amount' => 50],
            ['description' => 'Airtime', 'amount' => 20],
        ]);

        $this->assertEqualsWithDelta(70, $report->total_deductions, 0.001);
        $this->assertEqualsWithDelta(230, $report->cash_at_hand, 0.001); // 300 - 70
        $this->assertCount(2, $report->deductions);
    }

    public function test_counted_cash_is_stored(): void
    {
        $this->sale('cash', 200);

        $report = $this->service()->approve($this->date, $this->user, [], 195.0);

        $this->assertEqualsWithDelta(195, $report->counted_cash, 0.001);
    }

    public function test_cannot_approve_twice(): void
    {
        $this->sale('cash', 100);
        $this->service()->approve($this->date, $this->user);

        $this->expectException(ValidationException::class);
        $this->service()->approve($this->date, $this->user);
    }

    public function test_cannot_approve_with_no_sales(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->approve($this->date, $this->user);
    }

    public function test_reconciliation_identity_holds(): void
    {
        $this->sale('cash', 100);
        $this->sale('bank', 50);
        $this->sale('mobile_money', 30);
        $this->sale('credit', 20);

        $report = $this->service()->approve($this->date, $this->user);

        $settled = $report->total_cash + $report->total_bank
            + $report->total_mobile_money + $report->total_outstanding;
        $this->assertEqualsWithDelta($report->total_sales_value, $settled, 0.001);
    }
}
