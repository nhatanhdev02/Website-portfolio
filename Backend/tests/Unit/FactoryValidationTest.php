<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Hero;
use App\Models\About;
use App\Models\Service;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Models\SystemSettings;

class FactoryValidationTest extends TestCase
{
    public function test_admin_factory_generates_valid_attributes(): void
    {
        $attributes = Admin::factory()->definition();

        $this->assertArrayHasKey('username', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('password', $attributes);
        $this->assertNotNull($attributes['username']);
        $this->assertNotNull($attributes['email']);
        $this->assertTrue(filter_var($attributes['email'], FILTER_VALIDATE_EMAIL) !== false);
    }

    public function test_hero_factory_generates_valid_attributes(): void
    {
        $attributes = Hero::factory()->definition();

        $this->assertArrayHasKey('greeting_vi', $attributes);
        $this->assertArrayHasKey('greeting_en', $attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('title_vi', $attributes);
        $this->assertArrayHasKey('title_en', $attributes);
        $this->assertNotNull($attributes['greeting_vi']);
        $this->assertNotNull($attributes['greeting_en']);
        $this->assertNotNull($attributes['name']);
    }

    public function test_service_factory_generates_valid_attributes(): void
    {
        $attributes = Service::factory()->definition();

        $this->assertArrayHasKey('title_vi', $attributes);
        $this->assertArrayHasKey('title_en', $attributes);
        $this->assertArrayHasKey('description_vi', $attributes);
        $this->assertArrayHasKey('description_en', $attributes);
        $this->assertArrayHasKey('icon', $attributes);
        $this->assertArrayHasKey('color', $attributes);
        $this->assertArrayHasKey('bg_color', $attributes);
        $this->assertArrayHasKey('order', $attributes);
        $this->assertIsInt($attributes['order']);
    }

    public function test_project_factory_generates_valid_attributes(): void
    {
        $attributes = Project::factory()->definition();

        $this->assertArrayHasKey('title_vi', $attributes);
        $this->assertArrayHasKey('title_en', $attributes);
        $this->assertArrayHasKey('description_vi', $attributes);
        $this->assertArrayHasKey('description_en', $attributes);
        $this->assertArrayHasKey('technologies', $attributes);
        $this->assertArrayHasKey('category', $attributes);
        $this->assertArrayHasKey('featured', $attributes);
        $this->assertArrayHasKey('order', $attributes);
        $this->assertIsArray($attributes['technologies']);
        $this->assertIsBool($attributes['featured']);
        $this->assertIsInt($attributes['order']);
    }

    public function test_blog_post_factory_generates_valid_attributes(): void
    {
        $attributes = BlogPost::factory()->definition();

        $this->assertArrayHasKey('title_vi', $attributes);
        $this->assertArrayHasKey('title_en', $attributes);
        $this->assertArrayHasKey('content_vi', $attributes);
        $this->assertArrayHasKey('content_en', $attributes);
        $this->assertArrayHasKey('tags', $attributes);
        $this->assertArrayHasKey('status', $attributes);
        $this->assertIsArray($attributes['tags']);
        $this->assertContains($attributes['status'], ['draft', 'published']);
    }

    public function test_contact_message_factory_generates_valid_attributes(): void
    {
        $attributes = ContactMessage::factory()->definition();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('subject', $attributes);
        $this->assertArrayHasKey('message', $attributes);
        $this->assertNotNull($attributes['name']);
        $this->assertNotNull($attributes['email']);
        $this->assertTrue(filter_var($attributes['email'], FILTER_VALIDATE_EMAIL) !== false);
    }

    public function test_about_factory_generates_valid_attributes(): void
    {
        $attributes = About::factory()->definition();

        $this->assertArrayHasKey('content_vi', $attributes);
        $this->assertArrayHasKey('content_en', $attributes);
        $this->assertArrayHasKey('skills', $attributes);
        $this->assertArrayHasKey('experience', $attributes);
        $this->assertIsArray($attributes['skills']);
        $this->assertIsArray($attributes['experience']);
        $this->assertNotEmpty($attributes['skills']);
        $this->assertNotEmpty($attributes['experience']);
    }

    public function test_contact_info_factory_generates_valid_attributes(): void
    {
        $attributes = ContactInfo::factory()->definition();

        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('phone', $attributes);
        $this->assertArrayHasKey('social_links', $attributes);
        $this->assertArrayHasKey('business_hours', $attributes);
        $this->assertIsArray($attributes['social_links']);
        $this->assertTrue(filter_var($attributes['email'], FILTER_VALIDATE_EMAIL) !== false);
    }

    public function test_system_settings_factory_generates_valid_attributes(): void
    {
        $attributes = SystemSettings::factory()->definition();

        $this->assertArrayHasKey('key', $attributes);
        $this->assertArrayHasKey('value', $attributes);
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertContains($attributes['type'], ['string', 'boolean', 'integer', 'json']);
    }

    public function test_factory_states_return_correct_attributes(): void
    {
        // Test Admin factory states
        $adminNeverLoggedIn = Admin::factory()->neverLoggedIn()->make();
        $this->assertNull($adminNeverLoggedIn->last_login_at);

        // Test Hero factory states
        $heroNhatAnh = Hero::factory()->nhatAnhDev()->make();
        $this->assertEquals('Nhật Anh', $heroNhatAnh->name);

        // Test Service factory states
        $webDevService = Service::factory()->webDevelopment()->make();
        $this->assertEquals('Phát triển Web', $webDevService->title_vi);

        // Test Project factory states
        $featuredProject = Project::factory()->featured()->make();
        $this->assertTrue($featuredProject->featured);

        $webProject = Project::factory()->webProject()->make();
        $this->assertEquals('web', $webProject->category);

        // Test BlogPost factory states
        $publishedPost = BlogPost::factory()->published()->make();
        $this->assertEquals('published', $publishedPost->status);

        $draftPost = BlogPost::factory()->draft()->make();
        $this->assertEquals('draft', $draftPost->status);

        // Test ContactMessage factory states
        $unreadMessage = ContactMessage::factory()->unread()->make();
        $this->assertNull($unreadMessage->read_at);

        // Test SystemSettings factory states
        $stringType = SystemSettings::factory()->stringType()->make();
        $this->assertEquals('string', $stringType->type);

        $booleanType = SystemSettings::factory()->booleanType()->make();
        $this->assertEquals('boolean', $booleanType->type);
    }

    public function test_factories_generate_realistic_data(): void
    {
        // Test that factories generate realistic, varied data
        $admin1 = Admin::factory()->definition();
        $admin2 = Admin::factory()->definition();

        // Should generate different usernames and emails
        $this->assertNotEquals($admin1['username'], $admin2['username']);
        $this->assertNotEquals($admin1['email'], $admin2['email']);

        // Test project technologies are arrays with valid content
        $project = Project::factory()->definition();
        $this->assertIsArray($project['technologies']);
        $this->assertNotEmpty($project['technologies']);

        // Test blog post tags are arrays
        $blogPost = BlogPost::factory()->definition();
        $this->assertIsArray($blogPost['tags']);
        $this->assertNotEmpty($blogPost['tags']);

        // Test about skills and experience are properly structured
        $about = About::factory()->definition();
        $this->assertIsArray($about['skills']);
        $this->assertIsArray($about['experience']);

        // Experience should have proper structure
        foreach ($about['experience'] as $exp) {
            $this->assertIsArray($exp);
            $this->assertArrayHasKey('company', $exp);
            $this->assertArrayHasKey('position', $exp);
            $this->assertArrayHasKey('duration', $exp);
        }
    }
}
