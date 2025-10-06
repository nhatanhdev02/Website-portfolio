<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP whitelisting is enabled
        if (!config('security.ip_whitelist.enabled', false)) {
            return $next($request);
        }

        // Bypass in local environment if configured
        if (app()->environment('local') && config('security.ip_whitelist.bypass_in_local', true)) {
            return $next($request);
        }

        // Get allowed IPs from configuration
        $allowedIps = config('security.ip_whitelist.allowed_ips', []);

        // If no whitelist is configured or contains wildcard, allow all IPs
        if (empty($allowedIps) || in_array('*', $allowedIps)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if client IP is in the whitelist
        if (!$this->isIpAllowed($clientIp, $allowedIps)) {
            Log::warning('IP Whitelist: Blocked access attempt', [
                'ip' => $clientIp,
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'referer' => $request->header('referer'),
                'allowed_ips' => $allowedIps,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access denied from this IP address',
                'error_code' => 'IP_NOT_WHITELISTED'
            ], 403);
        }

        // Log successful IP whitelist validation
        Log::info('IP Whitelist: Access granted', [
            'ip' => $clientIp,
            'path' => $request->path(),
            'timestamp' => now()->toISOString()
        ]);

        return $next($request);
    }



    /**
     * Check if the given IP is allowed.
     */
    private function isIpAllowed(string $clientIp, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            // Support for CIDR notation
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInRange($clientIp, $allowedIp)) {
                    return true;
                }
            } else {
                // Exact IP match
                if ($clientIp === $allowedIp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range.
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);

        if ($bits === null) {
            $bits = 32;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}
