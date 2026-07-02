<?php

namespace Tests\Feature;

use App\Models\DailySalesReport;
use App\Models\DebtPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtRepaymentTest extends TestCase
{
    use RefreshDatabase;

    private function creditSale(User $recorder, float $total, float $due, ?string $businessDate = null): Sale
    {
        return Sale::create([
            'reference' => 'INV-'.fake()->unique()->numerify('REPAY-#####'),
            'user_id' => $recorder->id,
            'business_date' => $businessDate ?? now()->toDateString(),
            'payment_method' => 'credit',
            'total_amount' => $total,
            'amount_due' => $due,
            'paid_amount' => $total - $due,
            'customer_name' => 'John Banda',
            'customer_phone' => '0977123456',
            'status' => 'completed',
        ]);
    }

    public function test_pos_requires_a_phone_number_for_credit_sales(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $response = $this->actingAs($rep)->post('/pos', [
            'payment_method' => 'credit',
            'customer_name' => 'John Banda',
            'items' => [['product_name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $response->assertSessionHasErrors('customer_phone');

        $response = $this->actingAs($rep)->post('/pos', [
            'payment_method' => 'credit',
            'customer_name' => 'John Banda',
            'customer_phone' => '0977 123 456',
            'items' => [['product_name' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('sales', ['customer_name' => 'John Banda', 'customer_phone' => '0977 123 456']);
    }

    public function test_partial_repayment_reduces_the_balance_and_is_recorded(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $sale = $this->creditSale($rep, 500, 300);

        $response = $this->actingAs($rep)->post("/debtors/{$sale->id}/payments", [
            'amount' => 120,
            'payment_method' => 'mobile_money',
            'note' => 'Receipt 55',
        ]);

        $response->assertRedirect(route('debtors.index'));
        $this->assertEqualsWithDelta(180, $sale->fresh()->amount_due, 0.001);
        $this->assertDatabaseHas('debt_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'mobile_money',
            'received_by' => $rep->id,
            'note' => 'Receipt 55',
        ]);
    }

    public function test_repayment_cannot_exceed_the_outstanding_balance(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $sale = $this->creditSale($rep, 500, 100);

        $response = $this->actingAs($rep)->post("/debtors/{$sale->id}/payments", [
            'amount' => 150,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEqualsWithDelta(100, $sale->fresh()->amount_due, 0.001);
        $this->assertDatabaseCount('debt_payments', 0);
    }

    public function test_fully_repaid_invoice_leaves_the_debtors_page(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $sale = $this->creditSale($rep, 500, 200);

        // Follow the redirect so the success toast (which names the invoice)
        // is consumed before asserting on a clean page load
        $this->actingAs($rep)->followingRedirects()->post("/debtors/{$sale->id}/payments", [
            'amount' => 200,
            'payment_method' => 'cash',
        ]);

        $this->assertEqualsWithDelta(0, $sale->fresh()->amount_due, 0.001);
        $this->actingAs($rep)->get('/debtors')->assertDontSee($sale->reference);
    }

    public function test_repayment_of_an_old_invoice_lands_in_todays_day_end_and_locks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Debt from a previous trading day
        $oldSale = $this->creditSale($admin, 400, 400, now()->subDays(3)->toDateString());

        $this->actingAs($admin)->post("/debtors/{$oldSale->id}/payments", [
            'amount' => 250,
            'payment_method' => 'mobile_money',
        ])->assertSessionDoesntHaveErrors();

        // A cash sale today so the day-end has an invoice too
        Sale::create([
            'reference' => 'INV-TODAY-0001',
            'user_id' => $admin->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'total_amount' => 100,
            'amount_due' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)->post('/day-end', []);

        $report = DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($report);

        // Repayment settles into today's mobile money bucket; gross sales unaffected
        $this->assertEqualsWithDelta(250, $report->total_mobile_money, 0.001);
        $this->assertEqualsWithDelta(100, $report->total_cash, 0.001);
        $this->assertEqualsWithDelta(100, $report->total_sales_value, 0.001);

        // And the payment is locked into the report
        $this->assertSame($report->id, DebtPayment::first()->day_end_report_id);
    }

    public function test_repayment_blocked_once_todays_day_end_is_approved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sale = $this->creditSale($admin, 400, 400, now()->subDay()->toDateString());

        DailySalesReport::create([
            'user_id' => $admin->id,
            'sale_date' => now()->toDateString(),
            'total_sales_value' => 0,
            'total_deductions' => 0,
            'cash_at_hand' => 0,
            'status' => 'completed',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post("/debtors/{$sale->id}/payments", [
            'amount' => 100,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('debt_payments', 0);
    }
}
