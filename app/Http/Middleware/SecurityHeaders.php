<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Only apply HSTS in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Allow-listed third-party hosts (Google Fonts)
        $googleStyleHosts = 'https://fonts.googleapis.com';
        $googleFontHosts = 'https://fonts.gstatic.com';

        // More permissive CSP for development, stricter for production
        if (app()->environment('production')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' {$googleStyleHosts}; img-src 'self' data:; font-src 'self' {$googleFontHosts}; connect-src 'self';"
            );
        } else {
            // Development-friendly CSP - Allow Vite dev server
            // Support multiple ports (5173-5176) in case default port is in use
            // Note: IPv6 [::1] format is not valid in CSP, using localhost and 127.0.0.1 only
            $viteUrl = 'http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 http://localhost:5175 http://127.0.0.1:5175 http://localhost:5176 http://127.0.0.1:5176';
            $viteWs = 'ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174 ws://localhost:5175 ws://127.0.0.1:5175 ws://localhost:5176 ws://127.0.0.1:5176';
            // Allow Kaspersky antivirus scripts (optional - can be removed if not needed)
            $kaspersky = 'http://gc.kis.v2.scr.kaspersky-labs.com ws://gc.kis.v2.scr.kaspersky-labs.com';
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: {$viteUrl} {$kaspersky}; style-src 'self' 'unsafe-inline' blob: data: {$viteUrl} {$kaspersky} {$googleStyleHosts}; img-src 'self' data: blob:; font-src 'self' data: {$googleFontHosts}; connect-src 'self' ws: wss: {$viteWs} {$viteUrl} {$kaspersky}; object-src 'none'; base-uri 'self';"
            );
        }

        return $response;
    }
}
