<?php

namespace App\Http\Middleware;

use App\Services\Admin\DatabaseOptimizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryPerformanceMonitor
{
    public function __construct(
        private DatabaseOptimizationService $dbOptimization
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor in debug mode or when explicitly enabled
        $shouldMonitor = config('app.debug') || config('admin_cache.query_monitoring', false);

        if ($shouldMonitor) {
            $this->dbOptimization->enableQueryLogging();
        }

        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);

        if ($shouldMonitor) {
            $this->logPerformanceMetrics($request, $startTime, $endTime);
        }

        return $response;
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, float $startTime, float $endTime): void
    {
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $stats = $this->dbOptimization->getPerformanceStats();

        // Log slow requests (over 1 second)
        if ($executionTime > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'query_count' => $stats['total_queries'],
                'query_time_ms' => $stats['total_time'],
                'slow_queries' => $stats['slow_queries'],
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);
        }

        // Log requests with many queries (N+1 problem detection)
        if ($stats['total_queries'] > 20) {
            Log::warning('High query count detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $stats['total_queries'],
                'execution_time_ms' => round($executionTime, 2),
                'average_query_time_ms' => $stats['average_time']
            ]);
        }

        // Log slow queries
        if ($stats['slow_queries'] > 0) {
            $this->dbOptimization->logSlowQueries(100); // Log queries over 100ms
        }

        // Clear query log to prevent memory issues
        $this->dbOptimization->clearQueryLog();
    }
}
