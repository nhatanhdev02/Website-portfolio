<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for production environment.
     * This seeder creates minimal, production-ready data.
     */
    public function run(): void
    {
        $this->command->info('Creating production data...');

        // Create only essential admin user
        $this->createProductionAdmin();

        // Create production-ready content
        $this->createProductionContent();

        $this->command->info('Production data creation completed!');
    }

    /**
     * Create production admin user.
     */
    private function createProductionAdmin(): void
    {
        $this->command->info('Creating production admin user...');

        // Create main admin user
        \App\Models\Admin::factory()->withCredentials(
            'admin',
            'admin@nhatanhdev.com',
            'admin123' // Should be changed in production
        )->create();
    }

    /**
     * Create production-ready content.
     */
    private function createProductionContent(): void
    {
        $this->command->info('Creating production content...');

        // Create hero content
        \App\Models\Hero::factory()->nhatAnhDev()->create();

        // Create about content
        \App\Models\About::factory()->nhatAnhDev()->create();

        // Create core services
        \App\Models\Service::factory()->webDevelopment()->withOrder(1)->create();
        \App\Models\Service::factory()->mobileDevelopment()->withOrder(2)->create();
        \App\Models\Service::factory()->apiDevelopment()->withOrder(3)->create();

        // Create sample featured projects
        \App\Models\Project::factory(3)->featured()->webProject()->create();
        \App\Models\Project::factory(2)->featured()->mobileProject()->create();

        // Create sample regular projects
        \App\Models\Project::factory(5)->notFeatured()->create();

        // Create sample blog posts
        \App\Models\BlogPost::factory(3)->published()->laravelTutorial()->create();
        \App\Models\BlogPost::factory(2)->published()->create();

        // Create contact info
        \App\Models\ContactInfo::factory()->nhatAnhDev()->create();

        // Create essential system settings
        $this->createProductionSettings();
    }

    /**
     * Create production system settings.
     */
    private function createProductionSettings(): void
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
            ]
        ];

        foreach ($settings as $setting) {
            \App\Models\SystemSettings::create($setting);
        }
    }
}
