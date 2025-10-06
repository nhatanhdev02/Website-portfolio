<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\ServiceManagementService;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;

class ServiceManagementServiceTest extends TestCase
{
    private ServiceManagementService $serviceManagementService;
    private ServiceRepositoryInterface $serviceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceRepository = Mockery::mock(ServiceRepositoryInterface::class);
        $this->serviceManagementService = new ServiceManagementService($this->serviceRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_services(): void
    {
        // Arrange
        $services = new Collection([
            new Service(['id' => 1, 'title_en' => 'Service 1']),
            new Service(['id' => 2, 'title_en' => 'Service 2'])
        ]);

        $this->serviceRepository
            ->shouldReceive('getOrdered')
            ->once()
            ->andReturn($services);

        // Act
        $result = $this->serviceManagementService->getAllServices();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_get_service_by_id(): void
    {
        // Arrange
        $service = new Service(['id' => 1, 'title_en' => 'Test Service']);

        $this->serviceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($service);

        // Act
        $result = $this->serviceManagementService->getServiceById(1);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals('Test Service', $result->title_en);
    }

    /** @test */
    public function it_can_create_service(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dịch vụ test',
            'title_en' => 'Test Service',
            'description_vi' => 'Mô tả dịch vụ',
            'description_en' => 'Service description',
            'icon' => 'test-icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        $service = new Service(array_merge($data, ['id' => 1, 'order' => 1]));

        $this->serviceRepository
            ->shouldReceive('getNextOrder')
            ->once()
            ->andReturn(1);

        $this->serviceRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['order' => 1]))
            ->andReturn($service);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->serviceManagementService->createService($data, 1);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals('Test Service', $result->title_en);
    }

    /** @test */
    public function it_can_update_service(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dịch vụ cập nhật',
            'title_en' => 'Updated Service',
            'description_vi' => 'Mô tả cập nhật',
            'description_en' => 'Updated description',
            'icon' => 'updated-icon',
            'color' => '#00FF00',
            'bg_color' => '#E5FFE5'
        ];

        $service = new Service(array_merge($data, ['id' => 1]));

        $this->serviceRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn($service);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->serviceManagementService->updateService(1, $data, 1);

        // Assert
        $this->assertInstanceOf(Service::class, $result);
        $this->assertEquals('Updated Service', $result->title_en);
    }

    /** @test */
    public function it_can_delete_service(): void
    {
        // Arrange
        $service = new Service(['id' => 1, 'title_en' => 'Test Service']);

        $this->serviceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($service);

        $this->serviceRepository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->serviceManagementService->deleteService(1, 1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_service(): void
    {
        // Arrange
        $this->serviceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act
        $result = $this->serviceManagementService->deleteService(999, 1);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_reorder_services(): void
    {
        // Arrange
        $order = [3, 1, 2];

        $this->serviceRepository
            ->shouldReceive('updateOrder')
            ->once()
            ->with($order)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->serviceManagementService->reorderServices($order, 1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_service_data(): void
    {
        // Arrange
        $validData = [
            'title_vi' => 'Dịch vụ test',
            'title_en' => 'Test Service',
            'description_vi' => 'Mô tả dịch vụ',
            'description_en' => 'Service description',
            'icon' => 'test-icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        // Act & Assert - Should not throw exception
        $this->serviceManagementService->validateServiceData($validData);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => '', // Required field missing
            'title_en' => 'Test Service',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'icon' => 'icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->serviceManagementService->validateServiceData($invalidData);
    }

    /** @test */
    public function it_validates_color_format(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Dịch vụ test',
            'title_en' => 'Test Service',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'icon' => 'icon',
            'color' => 'invalid-color', // Invalid hex color
            'bg_color' => '#FFE5E5'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->serviceManagementService->validateServiceData($invalidData);
    }

    /** @test */
    public function it_validates_reorder_data(): void
    {
        // Arrange
        $validOrder = [1, 2, 3];

        // Mock the exists validation by not throwing exception
        // Act & Assert - Should not throw exception
        $this->serviceManagementService->validateReorderData($validOrder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_reorder_data_for_duplicates(): void
    {
        // Arrange
        $invalidOrder = [1, 2, 2]; // Duplicate IDs

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->serviceManagementService->validateReorderData($invalidOrder);
    }

    /** @test */
    public function it_validates_reorder_data_for_empty_array(): void
    {
        // Arrange
        $invalidOrder = []; // Empty array

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->serviceManagementService->validateReorderData($invalidOrder);
    }

    /** @test */
    public function it_logs_service_actions(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dịch vụ test',
            'title_en' => 'Test Service',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'icon' => 'icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        $service = new Service(array_merge($data, ['id' => 1, 'order' => 1]));

        $this->serviceRepository
            ->shouldReceive('getNextOrder')
            ->once()
            ->andReturn(1);

        $this->serviceRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($service);

        Log::shouldReceive('info')
            ->once()
            ->with('Service management action', Mockery::type('array'));

        // Act
        $this->serviceManagementService->createService($data, 1);

        // Assert - Log expectation is verified by Mockery
        $this->assertTrue(true);
    }
}
