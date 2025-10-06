<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\AboutRepository;
use App\Models\About;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AboutRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AboutRepository $repository;
    private About $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = Mockery::mock(About::class);
        $this->repository = new AboutRepository($this->model);
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
        $about = Mockery::mock(About::class);
        $this->model->shouldReceive('first')->once()->andReturn($about);

        // Act
        $result = $this->repository->getContent();

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }

    /** @test */
    public function it_returns_null_when_no_about_content_exists(): void
    {
        // Arrange
        $this->model->shouldReceive('first')->once()->andReturn(null);

        // Act
        $result = $this->repository->getContent();

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_existing_about_content(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'content_vi' => 'Nội dung về tôi',
            'content_en' => 'About me content'
        ];

        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with($data)->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(About::class, $result);
        $this->assertEquals($freshAbout, $result);
    }

    /** @test */
    public function it_creates_about_content_when_none_exists(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'content_vi' => 'Nội dung về tôi',
            'content_en' => 'About me content'
        ];

        $about = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn(null);
        $this->model->shouldReceive('create')->with($data)->once()->andReturn($about);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }

    /** @test */
    public function it_can_update_existing_profile_image(): void
    {
        // Arrange
        $imagePath = '/storage/images/profile.jpg';
        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with(['image' => $imagePath])->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateImage($imagePath);

        // Assert
        $this->assertInstanceOf(About::class, $result);
        $this->assertEquals($freshAbout, $result);
    }

    /** @test */
    public function it_creates_about_record_with_image_when_none_exists(): void
    {
        // Arrange
        $imagePath = '/storage/images/profile.jpg';
        $about = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn(null);
        $this->model->shouldReceive('create')->with(['image' => $imagePath])->once()->andReturn($about);

        // Act
        $result = $this->repository->updateImage($imagePath);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }

    /** @test */
    public function it_handles_empty_data_update(): void
    {
        // Arrange
        $data = [];
        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with($data)->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }

    /** @test */
    public function it_handles_partial_data_update(): void
    {
        // Arrange
        $data = ['title_en' => 'Updated Title'];
        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with($data)->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }

    /** @test */
    public function it_handles_empty_image_path(): void
    {
        // Arrange
        $imagePath = '';
        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with(['image' => $imagePath])->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateImage($imagePath);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }



    /** @test */
    public function it_handles_content_update_with_image(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Về tôi',
            'title_en' => 'About Me',
            'image' => '/storage/images/profile.jpg'
        ];

        $about = Mockery::mock(About::class);
        $freshAbout = Mockery::mock(About::class);

        $this->model->shouldReceive('first')->once()->andReturn($about);
        $about->shouldReceive('update')->with($data)->once();
        $about->shouldReceive('fresh')->once()->andReturn($freshAbout);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(About::class, $result);
    }
}
