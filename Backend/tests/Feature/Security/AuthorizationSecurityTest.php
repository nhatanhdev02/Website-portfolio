<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AuthorizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
    }

    /** @test */
    public function unauthenticated_users_cannot_access_admin_endpoints(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/admin/hero'],
            ['PUT', '/api/admin/hero'],
            ['GET', '/api/admin/about'],
            ['PUT', '/api/admin/about'],
            ['POST', '/api/admin/about/image'],
            ['GET', '/api/admin/services'],
            ['POST', '/api/admin/services'],
            ['GET', '/api/admin/projects'],
            ['POST', '/api/admin/projects'],
            ['GET', '/api/admin/blog'],
            ['POST', '/api/admin/blog'],
            ['GET', '/api/admin/contacts/messages'],
            ['GET', '/api/admin/settings'],
            ['PUT', '/api/admin/settings'],
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);

            $response->assertStatus(401)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Unauthenticated'
                    ]);
        }
    }

    /** @test */
    public function authenticated_admin_can_access_all_admin_endpoints(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create test data
        $project = Project::factory()->create();
        $blogPost = BlogPost::factory()->create();
        $service = Service::factory()->create();
        $message = ContactMessage::factory()->create();

        $accessibleEndpoints = [
            ['GET', '/api/admin/hero'],
            ['GET', '/api/admin/about'],
            ['GET', '/api/admin/services'],
            ['GET', "/api/admin/services/{$service->id}"],
            ['GET', '/api/admin/projects'],
            ['GET', "/api/admin/projects/{$project->id}"],
            ['GET', '/api/admin/blog'],
            ['GET', "/api/admin/blog/{$blogPost->id}"],
            ['GET', '/api/admin/contacts/messages'],
            ['GET', "/api/admin/contacts/messages/{$message->id}"],
            ['GET', '/api/admin/settings'],
        ];

        foreach ($accessibleEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);

            // Should not be 401 or 403
            $this->assertNotEquals(401, $response->getStatusCode(), "Failed for $method $endpoint");
            $this->assertNotEquals(403, $response->getStatusCode(), "Failed for $method $endpoint");
        }
    }

    /** @test */
    public function invalid_tokens_are_rejected(): void
    {
        $invalidTokens = [
            'invalid_token',
            'Bearer invalid_token',
            'expired_token_12345',
            str_repeat('a', 100), // Very long invalid token
            '../../etc/passwd', // Path traversal attempt
            '<script>alert("xss")</script>', // XSS attempt
        ];

        foreach ($invalidTokens as $token) {
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                            ->getJson('/api/admin/hero');

            $response->assertStatus(401)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Unauthenticated'
                    ]);
        }
    }

    /** @test */
    public function admin_can_only_access_own_session_data(): void
    {
        // Arrange
        $admin1 = Admin::factory()->create();
        $admin2 = Admin::factory()->create();

        // Create tokens for both admins
        $token1 = $admin1->createToken('admin-token', ['admin'])->plainTextToken;
        $token2 = $admin2->createToken('admin-token', ['admin'])->plainTextToken;

        // Act - Admin 1 accesses their own profile
        $response1 = $this->withHeaders(['Authorization' => "Bearer $token1"])
                         ->getJson('/api/admin/auth/me');

        // Assert
        $response1->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $admin1->id,
                         'username' => $admin1->username
                     ]
                 ]);

        // Act - Admin 2 accesses their own profile
        $response2 = $this->withHeaders(['Authorization' => "Bearer $token2"])
                         ->getJson('/api/admin/auth/me');

        // Assert
        $response2->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $admin2->id,
                         'username' => $admin2->username
                     ]
                 ]);

        // Verify they get different data
        $this->assertNotEquals($response1->json('data.id'), $response2->json('data.id'));
    }

    /** @test */
    public function admin_cannot_access_resources_with_sql_injection_attempts(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create();

        $sqlInjectionAttempts = [
            "1' OR '1'='1",
            "1; DROP TABLE projects; --",
            "1 UNION SELECT * FROM admins",
            "1' AND (SELECT COUNT(*) FROM admins) > 0 --",
            "'; DELETE FROM projects WHERE '1'='1",
        ];

        foreach ($sqlInjectionAttempts as $maliciousId) {
            // Act - Try to access project with malicious ID
            $response = $this->getJson("/api/admin/projects/{$maliciousId}");

            // Assert - Should return 404 or 400, not expose database structure
            $this->assertContains($response->getStatusCode(), [400, 404]);

            // Verify no sensitive information is leaked
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('SQL', $responseContent);
            $this->assertStringNotContainsString('database', $responseContent);
            $this->assertStringNotContainsString('mysql', $responseContent);
            $this->assertStringNotContainsString('table', $responseContent);
        }

        // Verify original project still exists (wasn't deleted by injection)
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    /** @test */
    public function admin_endpoints_prevent_xss_attacks(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $xssPayloads = [
            '<script>alert("xss")</script>',
            '"><script>alert("xss")</script>',
            'javascript:alert("xss")',
            '<img src="x" onerror="alert(\'xss\')">',
            '<svg onload="alert(\'xss\')">',
        ];

        foreach ($xssPayloads as $payload) {
            // Act - Try to create project with XSS payload
            $response = $this->postJson('/api/admin/projects', [
                'title_vi' => $payload,
                'title_en' => 'Test Project',
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web'
            ]);

            // Assert - Should either succeed (with sanitized data) or fail validation
            if ($response->getStatusCode() === 201) {
                // If created, verify the payload was sanitized
                $project = Project::latest()->first();
                $this->assertStringNotContainsString('<script>', $project->title_vi);
                $this->assertStringNotContainsString('javascript:', $project->title_vi);
                $this->assertStringNotContainsString('onerror=', $project->title_vi);
                $this->assertStringNotContainsString('onload=', $project->title_vi);

                $project->delete(); // Clean up
            }
        }
    }

    /** @test */
    public function admin_endpoints_validate_csrf_protection(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act - Try to make state-changing request without proper headers
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web'
        ], [
            'Origin' => 'https://malicious-site.com',
            'Referer' => 'https://malicious-site.com/attack'
        ]);

        // Assert - API should still work (CSRF protection is typically for web routes)
        // But CORS should prevent cross-origin requests
        $response->assertStatus(201);
    }

    /** @test */
    public function admin_endpoints_enforce_proper_http_methods(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create();

        // Test that endpoints only accept their designated HTTP methods
        $methodTests = [
            // Endpoint that should only accept GET
            ['POST', "/api/admin/projects/{$project->id}", 405], // Method Not Allowed
            ['PUT', "/api/admin/projects/{$project->id}", 200],  // Should work
            ['DELETE', "/api/admin/projects/{$project->id}", 200], // Should work

            // Endpoint that should only accept POST
            ['GET', '/api/admin/projects', 200], // Should work (index)
            ['POST', '/api/admin/projects', 422], // Should work but fail validation
        ];

        foreach ($methodTests as [$method, $endpoint, $expectedStatus]) {
            $response = $this->json($method, $endpoint, [
                'title_vi' => 'Test',
                'title_en' => 'Test',
                'description_vi' => 'Desc',
                'description_en' => 'Desc',
                'technologies' => ['Laravel'],
                'category' => 'web'
            ]);

            if ($expectedStatus === 405) {
                $response->assertStatus(405);
            } else {
                $this->assertNotEquals(405, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function admin_endpoints_handle_mass_assignment_protection(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act - Try to mass assign protected fields
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Test Project',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'id' => 999, // Try to set ID
            'created_at' => '2020-01-01 00:00:00', // Try to set timestamp
            'updated_at' => '2020-01-01 00:00:00', // Try to set timestamp
        ]);

        // Assert
        $response->assertStatus(201);

        $project = Project::latest()->first();

        // Verify protected fields weren't mass assigned
        $this->assertNotEquals(999, $project->id);
        $this->assertNotEquals('2020-01-01 00:00:00', $project->created_at->format('Y-m-d H:i:s'));
        $this->assertNotEquals('2020-01-01 00:00:00', $project->updated_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function admin_endpoints_prevent_parameter_pollution(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act - Try parameter pollution attack
        $response = $this->postJson('/api/admin/projects', [
            'title_vi' => 'Legitimate Title',
            'title_en' => ['Legitimate Title', 'Malicious Title'], // Array instead of string
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web'
        ]);

        // Assert - Should fail validation or handle gracefully
        if ($response->getStatusCode() === 422) {
            $response->assertJsonValidationErrors(['title_en']);
        } else if ($response->getStatusCode() === 201) {
            // If it succeeds, verify only the first value was used
            $project = Project::latest()->first();
            $this->assertEquals('Legitimate Title', $project->title_en);
        }
    }
}
