<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase;

    private function rep(string $pin = '1234'): User
    {
        return User::factory()->create([
            'role' => 'sales_rep',
            'pin' => Hash::make($pin),
        ]);
    }

    public function test_login_screen_lists_reps_with_pins_only(): void
    {
        $withPin = $this->rep();
        $withoutPin = User::factory()->create(['role' => 'sales_rep', 'pin' => null]);
        $admin = User::factory()->create(['role' => 'admin', 'pin' => Hash::make('9999')]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee($withPin->name);
        $response->assertDontSee($withoutPin->name);
        $response->assertDontSee($admin->name);
    }

    public function test_rep_can_sign_in_with_correct_pin(): void
    {
        $rep = $this->rep('4321');

        $response = $this->post('/login/pin', [
            'user_id' => $rep->id,
            'pin' => '4321',
        ]);

        $response->assertRedirect(route('pos.create', absolute: false));
        $this->assertAuthenticatedAs($rep);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $rep = $this->rep('4321');

        $response = $this->post('/login/pin', [
            'user_id' => $rep->id,
            'pin' => '0000',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_admin_cannot_use_pin_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'pin' => Hash::make('4321')]);

        $response = $this->post('/login/pin', [
            'user_id' => $admin->id,
            'pin' => '4321',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_pin_attempts_are_rate_limited(): void
    {
        $rep = $this->rep('4321');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/pin', ['user_id' => $rep->id, 'pin' => '0000']);
        }

        // Sixth attempt is throttled even with the correct PIN
        $response = $this->post('/login/pin', [
            'user_id' => $rep->id,
            'pin' => '4321',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_email_login_form_is_still_available_for_admins(): void
    {
        $this->get('/login/email')->assertOk();
    }
}
