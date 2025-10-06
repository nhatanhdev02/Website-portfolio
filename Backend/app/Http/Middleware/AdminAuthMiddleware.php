<?php

namespace App\Http\Middleware;

use App\Exceptions\Admin\AdminAuthException;
use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user('sanctum')) {
            throw AdminAuthException::unauthorized();
        }

        // Ensure the authenticated user is an Admin
        if (!$request->user('sanctum') instanceof Admin) {
            throw AdminAuthException::unauthorized();
        }

        // Log admin access for audit trail
        $admin = $request->user('sanctum');
        Log::info('Admin API Access', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'email' => $admin->email,
            'endpoint' => $request->path(),
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'session_id' => $request->session()?->getId(),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID', uniqid('req_', true))
        ]);

        // Update admin's last activity timestamp
        $admin->update(['last_login_at' => now()]);

        // Add admin context to request for use in other middleware/controllers
        $request->attributes->set('admin_user', $admin);

        return $next($request);
    }
}
