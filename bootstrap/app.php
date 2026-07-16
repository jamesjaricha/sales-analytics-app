<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Prevent open redirects - validate redirect URLs
        $middleware->validateCsrfTokens(except: [
            // Add any routes that need CSRF exemption (e.g., webhooks)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 404 errors gracefully
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Resource not found'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // Handle 403 errors gracefully
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            // Log the unauthorized access
            Log::warning('403 Access Denied', [
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->view('errors.403', [], 403);
        });

        // Log critical errors for debugging on shared hosting
        $exceptions->report(function (Throwable $e) {
            if (app()->environment('production')) {
                Log::error('Application Error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    })->create();
