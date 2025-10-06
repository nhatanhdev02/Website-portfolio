<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\ProjectService;
use App\Services\Admin\FileUploadService;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;

class ProjectServiceTest extends TestCase
{
    private ProjectService $projectService;
    private ProjectRepositoryInterface $projectRepository;
    private FileUploadService $fileUploadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRepository = Mockery::mock(ProjectRepositoryInterface::class);
        $this->fileUploadService = Mockery::mock(FileUploadService::class);
        $this->projectService = new ProjectService($this->projectRepository, $this->fileUploadService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_projects(): void
    {
        // Arrange
        $projects = new Collection([
            new Project(['id' => 1, 'title_en' => 'Project 1']),
            new Project(['id' => 2, 'title_en' => 'Project 2'])
        ]);

        $this->projectRepository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($projects);

        // Act
        $result = $this->projectService->getAllProjects();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_can_get_projects_by_category(): void
    {
        // Arrange
        $projects = new Collection([
            new Project(['id' => 1, 'title_en' => 'Web Project', 'category' => 'web'])
        ]);

        $this->projectRepository
            ->shouldReceive('findByCategory')
            ->once()
            ->with('web')
            ->andReturn($projects);

        // Act
        $result = $this->projectService->getAllProjects(['category' => 'web']);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_featured_projects(): void
    {
        // Arrange
        $projects = new Collection([
            new Project(['id' => 1, 'title_en' => 'Featured Project', 'featured' => true])
        ]);

        $this->projectRepository
            ->shouldReceive('findFeatured')
            ->once()
            ->andReturn($projects);

        // Act
        $result = $this->projectService->getAllProjects(['featured' => true]);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_can_get_project_by_id(): void
    {
        // Arrange
        $project = new Project(['id' => 1, 'title_en' => 'Test Project']);

        $this->projectRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($project);

        // Act
        $result = $this->projectService->getProjectById(1);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('Test Project', $result->title_en);
    }

    /** @test */
    public function it_can_create_project_without_image(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án',
            'description_en' => 'Project description',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web',
            'link' => 'https://example.com'
        ];

        $project = new Project(array_merge($data, ['id' => 1, 'order' => 1]));

        $this->projectRepository
            ->shouldReceive('getNextOrder')
            ->once()
            ->andReturn(1);

        $this->projectRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['order' => 1]))
            ->andReturn($project);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->createProject($data, null, 1);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('Test Project', $result->title_en);
    }

    /** @test */
    public function it_can_create_project_with_image(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án',
            'description_en' => 'Project description',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web'
        ];

        $image = Mockery::mock(UploadedFile::class);
        $imagePath = '/storage/projects/image.jpg';

        $this->fileUploadService
            ->shouldReceive('uploadImage')
            ->once()
            ->with($image, 'projects')
            ->andReturn($imagePath);

        $project = new Project(array_merge($data, ['id' => 1, 'image' => $imagePath, 'order' => 1]));

        $this->projectRepository
            ->shouldReceive('getNextOrder')
            ->once()
            ->andReturn(1);

        $this->projectRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['image' => $imagePath, 'order' => 1]))
            ->andReturn($project);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->createProject($data, $image, 1);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals($imagePath, $result->image);
    }

    /** @test */
    public function it_can_update_project(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án cập nhật',
            'title_en' => 'Updated Project',
            'description_vi' => 'Mô tả cập nhật',
            'description_en' => 'Updated description',
            'technologies' => ['Laravel', 'React'],
            'category' => 'web'
        ];

        $project = new Project(array_merge($data, ['id' => 1]));

        $this->projectRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn($project);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->updateProject(1, $data, null, 1);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('Updated Project', $result->title_en);
    }

    /** @test */
    public function it_can_delete_project(): void
    {
        // Arrange
        $project = new Project(['id' => 1, 'title_en' => 'Test Project']);

        $this->projectRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($project);

        $this->projectRepository
            ->shouldReceive('delete')
            ->once()
            ->with(1)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->deleteProject(1, 1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_project(): void
    {
        // Arrange
        $this->projectRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act
        $result = $this->projectService->deleteProject(999, 1);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_toggle_featured_status(): void
    {
        // Arrange
        $project = new Project(['id' => 1, 'featured' => true]);

        $this->projectRepository
            ->shouldReceive('toggleFeatured')
            ->once()
            ->with(1)
            ->andReturn($project);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->toggleFeatured(1, 1);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertTrue($result->featured);
    }

    /** @test */
    public function it_can_reorder_projects(): void
    {
        // Arrange
        $order = [3, 1, 2];

        $this->projectRepository
            ->shouldReceive('updateOrder')
            ->once()
            ->with($order)
            ->andReturn(true);

        Log::shouldReceive('info')->once();

        // Act
        $result = $this->projectService->reorderProjects($order, 1);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_project_data(): void
    {
        // Arrange
        $validData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án',
            'description_en' => 'Project description',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web',
            'link' => 'https://example.com'
        ];

        // Act & Assert - Should not throw exception
        $this->projectService->validateProjectData($validData);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => '', // Required field missing
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->projectService->validateProjectData($invalidData);
    }

    /** @test */
    public function it_validates_category_values(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'invalid-category' // Invalid category
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->projectService->validateProjectData($invalidData);
    }

    /** @test */
    public function it_validates_technologies_array(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => [], // Empty technologies array
            'category' => 'web'
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->projectService->validateProjectData($invalidData);
    }

    /** @test */
    public function it_validates_project_link_format(): void
    {
        // Arrange
        $invalidData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'link' => 'invalid-url' // Invalid URL format
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->projectService->validateProjectData($invalidData);
    }

    /** @test */
    public function it_logs_project_actions(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web'
        ];

        $project = new Project(array_merge($data, ['id' => 1, 'order' => 1]));

        $this->projectRepository
            ->shouldReceive('getNextOrder')
            ->once()
            ->andReturn(1);

        $this->projectRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($project);

        Log::shouldReceive('info')
            ->once()
            ->with('Project service action', Mockery::type('array'));

        // Act
        $this->projectService->createProject($data, null, 1);

        // Assert - Log expectation is verified by Mockery
        $this->assertTrue(true);
    }
}
