<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function authenticated_admin_can_get_all_projects(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Project::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/admin/projects');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title_vi',
                            'title_en',
                            'description_vi',
                            'description_en',
                            'image',
                            'link',
                            'technologies',
                            'category',
                            'featured',
                            'order',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function authenticated_admin_can_filter_projects_by_category(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Project::factory()->create(['category' => 'web']);
        Project::factory()->create(['category' => 'mobile']);
        Project::factory()->create(['category' => 'web']);

        // Act
        $response = $this->getJson('/api/admin/projects?category=web');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function authenticated_admin_can_filter_featured_projects(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Project::factory()->create(['featured' => true]);
        Project::factory()->create(['featured' => false]);
        Project::factory()->create(['featured' => true]);

        // Act
        $response = $this->getJson('/api/admin/projects?featured=1');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function authenticated_admin_can_get_single_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create([
            'title_en' => 'Test Project',
            'category' => 'web'
        ]);

        // Act
        $response = $this->getJson("/api/admin/projects/{$project->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title_vi',
                        'title_en',
                        'description_vi',
                        'description_en',
                        'image',
                        'link',
                        'technologies',
                        'category',
                        'featured',
                        'order',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title_en' => 'Test Project',
                        'category' => 'web'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_create_project_without_image(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $projectData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án test',
            'description_en' => 'Test project description',
            'link' => 'https://example.com',
            'technologies' => ['Laravel', 'Vue.js', 'MySQL'],
            'category' => 'web',
            'featured' => false
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'title_vi',
                        'title_en',
                        'description_vi',
                        'description_en',
                        'link',
                        'technologies',
                        'category',
                        'featured',
                        'order'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Project created successfully',
                    'data' => [
                        'title_en' => 'Test Project',
                        'category' => 'web',
                        'technologies' => ['Laravel', 'Vue.js', 'MySQL']
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'title_en' => 'Test Project',
            'category' => 'web'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_create_project_with_image(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $image = UploadedFile::fake()->image('project.jpg', 800, 600);

        $projectData = [
            'title_vi' => 'Dự án test',
            'title_en' => 'Test Project',
            'description_vi' => 'Mô tả dự án test',
            'description_en' => 'Test project description',
            'link' => 'https://example.com',
            'technologies' => ['Laravel', 'Vue.js'],
            'category' => 'web',
            'featured' => false,
            'image' => $image
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Project created successfully'
                ]);

        $this->assertDatabaseHas('projects', [
            'title_en' => 'Test Project',
            'category' => 'web'
        ]);

        // Verify image was stored
        $project = Project::where('title_en', 'Test Project')->first();
        $this->assertNotNull($project->image);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $project->image));
    }

    /** @test */
    public function authenticated_admin_can_update_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create([
            'title_en' => 'Original Project',
            'category' => 'web'
        ]);

        $updateData = [
            'title_vi' => 'Dự án cập nhật',
            'title_en' => 'Updated Project',
            'description_vi' => 'Mô tả cập nhật',
            'description_en' => 'Updated description',
            'link' => 'https://updated.com',
            'technologies' => ['Laravel', 'React'],
            'category' => 'web',
            'featured' => true
        ];

        // Act
        $response = $this->putJson("/api/admin/projects/{$project->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Project updated successfully',
                    'data' => [
                        'title_en' => 'Updated Project',
                        'featured' => true
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title_en' => 'Updated Project',
            'featured' => true
        ]);
    }

    /** @test */
    public function authenticated_admin_can_delete_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create();

        // Act
        $response = $this->deleteJson("/api/admin/projects/{$project->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Project deleted successfully'
                ]);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id
        ]);
    }

    /** @test */
    public function authenticated_admin_can_toggle_featured_status(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project = Project::factory()->create(['featured' => false]);

        // Act
        $response = $this->patchJson("/api/admin/projects/{$project->id}/toggle-featured");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Project featured status updated',
                    'data' => [
                        'featured' => true
                    ]
                ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'featured' => true
        ]);
    }

    /** @test */
    public function authenticated_admin_can_reorder_projects(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $project1 = Project::factory()->create(['order' => 1]);
        $project2 = Project::factory()->create(['order' => 2]);
        $project3 = Project::factory()->create(['order' => 3]);

        $newOrder = [$project3->id, $project1->id, $project2->id];

        // Act
        $response = $this->putJson('/api/admin/projects/reorder', [
            'order' => $newOrder
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Projects reordered successfully'
                ]);

        // Verify new order in database
        $this->assertDatabaseHas('projects', ['id' => $project3->id, 'order' => 1]);
        $this->assertDatabaseHas('projects', ['id' => $project1->id, 'order' => 2]);
        $this->assertDatabaseHas('projects', ['id' => $project2->id, 'order' => 3]);
    }

    /** @test */
    public function project_creation_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/projects', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'title_vi',
                    'title_en',
                    'description_vi',
                    'description_en',
                    'technologies',
                    'category'
                ]);
    }

    /** @test */
    public function project_creation_validates_category_values(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'invalid-category' // Invalid category
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['category']);
    }

    /** @test */
    public function project_creation_validates_technologies_array(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => [], // Empty array
            'category' => 'web'
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['technologies']);
    }

    /** @test */
    public function project_creation_validates_link_format(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'link' => 'invalid-url' // Invalid URL
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['link']);
    }

    /** @test */
    public function project_creation_validates_image_file(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024); // Not an image

        $projectData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'technologies' => ['Laravel'],
            'category' => 'web',
            'image' => $invalidFile
        ];

        // Act
        $response = $this->postJson('/api/admin/projects', $projectData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_projects(): void
    {
        // Act
        $response = $this->getJson('/api/admin/projects');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function returns_404_for_non_existent_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/projects/999');

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Project not found'
                ]);
    }
}
