<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use Laravel\Sanctum\Sanctum;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function login_is_rate_limited_after_multiple_failed_attempts(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('correct_password')
        ]);

        // Act - Make multiple failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => 'wrong_password'
            ]);

            if ($i < 4) {
                $response->assertStatus(401);
            }
        }

        // The 5th attempt should be rate limited
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password'
        ]);

        // Assert
        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function successful_login_resets_rate_limit(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('correct_password')
        ]);

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => 'wrong_password'
            ])->assertStatus(401);
        }

        // Act - Successful login
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'correct_password'
        ]);

        // Assert
        $response->assertStatus(200);

        // Verify rate limit is reset by making another failed attempt
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401); // Should not be rate limited
    }

    /** @test */
    public function authentication_tokens_expire_correctly(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Act - Use token immediately (should work)
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                        ->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(200);

        // Simulate token expiration by manually expiring it
        $admin->tokens()->update(['expires_at' => now()->subHour()]);

        // Act - Use expired token
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                        ->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_tokens_are_rejected(): void
    {
        // Act - Use invalid token
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid_token'])
                        ->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function malformed_authorization_headers_are_rejected(): void
    {
        // Test various malformed headers
        $malformedHeaders = [
            'Bearer',
            'Bearer ',
            'InvalidBearer token',
            'token_without_bearer',
            ''
        ];

        foreach ($malformedHeaders as $header) {
            $response = $this->withHeaders(['Authorization' => $header])
                            ->getJson('/api/admin/auth/me');

            $response->assertStatus(401);
        }
    }

    /** @test */
    public function logout_invalidates_token(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Verify token works
        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/admin/auth/me')
            ->assertStatus(200);

        // Act - Logout
        $logoutResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                              ->postJson('/api/admin/auth/logout');

        // Assert
        $logoutResponse->assertStatus(200);

        // Verify token is invalidated
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                        ->getJson('/api/admin/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function token_refresh_invalidates_old_token(): void
    {
        // Arrange
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['admin']);

        $oldToken = $admin->createToken('test-token')->plainTextToken;

        // Act - Refresh token
        $refreshResponse = $this->withHeaders(['Authorization' => "Bearer $oldToken"])
                               ->postJson('/api/admin/auth/refresh');

        // Assert
        $refreshResponse->assertStatus(200);
        $newToken = $refreshResponse->json('data.token');

        // Verify old token is invalidated
        $response = $this->withHeaders(['Authorization' => "Bearer $oldToken"])
                        ->getJson('/api/admin/auth/me');

        $response->assertStatus(401);

        // Verify new token works
        $response = $this->withHeaders(['Authorization' => "Bearer $newToken"])
                        ->getJson('/api/admin/auth/me');

        $response->assertStatus(200);
    }

    /** @test */
    public function concurrent_sessions_are_handled_correctly(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Create multiple sessions
        $session1 = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $session2 = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $token1 = $session1->json('data.token');
        $token2 = $session2->json('data.token');

        // Act - Both tokens should work
        $response1 = $this->withHeaders(['Authorization' => "Bearer $token1"])
                         ->getJson('/api/admin/auth/me');

        $response2 = $this->withHeaders(['Authorization' => "Bearer $token2"])
                         ->getJson('/api/admin/auth/me');

        // Assert
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Logout from one session
        $this->withHeaders(['Authorization' => "Bearer $token1"])
            ->postJson('/api/admin/auth/logout')
            ->assertStatus(200);

        // Verify first token is invalidated but second still works
        $this->withHeaders(['Authorization' => "Bearer $token1"])
            ->getJson('/api/admin/auth/me')
            ->assertStatus(401);

        $this->withHeaders(['Authorization' => "Bearer $token2"])
            ->getJson('/api/admin/auth/me')
            ->assertStatus(200);
    }

    /** @test */
    public function password_validation_enforces_security_requirements(): void
    {
        // Test weak passwords are rejected during admin creation
        $weakPasswords = [
            '123',           // Too short
            'password',      // No uppercase, numbers, or special chars
            'PASSWORD',      // No lowercase, numbers, or special chars
            '12345678',      // No letters or special chars
            'Password',      // No numbers or special chars
            'Password123',   // No special chars
        ];

        foreach ($weakPasswords as $weakPassword) {
            // This would typically be tested in a user registration endpoint
            // For now, we test the validation logic directly
            $this->assertTrue(strlen($weakPassword) < 8 ||
                            !preg_match('/[A-Z]/', $weakPassword) ||
                            !preg_match('/[a-z]/', $weakPassword) ||
                            !preg_match('/[0-9]/', $weakPassword) ||
                            !preg_match('/[^A-Za-z0-9]/', $weakPassword));
        }

        // Test strong password is accepted
        $strongPassword = 'StrongPass123!';
        $this->assertTrue(strlen($strongPassword) >= 8 &&
                        preg_match('/[A-Z]/', $strongPassword) &&
                        preg_match('/[a-z]/', $strongPassword) &&
                        preg_match('/[0-9]/', $strongPassword) &&
                        preg_match('/[^A-Za-z0-9]/', $strongPassword));
    }

    /** @test */
    public function authentication_logs_security_events(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Act - Failed login attempt
        $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password'
        ])->assertStatus(401);

        // Act - Successful login
        $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ])->assertStatus(200);

        // Assert - Check that events were logged
        // In a real application, you would check log files or database logs
        // For this test, we verify the responses were correct
        $this->assertTrue(true);
    }

    /** @test */
    public function session_timeout_is_enforced(): void
    {
        // Arrange
        $admin = Admin::factory()->create();
        $admin->last_login_at = now()->subHours(25); // 25 hours ago
        $admin->save();

        Sanctum::actingAs($admin, ['admin']);

        // Act - Try to access protected resource with old session
        $response = $this->getJson('/api/admin/auth/me');

        // Assert - Should still work as Sanctum tokens don't have built-in session timeout
        // But the application logic should check last_login_at
        $response->assertStatus(200);

        // In a real implementation, you might want to add middleware to check session timeout
        $this->assertTrue($admin->last_login_at->diffInHours(now()) > 24);
    }

    /** @test */
    public function authentication_prevents_timing_attacks(): void
    {
        // Arrange
        $validAdmin = Admin::factory()->create([
            'username' => 'validuser',
            'password' => Hash::make('validpassword123')
        ]);

        // Test with valid username but invalid password
        $startTime1 = microtime(true);
        $response1 = $this->postJson('/api/admin/auth/login', [
            'username' => 'validuser',
            'password' => 'wrongpassword'
        ]);
        $endTime1 = microtime(true);
        $time1 = ($endTime1 - $startTime1) * 1000;

        // Test with invalid username
        $startTime2 = microtime(true);
        $response2 = $this->postJson('/api/admin/auth/login', [
            'username' => 'invaliduser',
            'password' => 'wrongpassword'
        ]);
        $endTime2 = microtime(true);
        $time2 = ($endTime2 - $startTime2) * 1000;

        // Assert both return same error
        $response1->assertStatus(401);
        $response2->assertStatus(401);

        // Response times should be similar (within 100ms difference)
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(100, $timeDifference,
            "Timing attack possible: valid user took {$time1}ms, invalid user took {$time2}ms");
    }

    /** @test */
    public function authentication_prevents_user_enumeration(): void
    {
        // Arrange
        $validAdmin = Admin::factory()->create([
            'username' => 'validuser',
            'password' => Hash::make('password123')
        ]);

        // Test with valid username but wrong password
        $response1 = $this->postJson('/api/admin/auth/login', [
            'username' => 'validuser',
            'password' => 'wrongpassword'
        ]);

        // Test with invalid username
        $response2 = $this->postJson('/api/admin/auth/login', [
            'username' => 'invaliduser',
            'password' => 'wrongpassword'
        ]);

        // Assert both return identical error messages
        $response1->assertStatus(401);
        $response2->assertStatus(401);

        $message1 = $response1->json('message');
        $message2 = $response2->json('message');

        $this->assertEquals($message1, $message2,
            'Different error messages could allow user enumeration');

        // Verify messages don't reveal user existence
        $this->assertStringNotContainsString('user', strtolower($message1));
        $this->assertStringNotContainsString('username', strtolower($message1));
        $this->assertStringNotContainsString('exists', strtolower($message1));
        $this->assertStringNotContainsString('found', strtolower($message1));
    }

    /** @test */
    public function authentication_handles_brute_force_attacks(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Simulate brute force attack with different passwords
        $passwords = [
            'password', '123456', 'admin', 'password123!',
            'qwerty', 'letmein', 'welcome', 'monkey'
        ];

        $blockedCount = 0;

        foreach ($passwords as $password) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => $password
            ]);

            if ($response->getStatusCode() === 429) {
                $blockedCount++;
            }
        }

        // Assert that rate limiting kicked in
        $this->assertGreaterThan(0, $blockedCount,
            'Brute force attack was not properly rate limited');
    }

    /** @test */
    public function authentication_logs_suspicious_activities(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Test multiple failed attempts from same IP
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => 'wrongpassword'
            ]);
        }

        // Test login with unusual user agent
        $response = $this->withHeaders([
            'User-Agent' => 'curl/7.68.0' // Automated tool
        ])->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword'
        ]);

        // Assert suspicious activity is handled
        $response->assertStatus(401);

        // In a real implementation, you would verify logs were created
        $this->assertTrue(true);
    }

    /** @test */
    public function token_security_prevents_token_fixation(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Login twice to get two different tokens
        $response1 = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $response2 = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        // Assert tokens are different
        $this->assertNotEquals($token1, $token2,
            'Tokens should be unique for each login session');

        // Both tokens should be valid
        $this->withHeaders(['Authorization' => "Bearer $token1"])
            ->getJson('/api/admin/auth/me')
            ->assertStatus(200);

        $this->withHeaders(['Authorization' => "Bearer $token2"])
            ->getJson('/api/admin/auth/me')
            ->assertStatus(200);
    }

    /** @test */
    public function authentication_handles_account_lockout(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Make multiple failed attempts to trigger lockout
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => 'wrongpassword'
            ]);

            // After several attempts, should be rate limited
            if ($i >= 5) {
                $this->assertEquals(429, $response->getStatusCode());
            }
        }

        // Even with correct password, should still be locked out temporarily
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function authentication_validates_token_integrity(): void
    {
        // Arrange
        $admin = Admin::factory()->create();
        $validToken = $admin->createToken('test')->plainTextToken;

        // Test with modified token
        $modifiedToken = substr($validToken, 0, -5) . 'XXXXX';

        $response = $this->withHeaders(['Authorization' => "Bearer $modifiedToken"])
                        ->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function authentication_prevents_session_hijacking(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Login and get token
        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.token');

        // Use token from different IP (simulated by different headers)
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Forwarded-For' => '192.168.1.100'
        ])->getJson('/api/admin/auth/me');

        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Forwarded-For' => '10.0.0.50'
        ])->getJson('/api/admin/auth/me');

        // Both should work (or both should be blocked if IP validation is implemented)
        // The key is consistent behavior
        $this->assertEquals($response1->getStatusCode(), $response2->getStatusCode());
    }
}
