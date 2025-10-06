<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /** @test */
    public function admin_can_login_with_valid_credentials(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'user' => [
                            'id',
                            'username',
                            'email',
                            'last_login_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'username' => 'admin'
        ]);
    }

    /** @test */
    public function admin_cannot_login_with_invalid_credentials(): void
    {
        // Arrange
        Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword'
        ]);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid admin credentials provided'
                ]);
    }

    /** @test */
    public function admin_cannot_login_with_non_existent_username(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'nonexistent',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid admin credentials provided'
                ]);
    }

    /** @test */
    public function login_validates_required_fields(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/login', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['username', 'password']);
    }

    /** @test */
    public function authenticated_admin_can_logout(): void
    {
        // Arrange
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/auth/logout');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully logged out'
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_logout(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/logout');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function authenticated_admin_can_refresh_token(): void
    {
        // Arrange
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/auth/refresh');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'user' => [
                            'id',
                            'username',
                            'email',
                            'last_login_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_refresh_token(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/refresh');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function authenticated_admin_can_get_user_profile(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'testadmin',
            'email' => 'admin@test.com'
        ]);
        Sanctum::actingAs($admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'username',
                        'email',
                        'last_login_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'username' => 'testadmin',
                        'email' => 'admin@test.com'
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile(): void
    {
        // Act
        $response = $this->getJson('/api/admin/auth/me');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function login_request_is_rate_limited(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);

        // Act - Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/admin/auth/login', [
                'username' => 'admin',
                'password' => 'wrongpassword'
            ]);
        }

        // Assert - The 6th request should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function successful_login_updates_last_login_timestamp(): void
    {
        // Arrange
        $admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'last_login_at' => null
        ]);

        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(200);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    /** @test */
    public function login_validates_username_format(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => '', // Empty username
            'password' => 'password123'
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
    }

    /** @test */
    public function login_validates_password_length(): void
    {
        // Act
        $response = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => '123' // Too short
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }
}
