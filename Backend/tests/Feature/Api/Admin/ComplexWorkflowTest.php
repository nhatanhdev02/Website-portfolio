<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\BlogPost;
use App\Models\Project;
use App\Models\Service;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class ComplexWorkflowTest extends TestCase
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
    public function admin_can_complete_full_blog_publishing_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Create draft blog post
        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg', 400, 300);

        $blogData = [
            'title_vi' => 'Bài viết về Laravel',
            'title_en' => 'Laravel Tutorial',
            'content_vi' => 'Nội dung chi tiết về Laravel',
            'content_en' => 'Detailed Laravel content',
            'excerpt_vi' => 'Tóm tắt về Laravel',
            'excerpt_en' => 'Laravel summary',
            'status' => 'draft',
            'tags' => ['laravel', 'php', 'tutorial'],
            'thumbnail' => $thumbnail
        ];

        $createResponse = $this->postJson('/api/admin/blog', $blogData);
        $createResponse->assertStatus(201);

        $postId = $createResponse->json('data.id');

        // Step 2: Update the draft
        $updateData = [
            'title_vi' => 'Bài viết về Laravel - Cập nhật',
            'title_en' => 'Laravel Tutorial - Updated',
            'content_vi' => 'Nội dung chi tiết về Laravel - đã cập nhật',
            'content_en' => 'Detailed Laravel content - updated',
            'excerpt_vi' => 'Tóm tắt về Laravel - cập nhật',
            'excerpt_en' => 'Laravel summary - updated',
            'status' => 'draft',
            'tags' => ['laravel', 'php', 'tutorial', 'updated']
        ];

        $updateResponse = $this->putJson("/api/admin/blog/{$postId}", $updateData);
        $updateResponse->assertStatus(200);

        // Step 3: Publish the post
        $publishResponse = $this->patchJson("/api/admin/blog/{$postId}/publish");
        $publishResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'message' => 'Blog post published successfully',
                            'data' => [
                                'status' => 'published'
                            ]
                        ]);

        // Step 4: Verify published post appears in published list
        $publishedResponse = $this->getJson('/api/admin/blog/published/list');
        $publishedResponse->assertStatus(200);
        $this->assertCount(1, $publishedResponse->json('data.data'));

        // Step 5: Unpublish the post
        $unpublishResponse = $this->patchJson("/api/admin/blog/{$postId}/unpublish");
        $unpublishResponse->assertStatus(200)
                          ->assertJson([
                              'success' => true,
                              'message' => 'Blog post unpublished successfully',
                              'data' => [
                                  'status' => 'draft'
                              ]
                          ]);

        // Verify final state
        $this->assertDatabaseHas('blog_posts', [
            'id' => $postId,
            'title_en' => 'Laravel Tutorial - Updated',
            'status' => 'draft',
            'published_at' => null
        ]);
    }

    /** @test */
    public function admin_can_complete_project_management_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Create multiple projects
        $projects = [];
        for ($i = 1; $i <= 3; $i++) {
            $image = UploadedFile::fake()->image("project{$i}.jpg", 800, 600);

            $projectData = [
                'title_vi' => "Dự án {$i}",
                'title_en' => "Project {$i}",
                'description_vi' => "Mô tả dự án {$i}",
                'description_en' => "Project {$i} description",
                'technologies' => ['Laravel', 'Vue.js'],
                'category' => 'web',
                'featured' => false,
                'image' => $image
            ];

            $response = $this->postJson('/api/admin/projects', $projectData);
            $response->assertStatus(201);
            $projects[] = $response->json('data.id');
        }

        // Step 2: Toggle featured status for first project
        $featuredResponse = $this->patchJson("/api/admin/projects/{$projects[0]}/toggle-featured");
        $featuredResponse->assertStatus(200)
                         ->assertJson([
                             'success' => true,
                             'data' => [
                                 'featured' => true
                             ]
                         ]);

        // Step 3: Reorder projects
        $newOrder = [$projects[2], $projects[0], $projects[1]];
        $reorderResponse = $this->putJson('/api/admin/projects/reorder', [
            'order' => $newOrder
        ]);
        $reorderResponse->assertStatus(200);

        // Step 4: Get featured projects
        $featuredListResponse = $this->getJson('/api/admin/projects/featured/list');
        $featuredListResponse->assertStatus(200);
        $this->assertCount(1, $featuredListResponse->json('data'));

        // Step 5: Bulk action - delete last two projects
        $bulkResponse = $this->postJson('/api/admin/projects/bulk-action', [
            'action' => 'delete',
            'ids' => [$projects[1], $projects[2]]
        ]);
        $bulkResponse->assertStatus(200);

        // Verify final state
        $this->assertDatabaseHas('projects', ['id' => $projects[0], 'featured' => true]);
        $this->assertDatabaseMissing('projects', ['id' => $projects[1]]);
        $this->assertDatabaseMissing('projects', ['id' => $projects[2]]);
    }

    /** @test */
    public function admin_can_complete_service_management_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Create multiple services
        $services = [];
        $serviceData = [
            [
                'title_vi' => 'Phát triển Web',
                'title_en' => 'Web Development',
                'description_vi' => 'Tạo ứng dụng web hiện đại',
                'description_en' => 'Create modern web applications',
                'icon' => 'web-icon',
                'color' => '#FF6B6B',
                'bg_color' => '#FFE5E5'
            ],
            [
                'title_vi' => 'Phát triển Mobile',
                'title_en' => 'Mobile Development',
                'description_vi' => 'Tạo ứng dụng di động',
                'description_en' => 'Create mobile applications',
                'icon' => 'mobile-icon',
                'color' => '#4ECDC4',
                'bg_color' => '#E5F9F6'
            ],
            [
                'title_vi' => 'Tư vấn IT',
                'title_en' => 'IT Consulting',
                'description_vi' => 'Tư vấn giải pháp công nghệ',
                'description_en' => 'Technology solution consulting',
                'icon' => 'consulting-icon',
                'color' => '#45B7D1',
                'bg_color' => '#E5F4FD'
            ]
        ];

        foreach ($serviceData as $data) {
            $response = $this->postJson('/api/admin/services', $data);
            $response->assertStatus(201);
            $services[] = $response->json('data.id');
        }

        // Step 2: Update a service
        $updateData = [
            'title_vi' => 'Phát triển Web - Cập nhật',
            'title_en' => 'Web Development - Updated',
            'description_vi' => 'Tạo ứng dụng web hiện đại - cập nhật',
            'description_en' => 'Create modern web applications - updated',
            'icon' => 'web-icon-updated',
            'color' => '#FF0000',
            'bg_color' => '#FFE0E0'
        ];

        $updateResponse = $this->putJson("/api/admin/services/{$services[0]}", $updateData);
        $updateResponse->assertStatus(200);

        // Step 3: Reorder services
        $newOrder = [$services[2], $services[0], $services[1]];
        $reorderResponse = $this->putJson('/api/admin/services/reorder', [
            'order' => $newOrder
        ]);
        $reorderResponse->assertStatus(200);

        // Step 4: Bulk delete services
        $bulkResponse = $this->postJson('/api/admin/services/bulk-action', [
            'action' => 'delete',
            'ids' => [$services[1], $services[2]]
        ]);
        $bulkResponse->assertStatus(200);

        // Verify final state
        $this->assertDatabaseHas('services', [
            'id' => $services[0],
            'title_en' => 'Web Development - Updated',
            'color' => '#FF0000'
        ]);
        $this->assertDatabaseMissing('services', ['id' => $services[1]]);
        $this->assertDatabaseMissing('services', ['id' => $services[2]]);
    }

    /** @test */
    public function admin_can_complete_contact_message_management_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Create multiple contact messages
        $messages = [];
        for ($i = 1; $i <= 5; $i++) {
            $message = ContactMessage::factory()->create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => "Message content {$i}",
                'read_at' => null
            ]);
            $messages[] = $message->id;
        }

        // Step 2: Check unread count
        $unreadResponse = $this->getJson('/api/admin/contacts/messages/unread-count');
        $unreadResponse->assertStatus(200)
                      ->assertJson([
                          'success' => true,
                          'data' => [
                              'unread_count' => 5
                          ]
                      ]);

        // Step 3: Mark some messages as read individually
        $readResponse1 = $this->putJson("/api/admin/contacts/messages/{$messages[0]}/read");
        $readResponse1->assertStatus(200);

        $readResponse2 = $this->putJson("/api/admin/contacts/messages/{$messages[1]}/read");
        $readResponse2->assertStatus(200);

        // Step 4: Bulk mark remaining as read
        $bulkReadResponse = $this->postJson('/api/admin/contacts/messages/bulk-action', [
            'action' => 'mark_read',
            'ids' => [$messages[2], $messages[3]]
        ]);
        $bulkReadResponse->assertStatus(200);

        // Step 5: Mark one as unread
        $unreadResponse = $this->putJson("/api/admin/contacts/messages/{$messages[0]}/unread");
        $unreadResponse->assertStatus(200);

        // Step 6: Filter unread messages
        $filterResponse = $this->getJson('/api/admin/contacts/messages?status=unread');
        $filterResponse->assertStatus(200);
        $this->assertCount(2, $filterResponse->json('data.data')); // messages[0] and messages[4]

        // Step 7: Bulk delete some messages
        $bulkDeleteResponse = $this->postJson('/api/admin/contacts/messages/bulk-action', [
            'action' => 'delete',
            'ids' => [$messages[3], $messages[4]]
        ]);
        $bulkDeleteResponse->assertStatus(200);

        // Verify final state
        $this->assertDatabaseHas('contact_messages', ['id' => $messages[0], 'read_at' => null]);
        $this->assertDatabaseHas('contact_messages', ['id' => $messages[1]]);
        $this->assertDatabaseHas('contact_messages', ['id' => $messages[2]]);
        $this->assertDatabaseMissing('contact_messages', ['id' => $messages[3]]);
        $this->assertDatabaseMissing('contact_messages', ['id' => $messages[4]]);
    }

    /** @test */
    public function admin_can_complete_multi_step_content_creation_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Update hero section
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

        $heroResponse = $this->putJson('/api/admin/hero', $heroData);
        $heroResponse->assertStatus(200);

        // Step 2: Update about section with image
        $profileImage = UploadedFile::fake()->image('profile.jpg', 600, 600);
        $imageResponse = $this->postJson('/api/admin/about/image', [
            'image' => $profileImage
        ]);
        $imageResponse->assertStatus(200);

        $aboutData = [
            'content_vi' => 'Tôi là một lập trình viên full-stack',
            'content_en' => 'I am a full-stack developer',
            'skills' => ['Laravel', 'Vue.js', 'MySQL', 'Docker'],
            'experience_years' => 5
        ];

        $aboutResponse = $this->putJson('/api/admin/about', $aboutData);
        $aboutResponse->assertStatus(200);

        // Step 3: Create services
        $serviceData = [
            'title_vi' => 'Phát triển Web',
            'title_en' => 'Web Development',
            'description_vi' => 'Tạo ứng dụng web hiện đại',
            'description_en' => 'Create modern web applications',
            'icon' => 'web-icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        $serviceResponse = $this->postJson('/api/admin/services', $serviceData);
        $serviceResponse->assertStatus(201);

        // Step 4: Create project
        $projectImage = UploadedFile::fake()->image('project.jpg', 800, 600);
        $projectData = [
            'title_vi' => 'Dự án Portfolio',
            'title_en' => 'Portfolio Project',
            'description_vi' => 'Website portfolio cá nhân',
            'description_en' => 'Personal portfolio website',
            'technologies' => ['Laravel', 'Vue.js', 'Tailwind CSS'],
            'category' => 'web',
            'featured' => true,
            'image' => $projectImage
        ];

        $projectResponse = $this->postJson('/api/admin/projects', $projectData);
        $projectResponse->assertStatus(201);

        // Step 5: Create and publish blog post
        $blogThumbnail = UploadedFile::fake()->image('blog-thumb.jpg', 400, 300);
        $blogData = [
            'title_vi' => 'Hướng dẫn Laravel',
            'title_en' => 'Laravel Tutorial',
            'content_vi' => 'Hướng dẫn chi tiết về Laravel',
            'content_en' => 'Detailed Laravel tutorial',
            'excerpt_vi' => 'Tóm tắt hướng dẫn',
            'excerpt_en' => 'Tutorial summary',
            'status' => 'draft',
            'tags' => ['laravel', 'tutorial'],
            'thumbnail' => $blogThumbnail
        ];

        $blogResponse = $this->postJson('/api/admin/blog', $blogData);
        $blogResponse->assertStatus(201);

        $blogId = $blogResponse->json('data.id');
        $publishResponse = $this->patchJson("/api/admin/blog/{$blogId}/publish");
        $publishResponse->assertStatus(200);

        // Step 6: Update system settings
        $settingsData = [
            'site_name' => 'Nhật Anh Dev Portfolio',
            'default_language' => 'vi',
            'maintenance_mode' => false
        ];

        $settingsResponse = $this->putJson('/api/admin/settings', $settingsData);
        $settingsResponse->assertStatus(200);

        // Verify all content was created successfully
        $this->assertDatabaseHas('heroes', ['name' => 'Nhật Anh Dev']);
        $this->assertDatabaseHas('abouts', ['experience_years' => 5]);
        $this->assertDatabaseHas('services', ['title_en' => 'Web Development']);
        $this->assertDatabaseHas('projects', ['title_en' => 'Portfolio Project', 'featured' => true]);
        $this->assertDatabaseHas('blog_posts', ['title_en' => 'Laravel Tutorial', 'status' => 'published']);
        $this->assertDatabaseHas('system_settings', ['key' => 'site_name', 'value' => 'Nhật Anh Dev Portfolio']);
    }

    /** @test */
    public function admin_workflow_handles_validation_errors_gracefully(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Try to create project with invalid data
        $invalidProjectData = [
            'title_vi' => '', // Missing required field
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => [], // Empty array
            'category' => 'invalid-category' // Invalid category
        ];

        $projectResponse = $this->postJson('/api/admin/projects', $invalidProjectData);
        $projectResponse->assertStatus(422)
                        ->assertJsonValidationErrors(['title_vi', 'technologies', 'category']);

        // Step 2: Fix validation errors and create successfully
        $validProjectData = [
            'title_vi' => 'Dự án hợp lệ',
            'title_en' => 'Valid Project',
            'description_vi' => 'Mô tả hợp lệ',
            'description_en' => 'Valid description',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web'
        ];

        $validProjectResponse = $this->postJson('/api/admin/projects', $validProjectData);
        $validProjectResponse->assertStatus(201);

        // Step 3: Try bulk action with invalid data
        $projectId = $validProjectResponse->json('data.id');
        $invalidBulkResponse = $this->postJson('/api/admin/projects/bulk-action', [
            'action' => 'invalid-action', // Invalid action
            'ids' => [$projectId]
        ]);
        $invalidBulkResponse->assertStatus(422)
                           ->assertJsonValidationErrors(['action']);

        // Step 4: Perform valid bulk action
        $validBulkResponse = $this->postJson('/api/admin/projects/bulk-action', [
            'action' => 'delete',
            'ids' => [$projectId]
        ]);
        $validBulkResponse->assertStatus(200);

        // Verify project was deleted
        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
    }
}
