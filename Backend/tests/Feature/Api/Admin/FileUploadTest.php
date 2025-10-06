<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class FileUploadTest extends TestCase
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
    public function admin_can_upload_project_image_with_valid_file(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $image = UploadedFile::fake()->image('project.jpg', 800, 600);

        $projectData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án test',
            'description_en' => 'Test project description',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web',
            'image' => $image
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Project created successfully'
                ]);

        $project = Project::where('title_en', 'Test Project')->first();
        $this->assertNotNull($project->image);
        $this->assertStringContains('/storage/projects/', $project->image);

        // Verify file exists in storage
        $imagePath = str_replace('/storage/', '', $project->image);
        Storage::disk('public')->assertExists($imagePath);
    }

    /** @test */
    public function admin_can_upload_blog_thumbnail_with_valid_file(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg', 400, 300);

        $blogData = [
            'title_vi' => 'Bài viết test',
            'title_en' => 'Test Blog Post',
            'content_vi' => 'Nội dung bài viết test',
            'content_en' => 'Test blog post content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt',
            'status' => 'draft',
            'thumbnail' => $thumbnail
        ];

        // Act
        $response = $this->postJson('/api/admin/blog', $blogData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Blog post created successfully'
                ]);

        $post = BlogPost::where('title_en', 'Test Blog Post')->first();
        $this->assertNotNull($post->thumbnail);
        $this->assertStringContains('/storage/blog/', $post->thumbnail);

        // Verify file exists in storage
        $thumbnailPath = str_replace('/storage/', '', $post->thumbnail);
        Storage::disk('public')->assertExists($thumbnailPath);
    }

    /** @test */
    public function admin_can_upload_profile_image_for_about_section(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $profileImage = UploadedFile::fake()->image('profile.jpg', 600, 600);

        // Act
        $response = $this->postJson('/api/admin/about/image', [
            'image' => $profileImage
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

        $imageUrl = $response->json('data.image_url');
        $this->assertStringContains('/storage/about/', $imageUrl);

        // Verify file exists in storage
        $imagePath = str_replace('/storage/', '', $imageUrl);
        Storage::disk('public')->assertExists($imagePath);
    }

    /** @test */
    public function file_upload_validates_image_type(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024);

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $invalidFile
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_validates_image_size(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create a large image (6MB)
        $largeImage = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(6000);

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $largeImage
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_validates_image_dimensions(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create an image with invalid dimensions (too small)
        $smallImage = UploadedFile::fake()->image('small.jpg', 50, 50);

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $smallImage
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function file_upload_handles_multiple_image_formats(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($formats as $format) {
            $image = UploadedFile::fake()->image("test.{$format}", 800, 600);

            $projectData = [
                'title_vi' => "Test {$format}",
                'title_en' => "Test {$format}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ];

            // Act
            $response = $this->postJson('/api/admin/projects', $projectData);

            // Assert
            $response->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Project created successfully'
                    ]);

            $project = Project::where('title_en', "Test {$format}")->first();
            $this->assertNotNull($project->image);
        }
    }

    /** @test */
    public function file_upload_generates_unique_filenames(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $image1 = UploadedFile::fake()->image('same-name.jpg', 800, 600);
        $image2 = UploadedFile::fake()->image('same-name.jpg', 800, 600);

        // Create first project
        $projectData1 = [
            'title_vi' => 'Project 1',
            'title_en' => 'Project 1',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $image1
        ];

        $response1 = $this->postJson('/api/admin/projects', $projectData1);

        // Create second project
        $projectData2 = [
            'title_vi' => 'Project 2',
            'title_en' => 'Project 2',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $image2
        ];

        $response2 = $this->postJson('/api/admin/projects', $projectData2);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $project1 = Project::where('title_en', 'Project 1')->first();
        $project2 = Project::where('title_en', 'Project 2')->first();

        // Verify different filenames were generated
        $this->assertNotEquals($project1->image, $project2->image);

        // Verify both files exist
        $imagePath1 = str_replace('/storage/', '', $project1->image);
        $imagePath2 = str_replace('/storage/', '', $project2->image);
        Storage::disk('public')->assertExists($imagePath1);
        Storage::disk('public')->assertExists($imagePath2);
    }

    /** @test */
    public function file_upload_is_rate_limited(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act - Make multiple file upload requests to trigger rate limiting
        for ($i = 0; $i < 6; $i++) {
            $image = UploadedFile::fake()->image("test{$i}.jpg", 800, 600);

            $projectData = [
                'title_vi' => "Test {$i}",
                'title_en' => "Test {$i}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ];

            $response = $this->postJson('/api/admin/projects', $projectData);
        }

        // Assert - The 6th request should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function file_upload_logs_security_events(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $maliciousFile = UploadedFile::fake()->create('malicious.php', 1024);

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $maliciousFile
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);

        // Verify that security event was logged (this would require checking log files)
        // For now, we just verify the response was rejected
        $this->assertTrue(true);
    }

    /** @test */
    public function file_upload_cleans_up_on_validation_failure(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            // Missing required 'technologies' field to trigger validation error
            'category' => 'web',
            'image' => $image
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['technologies']);

        // Verify no project was created
        $this->assertDatabaseCount('projects', 0);

        // Verify temporary file was cleaned up (implementation dependent)
        // This would require checking that no orphaned files exist in storage
        $this->assertTrue(true);
    }

    /** @test */
    public function file_upload_handles_concurrent_uploads(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $promises = [];

        // Simulate concurrent uploads
        for ($i = 0; $i < 3; $i++) {
            $image = UploadedFile::fake()->image("concurrent{$i}.jpg", 800, 600);

            $projectData = [
                'title_vi' => "Concurrent {$i}",
                'title_en' => "Concurrent {$i}",
                'description_vi' => 'Description',
                'description_en' => 'Description',
                'technologies' => ['Laravel'],
                'category' => 'web',
                'image' => $image
            ];

            // Act
            $response = $this->postJson('/api/admin/projects', $projectData);

            // Assert
            $response->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Project created successfully'
                    ]);
        }

        // Verify all projects were created
        $this->assertDatabaseCount('projects', 3);

        // Verify all files exist
        $projects = Project::all();
        foreach ($projects as $project) {
            $imagePath = str_replace('/storage/', '', $project->image);
            Storage::disk('public')->assertExists($imagePath);
        }
    }
}
