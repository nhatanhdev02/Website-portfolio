<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\HeroRepository;
use App\Models\Hero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class HeroRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private HeroRepository $repository;
    private Hero $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = Mockery::mock(Hero::class);
        $this->repository = new HeroRepository($this->model);
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
        $hero = Mockery::mock(Hero::class);
        $this->model->shouldReceive('first')->once()->andReturn($hero);

        // Act
        $result = $this->repository->getContent();

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
    }

    /** @test */
    public function it_returns_null_when_no_hero_content_exists(): void
    {
        // Arrange
        $this->model->shouldReceive('first')->once()->andReturn(null);

        // Act
        $result = $this->repository->getContent();

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_existing_hero_content(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh'
        ];

        $hero = Mockery::mock(Hero::class);
        $freshHero = Mockery::mock(Hero::class);

        $this->model->shouldReceive('first')->once()->andReturn($hero);
        $hero->shouldReceive('update')->with($data)->once();
        $hero->shouldReceive('fresh')->once()->andReturn($freshHero);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
        $this->assertEquals($freshHero, $result);
    }

    /** @test */
    public function it_creates_hero_content_when_none_exists(): void
    {
        // Arrange
        $data = [
            'greeting_vi' => 'Xin chào',
            'greeting_en' => 'Hello',
            'name' => 'Nhật Anh'
        ];

        $hero = Mockery::mock(Hero::class);

        $this->model->shouldReceive('first')->once()->andReturn(null);
        $this->model->shouldReceive('create')->with($data)->once()->andReturn($hero);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
    }

    /** @test */
    public function it_handles_empty_data_update(): void
    {
        // Arrange
        $data = [];
        $hero = Mockery::mock(Hero::class);
        $freshHero = Mockery::mock(Hero::class);

        $this->model->shouldReceive('first')->once()->andReturn($hero);
        $hero->shouldReceive('update')->with($data)->once();
        $hero->shouldReceive('fresh')->once()->andReturn($freshHero);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
    }

    /** @test */
    public function it_handles_partial_data_update(): void
    {
        // Arrange
        $data = ['name' => 'Updated Name'];
        $hero = Mockery::mock(Hero::class);
        $freshHero = Mockery::mock(Hero::class);

        $this->model->shouldReceive('first')->once()->andReturn($hero);
        $hero->shouldReceive('update')->with($data)->once();
        $hero->shouldReceive('fresh')->once()->andReturn($freshHero);

        // Act
        $result = $this->repository->updateContent($data);

        // Assert
        $this->assertInstanceOf(Hero::class, $result);
    }
}
