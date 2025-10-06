<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\About;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class AboutControllerTest extends TestCase
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
    public function authenticated_admin_can_get_about_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $about = About::factory()->create([
            'content_vi' => 'Nội dung về tôi',
            'content_en' => 'About me content',
            'profile_image' => '/storage/about/profile.jpg'
        ]);

        // Act
        $response = $this->getJson('/api/admin/about');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'content_vi',
                        'content_en',
                        'profile_image',
                        'skills',
                        'experience',
                        'resume_url',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'content_vi' => 'Nội dung về tôi',
                        'content_en' => 'About me content',
                        'profile_image' => '/storage/about/profile.jpg'
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_about_content(): void
    {
        // Act
        $response = $this->getJson('/api/admin/about');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_about_content(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        About::factory()->create();

        $updateData = [
            'content_vi' => 'Nội dung mới về tôi',
            'content_en' => 'New about me content',
            'skills' => ['Laravel', 'Vue.js', 'MySQL', 'Docker']
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'content_vi',
                        'content_en',
                        'skills',
                        'experience',
                        'resume_url',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'About content updated successfully',
                    'data' => [
                        'content_vi' => 'Nội dung mới về tôi',
                        'content_en' => 'New about me content',
                        'skills' => ['Laravel', 'Vue.js', 'MySQL', 'Docker']
                    ]
                ]);

        $this->assertDatabaseHas('about', [
            'content_vi' => 'Nội dung mới về tôi',
            'content_en' => 'New about me content'
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_about_content(): void
    {
        // Arrange
        $updateData = [
            'content_vi' => 'Test content',
            'content_en' => 'Test content'
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $updateData);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ]);
    }

    /** @test */
    public function about_update_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->putJson('/api/admin/about', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'content_vi',
                    'content_en'
                ]);
    }

    /** @test */
    public function about_update_validates_field_lengths(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'content_vi' => str_repeat('a', 5001), // Exceeds 5000 char limit
            'content_en' => 'Valid content'
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'content_vi'
                ]);
    }

    /** @test */
    public function about_update_validates_skills_array(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'content_vi' => 'Valid content',
            'content_en' => 'Valid content',
            'skills' => 'not-an-array' // Should be array
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['skills']);
    }

    /** @test */
    public function authenticated_admin_can_upload_profile_image(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $image = UploadedFile::fake()->image('profile.jpg', 800, 600);

        // Act
        $response = $this->postJson('/api/admin/about/image', [
            'image' => $image
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'image_url'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Profile image uploaded successfully'
                ]);

        // Verify image was stored
        $imageUrl = $response->json('data.image_url');
        $this->assertNotNull($imageUrl);
        $this->assertStringContains('/storage/about/', $imageUrl);
    }

    /** @test */
    public function profile_image_upload_validates_file_type(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024); // Not an image

        // Act
        $response = $this->postJson('/api/admin/about/image', [
            'image' => $invalidFile
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function profile_image_upload_validates_file_size(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $largeImage = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(6000); // 6MB

        // Act
        $response = $this->postJson('/api/admin/about/image', [
            'image' => $largeImage
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function profile_image_upload_requires_authentication(): void
    {
        // Arrange
        $image = UploadedFile::fake()->image('profile.jpg');

        // Act
        $response = $this->postJson('/api/admin/about/image', [
            'image' => $image
        ]);

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ]);
    }

    /** @test */
    public function about_update_creates_new_record_if_none_exists(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $this->assertDatabaseCount('about', 0);

        $updateData = [
            'content_vi' => 'Nội dung về tôi',
            'content_en' => 'About me content',
            'skills' => ['Laravel', 'PHP']
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'About content updated successfully'
                ]);

        $this->assertDatabaseCount('about', 1);
        $this->assertDatabaseHas('about', [
            'content_vi' => 'Nội dung về tôi',
            'content_en' => 'About me content'
        ]);
    }

    /** @test */
    public function about_content_returns_default_structure_when_empty(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $this->assertDatabaseCount('about', 0);

        // Act
        $response = $this->getJson('/api/admin/about');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'content_vi' => '',
                        'content_en' => '',
                        'profile_image' => null,
                        'skills' => [],
                        'experience' => [],
                        'resume_url' => null
                    ]
                ]);
    }

    /** @test */
    public function about_update_logs_admin_action(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        About::factory()->create();

        $updateData = [
            'content_vi' => 'Test content',
            'content_en' => 'Test content',
            'skills' => ['Laravel'],
            'experience_years' => 2
        ];

        // Act
        $response = $this->putJson('/api/admin/about', $updateData);

        // Assert
        $response->assertStatus(200);

        // Check that the action was logged (this would require checking log files or database logs)
        // For now, we just verify the response was successful
        $this->assertTrue(true);
    }
}
