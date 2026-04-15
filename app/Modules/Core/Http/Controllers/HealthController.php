<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * HealthController.
 *
 * Liveness and readiness endpoints. Liveness answers "am I up?",
 * readiness answers "am I able to serve traffic?".
 *
 * Used by:
 *   - Docker healthchecks
 *   - Load balancer health probes
 *   - Monitoring (Grafana, uptime)
 */
final class HealthController
{
    /**
     * Liveness probe: minimal, no dependencies.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'service' => 'hosto-api',
                'version' => config('hosto.api.current_version'),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Readiness probe: checks database and Redis.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $allHealthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'data' => [
                'status' => $allHealthy ? 'ok' : 'degraded',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ],
        ], $allHealthy ? 200 : 503);
    }

    /**
     * @return array{status: string, detail?: string, latency_ms?: float}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'detail' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, detail?: string, latency_ms?: float}
     */
    private function checkRedis(): array
    {
        $start = microtime(true);
        try {
            Redis::ping();

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'detail' => $e->getMessage()];
        }
    }
}
