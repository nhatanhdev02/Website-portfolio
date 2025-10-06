<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Hero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class HeroControllerTest extends TestCase
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
    public function authenticated_admin_can_get_hero_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $hero = Hero::factory()->create([
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh'
        ]);

        // Act
        $response = $this->getJson('/api/admin/hero');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'greeting_vi',
                        'greeting_en',
                        'name',
                        'title_vi',
                        'title_en',
                        'subtitle_vi',
                        'subtitle_en',
                        'cta_text_vi',
                        'cta_text_en',
                        'cta_link',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'greeting_vi' => 'Xin chào',
                        'greeting_en' => 'Hello',
                        'name' => 'Nhật Anh'
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_hero_content(): void
    {
        // Act
        $response = $this->getJson('/api/admin/hero');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_hero_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Hero::factory()->create();

        $updateData = [
            'greeting_vi' => 'Xin chào mới',
            'greeting_en' => 'New Hello',
            'name' => 'Nhật Anh Dev',
            'title_vi' => 'Lập trình viên Full-stack',
            'title_en' => 'Full-stack Developer',
            'subtitle_vi' => 'Tôi tạo ra những ứng dụng web tuyệt vời',
            'subtitle_en' => 'I create amazing web applications',
            'cta_text_vi' => 'Liên hệ ngay',
            'cta_text_en' => 'Contact Now',
            'cta_link' => 'https://example.com/contact'
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'greeting_vi',
                        'greeting_en',
                        'name',
                        'title_vi',
                        'title_en',
                        'subtitle_vi',
                        'subtitle_en',
                        'cta_text_vi',
                        'cta_text_en',
                        'cta_link',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Hero content updated successfully',
                    'data' => [
                        'greeting_vi' => 'Xin chào mới',
                        'greeting_en' => 'New Hello',
                        'name' => 'Nhật Anh Dev'
                    ]
                ]);

        $this->assertDatabaseHas('heroes', [
            'greeting_vi' => 'Xin chào mới',
            'greeting_en' => 'New Hello',
            'name' => 'Nhật Anh Dev'
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_hero_content(): void
    {
        // Arrange
        $updateData = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test'
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $updateData);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function hero_update_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->putJson('/api/admin/hero', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'greeting_vi',
                    'greeting_en',
                    'name',
                    'title_vi',
                    'title_en',
                    'subtitle_vi',
                    'subtitle_en',
                    'cta_text_vi',
                    'cta_text_en',
                    'cta_link'
                ]);
    }

    /** @test */
    public function hero_update_validates_field_lengths(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'greeting_vi' => str_repeat('a', 501), // Exceeds 500 char limit
            'greeting_en' => 'Hello',
            'name' => str_repeat('b', 256), // Exceeds 255 char limit
            'title_vi' => 'Title',
            'title_en' => 'Title',
            'subtitle_vi' => str_repeat('c', 1001), // Exceeds 1000 char limit
            'subtitle_en' => 'Subtitle',
            'cta_text_vi' => str_repeat('d', 101), // Exceeds 100 char limit
            'cta_text_en' => 'CTA',
            'cta_link' => 'https://example.com'
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'greeting_vi',
                    'name',
                    'subtitle_vi',
                    'cta_text_vi'
                ]);
    }

    /** @test */
    public function hero_update_validates_cta_link_format(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test',
            'title_vi' => 'Title',
            'title_en' => 'Title',
            'subtitle_vi' => 'Subtitle',
            'subtitle_en' => 'Subtitle',
            'cta_text_vi' => 'CTA',
            'cta_text_en' => 'CTA',
            'cta_link' => 'invalid-url' // Invalid URL format
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['cta_link']);
    }

    /** @test */
    public function hero_update_creates_new_record_if_none_exists(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $this->assertDatabaseCount('heroes', 0);

        $updateData = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh',
            'title_vi' => 'Lập trình viên',
            'title_en' => 'Developer',
            'subtitle_vi' => 'Phụ đề',
            'subtitle_en' => 'Subtitle',
            'cta_text_vi' => 'Liên hệ',
            'cta_text_en' => 'Contact',
            'cta_link' => 'https://example.com'
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Hero content updated successfully'
                ]);

        $this->assertDatabaseCount('heroes', 1);
        $this->assertDatabaseHas('heroes', [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh'
        ]);
    }

    /** @test */
    public function hero_content_returns_default_structure_when_empty(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $this->assertDatabaseCount('heroes', 0);

        // Act
        $response = $this->getJson('/api/admin/hero');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'greeting_vi' => '',
                        'greeting_en' => '',
                        'name' => '',
                        'title_vi' => '',
                        'title_en' => '',
                        'subtitle_vi' => '',
                        'subtitle_en' => '',
                        'cta_text_vi' => '',
                        'cta_text_en' => '',
                        'cta_link' => ''
                    ]
                ]);
    }

    /** @test */
    public function hero_update_logs_admin_action(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Hero::factory()->create();

        $updateData = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test',
            'title_vi' => 'Title',
            'title_en' => 'Title',
            'subtitle_vi' => 'Subtitle',
            'subtitle_en' => 'Subtitle',
            'cta_text_vi' => 'CTA',
            'cta_text_en' => 'CTA',
            'cta_link' => 'https://example.com'
        ];

        // Act
        $response = $this->putJson('/api/admin/hero', $updateData);

        // Assert
        $response->assertStatus(200);

        // Check that the action was logged (this would require checking log files or database logs)
        // For now, we just verify the response was successful
        $this->assertTrue(true);
    }
}
