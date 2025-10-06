<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Admin;

use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create([
            'username' => 'admin',
            'password' => Hash::make('password123')
        ]);
        Storage::fake('public');
    }

    /** @test */
    public function complete_admin_content_management_workflow(): void
    {
        // Step 1: Admin Login
        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // Step 2: Update Hero Section
        $heroData = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh Dev',
            'title_vi' => 'Lập trình viên Full-stack',
            'title_en' => 'Full-stack Developer',
            'subtitle_vi' => 'Tôi tạo ra những ứng dụng web tuyệt vời',
            'subtitle_en' => 'I create amazing web applications',
            'cta_text_vi' => 'Liên hệ ngay',
            'cta_text_en' => 'Contact Now',
            'cta_link' => 'https://example.com/contact'
        ];

        $heroResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                            ->putJson('/api/admin/hero', $heroData);

        $heroResponse->assertStatus(200)
                    ->assertJson(['success' => true]);

        $this->assertDatabaseHas('heroes', [
            'name' => 'Nhật Anh Dev',
            'title_en' => 'Full-stack Developer'
        ]);

        // Step 3: Create Services
        $serviceData = [
            'title_vi' => 'Phát triển Web',
            'title_en' => 'Web Development',
            'description_vi' => 'Tạo ra các ứng dụng web hiện đại',
            'description_en' => 'Creating modern web applications',
            'icon' => 'web-icon',
            'color' => '#3B82F6',
            'bg_color' => '#EBF8FF'
        ];

        $serviceResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                               ->postJson('/api/admin/services', $serviceData);

        $serviceResponse->assertStatus(201)
                       ->assertJson(['success' => true]);

        $this->assertDatabaseHas('services', [
            'title_en' => 'Web Development',
            'color' => '#3B82F6'
        ]);

        // Step 4: Create Project with Image
        $projectImage = UploadedFile::fake()->image('project.jpg', 800, 600);

        $projectData = [
            'title_vi' => 'Dự án E-commerce',
            'title_en' => 'E-commerce Project',
            'description_vi' => 'Một trang web thương mại điện tử hoàn chỉnh',
            'description_en' => 'A complete e-commerce website',
            'link' => 'https://example-ecommerce.com',
            'technologies' => ['Laravel', 'Vue.js', 'MySQL', 'Redis'],
            'category' => 'web',
            'featured' => true,
            'image' => $projectImage
        ];

        $projectResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                               ->postJson('/api/admin/projects', $projectData);

        $projectResponse->assertStatus(201)
                       ->assertJson(['success' => true]);

        $project = Project::where('title_en', 'E-commerce Project')->first();
        $this->assertNotNull($project);
        $this->assertTrue($project->featured);
        $this->assertNotNull($project->image);

        // Step 5: Create and Publish Blog Post
        $blogThumbnail = UploadedFile::fake()->image('blog-thumb.jpg', 400, 300);

        $blogData = [
            'title_vi' => 'Hướng dẫn Laravel',
            'title_en' => 'Laravel Tutorial',
            'content_vi' => 'Nội dung hướng dẫn Laravel chi tiết...',
            'content_en' => 'Detailed Laravel tutorial content...',
            'excerpt_vi' => 'Học Laravel từ cơ bản đến nâng cao',
            'excerpt_en' => 'Learn Laravel from basic to advanced',
            'status' => 'draft',
            'tags' => ['laravel', 'php', 'tutorial'],
            'thumbnail' => $blogThumbnail
        ];

        $blogResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                            ->postJson('/api/admin/blog', $blogData);

        $blogResponse->assertStatus(201)
                    ->assertJson(['success' => true]);

        $blogPost = BlogPost::where('title_en', 'Laravel Tutorial')->first();
        $this->assertNotNull($blogPost);
        $this->assertEquals('draft', $blogPost->status);

        // Publish the blog post
        $publishResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                               ->patchJson("/api/admin/blog/{$blogPost->id}/publish");

        $publishResponse->assertStatus(200)
                       ->assertJson(['success' => true]);

        $blogPost->refresh();
        $this->assertEquals('published', $blogPost->status);
        $this->assertNotNull($blogPost->published_at);

        // Step 6: Manage Contact Messages
        $contactMessage = ContactMessage::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I would like to hire you for a project',
            'read_at' => null
        ]);

        // Get unread messages
        $messagesResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->getJson('/api/admin/contacts/messages?read_status=unread');

        $messagesResponse->assertStatus(200)
                        ->assertJson(['success' => true]);

        $this->assertCount(1, $messagesResponse->json('data.data'));

        // Mark message as read
        $markReadResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->patchJson("/api/admin/contacts/messages/{$contactMessage->id}/read");

        $markReadResponse->assertStatus(200)
                        ->assertJson(['success' => true]);

        $contactMessage->refresh();
        $this->assertNotNull($contactMessage->read_at);

        // Step 7: Update System Settings
        $settingsData = [
            'default_language' => 'en',
            'primary_color' => '#3B82F6',
            'maintenance_mode' => false
        ];

        $settingsResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->putJson('/api/admin/settings', $settingsData);

        $settingsResponse->assertStatus(200)
                        ->assertJson(['success' => true]);

        // Step 8: Reorder Projects
        $project2 = Project::factory()->create(['order' => 2]);
        $project3 = Project::factory()->create(['order' => 3]);

        $reorderData = [
            'order' => [$project3->id, $project->id, $project2->id]
        ];

        $reorderResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                               ->putJson('/api/admin/projects/reorder', $reorderData);

        $reorderResponse->assertStatus(200)
                       ->assertJson(['success' => true]);

        // Verify new order
        $this->assertDatabaseHas('projects', ['id' => $project3->id, 'order' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'order' => 2]);
        $this->assertDatabaseHas('projects', ['id' => $project2->id, 'order' => 3]);

        // Step 9: Get Dashboard Statistics
        $statsResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                             ->getJson('/api/admin/dashboard/stats');

        $statsResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'success',
                         'data' => [
                             'total_projects',
                             'published_posts',
                             'unread_messages',
                             'total_services'
                         ]
                     ]);

        // Step 10: Logout
        $logoutResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                              ->postJson('/api/admin/auth/logout');

        $logoutResponse->assertStatus(200)
                      ->assertJson(['success' => true]);

        // Verify token is invalidated
        $protectedResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                 ->getJson('/api/admin/hero');

        $protectedResponse->assertStatus(401);
    }

    /** @test */
    public function admin_can_perform_bulk_operations(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create multiple contact messages
        $messages = ContactMessage::factory()->count(5)->create(['read_at' => null]);
        $messageIds = $messages->pluck('id')->toArray();

        // Create multiple projects
        $projects = Project::factory()->count(3)->create();

        // Test bulk mark messages as read
        $bulkReadResponse = $this->postJson('/api/admin/contacts/messages/bulk-read', [
            'message_ids' => array_slice($messageIds, 0, 3)
        ]);

        $bulkReadResponse->assertStatus(200)
                        ->assertJson(['success' => true]);

        // Verify messages were marked as read
        foreach (array_slice($messageIds, 0, 3) as $id) {
            $this->assertDatabaseHas('contact_messages', [
                'id' => $id,
                'read_at' => now()->format('Y-m-d H:i:s')
            ]);
        }

        // Test bulk delete messages
        $bulkDeleteResponse = $this->deleteJson('/api/admin/contacts/messages/bulk-delete', [
            'message_ids' => array_slice($messageIds, 3, 2)
        ]);

        $bulkDeleteResponse->assertStatus(200)
                          ->assertJson(['success' => true]);

        // Verify messages were deleted
        foreach (array_slice($messageIds, 3, 2) as $id) {
            $this->assertDatabaseMissing('contact_messages', ['id' => $id]);
        }

        // Test project reordering with all projects
        $newOrder = $projects->pluck('id')->shuffle()->toArray();

        $reorderResponse = $this->putJson('/api/admin/projects/reorder', [
            'order' => $newOrder
        ]);

        $reorderResponse->assertStatus(200)
                       ->assertJson(['success' => true]);

        // Verify new order
        foreach ($newOrder as $index => $projectId) {
            $this->assertDatabaseHas('projects', [
                'id' => $projectId,
                'order' => $index + 1
            ]);
        }
    }

    /** @test */
    public function admin_workflow_handles_file_uploads_correctly(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Test multiple file uploads in sequence
        $profileImage = UploadedFile::fake()->image('profile.jpg', 300, 300);
        $projectImage = UploadedFile::fake()->image('project.png', 800, 600);
        $blogThumbnail = UploadedFile::fake()->image('blog.webp', 400, 300);

        // Upload profile image for about section
        $aboutResponse = $this->postJson('/api/admin/about/image', [
            'image' => $profileImage
        ]);

        $aboutResponse->assertStatus(200)
                     ->assertJson(['success' => true]);

        // Create project with image
        $projectData = [
            'title_vi' => 'Dự án với hình ảnh',
            'title_en' => 'Project with Image',
            'description_vi' => 'Mô tả dự án',
            'description_en' => 'Project description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $projectImage
        ];

        $projectResponse = $this->postJson('/api/admin/projects', $projectData);

        $projectResponse->assertStatus(201)
                       ->assertJson(['success' => true]);

        // Create blog post with thumbnail
        $blogData = [
            'title_vi' => 'Bài viết với thumbnail',
            'title_en' => 'Post with Thumbnail',
            'content_vi' => 'Nội dung',
            'content_en' => 'Content',
            'excerpt_vi' => 'Tóm tắt',
            'excerpt_en' => 'Excerpt',
            'thumbnail' => $blogThumbnail
        ];

        $blogResponse = $this->postJson('/api/admin/blog', $blogData);

        $blogResponse->assertStatus(201)
                    ->assertJson(['success' => true]);

        // Verify all files were stored
        $about = \App\Models\About::first();
        $project = Project::where('title_en', 'Project with Image')->first();
        $blog = BlogPost::where('title_en', 'Post with Thumbnail')->first();

        $this->assertNotNull($about->profile_image);
        $this->assertNotNull($project->image);
        $this->assertNotNull($blog->thumbnail);

        // Verify files exist in storage
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $about->profile_image)));
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $project->image)));
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $blog->thumbnail)));
    }

    /** @test */
    public function admin_workflow_maintains_data_consistency(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Create related data
        $service = Service::factory()->create(['order' => 1]);
        $project = Project::factory()->create(['order' => 1, 'featured' => false]);
        $blogPost = BlogPost::factory()->create(['status' => 'draft']);

        // Test cascading updates
        // Update service order
        $service2 = Service::factory()->create(['order' => 2]);
        $reorderResponse = $this->putJson('/api/admin/services/reorder', [
            'order' => [$service2->id, $service->id]
        ]);

        $reorderResponse->assertStatus(200);

        // Verify order consistency
        $this->assertDatabaseHas('services', ['id' => $service2->id, 'order' => 1]);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'order' => 2]);

        // Test featured project toggle
        $toggleResponse = $this->patchJson("/api/admin/projects/{$project->id}/toggle-featured");

        $toggleResponse->assertStatus(200);

        $project->refresh();
        $this->assertTrue($project->featured);

        // Test blog post publishing workflow
        $publishResponse = $this->patchJson("/api/admin/blog/{$blogPost->id}/publish");

        $publishResponse->assertStatus(200);

        $blogPost->refresh();
        $this->assertEquals('published', $blogPost->status);
        $this->assertNotNull($blogPost->published_at);

        // Test unpublishing
        $unpublishResponse = $this->patchJson("/api/admin/blog/{$blogPost->id}/unpublish");

        $unpublishResponse->assertStatus(200);

        $blogPost->refresh();
        $this->assertEquals('draft', $blogPost->status);
        $this->assertNull($blogPost->published_at);
    }
}
