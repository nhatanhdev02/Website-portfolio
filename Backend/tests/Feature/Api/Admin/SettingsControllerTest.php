<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SettingsControllerTest extends TestCase
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
    public function authenticated_admin_can_get_all_settings(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'site_name',
            'value' => 'Nhật Anh Dev'
        ]);
        SystemSettings::factory()->create([
            'key' => 'default_language',
            'value' => 'vi'
        ]);

        // Act
        $response = $this->getJson('/api/admin/settings');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'key',
                            'value',
                            'type',
                            'description',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function authenticated_admin_can_get_single_setting(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'site_name',
            'value' => 'Nhật Anh Dev',
            'type' => 'string'
        ]);

        // Act
        $response = $this->getJson('/api/admin/settings/site_name');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'key',
                        'value',
                        'type',
                        'description',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'key' => 'site_name',
                        'value' => 'Nhật Anh Dev',
                        'type' => 'string'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_all_settings(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create(['key' => 'site_name', 'value' => 'Old Name']);
        SystemSettings::factory()->create(['key' => 'default_language', 'value' => 'en']);

        $updateData = [
            'site_name' => 'New Site Name',
            'default_language' => 'vi',
            'maintenance_mode' => false
        ];

        // Act
        $response = $this->putJson('/api/admin/settings', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'site_name',
            'value' => 'New Site Name'
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_language',
            'value' => 'vi'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_update_single_setting(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'site_name',
            'value' => 'Old Name'
        ]);

        // Act
        $response = $this->putJson('/api/admin/settings/site_name', [
            'value' => 'Updated Site Name'
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Setting updated successfully',
                    'data' => [
                        'key' => 'site_name',
                        'value' => 'Updated Site Name'
                    ]
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'site_name',
            'value' => 'Updated Site Name'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_get_language_config(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'default_language',
            'value' => 'vi'
        ]);
        SystemSettings::factory()->create([
            'key' => 'supported_languages',
            'value' => json_encode(['vi', 'en'])
        ]);

        // Act
        $response = $this->getJson('/api/admin/settings/language/config');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'default_language',
                        'supported_languages'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'default_language' => 'vi',
                        'supported_languages' => ['vi', 'en']
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_language_config(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $languageData = [
            'default_language' => 'en',
            'supported_languages' => ['vi', 'en', 'ja']
        ];

        // Act
        $response = $this->putJson('/api/admin/settings/language/config', $languageData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Language configuration updated successfully'
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_language',
            'value' => 'en'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_get_theme_config(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'theme_primary_color',
            'value' => '#FF6B6B'
        ]);
        SystemSettings::factory()->create([
            'key' => 'theme_secondary_color',
            'value' => '#4ECDC4'
        ]);

        // Act
        $response = $this->getJson('/api/admin/settings/theme/config');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'primary_color',
                        'secondary_color'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'primary_color' => '#FF6B6B',
                        'secondary_color' => '#4ECDC4'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_theme_config(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $themeData = [
            'primary_color' => '#007BFF',
            'secondary_color' => '#6C757D',
            'accent_color' => '#28A745'
        ];

        // Act
        $response = $this->putJson('/api/admin/settings/theme/config', $themeData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Theme configuration updated successfully'
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'theme_primary_color',
            'value' => '#007BFF'
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'theme_secondary_color',
            'value' => '#6C757D'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_toggle_maintenance_mode(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'maintenance_mode',
            'value' => 'false'
        ]);

        // Act
        $response = $this->postJson('/api/admin/settings/maintenance/toggle');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Maintenance mode toggled successfully',
                    'data' => [
                        'maintenance_mode' => true
                    ]
                ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'maintenance_mode',
            'value' => 'true'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_reset_settings(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create(['key' => 'site_name', 'value' => 'Custom Name']);
        SystemSettings::factory()->create(['key' => 'theme_primary_color', 'value' => '#FF0000']);

        // Act
        $response = $this->postJson('/api/admin/settings/reset');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Settings reset to defaults successfully'
                ]);

        // Verify settings were reset to defaults
        $this->assertDatabaseHas('system_settings', [
            'key' => 'site_name',
            'value' => 'Nhật Anh Dev - Freelance Fullstack'
        ]);
    }

    /** @test */
    public function language_config_validates_default_language(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'default_language' => 'invalid-lang', // Invalid language code
            'supported_languages' => ['vi', 'en']
        ];

        // Act
        $response = $this->putJson('/api/admin/settings/language/config', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['default_language']);
    }

    /** @test */
    public function language_config_validates_supported_languages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'default_language' => 'vi',
            'supported_languages' => 'not-an-array' // Should be array
        ];

        // Act
        $response = $this->putJson('/api/admin/settings/language/config', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['supported_languages']);
    }

    /** @test */
    public function theme_config_validates_color_format(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'primary_color' => 'invalid-color', // Invalid hex color
            'secondary_color' => '#4ECDC4'
        ];

        // Act
        $response = $this->putJson('/api/admin/settings/theme/config', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['primary_color']);
    }

    /** @test */
    public function single_setting_update_validates_value(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'site_name',
            'type' => 'string'
        ]);

        // Act
        $response = $this->putJson('/api/admin/settings/site_name', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['value']);
    }

    /** @test */
    public function settings_update_creates_new_settings_if_not_exist(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $this->assertDatabaseCount('system_settings', 0);

        $updateData = [
            'site_name' => 'New Site',
            'default_language' => 'vi'
        ];

        // Act
        $response = $this->putJson('/api/admin/settings', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);

        $this->assertDatabaseCount('system_settings', 2);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'site_name',
            'value' => 'New Site'
        ]);
    }

    /** @test */
    public function maintenance_mode_toggle_is_rate_limited(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        SystemSettings::factory()->create([
            'key' => 'maintenance_mode',
            'value' => 'false'
        ]);

        // Act - Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/admin/settings/maintenance/toggle');
        }

        // Assert - The 6th request should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_settings(): void
    {
        // Act
        $response = $this->getJson('/api/admin/settings');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function returns_404_for_non_existent_setting(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/settings/non_existent_key');

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Setting not found'
                ]);
    }
}
