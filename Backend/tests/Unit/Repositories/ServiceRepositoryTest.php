<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\ServiceRepository;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ServiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRepository $repository;
    private Service $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = Mockery::mock(Service::class);
        $this->repository = new ServiceRepository($this->model);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_services_ordered_by_position(): void
    {
        // Arrange
        $collection = new Collection([
            Mockery::mock(Service::class),
            Mockery::mock(Service::class)
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('orderBy')->with('order')->once()->andReturn($builder);

        // Act
        $result = $this->repository->getOrdered();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_update_service_order(): void
    {
        // Arrange
        $order = [3, 1, 2];

        $builder1 = Mockery::mock(Builder::class);
        $builder1->shouldReceive('update')->with(['order' => 1])->once();

        $builder2 = Mockery::mock(Builder::class);
        $builder2->shouldReceive('update')->with(['order' => 2])->once();

        $builder3 = Mockery::mock(Builder::class);
        $builder3->shouldReceive('update')->with(['order' => 3])->once();

        $this->model->shouldReceive('where')->with('id', 3)->once()->andReturn($builder1);
        $this->model->shouldReceive('where')->with('id', 1)->once()->andReturn($builder2);
        $this->model->shouldReceive('where')->with('id', 2)->once()->andReturn($builder3);

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_update_order_failure(): void
    {
        // Arrange
        $order = [1, 2];

        $this->model->shouldReceive('where')->with('id', 1)->once()->andThrow(new \Exception('Database error'));

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_next_order_position(): void
    {
        // Arrange
        $this->model->shouldReceive('max')->with('order')->once()->andReturn(5);

        // Act
        $result = $this->repository->getNextOrder();

        // Assert
        $this->assertEquals(6, $result);
    }

    /** @test */
    public function it_returns_1_for_next_order_when_no_services_exist(): void
    {
        // Arrange
        $this->model->shouldReceive('max')->with('order')->once()->andReturn(null);

        // Act
        $result = $this->repository->getNextOrder();

        // Assert
        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_returns_1_for_next_order_when_max_is_zero(): void
    {
        // Arrange
        $this->model->shouldReceive('max')->with('order')->once()->andReturn(0);

        // Act
        $result = $this->repository->getNextOrder();

        // Assert
        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_creates_service_with_automatic_ordering(): void
    {
        // Arrange
        $data = [
            'title_en' => 'Web Development',
            'title_vi' => 'Phát triển web'
        ];

        $service = Mockery::mock(Service::class);

        $this->model->shouldReceive('max')->with('order')->once()->andReturn(3);
        $this->model->shouldReceive('create')->with(array_merge($data, ['order' => 4]))->once()->andReturn($service);

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
    }

    /** @test */
    public function it_creates_service_with_provided_order(): void
    {
        // Arrange
        $data = [
            'title_en' => 'Web Development',
            'title_vi' => 'Phát triển web',
            'order' => 10
        ];

        $service = Mockery::mock(Service::class);

        $this->model->shouldReceive('create')->with($data)->once()->andReturn($service);

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
    }

    /** @test */
    public function it_applies_order_by_filter(): void
    {
        // Arrange
        $filters = ['order_by' => 'title_en'];
        $collection = new Collection();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->with('title_en', 'asc')->once()->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('newQuery')->once()->andReturn($builder);

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function it_applies_order_by_filter_with_direction(): void
    {
        // Arrange
        $filters = [
            'order_by' => 'title_en',
            'order_direction' => 'desc'
        ];
        $collection = new Collection();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->with('title_en', 'desc')->once()->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('newQuery')->once()->andReturn($builder);

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function it_applies_default_ordering_when_no_order_by_filter(): void
    {
        // Arrange
        $filters = [];
        $collection = new Collection();

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->with('order')->once()->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn($collection);

        $this->model->shouldReceive('newQuery')->once()->andReturn($builder);

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function it_handles_empty_order_array(): void
    {
        // Arrange
        $order = [];

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_single_item_order_array(): void
    {
        // Arrange
        $order = [5];

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('update')->with(['order' => 1])->once();

        $this->model->shouldReceive('where')->with('id', 5)->once()->andReturn($builder);

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_creates_service_with_order_1_when_no_existing_services(): void
    {
        // Arrange
        $data = ['title_en' => 'First Service'];
        $service = Mockery::mock(Service::class);

        $this->model->shouldReceive('max')->with('order')->once()->andReturn(null);
        $this->model->shouldReceive('create')->with(array_merge($data, ['order' => 1]))->once()->andReturn($service);

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
    }
}
