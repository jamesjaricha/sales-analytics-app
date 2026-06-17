<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        // User must be logged in
        if (! Auth::check()) {
            return redirect('/login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        // Check if user role is in allowed roles
        if (! in_array($user->role, $roles)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            // Clear session and logout user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to login with message
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access this page. Please login with appropriate credentials.')
                ->with('unauthorized_access', true);
        }

        return $next($request);
    }
}
