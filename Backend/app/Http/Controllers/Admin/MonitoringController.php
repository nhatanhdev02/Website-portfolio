<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\MonitoringService;
use App\Services\Admin\AlertingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function __construct(
        private MonitoringService $monitoringService,
        private AlertingService $alertingService
    ) {}

    /**
     * Get monitoring dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        try {
            $data = [
                'system_metrics' => $this->monitoringService->getSystemMetrics(),
                'recent_alerts' => $this->alertingService->getAlertHistory(1),
                'health_status' => $this->getHealthStatus(),
                'performance_summary' => $this->getPerformanceSummary(),
                'error_summary' => $this->getErrorSummary(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Monitoring dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load monitoring dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system metrics for a specific time range.
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = $request->query('hours', 24);
        $hours = min(max($hours, 1), 168); // Limit between 1 hour and 7 days

        try {
            $metrics = $this->monitoringService->getRecentMetrics($hours);

            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'time_range' => $hours,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Metrics retrieval error', [
                'error' => $e->getMessage(),
                'hours' => $hours,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alert history.
     */
    public function alerts(Request $request): JsonResponse
    {
        $days = $request->query('days', 7);
        $days = min(max($days, 1), 30); // Limit between 1 day and 30 days

        try {
            $alerts = $this->alertingService->getAlertHistory($days);

            return response()->json([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'time_range' => $days,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Alert history retrieval error', [
                'error' => $e->getMessage(),
                'days' => $days,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alert history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance analytics.
     */
    public function performance(Request $request): JsonResponse
    {
        $hours = $request->query('hours', 24);
        $hours = min(max($hours, 1), 168);

        try {
            $data = [
                'response_times' => $this->getResponseTimeAnalytics($hours),
                'memory_usage' => $this->getMemoryUsageAnalytics($hours),
                'database_performance' => $this->getDatabasePerformanceAnalytics($hours),
                'cache_performance' => $this->getCachePerformanceAnalytics($hours),
                'error_rates' => $this->getErrorRateAnalytics($hours),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Performance analytics error', [
                'error' => $e->getMessage(),
                'hours' => $hours,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test alert system.
     */
    public function testAlert(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:email,slack,discord',
            'severity' => 'required|string|in:info,warning,critical',
        ]);

        try {
            $this->alertingService->sendAlert(
                'test_alert',
                'This is a test alert from the monitoring system',
                [
                    'test_data' => 'Test value',
                    'requested_by' => $request->user()->username ?? 'system',
                ],
                $request->input('severity')
            );

            return response()->json([
                'success' => true,
                'message' => 'Test alert sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Test alert error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test alert',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current health status summary.
     */
    private function getHealthStatus(): array
    {
        $cacheKey = 'health_status_summary';

        return Cache::remember($cacheKey, 300, function () {
            // This would typically call the health check controller
            // For now, return a basic status
            return [
                'overall_status' => 'healthy',
                'last_check' => now()->toISOString(),
                'components' => [
                    'database' => 'healthy',
                    'cache' => 'healthy',
                    'storage' => 'healthy',
                    'queue' => 'healthy',
                ],
            ];
        });
    }

    /**
     * Get performance summary.
     */
    private function getPerformanceSummary(): array
    {
        return [
            'avg_response_time' => $this->getAverageResponseTime(),
            'memory_usage_trend' => $this->getMemoryUsageTrend(),
            'database_query_performance' => $this->getDatabaseQueryPerformance(),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    /**
     * Get error summary.
     */
    private function getErrorSummary(): array
    {
        return [
            'error_rate_last_hour' => $this->getErrorRate(1),
            'error_rate_last_24h' => $this->getErrorRate(24),
            'most_common_errors' => $this->getMostCommonErrors(),
            'error_trend' => $this->getErrorTrend(),
        ];
    }

    /**
     * Get response time analytics.
     */
    private function getResponseTimeAnalytics(int $hours): array
    {
        // This would typically query stored metrics
        // For now, return sample data structure
        return [
            'average' => 150.5,
            'median' => 120.0,
            'p95' => 300.0,
            'p99' => 500.0,
            'trend' => 'stable',
        ];
    }

    /**
     * Get memory usage analytics.
     */
    private function getMemoryUsageAnalytics(int $hours): array
    {
        return [
            'average_mb' => 45.2,
            'peak_mb' => 78.5,
            'trend' => 'stable',
        ];
    }

    /**
     * Get database performance analytics.
     */
    private function getDatabasePerformanceAnalytics(int $hours): array
    {
        return [
            'avg_query_time' => 25.3,
            'slow_queries_count' => 5,
            'connection_pool_usage' => 65.0,
        ];
    }

    /**
     * Get cache performance analytics.
     */
    private function getCachePerformanceAnalytics(int $hours): array
    {
        return [
            'hit_rate' => 85.5,
            'miss_rate' => 14.5,
            'avg_response_time' => 2.1,
        ];
    }

    /**
     * Get error rate analytics.
     */
    private function getErrorRateAnalytics(int $hours): array
    {
        return [
            'total_errors' => 12,
            'error_rate_per_hour' => 0.5,
            'most_common_type' => 'ValidationException',
        ];
    }

    /**
     * Helper methods for performance summary.
     */
    private function getAverageResponseTime(): float
    {
        // This would query actual metrics
        return 150.5;
    }

    private function getMemoryUsageTrend(): string
    {
        return 'stable';
    }

    private function getDatabaseQueryPerformance(): array
    {
        return [
            'avg_time' => 25.3,
            'slow_queries' => 5,
        ];
    }

    private function getCacheHitRate(): float
    {
        return 85.5;
    }

    private function getErrorRate(int $hours): float
    {
        return $hours === 1 ? 0.5 : 0.3;
    }

    private function getMostCommonErrors(): array
    {
        return [
            'ValidationException' => 8,
            'ModelNotFoundException' => 3,
            'AuthenticationException' => 1,
        ];
    }

    private function getErrorTrend(): string
    {
        return 'decreasing';
    }
}
