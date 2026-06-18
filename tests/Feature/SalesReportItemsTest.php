<?php

namespace Tests\Feature;

use App\Models\DailySalesReport;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_day_end_report_shows_pos_line_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $report = DailySalesReport::create([
            'user_id' => $admin->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 5000,
            'total_deductions' => 0,
            'cash_at_hand' => 5000,
            'total_cash' => 5000,
            'total_bank' => 2799,
            'total_mobile_money' => 3499,
            'total_outstanding' => 0,
            'status' => 'completed',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $sale = Sale::create([
            'reference' => 'INV-TEST-0001',
            'user_id' => $admin->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'total_amount' => 5000,
            'status' => 'completed',
            'day_end_report_id' => $report->id,
        ]);
        $sale->items()->create([
            'product_name' => 'Luxpower 5kVA Inverter',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_price' => 5000,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.show', $report->id))
            ->assertOk()
            ->assertSee('Luxpower 5kVA Inverter') // POS item now shown
            ->assertSee('Settlement Breakdown')
            ->assertSee('Cash @ Bank')
            ->assertSee('Mobile Money');
    }

    public function test_legacy_report_still_shows_its_own_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $report = DailySalesReport::create([
            'user_id' => $admin->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 200,
            'total_deductions' => 0,
            'cash_at_hand' => 200,
            'status' => 'completed',
        ]);
        $report->items()->create([
            'product_name' => 'Legacy Solar Panel',
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.show', $report->id))
            ->assertOk()
            ->assertSee('Legacy Solar Panel') // no regression
            ->assertDontSee('Settlement Breakdown'); // hidden for legacy reports
    }
}
