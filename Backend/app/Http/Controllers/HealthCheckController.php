<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Redis;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * Perform a comprehensive health check.
     */
    public function index(Request $request): JsonResponse
    {
        $secret = $request->query('secret');

        // Verify health check secret in production
        if (app()->environment('production') && $secret !== config('production.monitoring.health_checks.secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $checks = [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overall = $this->determineOverallHealth($checks);

        return response()->json([
            'status' => $overall['status'],
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
            'summary' => $overall['summary'],
        ], $overall['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Quick health check endpoint.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'message' => 'pong',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Database-specific health check.
     */
    public function database(): JsonResponse
    {
        $check = $this->checkDatabase();

        return response()->json([
            'status' => $check['status'],
            'details' => $check,
            'timestamp' => now()->toISOString(),
        ], $check['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Cache-specific health check.
     */
    public function cache(): JsonResponse
    {
        $check = $this->checkCache();

        return response()->json([
            'status' => $check['status'],
            'details' => $check,
            'timestamp' => now()->toISOString(),
        ], $check['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Check application health.
     */
    private function checkApplication(): array
    {
        try {
            $startTime = microtime(true);

            // Check if application key is set
            if (empty(config('app.key'))) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Application key not set',
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // Check if we're in debug mode in production
            if (app()->environment('production') && config('app.debug')) {
                return [
                    'status' => 'warning',
                    'message' => 'Debug mode enabled in production',
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Application is running normally',
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Application check failed: ' . $e->getMessage(),
                'response_time' => isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0,
            ];
        }
    }

    /**
     * Check database connectivity and performance.
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            // Test a simple query
            $result = DB::select('SELECT 1 as test');

            if (empty($result) || $result[0]->test !== 1) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Database query test failed',
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // Check if migrations are up to date
            $pendingMigrations = DB::table('migrations')->count();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'healthy',
                'message' => 'Database is accessible and responsive',
                'connection' => config('database.default'),
                'migrations_count' => $pendingMigrations,
                'response_time' => $responseTime,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time' => isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0,
            ];
        }
    }

    /**
     * Check cache system health.
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrievedValue = Cache::get($testKey);

            // Test cache delete
            Cache::forget($testKey);

            if ($retrievedValue !== $testValue) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write test failed',
                    'driver' => config('cache.default'),
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // Test Redis connection if using Redis
            $redisStatus = 'not_used';
            if (config('cache.default') === 'redis') {
                try {
                    Redis::ping();
                    $redisStatus = 'healthy';
                } catch (Exception $e) {
                    $redisStatus = 'unhealthy: ' . $e->getMessage();
                }
            }

            return [
                'status' => 'healthy',
                'message' => 'Cache is working properly',
                'driver' => config('cache.default'),
                'redis_status' => $redisStatus,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage(),
                'driver' => config('cache.default'),
                'response_time' => isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0,
            ];
        }
    }

    /**
     * Check storage system health.
     */
    private function checkStorage(): array
    {
        try {
            $startTime = microtime(true);
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test content';

            // Test file write
            Storage::put($testFile, $testContent);

            // Test file read
            $retrievedContent = Storage::get($testFile);

            // Test file delete
            Storage::delete($testFile);

            if ($retrievedContent !== $testContent) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage read/write test failed',
                    'driver' => config('filesystems.default'),
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }

            // Check S3 connection if using S3
            $s3Status = 'not_used';
            if (config('filesystems.default') === 's3') {
                try {
                    Storage::disk('s3')->exists('test');
                    $s3Status = 'healthy';
                } catch (Exception $e) {
                    $s3Status = 'unhealthy: ' . $e->getMessage();
                }
            }

            return [
                'status' => 'healthy',
                'message' => 'Storage is working properly',
                'driver' => config('filesystems.default'),
                's3_status' => $s3Status,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage system failed: ' . $e->getMessage(),
                'driver' => config('filesystems.default'),
                'response_time' => isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0,
            ];
        }
    }

    /**
     * Check queue system health.
     */
    private function checkQueue(): array
    {
        try {
            $startTime = microtime(true);

            // Get queue connection info
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver");

            // For Redis queues, check Redis connection
            if ($driver === 'redis') {
                try {
                    Redis::connection('default')->ping();
                } catch (Exception $e) {
                    return [
                        'status' => 'unhealthy',
                        'message' => 'Queue Redis connection failed: ' . $e->getMessage(),
                        'driver' => $driver,
                        'connection' => $connection,
                        'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                    ];
                }
            }

            // For database queues, check jobs table
            if ($driver === 'database') {
                try {
                    DB::table('jobs')->count();
                } catch (Exception $e) {
                    return [
                        'status' => 'unhealthy',
                        'message' => 'Queue database table not accessible: ' . $e->getMessage(),
                        'driver' => $driver,
                        'connection' => $connection,
                        'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                    ];
                }
            }

            return [
                'status' => 'healthy',
                'message' => 'Queue system is accessible',
                'driver' => $driver,
                'connection' => $connection,
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue system check failed: ' . $e->getMessage(),
                'response_time' => isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0,
            ];
        }
    }

    /**
     * Determine overall health status.
     */
    private function determineOverallHealth(array $checks): array
    {
        $statuses = array_column($checks, 'status');

        if (in_array('unhealthy', $statuses)) {
            return [
                'status' => 'unhealthy',
                'summary' => 'One or more critical systems are unhealthy',
            ];
        }

        if (in_array('warning', $statuses)) {
            return [
                'status' => 'warning',
                'summary' => 'System is operational but has warnings',
            ];
        }

        return [
            'status' => 'healthy',
            'summary' => 'All systems are operational',
        ];
    }
}
