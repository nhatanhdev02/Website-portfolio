<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production
        if (config('production.ssl.force_https') && !$request->isSecure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // Add security headers in production
        if (app()->environment('production')) {
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Add security headers to the response.
     */
    private function addSecurityHeaders(Response $response): void
    {
        // HSTS Header
        if (config('production.security_headers.hsts.enabled')) {
            $hstsValue = 'max-age=' . config('production.security_headers.hsts.max_age');

            if (config('production.security_headers.hsts.include_subdomains')) {
                $hstsValue .= '; includeSubDomains';
            }

            if (config('production.security_headers.hsts.preload')) {
                $hstsValue .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hstsValue);
        }

        // Content Security Policy
        if (config('production.security_headers.csp.enabled')) {
            $response->headers->set('Content-Security-Policy', config('production.security_headers.csp.policy'));
        }

        // Permissions Policy
        if (config('production.security_headers.permissions_policy.enabled')) {
            $response->headers->set('Permissions-Policy', config('production.security_headers.permissions_policy.policy'));
        }

        // Additional security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
    }
}
