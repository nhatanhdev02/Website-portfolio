<?php

namespace Tests\Feature\Middleware;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test admin
        $this->admin = Admin::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com'
        ]);
    }

    /** @test */
    public function security_headers_are_added_to_api_responses(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');

        // Check security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Check that server information is removed
        $this->assertFalse($response->headers->has('Server'));
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    /** @test */
    public function csp_header_is_added_to_api_responses(): void
    {
        Config::set('security.headers.csp.enabled', true);

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');

        $response->assertHeader('Content-Security-Policy');
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContains("default-src 'none'", $csp);
        $this->assertStringContains("frame-ancestors 'none'", $csp);
    }

    /** @test */
    public function hsts_header_is_added_in_production(): void
    {
        Config::set('app.env', 'production');
        Config::set('security.headers.hsts.enabled', true);

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');

        $response->assertHeader('Strict-Transport-Security');
        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertStringContains('max-age=', $hsts);
        $this->assertStringContains('includeSubDomains', $hsts);
        $this->assertStringContains('preload', $hsts);
    }

    /** @test */
    public function admin_auth_middleware_blocks_unauthenticated_requests(): void
    {
        $response = $this->getJson('/api/admin/hero');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized access to admin resources'
        ]);
    }

    /** @test */
    public function admin_auth_middleware_allows_authenticated_admin(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');

        $response->assertStatus(200);
    }

    /** @test */
    public function request_logging_middleware_logs_api_requests(): void
    {
        Log::shouldReceive('log')
            ->once()
            ->with('info', 'API Request', \Mockery::type('array'));

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');
    }

    /** @test */
    public function ip_whitelist_allows_access_when_disabled(): void
    {
        Config::set('security.ip_whitelist.enabled', false);

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');

        $response->assertStatus(200);
    }

    /** @test */
    public function ip_whitelist_blocks_non_whitelisted_ips(): void
    {
        Config::set('security.ip_whitelist.enabled', true);
        Config::set('security.ip_whitelist.bypass_in_local', false);
        Config::set('security.ip_whitelist.allowed_ips', ['192.168.1.1']);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'testadmin',
            'password' => 'password'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Access denied from this IP address',
            'error_code' => 'IP_NOT_WHITELISTED'
        ]);
    }

    /** @test */
    public function ip_whitelist_allows_whitelisted_ips(): void
    {
        Config::set('security.ip_whitelist.enabled', true);
        Config::set('security.ip_whitelist.bypass_in_local', false);
        Config::set('security.ip_whitelist.allowed_ips', ['127.0.0.1']);

        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'testadmin',
            'password' => 'password'
        ]);

        // Should not be blocked by IP whitelist (will fail auth instead)
        $response->assertStatus(422); // Validation error for missing password
    }

    /** @test */
    public function cors_headers_are_present_for_api_requests(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/admin/auth/login');

        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    /** @test */
    public function rate_limiting_blocks_excessive_auth_requests(): void
    {
        // Override rate limit for testing
        Config::set('security.rate_limits.admin_auth.per_minute', 2);

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'invalid',
                'password' => 'invalid'
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // Next request should be rate limited
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'invalid',
            'password' => 'invalid'
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'message' => 'Too many authentication attempts. Please try again later.'
        ]);
    }

    /** @test */
    public function admin_activity_is_logged_on_api_access(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Admin API Access', \Mockery::type('array'));

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/admin/hero');
    }

    /** @test */
    public function security_configuration_can_be_loaded(): void
    {
        $config = config('security');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('ip_whitelist', $config);
        $this->assertArrayHasKey('headers', $config);
        $this->assertArrayHasKey('audit_logging', $config);
        $this->assertArrayHasKey('rate_limits', $config);
        $this->assertArrayHasKey('session', $config);
        $this->assertArrayHasKey('file_upload', $config);
    }
}
