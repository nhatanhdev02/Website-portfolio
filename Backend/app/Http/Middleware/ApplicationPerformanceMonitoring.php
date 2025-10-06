<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Admin\MonitoringService;
use Illuminate\Support\Facades\Log;
use Exception;

class ApplicationPerformanceMonitoring
{
    private MonitoringService $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Set New Relic transaction name if available
        if (extension_loaded('newrelic')) {
            $transactionName = $request->route() ? $request->route()->getName() : $request->path();
            newrelic_name_transaction($transactionName);
        }

        try {
            $response = $next($request);

            // Record successful request metrics
            $this->recordRequestMetrics($request, $response, $startTime, $startMemory);

            return $response;

        } catch (Exception $e) {
            // Record error metrics
            $this->recordErrorMetrics($request, $e, $startTime, $startMemory);

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Record metrics for successful requests.
     */
    private function recordRequestMetrics(Request $request, Response $response, float $startTime, int $startMemory): void
    {
        if (!config('production.monitoring.apm.enabled', false)) {
            return;
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'response_status' => $response->getStatusCode(),
            'response_time' => round(($endTime - $startTime) * 1000, 2), // milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        // Add route information if available
        if ($request->route()) {
            $metrics['route_name'] = $request->route()->getName();
            $metrics['route_action'] = $request->route()->getActionName();
        }

        // Add user information if authenticated
        if ($request->user()) {
            $metrics['user_id'] = $request->user()->id;
            $metrics['user_type'] = get_class($request->user());
        }

        // Record slow requests
        if ($metrics['response_time'] > 1000) { // Slower than 1 second
            Log::warning('Slow Request Detected', $metrics);

            if (extension_loaded('newrelic')) {
                newrelic_record_custom_event('SlowRequest', $metrics);
            }
        }

        // Record high memory usage
        if ($metrics['memory_usage_mb'] > 50) { // More than 50MB
            Log::warning('High Memory Usage Request', $metrics);

            if (extension_loaded('newrelic')) {
                newrelic_record_custom_event('HighMemoryRequest', $metrics);
            }
        }

        // Record metrics
        $this->monitoringService->recordMetrics($metrics);

        // Add performance headers to response
        if (config('app.debug') || app()->environment('local')) {
            $response->headers->set('X-Response-Time', $metrics['response_time'] . 'ms');
            $response->headers->set('X-Memory-Usage', $metrics['memory_usage_mb'] . 'MB');
        }
    }

    /**
     * Record metrics for failed requests.
     */
    private function recordErrorMetrics(Request $request, Exception $exception, float $startTime, int $startMemory): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'response_time' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage' => $endMemory - $startMemory,
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString(),
        ];

        // Add route information if available
        if ($request->route()) {
            $metrics['route_name'] = $request->route()->getName();
            $metrics['route_action'] = $request->route()->getActionName();
        }

        // Add user information if authenticated
        if ($request->user()) {
            $metrics['user_id'] = $request->user()->id;
            $metrics['user_type'] = get_class($request->user());
        }

        // Record error
        $this->monitoringService->recordError($exception, $metrics);

        // Send to New Relic
        if (extension_loaded('newrelic')) {
            newrelic_notice_error($exception->getMessage(), $exception);
            newrelic_record_custom_event('RequestError', $metrics);
        }
    }
}
