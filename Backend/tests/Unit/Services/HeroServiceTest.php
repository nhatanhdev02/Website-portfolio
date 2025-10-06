<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\HeroService;
use App\Repositories\Contracts\HeroRepositoryInterface;
use App\Models\Hero;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;

class HeroServiceTest extends TestCase
{
    private HeroService $heroService;
    private HeroRepositoryInterface $heroRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->heroRepository = Mockery::mock(HeroRepositoryInterface::class);
        $this->heroService = new HeroService($this->heroRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_hero_content(): void
    {
        // Arrange
        $hero = new Hero([
            'id' => 1,
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh',
            'title_vi' => 'Lập trình viên',
            'title_en' => 'Developer',
            'subtitle_vi' => 'Phụ đề',
            'subtitle_en' => 'Subtitle',
            'cta_text_vi' => 'Liên hệ',
            'cta_text_en' => 'Contact',
            'cta_link' => 'https://example.com'
        ]);
        $hero->updated_at = now();

        $this->heroRepository
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($hero);

        // Act
        $result = $this->heroService->getHeroContent();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Xin chào', $result['greeting_vi']);
        $this->assertEquals('Hello', $result['greeting_en']);
        $this->assertEquals('Nhật Anh', $result['name']);
    }

    /** @test */
    public function it_returns_default_structure_when_no_hero_content_exists(): void
    {
        // Arrange
        $this->heroRepository
            ->shouldReceive('getContent')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->heroService->getHeroContent();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('', $result['greeting_vi']);
        $this->assertEquals('', $result['greeting_en']);
        $this->assertEquals('', $result['name']);
        $this->assertArrayHasKey('title_vi', $result);
        $this->assertArrayHasKey('cta_link', $result);
    }

    /** @test */
    public function it_can_update_hero_content(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào mới',
            'greeting_en' => 'New Hello',
            'name' => 'Nhật Anh',
            'title_vi' => 'Lập trình viên',
            'title_en' => 'Developer',
            'subtitle_vi' => 'Phụ đề mới',
            'subtitle_en' => 'New Subtitle',
            'cta_text_vi' => 'Liên hệ',
            'cta_text_en' => 'Contact',
            'cta_link' => 'https://example.com'
        ];

        $hero = new Hero($data);

        $this->heroRepository
            ->shouldReceive('updateContent')
            ->once()
            ->with($data)
            ->andReturn($hero);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->heroService->updateHeroContent($data, 1);

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
        $this->assertEquals('Xin chào mới', $result->greeting_vi);
        $this->assertEquals('New Hello', $result->greeting_en);
    }

    /** @test */
    public function it_validates_hero_data_before_update(): void
    {
        // Arrange
        $invalidData = [
            'greeting_vi' => '', // Required field missing
            'greeting_en' => 'Hello',
            'name' => 'Test'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->heroService->updateHeroContent($invalidData);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test Name',
            'title_vi' => 'Title VI',
            'title_en' => 'Title EN',
            'subtitle_vi' => 'Subtitle VI',
            'subtitle_en' => 'Subtitle EN',
            'cta_text_vi' => 'CTA VI',
            'cta_text_en' => 'CTA EN',
            'cta_link' => 'https://example.com'
        ];

        // Act & Assert - Should not throw exception
        $this->heroService->validateHeroData($data);
        $this->assertTrue(true); // If we reach here, validation passed
    }

    /** @test */
    public function it_validates_cta_link_format(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test Name',
            'title_vi' => 'Title VI',
            'title_en' => 'Title EN',
            'subtitle_vi' => 'Subtitle VI',
            'subtitle_en' => 'Subtitle EN',
            'cta_text_vi' => 'CTA VI',
            'cta_text_en' => 'CTA EN',
            'cta_link' => 'invalid-url' // Invalid URL
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->heroService->validateHeroData($data);
    }

    /** @test */
    public function it_validates_field_length_limits(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => str_repeat('a', 501), // Exceeds 500 char limit
            'greeting_en' => 'Hello',
            'name' => 'Test Name',
            'title_vi' => 'Title VI',
            'title_en' => 'Title EN',
            'subtitle_vi' => 'Subtitle VI',
            'subtitle_en' => 'Subtitle EN',
            'cta_text_vi' => 'CTA VI',
            'cta_text_en' => 'CTA EN',
            'cta_link' => 'https://example.com'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->heroService->validateHeroData($data);
    }

    /** @test */
    public function it_logs_update_actions(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Test Name',
            'title_vi' => 'Title VI',
            'title_en' => 'Title EN',
            'subtitle_vi' => 'Subtitle VI',
            'subtitle_en' => 'Subtitle EN',
            'cta_text_vi' => 'CTA VI',
            'cta_text_en' => 'CTA EN',
            'cta_link' => 'https://example.com'
        ];

        $hero = new Hero($data);

        $this->heroRepository
            ->shouldReceive('updateContent')
            ->once()
            ->andReturn($hero);

        Log::shouldReceive('info')
            ->once()
            ->with('Hero service action', Mockery::type('array'));

        // Act
        $this->heroService->updateHeroContent($data, 1);

        // Assert - Log expectation is verified by Mockery
        $this->assertTrue(true);
    }
}
