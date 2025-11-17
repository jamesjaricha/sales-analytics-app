<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [];
        $status = 200;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $status = 503;
        }

        // Cache check
        try {
            Cache::put('health_check', 'ok', 60);
            $checks['cache'] = Cache::get('health_check') === 'ok' ? 'ok' : 'error';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
            $status = 503;
        }

        // Storage check
        $checks['storage'] = is_writable(storage_path()) ? 'ok' : 'error';
        if ($checks['storage'] === 'error') {
            $status = 503;
        }

        return response()->json([
            'status' => $status === 200 ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $status);
    }
}