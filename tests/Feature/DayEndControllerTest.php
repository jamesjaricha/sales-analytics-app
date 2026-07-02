<?php

namespace Tests\Feature;

use App\Models\DailySalesReport;
use App\Models\DayOpening;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayEndControllerTest extends TestCase
{
    use RefreshDatabase;

    private function saleToday(string $method, float $amount): Sale
    {
        return Sale::create([
            'reference' => 'INV-'.fake()->unique()->numerify('TEST-#####'),
            'user_id' => User::factory()->create()->id,
            'business_date' => now()->toDateString(),
            'payment_method' => $method,
            'total_amount' => $amount,
            'amount_due' => $method === 'credit' ? $amount : 0,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_approve_the_day_end(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->saleToday('cash', 400);
        $this->saleToday('mobile_money', 100);

        $response = $this->actingAs($admin)->post('/day-end', [
            'expenses' => [['description' => 'Fuel', 'amount' => 50]],
            'counted_cash' => 345,
        ]);

        $report = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('day-end.show', $report));
        $this->assertEqualsWithDelta(350, $report->cash_at_hand, 0.001); // 400 cash - 50 expense
        $this->assertEqualsWithDelta(345, $report->counted_cash, 0.001);
        $this->assertSame(2, $report->sales()->count());
    }

    public function test_day_end_maths_with_opening_balance_partials_and_mixed_expenses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->saleToday('cash', 400);
        $this->saleToday('mobile_money', 100);

        // Credit invoice of 200 with 50 paid now in cash → 150 outstanding
        Sale::create([
            'reference' => 'INV-TEST-PARTIAL',
            'user_id' => User::factory()->create()->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'credit',
            'total_amount' => 200,
            'amount_due' => 150,
            'paid_amount' => 50,
            'paid_via' => 'cash',
            'customer_name' => 'Partial Customer',
            'status' => 'completed',
        ]);

        // Balance b/f captured at sign-in, not posted with the day-end
        DayOpening::create([
            'business_date' => now()->toDateString(),
            'opening_balance' => 200,
            'opened_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/day-end', [
            'expenses' => [
                ['description' => 'Fuel', 'amount' => 50, 'payment_method' => 'cash'],
                ['description' => 'Airtime', 'amount' => 30, 'payment_method' => 'mobile_money'],
            ],
            'counted_cash' => 600,
        ]);

        $report = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('day-end.show', $report));

        // Cash bucket = 400 cash sales + 50 cash partial = 450
        $this->assertEqualsWithDelta(450, $report->total_cash, 0.001);
        $this->assertEqualsWithDelta(100, $report->total_mobile_money, 0.001);
        $this->assertEqualsWithDelta(150, $report->total_outstanding, 0.001);

        // Cash at hand = takings only: 450 cash − 50 cash expenses.
        // The b/f is stored separately, NOT inside cash at hand.
        $this->assertEqualsWithDelta(400, $report->cash_at_hand, 0.001);
        $this->assertEqualsWithDelta(80, $report->total_deductions, 0.001);
        $this->assertEqualsWithDelta(200, $report->opening_balance, 0.001);

        $this->assertDatabaseHas('deductions', [
            'daily_sales_report_id' => $report->id,
            'description' => 'Airtime',
            'payment_method' => 'mobile_money',
        ]);
    }

    public function test_report_nets_bank_and_mobile_expenses_and_reconciles_total_held(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->saleToday('cash', 400);
        $this->saleToday('mobile_money', 300);

        DayOpening::create([
            'business_date' => now()->toDateString(),
            'opening_balance' => 200,
            'opened_by' => $admin->id,
        ]);

        $this->actingAs($admin)->post('/day-end', [
            'expenses' => [
                ['description' => 'Fuel', 'amount' => 50, 'payment_method' => 'cash'],
                ['description' => 'Airtime', 'amount' => 30, 'payment_method' => 'mobile_money'],
            ],
            'counted_cash' => 550,
        ]);

        $report = DailySalesReport::whereNotNull('approved_at')->first();

        // Cash at hand = takings only (400 cash − 50 cash expense); b/f excluded
        $this->assertEqualsWithDelta(350, $report->cash_at_hand, 0.001);
        // Stored channel columns remain gross
        $this->assertEqualsWithDelta(300, $report->total_mobile_money, 0.001);

        // Report page shows the netted mobile (270) and reconciled total held (620)
        $response = $this->actingAs($admin)->get(route('day-end.show', $report));
        $response->assertOk();
        $response->assertSee('270.00'); // 300 mobile − 30 mobile expense
        $response->assertSee('620.00'); // 350 cash at hand + 270 mobile + 0 bank
        $response->assertSee('550.00'); // expected in drawer = 200 b/f + 350 takings

        // The submitter report (sales.show) nets the same way and explains the maths
        $salesView = $this->actingAs($admin)->get('/sales/'.$report->id);
        $salesView->assertOk();
        $salesView->assertSee('270.00');
        $salesView->assertSee('620.00');
        $salesView->assertSee('cash expenses');

        // And its PDF renders without error
        $this->actingAs($admin)->get('/sales/'.$report->id.'/pdf')->assertOk();
    }

    public function test_admin_can_reopen_todays_day_end_and_reapprove(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = $this->saleToday('cash', 400);

        $this->actingAs($admin)->post('/day-end', [
            'expenses' => [['description' => 'Fuel', 'amount' => 50, 'payment_method' => 'cash']],
        ]);
        $report = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($sale->fresh()->day_end_report_id);

        $response = $this->actingAs($admin)->post("/day-end/{$report->id}/reopen");

        $response->assertRedirect(route('day-end.create'));
        $this->assertDatabaseMissing('daily_sales_reports', ['id' => $report->id]);
        $this->assertNull($sale->fresh()->day_end_report_id); // unlocked
        $this->assertDatabaseCount('deductions', 0);

        // Day can be corrected and approved again
        $this->actingAs($admin)->post('/day-end', ['counted_cash' => 400]);
        $fresh = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($fresh);
        $this->assertEqualsWithDelta(400, $fresh->cash_at_hand, 0.001);
    }

    public function test_sales_rep_cannot_reopen_a_day_end(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $this->saleToday('cash', 400);

        $this->actingAs($admin)->post('/day-end', []);
        $report = DailySalesReport::whereNotNull('approved_at')->first();

        $this->actingAs($rep)->post("/day-end/{$report->id}/reopen");

        $this->assertDatabaseHas('daily_sales_reports', ['id' => $report->id]);
    }

    public function test_an_older_day_end_cannot_be_reopened(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $old = DailySalesReport::create([
            'user_id' => $admin->id,
            'sale_date' => now()->subDay()->toDateString(),
            'total_sales_value' => 100,
            'total_deductions' => 0,
            'cash_at_hand' => 100,
            'status' => 'completed',
            'approved_by' => $admin->id,
            'approved_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->post("/day-end/{$old->id}/reopen");

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('daily_sales_reports', ['id' => $old->id]);
    }

    public function test_sales_rep_can_run_the_day_end(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $this->saleToday('cash', 400);

        $this->actingAs($rep)->get('/day-end')->assertOk();

        $response = $this->actingAs($rep)->post('/day-end', [
            'counted_cash' => 400,
        ]);

        $report = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('day-end.show', $report));
        $this->assertSame($rep->id, $report->approved_by);
    }

    public function test_guest_cannot_access_day_end(): void
    {
        $this->get('/day-end')->assertRedirect();
    }
}
