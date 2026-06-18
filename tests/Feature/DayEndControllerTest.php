<?php

namespace Tests\Feature;

use App\Models\DailySalesReport;
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
