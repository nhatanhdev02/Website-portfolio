<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create hero content - use specific content for production
        \App\Models\Hero::factory()->nhatAnhDev()->create();

        // Create about content - use specific content for production
        \App\Models\About::factory()->nhatAnhDev()->create();

        // Create core services with proper ordering
        \App\Models\Service::factory()->webDevelopment()->withOrder(1)->create();
        \App\Models\Service::factory()->mobileDevelopment()->withOrder(2)->create();
        \App\Models\Service::factory()->apiDevelopment()->withOrder(3)->create();

        // Create additional services for variety
        \App\Models\Service::factory()->withOrder(4)->create([
            'title_vi' => 'Tư vấn Công nghệ',
            'title_en' => 'Technology Consulting',
            'description_vi' => 'Tư vấn giải pháp công nghệ phù hợp cho doanh nghiệp',
            'description_en' => 'Provide technology solutions consulting for businesses',
            'icon' => 'consulting-icon',
            'color' => '#9B59B6',
            'bg_color' => '#F4E5F7',
        ]);

        \App\Models\Service::factory()->withOrder(5)->create([
            'title_vi' => 'Bảo trì & Hỗ trợ',
            'title_en' => 'Maintenance & Support',
            'description_vi' => 'Dịch vụ bảo trì và hỗ trợ kỹ thuật 24/7',
            'description_en' => '24/7 maintenance and technical support services',
            'icon' => 'support-icon',
            'color' => '#E67E22',
            'bg_color' => '#FDF2E9',
        ]);

        // Create additional random services for testing
        if (app()->environment(['local', 'testing'])) {
            \App\Models\Service::factory(3)->create();
        }

        // Create featured projects with specific categories
        \App\Models\Project::factory(3)->featured()->webProject()->create();
        \App\Models\Project::factory(2)->featured()->mobileProject()->create();
        \App\Models\Project::factory(2)->featured()->apiProject()->create();

        // Create regular projects across different categories
        \App\Models\Project::factory(5)->notFeatured()->webProject()->create();
        \App\Models\Project::factory(4)->notFeatured()->mobileProject()->create();
        \App\Models\Project::factory(3)->notFeatured()->apiProject()->create();
        \App\Models\Project::factory(3)->notFeatured()->withCategory('ecommerce')->create();

        // Create blog posts with realistic content
        \App\Models\BlogPost::factory(5)->published()->laravelTutorial()->create();
        \App\Models\BlogPost::factory(3)->published()->withTags(['Vue.js', 'JavaScript', 'Frontend'])->create();
        \App\Models\BlogPost::factory(2)->recentPublished()->withTags(['API', 'Backend', 'Best Practices'])->create();
        \App\Models\BlogPost::factory(4)->draft()->create();

        // Create some scheduled posts for testing
        if (app()->environment(['local', 'testing'])) {
            \App\Models\BlogPost::factory(2)->scheduled()->create();
        }

        // Create contact messages with realistic scenarios
        \App\Models\ContactMessage::factory(10)->read()->businessInquiry()->create();
        \App\Models\ContactMessage::factory(5)->unread()->businessInquiry()->create();
        \App\Models\ContactMessage::factory(3)->recentUnread()->create();
        \App\Models\ContactMessage::factory(2)->urgent()->create();

        // Create messages from different domains for testing
        if (app()->environment(['local', 'testing'])) {
            \App\Models\ContactMessage::factory(3)->fromDomain('gmail.com')->create();
            \App\Models\ContactMessage::factory(2)->fromDomain('company.com')->create();
        }

        // Create contact info - use specific info for production
        \App\Models\ContactInfo::factory()->nhatAnhDev()->create();

        // Create comprehensive system settings
        $this->createSystemSettings();
    }

    /**
     * Create system settings with proper configuration.
     */
    private function createSystemSettings(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Nhật Anh Dev - Freelance Fullstack',
                'type' => 'string',
                'description' => 'Website name displayed in header and title'
            ],
            [
                'key' => 'site_description',
                'value' => 'Professional freelance full-stack developer specializing in modern web applications',
                'type' => 'string',
                'description' => 'Website meta description for SEO'
            ],
            [
                'key' => 'default_language',
                'value' => 'vi',
                'type' => 'string',
                'description' => 'Default language for the website (vi/en)'
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Enable/disable maintenance mode'
            ],
            [
                'key' => 'theme_colors',
                'value' => json_encode([
                    'primary' => '#FF6B6B',
                    'secondary' => '#4ECDC4',
                    'accent' => '#45B7D1',
                    'background' => '#FFFFFF',
                    'text' => '#333333'
                ]),
                'type' => 'json',
                'description' => 'Complete theme color palette'
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@nhatanhdev.com',
                'type' => 'string',
                'description' => 'Primary contact email address'
            ],
            [
                'key' => 'max_upload_size',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum file upload size in MB'
            ],
            [
                'key' => 'cache_duration',
                'value' => '3600',
                'type' => 'integer',
                'description' => 'Cache duration in seconds'
            ],
            [
                'key' => 'posts_per_page',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Number of blog posts per page'
            ],
            [
                'key' => 'projects_per_page',
                'value' => '12',
                'type' => 'integer',
                'description' => 'Number of projects per page'
            ]
        ];

        foreach ($settings as $setting) {
            \App\Models\SystemSettings::create($setting);
        }

        // Create additional test settings for development
        if (app()->environment(['local', 'testing'])) {
            \App\Models\SystemSettings::factory(5)->create();
        }
    }
}
