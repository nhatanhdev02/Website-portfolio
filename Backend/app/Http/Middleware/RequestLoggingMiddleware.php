<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware
{
    /**
     * Handle an incoming request and log it for audit purposes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds

        // Determine log level based on response status
        $logLevel = $this->getLogLevel($response->getStatusCode());

        // Log the request details for audit trail
        Log::log($logLevel, 'API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'admin_id' => $request->attributes->get('admin_user')?->id,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request_size' => strlen($request->getContent()),
            'response_size' => strlen($response->getContent()),
            'memory_usage' => memory_get_peak_usage(true),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID', uniqid('req_', true)),
            'headers' => $this->getFilteredHeaders($request),
            'query_params' => $request->query(),
            'is_secure' => $request->isSecure(),
            'protocol' => $request->getProtocolVersion(),
        ]);

        return $response;
    }

    /**
     * Get filtered headers (excluding sensitive information).
     */
    private function getFilteredHeaders(Request $request): array
    {
        $headers = $request->headers->all();

        // Remove sensitive headers
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-csrf-token',
            'x-xsrf-token',
            'set-cookie'
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }

        return $headers;
    }

    /**
     * Determine log level based on HTTP status code.
     */
    private function getLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            $statusCode >= 300 => 'info',
            default => 'info'
        };
    }
}
