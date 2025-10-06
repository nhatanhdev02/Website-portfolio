<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Admin authentication rate limiting - very strict
        RateLimiter::for('admin-auth', function (Request $request) {
            $limits = config('security.rate_limits.admin_auth');

            return [
                Limit::perMinute($limits['per_minute'])->by($request->ip())
                    ->response(function (Request $request, array $headers) {
                        Log::warning('Admin auth rate limit exceeded', [
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'timestamp' => now()->toISOString()
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Too many authentication attempts. Please try again later.',
                            'retry_after' => $headers['Retry-After'] ?? 60
                        ], 429, $headers);
                    }),
                Limit::perHour($limits['per_hour'])->by($request->ip()),
                Limit::perDay($limits['per_day'])->by($request->ip()),
            ];
        });

        // Admin API operations - moderate limiting
        RateLimiter::for('admin-api', function (Request $request) {
            $user = $request->user();
            $identifier = $user?->id ?? $request->ip();
            $limits = config('security.rate_limits.admin_api');

            return [
                Limit::perMinute($limits['per_minute'])->by($identifier)
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'API rate limit exceeded. Please slow down your requests.',
                            'retry_after' => $headers['Retry-After'] ?? 60
                        ], 429, $headers);
                    }),
                Limit::perHour($limits['per_hour'])->by($identifier),
                Limit::perDay($limits['per_day'])->by($identifier),
            ];
        });

        // File upload operations - strict limiting
        RateLimiter::for('file-upload', function (Request $request) {
            $user = $request->user();
            $identifier = $user?->id ?? $request->ip();
            $limits = config('security.rate_limits.file_upload');

            return [
                Limit::perMinute($limits['per_minute'])->by($identifier)
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'File upload rate limit exceeded. Please wait before uploading more files.',
                            'retry_after' => $headers['Retry-After'] ?? 60
                        ], 429, $headers);
                    }),
                Limit::perHour($limits['per_hour'])->by($identifier),
                Limit::perDay($limits['per_day'])->by($identifier),
            ];
        });

        // Bulk operations - very strict limiting
        RateLimiter::for('bulk-operations', function (Request $request) {
            $user = $request->user();
            $identifier = $user?->id ?? $request->ip();
            $limits = config('security.rate_limits.bulk_operations');

            return [
                Limit::perMinute($limits['per_minute'])->by($identifier)
                    ->response(function (Request $request, array $headers) use ($user) {
                        Log::warning('Bulk operations rate limit exceeded', [
                            'user_id' => $user?->id,
                            'ip' => $request->ip(),
                            'endpoint' => $request->path(),
                            'timestamp' => now()->toISOString()
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Bulk operations rate limit exceeded. Please wait before performing more bulk actions.',
                            'retry_after' => $headers['Retry-After'] ?? 60
                        ], 429, $headers);
                    }),
                Limit::perHour($limits['per_hour'])->by($identifier),
                Limit::perDay($limits['per_day'])->by($identifier),
            ];
        });

        // System operations (maintenance, reset) - extremely strict
        RateLimiter::for('system-operations', function (Request $request) {
            $user = $request->user();
            $identifier = $user?->id ?? $request->ip();
            $limits = config('security.rate_limits.system_operations');

            return [
                Limit::perMinute($limits['per_minute'])->by($identifier)
                    ->response(function (Request $request, array $headers) use ($user) {
                        Log::critical('System operations rate limit exceeded', [
                            'user_id' => $user?->id,
                            'ip' => $request->ip(),
                            'endpoint' => $request->path(),
                            'timestamp' => now()->toISOString()
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'System operations rate limit exceeded. Critical operations are heavily restricted.',
                            'retry_after' => $headers['Retry-After'] ?? 60
                        ], 429, $headers);
                    }),
                Limit::perHour($limits['per_hour'])->by($identifier),
                Limit::perDay($limits['per_day'])->by($identifier),
            ];
        });

        // General API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
