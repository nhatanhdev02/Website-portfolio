<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;
use App\Models\Hero;
use App\Models\About;
use App\Models\Service;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Models\SystemSettings;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_factory_creates_valid_data(): void
    {
        $admin = Admin::factory()->make();

        $this->assertNotNull($admin->username);
        $this->assertNotNull($admin->email);
        $this->assertNotNull($admin->password);
        $this->assertTrue(filter_var($admin->email, FILTER_VALIDATE_EMAIL) !== false);
    }

    /** @test */
    public function admin_factory_states_work(): void
    {
        $neverLoggedIn = Admin::factory()->neverLoggedIn()->make();
        $recentLogin = Admin::factory()->recentLogin()->make();
        $withCredentials = Admin::factory()->withCredentials('testuser', 'test@example.com')->make();

        $this->assertNull($neverLoggedIn->last_login_at);
        $this->assertNotNull($recentLogin->last_login_at);
        $this->assertEquals('testuser', $withCredentials->username);
        $this->assertEquals('test@example.com', $withCredentials->email);
    }

    /** @test */
    public function hero_factory_creates_valid_data(): void
    {
        $hero = Hero::factory()->make();

        $this->assertNotNull($hero->greeting_vi);
        $this->assertNotNull($hero->greeting_en);
        $this->assertNotNull($hero->name);
        $this->assertNotNull($hero->title_vi);
        $this->assertNotNull($hero->title_en);
    }

    /** @test */
    public function hero_factory_nhat_anh_dev_state_works(): void
    {
        $hero = Hero::factory()->nhatAnhDev()->make();

        $this->assertEquals('Nhật Anh', $hero->name);
        $this->assertEquals('Xin chào, tôi là', $hero->greeting_vi);
        $this->assertEquals('Hello, I am', $hero->greeting_en);
    }

    /** @test */
    public function service_factory_creates_valid_data(): void
    {
        $service = Service::factory()->make();

        $this->assertNotNull($service->title_vi);
        $this->assertNotNull($service->title_en);
        $this->assertNotNull($service->description_vi);
        $this->assertNotNull($service->description_en);
        $this->assertNotNull($service->icon);
        $this->assertNotNull($service->color);
        $this->assertNotNull($service->bg_color);
        $this->assertIsInt($service->order);
    }

    /** @test */
    public function service_factory_states_work(): void
    {
        $webDev = Service::factory()->webDevelopment()->make();
        $mobileDev = Service::factory()->mobileDevelopment()->make();
        $apiDev = Service::factory()->apiDevelopment()->make();
        $withOrder = Service::factory()->withOrder(5)->make();

        $this->assertEquals('Phát triển Web', $webDev->title_vi);
        $this->assertEquals('Web Development', $webDev->title_en);
        $this->assertEquals('Phát triển Mobile', $mobileDev->title_vi);
        $this->assertEquals('Mobile Development', $mobileDev->title_en);
        $this->assertEquals('API Development', $apiDev->title_vi);
        $this->assertEquals('API Development', $apiDev->title_en);
        $this->assertEquals(5, $withOrder->order);
    }

    /** @test */
    public function project_factory_creates_valid_data(): void
    {
        $project = Project::factory()->make();

        $this->assertNotNull($project->title_vi);
        $this->assertNotNull($project->title_en);
        $this->assertNotNull($project->description_vi);
        $this->assertNotNull($project->description_en);
        $this->assertNotNull($project->image);
        $this->assertIsArray($project->technologies);
        $this->assertNotNull($project->category);
        $this->assertIsBool($project->featured);
        $this->assertIsInt($project->order);
    }

    /** @test */
    public function project_factory_states_work(): void
    {
        $featured = Project::factory()->featured()->make();
        $notFeatured = Project::factory()->notFeatured()->make();
        $webProject = Project::factory()->webProject()->make();
        $mobileProject = Project::factory()->mobileProject()->make();
        $apiProject = Project::factory()->apiProject()->make();

        $this->assertTrue($featured->featured);
        $this->assertFalse($notFeatured->featured);
        $this->assertEquals('web', $webProject->category);
        $this->assertEquals('mobile', $mobileProject->category);
        $this->assertEquals('api', $apiProject->category);
    }

    /** @test */
    public function blog_post_factory_creates_valid_data(): void
    {
        $blogPost = BlogPost::factory()->make();

        $this->assertNotNull($blogPost->title_vi);
        $this->assertNotNull($blogPost->title_en);
        $this->assertNotNull($blogPost->content_vi);
        $this->assertNotNull($blogPost->content_en);
        $this->assertNotNull($blogPost->excerpt_vi);
        $this->assertNotNull($blogPost->excerpt_en);
        $this->assertIsArray($blogPost->tags);
        $this->assertContains($blogPost->status, ['draft', 'published']);
    }

    /** @test */
    public function blog_post_factory_states_work(): void
    {
        $published = BlogPost::factory()->published()->make();
        $draft = BlogPost::factory()->draft()->make();
        $laravelTutorial = BlogPost::factory()->laravelTutorial()->make();
        $withTags = BlogPost::factory()->withTags(['PHP', 'Laravel'])->make();

        $this->assertEquals('published', $published->status);
        $this->assertNotNull($published->published_at);
        $this->assertEquals('draft', $draft->status);
        $this->assertNull($draft->published_at);
        $this->assertStringContainsString('Laravel', $laravelTutorial->title_vi);
        $this->assertEquals(['PHP', 'Laravel'], $withTags->tags);
    }

    /** @test */
    public function contact_message_factory_creates_valid_data(): void
    {
        $message = ContactMessage::factory()->make();

        $this->assertNotNull($message->name);
        $this->assertNotNull($message->email);
        $this->assertNotNull($message->subject);
        $this->assertNotNull($message->message);
        $this->assertTrue(filter_var($message->email, FILTER_VALIDATE_EMAIL) !== false);
    }

    /** @test */
    public function contact_message_factory_states_work(): void
    {
        $unread = ContactMessage::factory()->unread()->make();
        $read = ContactMessage::factory()->read()->make();
        $urgent = ContactMessage::factory()->urgent()->make();
        $businessInquiry = ContactMessage::factory()->businessInquiry()->make();

        $this->assertNull($unread->read_at);
        $this->assertNotNull($read->read_at);
        $this->assertStringContainsString('URGENT:', $urgent->subject);
        $this->assertNotNull($businessInquiry->subject);
    }

    /** @test */
    public function about_factory_creates_valid_data(): void
    {
        $about = About::factory()->make();

        $this->assertNotNull($about->content_vi);
        $this->assertNotNull($about->content_en);
        $this->assertNotNull($about->profile_image);
        $this->assertIsArray($about->skills);
        $this->assertIsArray($about->experience);
        $this->assertNotEmpty($about->skills);
        $this->assertNotEmpty($about->experience);
    }

    /** @test */
    public function about_factory_states_work(): void
    {
        $nhatAnhDev = About::factory()->nhatAnhDev()->make();
        $backendFocused = About::factory()->backendFocused()->make();
        $frontendFocused = About::factory()->frontendFocused()->make();
        $withSkills = About::factory()->withSkills(['PHP', 'JavaScript'])->make();

        $this->assertStringContainsString('Nhật Anh', $nhatAnhDev->content_vi);
        $this->assertContains('PHP', $backendFocused->skills);
        $this->assertContains('JavaScript', $frontendFocused->skills);
        $this->assertEquals(['PHP', 'JavaScript'], $withSkills->skills);
    }

    /** @test */
    public function contact_info_factory_creates_valid_data(): void
    {
        $contactInfo = ContactInfo::factory()->make();

        $this->assertNotNull($contactInfo->email);
        $this->assertNotNull($contactInfo->phone);
        $this->assertNotNull($contactInfo->address);
        $this->assertIsArray($contactInfo->social_links);
        $this->assertNotNull($contactInfo->business_hours);
        $this->assertTrue(filter_var($contactInfo->email, FILTER_VALIDATE_EMAIL) !== false);
    }

    /** @test */
    public function system_settings_factory_creates_valid_data(): void
    {
        $setting = SystemSettings::factory()->make();

        $this->assertNotNull($setting->key);
        $this->assertNotNull($setting->value);
        $this->assertNotNull($setting->type);
        $this->assertNotNull($setting->description);
        $this->assertContains($setting->type, ['string', 'boolean', 'integer', 'json']);
    }

    /** @test */
    public function system_settings_factory_states_work(): void
    {
        $stringType = SystemSettings::factory()->stringType()->make();
        $booleanType = SystemSettings::factory()->booleanType()->make();
        $integerType = SystemSettings::factory()->integerType()->make();
        $jsonType = SystemSettings::factory()->jsonType()->make();

        $this->assertEquals('string', $stringType->type);
        $this->assertEquals('boolean', $booleanType->type);
        $this->assertContains($booleanType->value, ['0', '1']);
        $this->assertEquals('integer', $integerType->type);
        $this->assertEquals('json', $jsonType->type);
        $this->assertJson($jsonType->value);
    }
}
