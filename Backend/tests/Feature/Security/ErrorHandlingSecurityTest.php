<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

class ErrorHandlingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function api_returns_consistent_error_format_for_validation_errors(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => '', // Required field missing
            'title_en' => str_repeat('a', 300), // Too long
            'description_vi' => 'Valid description',
            'description_en' => 'Valid description',
            'technologies' => 'not_an_array', // Should be array
            'category' => 'invalid_category' // Invalid enum value
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'title_vi',
                        'title_en',
                        'technologies',
                        'category'
                    ]
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'The given data was invalid.'
                ]);

        // Verify no sensitive information is leaked
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('database', strtolower($responseContent));
        $this->assertStringNotContainsString('sql', strtolower($responseContent));
        $this->assertStringNotContainsString('mysql', strtolower($responseContent));
        $this->assertStringNotContainsString('password', strtolower($responseContent));
    }

    /** @test */
    public function api_handles_database_connection_errors_gracefully(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Simulate database connection error by using invalid database config
        config(['database.connections.mysql.database' => 'nonexistent_database']);

        // Act
        $response = $this->getJson('/api/admin/projects');

        // Assert - Should return 500 with generic error message
        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Internal Server Error'
                ]);

        // Verify no database connection details are leaked
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('nonexistent_database', $responseContent);
        $this->assertStringNotContainsString('Connection refused', $responseContent);
        $this->assertStringNotContainsString('Access denied', $responseContent);
    }

    /** @test */
    public function api_handles_file_system_errors_gracefully(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create a valid image
        $image = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Simulate file system error by making storage read-only
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andThrow(new \Exception('Disk full'));

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $image
        ]);

        // Assert
        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'File upload failed'
                ]);

        // Verify no file system paths are leaked
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('/storage/', $responseContent);
        $this->assertStringNotContainsString('/var/www/', $responseContent);
        $this->assertStringNotContainsString('Disk full', $responseContent);
    }

    /** @test */
    public function api_handles_resource_not_found_errors_consistently(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $nonExistentIds = [999, 'abc', -1, 0];

        foreach ($nonExistentIds as $id) {
            // Act
            $response = $this->getJson("/api/admin/projects/{$id}");

            // Assert
            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Resource not found'
                    ]);

            // Verify no database structure information is leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('projects', strtolower($responseContent));
            $this->assertStringNotContainsString('table', strtolower($responseContent));
            $this->assertStringNotContainsString('model', strtolower($responseContent));
        }
    }

    /** @test */
    public function api_handles_authentication_errors_securely(): void
    {
        $invalidTokens = [
            'expired_token',
            'malformed.token.here',
            'Bearer invalid',
            str_repeat('a', 1000), // Very long token
            '../../etc/passwd', // Path traversal
            '<script>alert("xss")</script>' // XSS attempt
        ];

        foreach ($invalidTokens as $token) {
            // Act
            $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
                            ->getJson('/api/admin/projects');

            // Assert
            $response->assertStatus(401)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Unauthenticated'
                    ]);

            // Verify no token information is leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString($token, $responseContent);
            $this->assertStringNotContainsString('sanctum', strtolower($responseContent));
            $this->assertStringNotContainsString('bearer', strtolower($responseContent));
        }
    }

    /** @test */
    public function api_handles_authorization_errors_without_information_disclosure(): void
    {
        // Arrange - Create another admin
        $otherAdmin = Admin::factory()->create();
        $project = Project::factory()->create();

        // Act - Try to access resource as different admin (if role-based access exists)
        Sanctum::actingAs($otherAdmin, ['admin']);
        $response = $this->getJson("/api/admin/projects/{$project->id}");

        // Assert - Should either work (if no role restrictions) or return 403
        if ($response->getStatusCode() === 403) {
            $response->assertJson([
                'success' => false,
                'message' => 'Forbidden'
            ]);

            // Verify no resource information is leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString($project->title_en, $responseContent);
            $this->assertStringNotContainsString($project->description_en, $responseContent);
        } else {
            // If no role restrictions, should return 200
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function api_handles_rate_limiting_errors_gracefully(): void
    {
        // Arrange - Set low rate limit for testing
        config(['security.rate_limits.admin_auth.per_minute' => 2]);

        // Act - Exceed rate limit
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'invalid',
                'password' => 'invalid'
            ]);
        }

        // Assert - Last request should be rate limited
        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Too many requests'
                ]);

        // Verify no rate limiting implementation details are leaked
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('redis', strtolower($responseContent));
        $this->assertStringNotContainsString('cache', strtolower($responseContent));
        $this->assertStringNotContainsString('throttle', strtolower($responseContent));
    }

    /** @test */
    public function api_handles_malformed_json_requests_securely(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $malformedJsons = [
            '{"title": "test"', // Missing closing brace
            '{"title": "test",}', // Trailing comma
            '{title: "test"}', // Unquoted key
            '{"title": "test" "description": "desc"}', // Missing comma
            'not json at all',
            '{"title": "' . str_repeat('a', 10000) . '"}' // Very large JSON
        ];

        foreach ($malformedJsons as $malformedJson) {
            // Act
            $response = $this->call('POST', '/api/admin/projects', [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken
            ], $malformedJson);

            // Assert
            $response->assertStatus(400)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Invalid JSON payload'
                    ]);

            // Verify no JSON parsing details are leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('syntax error', strtolower($responseContent));
            $this->assertStringNotContainsString('parse error', strtolower($responseContent));
            $this->assertStringNotContainsString('unexpected', strtolower($responseContent));
        }
    }

    /** @test */
    public function api_handles_server_errors_without_stack_traces(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Force a server error by calling a non-existent method
        // This would typically be done by mocking a service to throw an exception
        Log::shouldReceive('error')->once();

        // Act - Try to trigger a server error
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Desc',
            'description_en' => 'Desc',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => 'invalid_image_data' // This should cause an error
        ]);

        // Assert
        if ($response->getStatusCode() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'Internal Server Error'
            ]);

            // Verify no stack trace or debug information is leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('stack trace', strtolower($responseContent));
            $this->assertStringNotContainsString('file:', strtolower($responseContent));
            $this->assertStringNotContainsString('line:', strtolower($responseContent));
            $this->assertStringNotContainsString('/var/www/', $responseContent);
            $this->assertStringNotContainsString('app/', $responseContent);
        }
    }

    /** @test */
    public function api_logs_security_relevant_errors(): void
    {
        // Arrange
        Log::shouldReceive('warning')
            ->once()
            ->with('Security: Invalid authentication attempt', \Mockery::type('array'));

        // Act - Make invalid authentication attempt
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrong_password'
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function api_handles_concurrent_error_scenarios(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create multiple concurrent requests that will fail
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/admin/projects/999'); // Non-existent resource
        }

        // Assert - All should return consistent error responses
        foreach ($responses as $response) {
            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Resource not found'
                    ]);
        }
    }

    /** @test */
    public function api_handles_memory_exhaustion_gracefully(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Try to create a request that would consume excessive memory
        $largeArray = array_fill(0, 100000, 'large_string_' . str_repeat('x', 1000));

        // Act
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Desc',
            'description_en' => 'Desc',
            'technologies' => $largeArray, // Extremely large array
            'category' => 'web'
        ]);

        // Assert - Should handle gracefully (either validation error or server error)
        $this->assertContains($response->getStatusCode(), [400, 422, 500]);

        if ($response->getStatusCode() === 500) {
            $response->assertJson([
                'success' => false,
                'message' => 'Internal Server Error'
            ]);
        }

        // Verify no memory information is leaked
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('memory', strtolower($responseContent));
        $this->assertStringNotContainsString('bytes', strtolower($responseContent));
        $this->assertStringNotContainsString('exhausted', strtolower($responseContent));
    }

    /** @test */
    public function api_handles_timeout_scenarios(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // This test would typically involve mocking a service to simulate timeout
        // For now, we'll test that the API can handle slow operations

        // Create a large dataset to potentially cause timeout
        Project::factory()->count(1000)->create();

        // Act - Request that might timeout
        $startTime = microtime(true);
        $response = $this->getJson('/api/admin/projects?per_page=1000');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        // Assert - Should complete within reasonable time or return timeout error
        if ($response->getStatusCode() === 200) {
            $this->assertLessThan(30000, $responseTime); // 30 seconds max
        } elseif ($response->getStatusCode() === 504) {
            $response->assertJson([
                'success' => false,
                'message' => 'Request timeout'
            ]);
        }
    }
}
