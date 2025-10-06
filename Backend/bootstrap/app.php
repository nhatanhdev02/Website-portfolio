<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Configure route model binding for admin routes
            Route::model('service', \App\Models\Service::class);
            Route::model('project', \App\Models\Project::class);
            Route::model('blogPost', \App\Models\BlogPost::class);
            Route::model('contactMessage', \App\Models\ContactMessage::class);
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum middleware for stateful authentication
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Global API middleware stack
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\ProductionSecurityMiddleware::class,
            \App\Http\Middleware\ApplicationPerformanceMonitoring::class,
            \App\Http\Middleware\ErrorTrackingMiddleware::class,
        ]);

        // Middleware aliases for route-specific application
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
            'request.logging' => \App\Http\Middleware\RequestLoggingMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelistMiddleware::class,
            'production.security' => \App\Http\Middleware\ProductionSecurityMiddleware::class,
            'apm' => \App\Http\Middleware\ApplicationPerformanceMonitoring::class,
            'error.tracking' => \App\Http\Middleware\ErrorTrackingMiddleware::class,
        ]);

        // Configure API throttling with custom rate limiters
        $middleware->throttleApi('api');

        // Trust proxies for proper IP detection behind load balancers
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Import required exception classes
        $exceptions->render(function (
            \App\Exceptions\Admin\AdminAuthException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::warning('Admin Authentication Exception', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'AUTH_ERROR',
                    'timestamp' => now()->toISOString(),
                ], $e->getCode());
            }
        });

        $exceptions->render(function (
            \App\Exceptions\Admin\ValidationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::info('Validation Exception', [
                    'errors' => $e->getErrors(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'input' => $request->except(['password', 'password_confirmation']),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->getErrors(),
                    'error_code' => 'VALIDATION_ERROR',
                    'timestamp' => now()->toISOString(),
                ], $e->getCode());
            }
        });

        $exceptions->render(function (
            \App\Exceptions\Admin\FileUploadException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('File Upload Exception', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'files' => array_keys($request->allFiles()),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'FILE_UPLOAD_ERROR',
                    'timestamp' => now()->toISOString(),
                ], $e->getCode());
            }
        });

        $exceptions->render(function (
            \App\Exceptions\Admin\ResourceNotFoundException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::info('Resource Not Found Exception', [
                    'message' => $e->getMessage(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'parameters' => $request->route()?->parameters() ?? [],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'timestamp' => now()->toISOString(),
                ], $e->getCode());
            }
        });

        $exceptions->render(function (
            \App\Exceptions\Admin\BusinessLogicException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::warning('Business Logic Exception', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'input' => $request->except(['password', 'password_confirmation']),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'BUSINESS_LOGIC_ERROR',
                    'timestamp' => now()->toISOString(),
                ], $e->getCode());
            }
        });

        // Handle Laravel's built-in validation exceptions
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::info('Laravel Validation Exception', [
                    'errors' => $e->errors(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'input' => $request->except(['password', 'password_confirmation']),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                    'timestamp' => now()->toISOString(),
                ], 422);
            }
        });

        // Handle model not found exceptions
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::info('Model Not Found Exception', [
                    'model' => $e->getModel(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'parameters' => $request->route()?->parameters() ?? [],
                ]);

                $modelName = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "The requested {$modelName} was not found.",
                    'error_code' => 'RESOURCE_NOT_FOUND',
                    'timestamp' => now()->toISOString(),
                ], 404);
            }
        });

        // Handle authentication exceptions
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::info('Authentication Exception', [
                    'message' => $e->getMessage(),
                    'guards' => $e->guards(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'error_code' => 'UNAUTHENTICATED',
                    'timestamp' => now()->toISOString(),
                ], 401);
            }
        });

        // Handle authorization exceptions
        $exceptions->render(function (
            \Illuminate\Auth\Access\AuthorizationException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::warning('Authorization Exception', [
                    'message' => $e->getMessage(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                    'error_code' => 'UNAUTHORIZED',
                    'timestamp' => now()->toISOString(),
                ], 403);
            }
        });

        // Handle method not allowed exceptions
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The specified method for the request is invalid.',
                    'error_code' => 'METHOD_NOT_ALLOWED',
                    'timestamp' => now()->toISOString(),
                ], 405);
            }
        });

        // Handle not found exceptions
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested endpoint was not found.',
                    'error_code' => 'ENDPOINT_NOT_FOUND',
                    'timestamp' => now()->toISOString(),
                ], 404);
            }
        });

        // Handle too many requests exceptions
        $exceptions->render(function (
            \Illuminate\Http\Exceptions\ThrottleRequestsException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::warning('Rate Limit Exceeded', [
                    'ip' => $request->ip(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                    'timestamp' => now()->toISOString(),
                ], 429);
            }
        });

        // Handle general exceptions with detailed logging
        $exceptions->render(function (
            \Throwable $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson()) {
                // Log detailed error information
                \Illuminate\Support\Facades\Log::error('Unhandled Exception', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'input' => $request->except(['password', 'password_confirmation']),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Return user-friendly error response
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = app()->environment('production')
                    ? 'An unexpected error occurred. Please try again later.'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'INTERNAL_SERVER_ERROR',
                    'timestamp' => now()->toISOString(),
                ], $statusCode);
            }
        });
    })->create();
