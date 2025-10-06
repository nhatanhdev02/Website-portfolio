<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\AboutService;
use App\Services\Admin\FileUploadService;
use App\Repositories\Contracts\AboutRepositoryInterface;
use App\Models\About;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;

class AboutServiceTest extends TestCase
{
    private AboutService $aboutService;
    private AboutRepositoryInterface $aboutRepository;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aboutRepository = Mockery::mock(AboutRepositoryInterface::class);
        $this->fileUploadService = Mockery::mock(FileUploadService::class);
        $this->aboutService = new AboutService($this->aboutRepository, $this->fileUploadService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_about_content(): void
    {
        // Arrange
        $about = new About([
            'id' => 1,
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Mô tả',
            'description_en' => 'Description',
            'profile_image' => '/storage/about/image.jpg',
            'skills' => ['PHP', 'Laravel', 'Vue.js'],
            'experience_years' => 5,
            'projects_completed' => 50,
            'cv_link' => 'https://example.com/cv.pdf'
        ]);
        $about->updated_at = now();

        $this->aboutRepository
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($about);

        // Act
        $result = $this->aboutService->getAboutContent();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Về tôi', $result['title_vi']);
        $this->assertEquals('About Me', $result['title_en']);
        $this->assertEquals(['PHP', 'Laravel', 'Vue.js'], $result['skills']);
        $this->assertEquals(5, $result['experience_years']);
    }

    /** @test */
    public function it_returns_default_structure_when_no_about_content_exists(): void
    {
        // Arrange
        $this->aboutRepository
            ->shouldReceive('getContent')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->aboutService->getAboutContent();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('', $result['title_vi']);
        $this->assertEquals('', $result['title_en']);
        $this->assertEquals([], $result['skills']);
        $this->assertEquals(0, $result['experience_years']);
        $this->assertEquals(0, $result['projects_completed']);
    }

    /** @test */
    public function it_can_update_about_content(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Về tôi mới',
            'title_en' => 'New About Me',
            'description_vi' => 'Mô tả mới',
            'description_en' => 'New Description',
            'skills' => ['PHP', 'Laravel'],
            'experience_years' => 6,
            'projects_completed' => 60,
            'cv_link' => 'https://example.com/new-cv.pdf'
        ];

        $about = new About($data);

        $this->aboutRepository
            ->shouldReceive('updateContent')
            ->once()
            ->with($data)
            ->andReturn($about);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->aboutService->updateAboutContent($data, 1);

        // Assert
        $this->assertInstanceOf(About::class, $result);
        $this->assertEquals('Về tôi mới', $result->title_vi);
        $this->assertEquals('New About Me', $result->title_en);
    }

    /** @test */
    public function it_can_update_profile_image(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getClientOriginalName')->andReturn('profile.jpg');
        $file->shouldReceive('getSize')->andReturn(1024);

        $imagePath = '/storage/about/new-image.jpg';
        $about = new About(['profile_image' => $imagePath]);

        $this->fileUploadService
            ->shouldReceive('uploadImage')
            ->once()
            ->with($file, 'about')
            ->andReturn($imagePath);

        $this->aboutRepository
            ->shouldReceive('updateImage')
            ->once()
            ->with($imagePath)
            ->andReturn($about);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->aboutService->updateProfileImage($file, 1);

        // Assert
        $this->assertInstanceOf(About::class, $result);
        $this->assertEquals($imagePath, $result->profile_image);
    }

    /** @test */
    public function it_validates_about_data(): void
    {
        // Arrange
        $validData = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Mô tả',
            'description_en' => 'Description',
            'skills' => ['PHP', 'Laravel'],
            'experience_years' => 5,
            'projects_completed' => 50,
            'cv_link' => 'https://example.com/cv.pdf'
        ];

        // Act & Assert - Should not throw exception
        $this->aboutService->validateAboutData($validData);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => '', // Required field missing
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => ['PHP'],
            'experience_years' => 5,
            'projects_completed' => 50
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->aboutService->validateAboutData($invalidData);
    }

    /** @test */
    public function it_validates_skills_array(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => [], // Empty skills array
            'experience_years' => 5,
            'projects_completed' => 50
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->aboutService->validateAboutData($invalidData);
    }

    /** @test */
    public function it_validates_experience_years_range(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => ['PHP'],
            'experience_years' => -1, // Invalid negative value
            'projects_completed' => 50
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->aboutService->validateAboutData($invalidData);
    }

    /** @test */
    public function it_validates_cv_link_format(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => ['PHP'],
            'experience_years' => 5,
            'projects_completed' => 50,
            'cv_link' => 'invalid-url' // Invalid URL format
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->aboutService->validateAboutData($invalidData);
    }

    /** @test */
    public function it_validates_profile_image(): void
    {
        // Arrange
        $file = Mockery::mock(UploadedFile::class);

        // Act & Assert - Should not throw exception for valid image
        $this->aboutService->validateProfileImage($file);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_field_length_limits(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => str_repeat('a', 256), // Exceeds 255 char limit
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => ['PHP'],
            'experience_years' => 5,
            'projects_completed' => 50
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->aboutService->validateAboutData($invalidData);
    }

    /** @test */
    public function it_logs_update_actions(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'skills' => ['PHP'],
            'experience_years' => 5,
            'projects_completed' => 50
        ];

        $about = new About($data);

        $this->aboutRepository
            ->shouldReceive('updateContent')
            ->once()
            ->andReturn($about);

        Log::shouldReceive('info')
            ->once()
            ->with('About service action', Mockery::type('array'));

        // Act
        $this->aboutService->updateAboutContent($data, 1);

        // Assert - Log expectation is verified by Mockery
        $this->assertTrue(true);
    }
}
