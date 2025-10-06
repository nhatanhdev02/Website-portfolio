<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and add security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Basic security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (Feature Policy replacement)
        if (config('security.headers.permissions_policy.enabled', true)) {
            $response->headers->set('Permissions-Policy',
                config('security.headers.permissions_policy.policy',
                    'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()')
            );
        }

        // Content Security Policy for API responses
        if ($request->is('api/*') && config('security.headers.csp.enabled', true)) {
            $csp = config('security.headers.csp.policy',
                "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none';"
            );
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Strict Transport Security (HTTPS only)
        if (($request->isSecure() || env('APP_ENV') === 'production') &&
            config('security.headers.hsts.enabled', true)) {

            $hstsValue = 'max-age=' . config('security.headers.hsts.max_age', 31536000);

            if (config('security.headers.hsts.include_subdomains', true)) {
                $hstsValue .= '; includeSubDomains';
            }

            if (config('security.headers.hsts.preload', true)) {
                $hstsValue .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hstsValue);
        }

        // Remove server information leakage
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('X-Generator');

        // Add rate limit headers for transparency
        if ($request->is('api/*')) {
            $this->addRateLimitHeaders($response, $request);
        }

        return $response;
    }

    /**
     * Add rate limit headers to the response.
     */
    private function addRateLimitHeaders(Response $response, Request $request): void
    {
        // These headers are typically added by Laravel's throttle middleware
        // but we ensure they're present for API transparency
        if (!$response->headers->has('X-RateLimit-Limit')) {
            // Default values - actual values will be set by throttle middleware
            $response->headers->set('X-RateLimit-Limit', '60');
            $response->headers->set('X-RateLimit-Remaining', '59');
        }
    }
}
