<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Admin\AlertingService;
use App\Services\Admin\MonitoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ErrorTrackingMiddleware
{
    private AlertingService $alertingService;
    private MonitoringService $monitoringService;

    public function __construct(AlertingService $alertingService, MonitoringService $monitoringService)
    {
        $this->alertingService = $alertingService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            // Track error responses
            if ($response->getStatusCode() >= 400) {
                $this->trackErrorResponse($request, $response);
            }

            return $response;

        } catch (Exception $e) {
            // Track exceptions
            $this->trackException($request, $e);

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Track error responses.
     */
    private function trackErrorResponse(Request $request, Response $response): void
    {
        if (!config('monitoring.error_monitoring.enabled', true)) {
            return;
        }

        $statusCode = $response->getStatusCode();
        $errorType = $this->getErrorTypeFromStatusCode($statusCode);

        // Record error metrics
        $this->recordErrorMetrics($errorType, $request, [
            'status_code' => $statusCode,
            'response_content' => $this->getResponseContent($response),
        ]);

        // Check error rate and send alerts if threshold exceeded
        $this->checkErrorRateThreshold($errorType);
    }

    /**
     * Track exceptions.
     */
    private function trackException(Request $request, Exception $exception): void
    {
        if (!config('monitoring.error_monitoring.enabled', true)) {
            return;
        }

        $errorType = get_class($exception);

        // Only track configured error types
        $trackedTypes = config('monitoring.error_monitoring.track_error_types', []);
        if (!empty($trackedTypes) && !in_array($errorType, $trackedTypes)) {
            return;
        }

        // Record error metrics
        $this->recordErrorMetrics($errorType, $request, [
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ]);

        // Record error for monitoring service
        $this->monitoringService->recordError($exception, [
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'user_id' => $request->user()?->id,
        ]);

        // Check error rate and send alerts if threshold exceeded
        $this->checkErrorRateThreshold($errorType);
    }

    /**
     * Record error metrics in cache.
     */
    private function recordErrorMetrics(string $errorType, Request $request, array $additionalData = []): void
    {
        $timeWindow = config('monitoring.error_monitoring.time_window_minutes', 15);
        $currentWindow = now()->format('Y-m-d-H') . ':' . floor(now()->minute / $timeWindow) * $timeWindow;

        // Overall error count
        $overallKey = "error_count_{$currentWindow}";
        $overallCount = Cache::get($overallKey, 0);
        Cache::put($overallKey, $overallCount + 1, ($timeWindow + 5) * 60);

        // Error type specific count
        $typeKey = "error_count_{$errorType}_{$currentWindow}";
        $typeCount = Cache::get($typeKey, 0);
        Cache::put($typeKey, $typeCount + 1, ($timeWindow + 5) * 60);

        // Store error details
        $errorDetails = [
            'type' => $errorType,
            'timestamp' => now()->toISOString(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'user_id' => $request->user()?->id,
        ];

        $errorDetails = array_merge($errorDetails, $additionalData);

        // Store recent errors for analysis
        $recentErrorsKey = "recent_errors_{$currentWindow}";
        $recentErrors = Cache::get($recentErrorsKey, []);
        $recentErrors[] = $errorDetails;

        // Keep only last 100 errors per window
        if (count($recentErrors) > 100) {
            $recentErrors = array_slice($recentErrors, -100);
        }

        Cache::put($recentErrorsKey, $recentErrors, ($timeWindow + 5) * 60);

        // Log error for debugging
        Log::info('Error tracked', $errorDetails);
    }

    /**
     * Check error rate threshold and send alerts.
     */
    private function checkErrorRateThreshold(string $errorType): void
    {
        $timeWindow = config('monitoring.error_monitoring.time_window_minutes', 15);
        $threshold = config('monitoring.error_monitoring.error_rate_threshold', 5);
        $currentWindow = now()->format('Y-m-d-H') . ':' . floor(now()->minute / $timeWindow) * $timeWindow;

        // Check overall error rate
        $overallKey = "error_count_{$currentWindow}";
        $overallCount = Cache::get($overallKey, 0);

        if ($overallCount >= $threshold) {
            // Check if we already sent an alert for this window
            $alertKey = "error_rate_alert_{$currentWindow}";
            if (!Cache::has($alertKey)) {
                $this->alertingService->sendErrorRateAlert($overallCount, $timeWindow);
                Cache::put($alertKey, true, ($timeWindow + 5) * 60);
            }
        }

        // Check specific error type rate (if it's a significant portion)
        $typeKey = "error_count_{$errorType}_{$currentWindow}";
        $typeCount = Cache::get($typeKey, 0);

        if ($typeCount >= max(3, $threshold / 2)) {
            $typeAlertKey = "error_type_alert_{$errorType}_{$currentWindow}";
            if (!Cache::has($typeAlertKey)) {
                $this->alertingService->sendAlert(
                    'high_error_type_rate',
                    "High error rate for {$errorType}: {$typeCount} errors in {$timeWindow} minutes",
                    [
                        'error_type' => $errorType,
                        'error_count' => $typeCount,
                        'time_window' => $timeWindow,
                        'rate_per_minute' => round($typeCount / $timeWindow, 2),
                    ],
                    'warning'
                );
                Cache::put($typeAlertKey, true, ($timeWindow + 5) * 60);
            }
        }
    }

    /**
     * Get error type from HTTP status code.
     */
    private function getErrorTypeFromStatusCode(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'ServerError',
            $statusCode >= 400 => 'ClientError',
            default => 'UnknownError',
        };
    }

    /**
     * Get response content for logging (truncated).
     */
    private function getResponseContent(Response $response): string
    {
        $content = $response->getContent();

        if (strlen($content) > 500) {
            return substr($content, 0, 500) . '... [truncated]';
        }

        return $content;
    }
}
