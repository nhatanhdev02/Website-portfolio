<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Exception;

class MonitoringService
{
    /**
     * Record application metrics.
     */
    public function recordMetrics(array $metrics): void
    {
        try {
            // Log metrics to application log
            Log::channel('daily')->info('Application Metrics', $metrics);

            // Send to New Relic if available
            if (extension_loaded('newrelic')) {
                foreach ($metrics as $key => $value) {
                    if (is_numeric($value)) {
                        newrelic_record_custom_event('AdminMetric', [
                            'metric_name' => $key,
                            'value' => $value,
                            'timestamp' => now()->timestamp,
                        ]);
                    }
                }
            }

            // Store in cache for dashboard display
            $cacheKey = 'admin_metrics_' . now()->format('Y-m-d-H');
            $existingMetrics = Cache::get($cacheKey, []);
            $existingMetrics[] = array_merge($metrics, ['timestamp' => now()->toISOString()]);

            // Keep only last 100 entries per hour
            if (count($existingMetrics) > 100) {
                $existingMetrics = array_slice($existingMetrics, -100);
            }

            Cache::put($cacheKey, $existingMetrics, 3600);
        } catch (Exception $e) {
            Log::error('Failed to record metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics,
            ]);
        }
    }

    /**
     * Get system performance metrics.
     */
    public function getSystemMetrics(): array
    {
        $metrics = [
            'timestamp' => now()->toISOString(),
            'memory_usage' => $this->getMemoryUsage(),
            'database_metrics' => $this->getDatabaseMetrics(),
            'cache_metrics' => $this->getCacheMetrics(),
            'queue_metrics' => $this->getQueueMetrics(),
            'disk_usage' => $this->getDiskUsage(),
        ];

        $this->recordMetrics($metrics);

        return $metrics;
    }

    /**
     * Get memory usage metrics.
     */
    private function getMemoryUsage(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get database performance metrics.
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $startTime = microtime(true);

            // Test query performance
            DB::select('SELECT 1');
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Get connection info
            $connectionName = config('database.default');
            $connection = DB::connection();

            return [
                'connection' => $connectionName,
                'query_response_time' => $queryTime,
                'active_connections' => $this->getActiveConnections(),
                'slow_queries' => $this->getSlowQueryCount(),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'unhealthy',
            ];
        }
    }

    /**
     * Get cache performance metrics.
     */
    private function getCacheMetrics(): array
    {
        try {
            $startTime = microtime(true);

            // Test cache performance
            $testKey = 'monitoring_test_' . time();
            Cache::put($testKey, 'test', 60);
            Cache::get($testKey);
            Cache::forget($testKey);

            $cacheTime = round((microtime(true) - $startTime) * 1000, 2);

            $metrics = [
                'driver' => config('cache.default'),
                'response_time' => $cacheTime,
            ];

            // Redis-specific metrics
            if (config('cache.default') === 'redis') {
                $metrics['redis_info'] = $this->getRedisInfo();
            }

            return $metrics;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'unhealthy',
            ];
        }
    }

    /**
     * Get queue metrics.
     */
    private function getQueueMetrics(): array
    {
        try {
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver");

            $metrics = [
                'driver' => $driver,
                'connection' => $connection,
            ];

            if ($driver === 'database') {
                $metrics['pending_jobs'] = DB::table('jobs')->count();
                $metrics['failed_jobs'] = DB::table('failed_jobs')->count();
            }

            if ($driver === 'redis') {
                try {
                    $redis = Redis::connection('default');
                    $metrics['queue_length'] = $redis->llen('queues:default');
                } catch (Exception $e) {
                    $metrics['error'] = $e->getMessage();
                }
            }

            return $metrics;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'unhealthy',
            ];
        }
    }

    /**
     * Get disk usage metrics.
     */
    private function getDiskUsage(): array
    {
        try {
            $storagePath = storage_path();

            return [
                'storage_path' => $storagePath,
                'free_bytes' => disk_free_space($storagePath),
                'total_bytes' => disk_total_space($storagePath),
                'free_mb' => round(disk_free_space($storagePath) / 1024 / 1024, 2),
                'total_mb' => round(disk_total_space($storagePath) / 1024 / 1024, 2),
                'usage_percentage' => round((1 - disk_free_space($storagePath) / disk_total_space($storagePath)) * 100, 2),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'unhealthy',
            ];
        }
    }

    /**
     * Get active database connections count.
     */
    private function getActiveConnections(): int
    {
        try {
            if (config('database.default') === 'mysql') {
                $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                return (int) $result[0]->Value;
            }

            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get slow query count from logs.
     */
    private function getSlowQueryCount(): int
    {
        try {
            // This would typically read from slow query log
            // For now, return 0 as placeholder
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get Redis information.
     */
    private function getRedisInfo(): array
    {
        try {
            $redis = Redis::connection('default');
            $info = $redis->info();

            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Record error for monitoring.
     */
    public function recordError(Exception $exception, array $context = []): void
    {
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        // Log error
        Log::error('Application Error', $errorData);

        // Send to New Relic
        if (extension_loaded('newrelic')) {
            newrelic_notice_error($exception->getMessage(), $exception);
        }

        // Send to Sentry if configured
        if (config('production.monitoring.error_tracking.sentry.dsn')) {
            app('sentry')->captureException($exception);
        }

        // Store error metrics
        $this->recordMetrics([
            'error_count' => 1,
            'error_type' => get_class($exception),
        ]);
    }

    /**
     * Get recent metrics for dashboard.
     */
    public function getRecentMetrics(int $hours = 24): array
    {
        $metrics = [];
        $now = now();

        for ($i = 0; $i < $hours; $i++) {
            $hour = $now->copy()->subHours($i);
            $cacheKey = 'admin_metrics_' . $hour->format('Y-m-d-H');
            $hourlyMetrics = Cache::get($cacheKey, []);

            if (!empty($hourlyMetrics)) {
                $metrics[$hour->format('Y-m-d H:00')] = $hourlyMetrics;
            }
        }

        return $metrics;
    }

    /**
     * Clear old metrics data.
     */
    public function clearOldMetrics(int $daysToKeep = 7): void
    {
        $cutoffDate = now()->subDays($daysToKeep);

        // Clear cache entries older than cutoff date
        for ($i = $daysToKeep; $i < 30; $i++) {
            $date = now()->subDays($i);
            for ($hour = 0; $hour < 24; $hour++) {
                $cacheKey = 'admin_metrics_' . $date->format('Y-m-d') . '-' . str_pad($hour, 2, '0', STR_PAD_LEFT);
                Cache::forget($cacheKey);
            }
        }
    }
}
