<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BaseRepository $repository;
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = Mockery::mock(Model::class);
        $this->repository = new class($this->model) extends BaseRepository {
            // Concrete implementation for testing
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_find_all_records(): void
    {
        // Arrange
        $collection = new Collection([
            Mockery::mock(Model::class),
            Mockery::mock(Model::class)
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('newQuery')->once()->andReturn($builder);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_find_all_records_with_filters(): void
    {
        // Arrange
        $filters = ['status' => 'active'];
        $collection = new Collection([Mockery::mock(Model::class)]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('newQuery')->once()->andReturn($builder);

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_find_record_by_id(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $this->model->shouldReceive('find')->with(1)->once()->andReturn($model);

        // Act
        $result = $this->repository->findById(1);

        // Assert
        $this->assertInstanceOf(Model::class, $result);
    }

    /** @test */
    public function it_returns_null_when_record_not_found(): void
    {
        // Arrange
        $this->model->shouldReceive('find')->with(999)->once()->andReturn(null);

        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_create_record(): void
    {
        // Arrange
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        $model = Mockery::mock(Model::class);

        $this->model->shouldReceive('create')->with($data)->once()->andReturn($model);

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Model::class, $result);
    }

    /** @test */
    public function it_can_update_record(): void
    {
        // Arrange
        $data = ['name' => 'Updated Name'];
        $model = Mockery::mock(Model::class);
        $freshModel = Mockery::mock(Model::class);

        $model->shouldReceive('update')->with($data)->once();
        $model->shouldReceive('fresh')->once()->andReturn($freshModel);

        $this->model->shouldReceive('findOrFail')->with(1)->once()->andReturn($model);

        // Act
        $result = $this->repository->update(1, $data);

        // Assert
        $this->assertInstanceOf(Model::class, $result);
        $this->assertEquals($freshModel, $result);
    }

    /** @test */
    public function it_can_delete_record(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('delete')->once()->andReturn(true);

        $this->model->shouldReceive('findOrFail')->with(1)->once()->andReturn($model);

        // Act
        $result = $this->repository->delete(1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_delete_failure(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('delete')->once()->andReturn(false);

        $this->model->shouldReceive('findOrFail')->with(1)->once()->andReturn($model);

        // Act
        $result = $this->repository->delete(1);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_throws_exception_when_updating_non_existent_record(): void
    {
        // Arrange
        $this->model->shouldReceive('findOrFail')->with(999)->once()->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->update(999, ['name' => 'Test']);
    }

    /** @test */
    public function it_throws_exception_when_deleting_non_existent_record(): void
    {
        // Arrange
        $this->model->shouldReceive('findOrFail')->with(999)->once()->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException());

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->delete(999);
    }
}
