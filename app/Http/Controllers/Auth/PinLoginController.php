<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PinLoginController extends Controller
{
    /**
     * The till PIN screen: pick your name, enter your PIN.
     * Admins (and reps without a PIN) use the email login link instead.
     */
    public function create(): View
    {
        $reps = User::where('role', 'sales_rep')
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('auth.pin-login', ['reps' => $reps]);
    }

    /**
     * Authenticate a sales rep by user + PIN.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $throttleKey = Str::transliterate('pin|'.$validated['user_id'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'pin' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('id', $validated['user_id'])
            ->where('role', 'sales_rep')
            ->whereNotNull('pin')
            ->first();

        if (! $user || ! Hash::check($validated['pin'], $user->pin)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'pin' => 'That PIN is not correct.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('pos.create', absolute: false));
    }
}
