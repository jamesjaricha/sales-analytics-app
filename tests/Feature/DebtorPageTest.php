<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtorPageTest extends TestCase
{
    use RefreshDatabase;

    private function creditSale(User $recorder, string $customer, float $total, float $due, array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'reference' => 'INV-'.fake()->unique()->numerify('DEBT-#####'),
            'user_id' => $recorder->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'credit',
            'total_amount' => $total,
            'amount_due' => $due,
            'paid_amount' => $total - $due,
            'paid_via' => $total - $due > 0 ? 'cash' : null,
            'customer_name' => $customer,
            'status' => 'completed',
        ], $overrides));
    }

    public function test_lists_outstanding_debtors_with_recorder_and_date(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep', 'name' => 'Chipo Mwale']);
        $this->creditSale($rep, 'John Banda', 500, 300);

        $response = $this->actingAs($rep)->get('/debtors');

        $response->assertOk();
        $response->assertSee('John Banda');
        $response->assertSee('Chipo Mwale');
        $response->assertSee('300.00');
        $response->assertSee(now()->format('D, d M Y'));
    }

    public function test_settled_and_non_credit_invoices_are_excluded(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $this->creditSale($rep, 'Fully Paid Customer', 200, 0);
        Sale::create([
            'reference' => 'INV-CASH-0001',
            'user_id' => $rep->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'total_amount' => 150,
            'amount_due' => 0,
            'customer_name' => 'Cash Customer',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($rep)->get('/debtors');

        $response->assertOk();
        $response->assertDontSee('Fully Paid Customer');
        $response->assertDontSee('Cash Customer');
        $response->assertSee('No outstanding debts');
    }

    public function test_debts_are_grouped_per_customer_with_a_total(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);
        $this->creditSale($rep, 'Mary Phiri', 400, 250);
        $this->creditSale($rep, 'mary phiri', 100, 100); // same client, different casing

        $response = $this->actingAs($rep)->get('/debtors');

        $response->assertOk();
        $response->assertSee('350.00'); // grouped total per client
    }

    public function test_search_filters_by_customer_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->creditSale($admin, 'John Banda', 500, 300);
        $this->creditSale($admin, 'Mary Phiri', 200, 200);

        $response = $this->actingAs($admin)->get('/debtors?q=banda');

        $response->assertOk();
        $response->assertSee('John Banda');
        $response->assertDontSee('Mary Phiri');
    }

    public function test_guests_cannot_view_debtors(): void
    {
        $this->get('/debtors')->assertRedirect();
    }
}
