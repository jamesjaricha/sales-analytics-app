<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        // User must be logged in
        if (! Auth::check()) {
            return redirect('/login');
        }

        // Check if user role is in allowed roles
        if (! in_array(Auth::user()->role, $roles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
