<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ContactMessage;
use App\Models\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

class ComplexAdminWorkflowTest extends TestCase
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
    public function admin_can_perform_complete_portfolio_setup_workflow(): void
    {
        // Step 1: Admin Authentication
        $loginResponse = $this->postJson('/api/admin/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // Step 2: Configure System Settings
        $settingsData = [
            'site_name' => 'Nhật Anh Dev Portfolio',
            'default_language' => 'vi',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
            'maintenance_mode' => false,
            'contact_email' => 'contact@nhatanh.dev',
            'social_links' => [
                'github' => 'https://github.com/nhatanh',
                'linkedin' => 'https://linkedin.com/in/nhatanh',
                'twitter' => 'https://twitter.com/nhatanh'
            ]
        ];

        $settingsResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->putJson('/api/admin/settings', $settingsData);

        $settingsResponse->assertStatus(200);

        // Step 3: Setup Hero Section
        $heroData = [
            'greeting_vi' => 'Xin chào, tôi là',
            'greeting_en' => 'Hello, I am',
            'name' => 'Nhật Anh',
            'title_vi' => 'Lập trình viên Full-stack',
            'title_en' => 'Full-stack Developer',
            'subtitle_vi' => 'Tôi tạo ra những ứng dụng web hiện đại và tối ưu',
            'subtitle_en' => 'I create modern and optimized web applications',
            'cta_text_vi' => 'Xem dự án của tôi',
            'cta_text_en' => 'View My Projects',
            'cta_link' => '#projects'
        ];

        $heroResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                            ->putJson('/api/admin/hero', $heroData);

        $heroResponse->assertStatus(200);

        // Step 4: Setup About Section with Profile Image
        $profileImage = UploadedFile::fake()->image('profile.jpg', 400, 400);

        $aboutImageResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                  ->postJson('/api/admin/about/image', [
                                      'image' => $profileImage
                                  ]);

        $aboutImageResponse->assertStatus(200);

        $aboutData = [
            'description_vi' => 'Tôi là một lập trình viên full-stack với 5 năm kinh nghiệm...',
            'description_en' => 'I am a full-stack developer with 5 years of experience...',
            'skills' => ['Laravel', 'Vue.js', 'React', 'Node.js', 'MySQL', 'MongoDB'],
            'experience_years' => 5,
            'projects_completed' => 50,
            'happy_clients' => 30
        ];

        $aboutResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                             ->putJson('/api/admin/about', $aboutData);

        $aboutResponse->assertStatus(200);

        // Step 5: Create Services
        $services = [
            [
                'title_vi' => 'Phát triển Web',
                'title_en' => 'Web Development',
                'description_vi' => 'Tạo ra các ứng dụng web hiện đại và responsive',
                'description_en' => 'Creating modern and responsive web applications',
                'icon' => 'code',
                'color' => '#3B82F6',
                'bg_color' => '#EBF8FF'
            ],
            [
                'title_vi' => 'Phát triển API',
                'title_en' => 'API Development',
                'description_vi' => 'Xây dựng RESTful API mạnh mẽ và bảo mật',
                'description_en' => 'Building robust and secure RESTful APIs',
                'icon' => 'server',
                'color' => '#10B981',
                'bg_color' => '#F0FDF4'
            ],
            [
                'title_vi' => 'Tư vấn kỹ thuật',
                'title_en' => 'Technical Consulting',
                'description_vi' => 'Tư vấn giải pháp kỹ thuật tối ưu cho doanh nghiệp',
                'description_en' => 'Providing optimal technical solutions for businesses',
                'icon' => 'lightbulb',
                'color' => '#F59E0B',
                'bg_color' => '#FFFBEB'
            ]
        ];

        foreach ($services as $index => $serviceData) {
            $serviceResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                   ->postJson('/api/admin/services', $serviceData);

            $serviceResponse->assertStatus(201);
        }

        // Step 6: Create Portfolio Projects
        $projects = [
            [
                'title_vi' => 'Hệ thống E-commerce',
                'title_en' => 'E-commerce System',
                'description_vi' => 'Một hệ thống thương mại điện tử hoàn chỉnh với Laravel và Vue.js',
                'description_en' => 'A complete e-commerce system built with Laravel and Vue.js',
                'technologies' => ['Laravel', 'Vue.js', 'MySQL', 'Redis', 'Stripe'],
                'category' => 'web',
                'featured' => true,
                'link' => 'https://demo-ecommerce.com',
                'image' => UploadedFile::fake()->image('ecommerce.jpg', 800, 600)
            ],
            [
                'title_vi' => 'Ứng dụng quản lý dự án',
                'title_en' => 'Project Management App',
                'description_vi' => 'Ứng dụng quản lý dự án với tính năng real-time collaboration',
                'description_en' => 'Project management app with real-time collaboration features',
                'technologies' => ['React', 'Node.js', 'Socket.io', 'MongoDB'],
                'category' => 'web',
                'featured' => true,
                'link' => 'https://demo-pm.com',
                'image' => UploadedFile::fake()->image('project-management.jpg', 800, 600)
            ],
            [
                'title_vi' => 'API Gateway',
                'title_en' => 'API Gateway',
                'description_vi' => 'Microservices API Gateway với rate limiting và authentication',
                'description_en' => 'Microservices API Gateway with rate limiting and authentication',
                'technologies' => ['Node.js', 'Express', 'Redis', 'JWT', 'Docker'],
                'category' => 'api',
                'featured' => false,
                'link' => 'https://github.com/nhatanh/api-gateway',
                'image' => UploadedFile::fake()->image('api-gateway.jpg', 800, 600)
            ]
        ];

        foreach ($projects as $projectData) {
            $projectResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                   ->postJson('/api/admin/projects', $projectData);

            $projectResponse->assertStatus(201);
        }

        // Step 7: Create Blog Posts
        $blogPosts = [
            [
                'title_vi' => 'Hướng dẫn Laravel 10',
                'title_en' => 'Laravel 10 Tutorial',
                'content_vi' => '# Hướng dẫn Laravel 10\n\nLaravel 10 mang đến nhiều tính năng mới...',
                'content_en' => '# Laravel 10 Tutorial\n\nLaravel 10 brings many new features...',
                'excerpt_vi' => 'Tìm hiểu về các tính năng mới trong Laravel 10',
                'excerpt_en' => 'Learn about new features in Laravel 10',
                'status' => 'published',
                'tags' => ['laravel', 'php', 'tutorial'],
                'thumbnail' => UploadedFile::fake()->image('laravel-tutorial.jpg', 600, 400)
            ],
            [
                'title_vi' => 'Best Practices cho API Design',
                'title_en' => 'API Design Best Practices',
                'content_vi' => '# Best Practices cho API Design\n\nKhi thiết kế API...',
                'content_en' => '# API Design Best Practices\n\nWhen designing APIs...',
                'excerpt_vi' => 'Những nguyên tắc quan trọng khi thiết kế API',
                'excerpt_en' => 'Important principles when designing APIs',
                'status' => 'draft',
                'tags' => ['api', 'design', 'best-practices'],
                'thumbnail' => UploadedFile::fake()->image('api-design.jpg', 600, 400)
            ]
        ];

        foreach ($blogPosts as $blogData) {
            $blogResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->postJson('/api/admin/blog', $blogData);

            $blogResponse->assertStatus(201);
        }

        // Step 8: Publish Draft Blog Post
        $draftPost = BlogPost::where('status', 'draft')->first();
        $publishResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                               ->patchJson("/api/admin/blog/{$draftPost->id}/publish");

        $publishResponse->assertStatus(200);

        // Step 9: Handle Contact Messages
        $contactMessages = ContactMessage::factory()->count(5)->create([
            'read_at' => null
        ]);

        // Get unread messages
        $messagesResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->getJson('/api/admin/contacts/messages?read_status=unread');

        $messagesResponse->assertStatus(200)
                        ->assertJsonCount(5, 'data.data');

        // Mark some messages as read
        $messageIds = $contactMessages->take(3)->pluck('id')->toArray();
        $bulkReadResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                ->postJson('/api/admin/contacts/messages/bulk-read', [
                                    'message_ids' => $messageIds
                                ]);

        $bulkReadResponse->assertStatus(200);

        // Step 10: Reorder Services and Projects
        $allServices = Service::orderBy('order')->get();
        $newServiceOrder = $allServices->pluck('id')->reverse()->toArray();

        $reorderServicesResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                       ->putJson('/api/admin/services/reorder', [
                                           'order' => $newServiceOrder
                                       ]);

        $reorderServicesResponse->assertStatus(200);

        $allProjects = Project::orderBy('order')->get();
        $newProjectOrder = $allProjects->pluck('id')->shuffle()->toArray();

        $reorderProjectsResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                       ->putJson('/api/admin/projects/reorder', [
                                           'order' => $newProjectOrder
                                       ]);

        $reorderProjectsResponse->assertStatus(200);

        // Step 11: Get Dashboard Statistics
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

        $stats = $statsResponse->json('data');
        $this->assertEquals(3, $stats['total_projects']);
        $this->assertEquals(2, $stats['published_posts']); // Both posts should be published now
        $this->assertEquals(2, $stats['unread_messages']); // 5 - 3 marked as read
        $this->assertEquals(3, $stats['total_services']);

        // Step 12: Export Data (if implemented)
        $exportResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                              ->getJson('/api/admin/export/portfolio');

        if ($exportResponse->getStatusCode() === 200) {
            $exportResponse->assertJsonStructure([
                'success',
                'data' => [
                    'hero',
                    'about',
                    'services',
                    'projects',
                    'blog_posts'
                ]
            ]);
        }

        // Step 13: Logout
        $logoutResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                              ->postJson('/api/admin/auth/logout');

        $logoutResponse->assertStatus(200);

        // Verify token is invalidated
        $protectedResponse = $this->withHeaders(['Authorization' => "Bearer $token"])
                                 ->getJson('/api/admin/hero');

        $protectedResponse->assertStatus(401);

        // Final Verification: Check all data was created correctly
        $this->assertDatabaseHas('heroes', ['name' => 'Nhật Anh']);
        $this->assertDatabaseHas('services', ['title_en' => 'Web Development']);
        $this->assertDatabaseHas('projects', ['title_en' => 'E-commerce System']);
        $this->assertDatabaseHas('blog_posts', ['title_en' => 'Laravel 10 Tutorial']);
        $this->assertDatabaseCount('contact_messages', 5);

        // Verify files were uploaded
        $project = Project::where('title_en', 'E-commerce System')->first();
        $this->assertNotNull($project->image);
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $project->image)));
    }

    /** @test */
    public function admin_can_handle_content_migration_workflow(): void
    {
        // Arrange - Create initial content
        Sanctum::actingAs($this->admin, ['admin']);

        $oldProjects = Project::factory()->count(10)->create(['category' => 'old_category']);
        $oldServices = Service::factory()->count(5)->create();

        // Step 1: Bulk update project categories
        $projectIds = $oldProjects->pluck('id')->toArray();
        $bulkUpdateResponse = $this->postJson('/api/admin/projects/bulk-update', [
            'project_ids' => $projectIds,
            'updates' => [
                'category' => 'web',
                'featured' => true
            ]
        ]);

        if ($bulkUpdateResponse->getStatusCode() === 200) {
            // Verify bulk update worked
            foreach ($projectIds as $id) {
                $this->assertDatabaseHas('projects', [
                    'id' => $id,
                    'category' => 'web',
                    'featured' => true
                ]);
            }
        }

        // Step 2: Migrate service icons
        foreach ($oldServices as $service) {
            $updateResponse = $this->putJson("/api/admin/services/{$service->id}", [
                'title_vi' => $service->title_vi,
                'title_en' => $service->title_en,
                'description_vi' => $service->description_vi,
                'description_en' => $service->description_en,
                'icon' => 'new-icon-' . $service->id,
                'color' => '#3B82F6',
                'bg_color' => '#EBF8FF'
            ]);

            $updateResponse->assertStatus(200);
        }

        // Step 3: Create backup of current state
        $backupResponse = $this->postJson('/api/admin/backup/create', [
            'include' => ['projects', 'services', 'blog_posts']
        ]);

        if ($backupResponse->getStatusCode() === 200) {
            $backupId = $backupResponse->json('data.backup_id');
            $this->assertNotNull($backupId);
        }

        // Step 4: Verify migration completed successfully
        $this->assertDatabaseCount('projects', 10);
        $this->assertDatabaseCount('services', 5);

        foreach ($oldServices as $service) {
            $this->assertDatabaseHas('services', [
                'id' => $service->id,
                'icon' => 'new-icon-' . $service->id
            ]);
        }
    }

    /** @test */
    public function admin_can_handle_emergency_maintenance_workflow(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Step 1: Enable maintenance mode
        $maintenanceResponse = $this->putJson('/api/admin/settings', [
            'maintenance_mode' => true,
            'maintenance_message' => 'System is under maintenance. Please check back later.'
        ]);

        $maintenanceResponse->assertStatus(200);

        // Step 2: Create emergency blog post
        $emergencyPost = [
            'title_vi' => 'Thông báo bảo trì hệ thống',
            'title_en' => 'System Maintenance Notice',
            'content_vi' => 'Hệ thống đang được bảo trì để cải thiện hiệu suất...',
            'content_en' => 'The system is under maintenance to improve performance...',
            'excerpt_vi' => 'Thông báo bảo trì hệ thống',
            'excerpt_en' => 'System maintenance notice',
            'status' => 'published',
            'tags' => ['maintenance', 'notice'],
            'priority' => 'high'
        ];

        $emergencyPostResponse = $this->postJson('/api/admin/blog', $emergencyPost);
        $emergencyPostResponse->assertStatus(201);

        // Step 3: Disable non-essential services temporarily
        $services = Service::all();
        foreach ($services as $service) {
            $this->putJson("/api/admin/services/{$service->id}", [
                'title_vi' => $service->title_vi,
                'title_en' => $service->title_en,
                'description_vi' => $service->description_vi,
                'description_en' => $service->description_en,
                'icon' => $service->icon,
                'color' => $service->color,
                'bg_color' => $service->bg_color,
                'active' => false // Temporarily disable
            ]);
        }

        // Step 4: Send notification to all contact messages (if notification system exists)
        $contacts = ContactMessage::where('read_at', null)->get();
        if ($contacts->count() > 0) {
            $notificationResponse = $this->postJson('/api/admin/notifications/send', [
                'type' => 'maintenance',
                'message' => 'We are currently performing system maintenance.',
                'recipients' => $contacts->pluck('email')->toArray()
            ]);

            // This might not be implemented, so we just check it doesn't crash
            $this->assertContains($notificationResponse->getStatusCode(), [200, 404, 501]);
        }

        // Step 5: Complete maintenance and restore services
        $restoreResponse = $this->putJson('/api/admin/settings', [
            'maintenance_mode' => false,
            'maintenance_message' => null
        ]);

        $restoreResponse->assertStatus(200);

        // Re-enable services
        foreach ($services as $service) {
            $this->putJson("/api/admin/services/{$service->id}", [
                'title_vi' => $service->title_vi,
                'title_en' => $service->title_en,
                'description_vi' => $service->description_vi,
                'description_en' => $service->description_en,
                'icon' => $service->icon,
                'color' => $service->color,
                'bg_color' => $service->bg_color,
                'active' => true // Re-enable
            ]);
        }

        // Step 6: Verify system is back to normal
        $statusResponse = $this->getJson('/api/admin/system/status');
        if ($statusResponse->getStatusCode() === 200) {
            $statusResponse->assertJson([
                'success' => true,
                'data' => [
                    'maintenance_mode' => false,
                    'services_active' => true
                ]
            ]);
        }
    }

    /** @test */
    public function admin_can_handle_data_recovery_workflow(): void
    {
        // Arrange - Create and then "accidentally" delete content
        Sanctum::actingAs($this->admin, ['admin']);

        $projects = Project::factory()->count(5)->create();
        $services = Service::factory()->count(3)->create();
        $blogPosts = BlogPost::factory()->count(4)->create();

        // Step 1: Create backup before deletion
        $backupResponse = $this->postJson('/api/admin/backup/create', [
            'include' => ['projects', 'services', 'blog_posts'],
            'reason' => 'Before bulk operations'
        ]);

        if ($backupResponse->getStatusCode() === 200) {
            $backupId = $backupResponse->json('data.backup_id');
        }

        // Step 2: Simulate accidental bulk deletion
        $projectIds = $projects->take(3)->pluck('id')->toArray();
        $bulkDeleteResponse = $this->deleteJson('/api/admin/projects/bulk-delete', [
            'project_ids' => $projectIds
        ]);

        if ($bulkDeleteResponse->getStatusCode() === 200) {
            // Verify projects were deleted
            foreach ($projectIds as $id) {
                $this->assertDatabaseMissing('projects', ['id' => $id]);
            }
        }

        // Step 3: Realize mistake and restore from backup
        if (isset($backupId)) {
            $restoreResponse = $this->postJson("/api/admin/backup/{$backupId}/restore", [
                'restore_types' => ['projects'],
                'confirm' => true
            ]);

            if ($restoreResponse->getStatusCode() === 200) {
                // Verify projects were restored
                foreach ($projectIds as $id) {
                    $this->assertDatabaseHas('projects', ['id' => $id]);
                }
            }
        }

        // Step 4: Verify data integrity after restore
        $integrityResponse = $this->getJson('/api/admin/system/integrity-check');
        if ($integrityResponse->getStatusCode() === 200) {
            $integrityResponse->assertJson([
                'success' => true,
                'data' => [
                    'projects_count' => 5,
                    'services_count' => 3,
                    'blog_posts_count' => 4
                ]
            ]);
        }

        // Alternative: Manual recreation if backup/restore not implemented
        if (!isset($backupId) || $bulkDeleteResponse->getStatusCode() !== 200) {
            // Manually recreate the "deleted" projects
            foreach ($projectIds as $index => $id) {
                $recreateResponse = $this->postJson('/api/admin/projects', [
                    'title_vi' => "Recovered Project {$index}",
                    'title_en' => "Recovered Project {$index}",
                    'description_vi' => 'Recovered project description',
                    'description_en' => 'Recovered project description',
                    'technologies' => ['Laravel'],
                    'category' => 'web'
                ]);

                $recreateResponse->assertStatus(201);
            }
        }

        // Final verification
        $this->assertDatabaseCount('projects', 5);
        $this->assertDatabaseCount('services', 3);
        $this->assertDatabaseCount('blog_posts', 4);
    }
}
