<?php

namespace Tests\Feature;

use App\Models\DailySalesReport;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportingService $reporting;

    private User $user;

    private Carbon $start;

    private Carbon $end;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporting = app(ReportingService::class);
        $this->user = User::factory()->create();
        $this->start = now()->startOfMonth();
        $this->end = now()->endOfMonth();
    }

    private function posSale(string $method, float $amount, ?string $date = null): Sale
    {
        return Sale::create([
            'reference' => 'INV-'.fake()->unique()->numerify('TEST-#####'),
            'user_id' => $this->user->id,
            'business_date' => $date ?? now()->toDateString(),
            'payment_method' => $method,
            'total_amount' => $amount,
            'amount_due' => $method === 'credit' ? $amount : 0,
            'status' => 'completed',
        ]);
    }

    private function legacyReport(float $total, ?string $date = null): DailySalesReport
    {
        return DailySalesReport::create([
            'user_id' => $this->user->id,
            'sale_date' => $date ?? now()->toDateString(),
            'total_sales_value' => $total,
            'total_deductions' => 0,
            'cash_at_hand' => $total,
            'status' => 'completed',
            // approved_at intentionally null => legacy batch report
        ]);
    }

    public function test_total_sales_unions_pos_and_legacy(): void
    {
        $this->posSale('cash', 100);
        $this->posSale('bank', 50);
        $this->legacyReport(200, now()->subDay()->toDateString());

        $this->assertEqualsWithDelta(350, $this->reporting->totalSales($this->start, $this->end), 0.001);
    }

    public function test_total_sales_excludes_day_end_summaries(): void
    {
        $a = $this->posSale('cash', 100);
        $b = $this->posSale('bank', 50);

        // An approved day-end summary for today (sale_date is unique, so it's the only report row)
        $dayEnd = DailySalesReport::create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 150,
            'total_deductions' => 0,
            'cash_at_hand' => 100,
            'status' => 'completed',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);
        Sale::whereIn('id', [$a->id, $b->id])->update(['day_end_report_id' => $dayEnd->id]);

        // 150 (the invoices) — NOT 300 (invoices + the day-end summary counted again)
        $this->assertEqualsWithDelta(150, $this->reporting->totalSales($this->start, $this->end), 0.001);
    }

    public function test_settlement_breakdown(): void
    {
        $this->posSale('cash', 100);
        $this->posSale('cash', 50);
        $this->posSale('bank', 200);
        $this->posSale('mobile_money', 30);
        $this->posSale('credit', 80);

        $b = $this->reporting->settlementBreakdown($this->start, $this->end);

        $this->assertEqualsWithDelta(150, $b['cash'], 0.001);
        $this->assertEqualsWithDelta(200, $b['bank'], 0.001);
        $this->assertEqualsWithDelta(30, $b['mobile_money'], 0.001);
        $this->assertEqualsWithDelta(80, $b['outstanding'], 0.001);
        $this->assertEqualsWithDelta(460, $b['total'], 0.001);
    }

    public function test_top_products_unions_both_item_sources(): void
    {
        $sale = $this->posSale('cash', 300);
        $sale->items()->create(['product_name' => 'Panel', 'quantity' => 3, 'unit_price' => 100, 'total_price' => 300]);

        $report = $this->legacyReport(400);
        $report->items()->create(['product_name' => 'Panel', 'quantity' => 2, 'unit_price' => 100, 'total_price' => 200]);
        $report->items()->create(['product_name' => 'Battery', 'quantity' => 1, 'unit_price' => 200, 'total_price' => 200]);

        $top = $this->reporting->topProducts($this->start, $this->end);

        $this->assertSame('Panel', $top->first()->product_name);
        $this->assertSame(5, $top->first()->total_quantity); // 3 POS + 2 legacy
        $this->assertSame('Battery', $top->last()->product_name);
        $this->assertSame(1, $top->last()->total_quantity);
    }

    public function test_daily_totals_union_per_day(): void
    {
        $today = now()->toDateString();
        $this->posSale('cash', 100, $today);
        $this->legacyReport(50, $today);

        $totals = $this->reporting->dailyTotals(now()->startOfDay(), now()->endOfDay());

        $this->assertEqualsWithDelta(150, $totals[$today], 0.001);
    }
}
