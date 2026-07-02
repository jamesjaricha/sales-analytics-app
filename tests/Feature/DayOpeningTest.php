<?php

namespace Tests\Feature;

use App\Models\DayOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayOpeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_redirects_to_day_open_until_the_day_is_opened(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $this->actingAs($rep)->get('/pos')->assertRedirect(route('day.open'));
    }

    public function test_opening_the_day_stores_the_balance_and_unlocks_the_pos(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $response = $this->actingAs($rep)->post('/day/open', ['opening_balance' => 250.50]);

        $response->assertRedirect(route('pos.create'));
        $this->assertDatabaseHas('day_openings', [
            'business_date' => now()->toDateString().' 00:00:00',
            'opened_by' => $rep->id,
        ]);
        $this->assertEqualsWithDelta(250.50, DayOpening::forDate(now()->toDateString())->opening_balance, 0.001);

        $this->actingAs($rep)->get('/pos')->assertOk();
    }

    public function test_reopening_updates_the_existing_balance_instead_of_duplicating(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $this->actingAs($rep)->post('/day/open', ['opening_balance' => 100]);
        $this->actingAs($rep)->post('/day/open', ['opening_balance' => 175]);

        $this->assertSame(1, DayOpening::count());
        $this->assertEqualsWithDelta(175, DayOpening::forDate(now()->toDateString())->opening_balance, 0.001);
    }

    public function test_day_end_uses_the_sign_in_opening_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DayOpening::create([
            'business_date' => now()->toDateString(),
            'opening_balance' => 300,
            'opened_by' => $admin->id,
        ]);
        \App\Models\Sale::create([
            'reference' => 'INV-TEST-OPEN',
            'user_id' => $admin->id,
            'business_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'total_amount' => 400,
            'amount_due' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)->post('/day-end', []);

        $report = \App\Models\DailySalesReport::whereNotNull('approved_at')->first();
        $this->assertNotNull($report);
        $this->assertEqualsWithDelta(300, $report->opening_balance, 0.001);
        $this->assertEqualsWithDelta(700, $report->cash_at_hand, 0.001); // 300 b/f + 400 cash
    }

    public function test_opening_balance_is_required_and_numeric(): void
    {
        $rep = User::factory()->create(['role' => 'sales_rep']);

        $this->actingAs($rep)->post('/day/open', [])->assertSessionHasErrors('opening_balance');
        $this->actingAs($rep)->post('/day/open', ['opening_balance' => 'abc'])->assertSessionHasErrors('opening_balance');
        $this->actingAs($rep)->post('/day/open', ['opening_balance' => -5])->assertSessionHasErrors('opening_balance');
    }
}
