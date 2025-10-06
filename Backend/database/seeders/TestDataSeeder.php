<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds for testing purposes.
     * This seeder creates comprehensive test data for all models.
     */
    public function run(): void
    {
        // Only run in testing/local environments
        if (!app()->environment(['local', 'testing'])) {
            $this->command->info('TestDataSeeder skipped - not in testing environment');
            return;
        }

        $this->command->info('Creating comprehensive test data...');

        // Create multiple admin users with different states
        $this->createTestAdmins();

        // Create multiple hero variations for testing
        $this->createTestHeroContent();

        // Create multiple about variations
        $this->createTestAboutContent();

        // Create comprehensive service data
        $this->createTestServices();

        // Create diverse project portfolio
        $this->createTestProjects();

        // Create comprehensive blog content
        $this->createTestBlogPosts();

        // Create realistic contact scenarios
        $this->createTestContactData();

        // Create various system configurations
        $this->createTestSystemSettings();

        $this->command->info('Test data creation completed!');
    }

    /**
     * Create test admin users with various states.
     */
    private function createTestAdmins(): void
    {
        $this->command->info('Creating test admin users...');

        // Admin with recent activity
        \App\Models\Admin::factory()->withCredentials(
            'superadmin',
            'super@test.com',
            'password123'
        )->recentLogin()->create();

        // Admin who never logged in
        \App\Models\Admin::factory()->withCredentials(
            'newadmin',
            'new@test.com',
            'password123'
        )->neverLoggedIn()->create();

        // Random admins with various login patterns
        \App\Models\Admin::factory(3)->recentLogin()->create();
        \App\Models\Admin::factory(2)->neverLoggedIn()->create();
    }

    /**
     * Create test hero content variations.
     */
    private function createTestHeroContent(): void
    {
        $this->command->info('Creating test hero content...');

        // Create additional hero variations for A/B testing
        \App\Models\Hero::factory()->create([
            'greeting_vi' => 'Chào mừng đến với',
            'greeting_en' => 'Welcome to',
            'name' => 'Portfolio Hub',
            'title_vi' => 'Trung tâm phát triển phần mềm',
            'title_en' => 'Software Development Center',
        ]);
    }

    /**
     * Create test about content with different skill focuses.
     */
    private function createTestAboutContent(): void
    {
        $this->command->info('Creating test about content...');

        // Backend-focused developer profile
        \App\Models\About::factory()->backendFocused()->create();

        // Frontend-focused developer profile
        \App\Models\About::factory()->frontendFocused()->create();

        // Full-stack with custom skills
        \App\Models\About::factory()->withSkills([
            'PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React', 'Node.js',
            'Python', 'Django', 'Docker', 'AWS', 'MySQL', 'PostgreSQL'
        ])->create();
    }

    /**
     * Create comprehensive service test data.
     */
    private function createTestServices(): void
    {
        $this->command->info('Creating test services...');

        // Create services with specific orders for testing drag-drop functionality
        for ($i = 6; $i <= 10; $i++) {
            \App\Models\Service::factory()->withOrder($i)->create();
        }

        // Create services with duplicate orders to test conflict resolution
        \App\Models\Service::factory()->withOrder(1)->create();
        \App\Models\Service::factory()->withOrder(1)->create();
    }

    /**
     * Create diverse project portfolio for testing.
     */
    private function createTestProjects(): void
    {
        $this->command->info('Creating test projects...');

        // Create projects for each category
        $categories = ['web', 'mobile', 'desktop', 'api', 'ecommerce', 'cms'];

        foreach ($categories as $category) {
            // Featured projects in each category
            \App\Models\Project::factory(2)->featured()->withCategory($category)->create();

            // Regular projects in each category
            \App\Models\Project::factory(3)->notFeatured()->withCategory($category)->create();
        }

        // Create projects with specific orders for testing
        \App\Models\Project::factory()->featured()->withOrder(1)->create();
        \App\Models\Project::factory()->featured()->withOrder(2)->create();
        \App\Models\Project::factory()->featured()->withOrder(3)->create();

        // Create projects without links for testing
        \App\Models\Project::factory(3)->create(['link' => null]);
    }

    /**
     * Create comprehensive blog content for testing.
     */
    private function createTestBlogPosts(): void
    {
        $this->command->info('Creating test blog posts...');

        // Create posts with specific tag combinations
        $tagCombinations = [
            ['Laravel', 'PHP', 'Backend'],
            ['Vue.js', 'JavaScript', 'Frontend'],
            ['React', 'TypeScript', 'Frontend'],
            ['Node.js', 'API', 'Backend'],
            ['Docker', 'DevOps', 'Deployment'],
            ['AWS', 'Cloud', 'Infrastructure'],
        ];

        foreach ($tagCombinations as $tags) {
            \App\Models\BlogPost::factory()->published()->withTags($tags)->create();
            \App\Models\BlogPost::factory()->draft()->withTags($tags)->create();
        }

        // Create posts with different publication dates
        \App\Models\BlogPost::factory(3)->recentPublished()->create();
        \App\Models\BlogPost::factory(2)->scheduled()->create();

        // Create Laravel-specific tutorials
        \App\Models\BlogPost::factory(5)->laravelTutorial()->published()->create();
    }

    /**
     * Create realistic contact test data.
     */
    private function createTestContactData(): void
    {
        $this->command->info('Creating test contact data...');

        // Create contact messages from different scenarios
        \App\Models\ContactMessage::factory(5)->businessInquiry()->unread()->create();
        \App\Models\ContactMessage::factory(3)->urgent()->unread()->create();
        \App\Models\ContactMessage::factory(10)->businessInquiry()->read()->create();

        // Create messages from specific domains
        $domains = ['gmail.com', 'yahoo.com', 'company.com', 'startup.io', 'enterprise.org'];
        foreach ($domains as $domain) {
            \App\Models\ContactMessage::factory(2)->fromDomain($domain)->create();
        }

        // Create contact info variations
        \App\Models\ContactInfo::factory()->minimalSocial()->create();
        \App\Models\ContactInfo::factory()->allSocialPlatforms()->create();
    }

    /**
     * Create various system settings for testing.
     */
    private function createTestSystemSettings(): void
    {
        $this->command->info('Creating test system settings...');

        // Create settings of different types
        \App\Models\SystemSettings::factory(3)->stringType()->create();
        \App\Models\SystemSettings::factory(2)->booleanType()->create();
        \App\Models\SystemSettings::factory(2)->integerType()->create();
        \App\Models\SystemSettings::factory(2)->jsonType()->create();

        // Create theme variations
        \App\Models\SystemSettings::factory()->themeSettings()->create([
            'key' => 'dark_theme_colors',
            'description' => 'Dark theme color palette'
        ]);

        // Create site configuration variations
        \App\Models\SystemSettings::factory(3)->siteConfig()->create();
    }
}
