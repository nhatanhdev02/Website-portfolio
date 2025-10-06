<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\ProjectRepository;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProjectRepository(new Project());
    }

    /** @test */
    public function it_can_find_projects_by_category(): void
    {
        // Arrange
        $webProject1 = Project::factory()->create(['category' => 'web', 'order' => 1]);
        $webProject2 = Project::factory()->create(['category' => 'web', 'order' => 2]);
        $mobileProject = Project::factory()->create(['category' => 'mobile', 'order' => 1]);

        // Act
        $result = $this->repository->findByCategory('web');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($webProject1));
        $this->assertTrue($result->contains($webProject2));
        $this->assertFalse($result->contains($mobileProject));

        // Verify ordering
        $this->assertEquals($webProject1->id, $result->first()->id);
        $this->assertEquals($webProject2->id, $result->last()->id);
    }

    /** @test */
    public function it_can_find_featured_projects(): void
    {
        // Arrange
        $featuredProject1 = Project::factory()->create(['featured' => true, 'order' => 1]);
        $featuredProject2 = Project::factory()->create(['featured' => true, 'order' => 2]);
        $regularProject = Project::factory()->create(['featured' => false, 'order' => 3]);

        // Act
        $result = $this->repository->findFeatured();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($featuredProject1));
        $this->assertTrue($result->contains($featuredProject2));
        $this->assertFalse($result->contains($regularProject));

        // Verify ordering
        $this->assertEquals($featuredProject1->id, $result->first()->id);
        $this->assertEquals($featuredProject2->id, $result->last()->id);
    }

    /** @test */
    public function it_can_toggle_featured_status_from_false_to_true(): void
    {
        // Arrange
        $project = Project::factory()->create(['featured' => false]);

        // Act
        $result = $this->repository->toggleFeatured($project->id);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertTrue($result->featured);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'featured' => true
        ]);
    }

    /** @test */
    public function it_can_toggle_featured_status_from_true_to_false(): void
    {
        // Arrange
        $project = Project::factory()->create(['featured' => true]);

        // Act
        $result = $this->repository->toggleFeatured($project->id);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertFalse($result->featured);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'featured' => false
        ]);
    }

    /** @test */
    public function it_can_update_project_order(): void
    {
        // Arrange
        $project1 = Project::factory()->create(['order' => 1]);
        $project2 = Project::factory()->create(['order' => 2]);
        $project3 = Project::factory()->create(['order' => 3]);

        $order = [$project3->id, $project1->id, $project2->id];

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertTrue($result);

        // Verify in database
        $this->assertDatabaseHas('projects', ['id' => $project3->id, 'order' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $project1->id, 'order' => 2]);
        $this->assertDatabaseHas('projects', ['id' => $project2->id, 'order' => 3]);
    }

    /** @test */
    public function it_handles_update_order_with_invalid_ids(): void
    {
        // Arrange - using non-existent IDs
        $order = [999, 998];

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert - should still return true as the method doesn't validate existence
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_next_order_position(): void
    {
        // Arrange
        Project::factory()->create(['order' => 3]);
        Project::factory()->create(['order' => 5]);
        Project::factory()->create(['order' => 1]);

        // Act
        $result = $this->repository->getNextOrder();

        // Assert
        $this->assertEquals(6, $result);
    }

    /** @test */
    public function it_returns_1_for_next_order_when_no_projects_exist(): void
    {
        // Act
        $result = $this->repository->getNextOrder();

        // Assert
        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_creates_project_with_automatic_ordering(): void
    {
        // Arrange
        Project::factory()->create(['order' => 3]);
        Project::factory()->create(['order' => 1]);

        $data = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả',
            'description_en' => 'Description',
            'category' => 'web',
            'technologies' => ['Laravel', 'Vue.js'],
            'image' => 'test.jpg'
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals(4, $result->order);
        $this->assertEquals('Test Project', $result->title_en);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'title_en' => 'Test Project',
            'order' => 4
        ]);
    }

    /** @test */
    public function it_creates_project_with_provided_order(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả',
            'description_en' => 'Description',
            'category' => 'web',
            'technologies' => ['Laravel', 'Vue.js'],
            'image' => 'test.jpg',
            'order' => 10
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals(10, $result->order);
        $this->assertEquals('Test Project', $result->title_en);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'title_en' => 'Test Project',
            'order' => 10
        ]);
    }

    /** @test */
    public function it_applies_category_filter(): void
    {
        // Arrange
        $webProject = Project::factory()->create(['category' => 'web']);
        $mobileProject = Project::factory()->create(['category' => 'mobile']);

        $filters = ['category' => 'mobile'];

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($mobileProject));
        $this->assertFalse($result->contains($webProject));
    }

    /** @test */
    public function it_applies_featured_filter(): void
    {
        // Arrange
        $featuredProject = Project::factory()->create(['featured' => true]);
        $regularProject = Project::factory()->create(['featured' => false]);

        $filters = ['featured' => true];

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($featuredProject));
        $this->assertFalse($result->contains($regularProject));
    }

    /** @test */
    public function it_applies_technologies_filter_with_array(): void
    {
        // Arrange
        $laravelProject = Project::factory()->create(['technologies' => ['Laravel', 'PHP']]);
        $vueProject = Project::factory()->create(['technologies' => ['Vue.js', 'JavaScript']]);
        $fullStackProject = Project::factory()->create(['technologies' => ['Laravel', 'Vue.js', 'MySQL']]);

        $filters = ['technologies' => ['Laravel', 'Vue.js']];

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result); // Only fullStackProject contains both
        $this->assertTrue($result->contains($fullStackProject));
        $this->assertFalse($result->contains($laravelProject));
        $this->assertFalse($result->contains($vueProject));
    }

    /** @test */
    public function it_applies_technologies_filter_with_string(): void
    {
        // Arrange
        $laravelProject = Project::factory()->create(['technologies' => ['Laravel', 'PHP']]);
        $vueProject = Project::factory()->create(['technologies' => ['Vue.js', 'JavaScript']]);

        $filters = ['technologies' => 'Laravel'];

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($laravelProject));
        $this->assertFalse($result->contains($vueProject));
    }

    /** @test */
    public function it_applies_multiple_filters(): void
    {
        // Arrange
        $matchingProject = Project::factory()->create([
            'category' => 'web',
            'featured' => true,
            'technologies' => ['Laravel', 'Vue.js']
        ]);
        $nonMatchingProject1 = Project::factory()->create([
            'category' => 'mobile',
            'featured' => true,
            'technologies' => ['Laravel']
        ]);
        $nonMatchingProject2 = Project::factory()->create([
            'category' => 'web',
            'featured' => false,
            'technologies' => ['Laravel']
        ]);

        $filters = [
            'category' => 'web',
            'featured' => true,
            'technologies' => 'Laravel'
        ];

        // Act
        $result = $this->repository->findAll($filters);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($matchingProject));
        $this->assertFalse($result->contains($nonMatchingProject1));
        $this->assertFalse($result->contains($nonMatchingProject2));
    }

    /** @test */
    public function it_returns_all_projects_when_no_filters_applied(): void
    {
        // Arrange
        $project1 = Project::factory()->create(['order' => 1]);
        $project2 = Project::factory()->create(['order' => 2]);
        $project3 = Project::factory()->create(['order' => 3]);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);

        // Verify ordering
        $this->assertEquals($project1->id, $result->first()->id);
        $this->assertEquals($project3->id, $result->last()->id);
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
        $project = Project::factory()->create(['order' => 5]);
        $order = [$project->id];

        // Act
        $result = $this->repository->updateOrder($order);

        // Assert
        $this->assertTrue($result);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'order' => 1
        ]);
    }

    /** @test */
    public function it_tests_base_repository_crud_operations(): void
    {
        // Test findById
        $project = Project::factory()->create();
        $found = $this->repository->findById($project->id);
        $this->assertInstanceOf(Project::class, $found);
        $this->assertEquals($project->id, $found->id);

        // Test findById with non-existent ID
        $notFound = $this->repository->findById(999);
        $this->assertNull($notFound);

        // Test update
        $updateData = ['title_en' => 'Updated Title'];
        $updated = $this->repository->update($project->id, $updateData);
        $this->assertEquals('Updated Title', $updated->title_en);

        // Test delete
        $deleted = $this->repository->delete($project->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    /** @test */
    public function it_creates_project_with_order_1_when_no_existing_projects(): void
    {
        // Arrange
        $data = [
            'title_vi' => 'Dự án đầu tiên',
            'title_en' => 'First Project',
            'description_vi' => 'Mô tả',
            'description_en' => 'Description',
            'category' => 'web',
            'technologies' => ['Laravel'],
            'image' => 'test.jpg'
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals(1, $result->order);

        // Verify in database
        $this->assertDatabaseHas('projects', [
            'title_en' => 'First Project',
            'order' => 1
        ]);
    }
}
