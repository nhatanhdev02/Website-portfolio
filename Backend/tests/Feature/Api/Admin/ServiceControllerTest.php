<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->admin = Admin::factory()->create();
    }

    /** @test */
    public function authenticated_admin_can_get_all_services(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        Service::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/admin/services');

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
                            'icon',
                            'color',
                            'bg_color',
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
    public function authenticated_admin_can_get_single_service(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service = Service::factory()->create([
            'title_en' => 'Web Development',
            'icon' => 'web-icon',
            'color' => '#FF6B6B'
        ]);

        // Act
        $response = $this->getJson("/api/admin/services/{$service->id}");

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
                        'icon',
                        'color',
                        'bg_color',
                        'order',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title_en' => 'Web Development',
                        'icon' => 'web-icon',
                        'color' => '#FF6B6B'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_create_service(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $serviceData = [
            'title_vi' => 'Phát triển web',
            'title_en' => 'Web Development',
            'description_vi' => 'Tôi tạo ra các ứng dụng web hiện đại',
            'description_en' => 'I create modern web applications',
            'icon' => 'web-icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        // Act
        $response = $this->postJson('/api/admin/services', $serviceData);

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
                        'icon',
                        'color',
                        'bg_color',
                        'order'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Service created successfully',
                    'data' => [
                        'title_en' => 'Web Development',
                        'icon' => 'web-icon',
                        'color' => '#FF6B6B'
                    ]
                ]);

        $this->assertDatabaseHas('services', [
            'title_en' => 'Web Development',
            'icon' => 'web-icon',
            'color' => '#FF6B6B'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_update_service(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service = Service::factory()->create([
            'title_en' => 'Original Service',
            'color' => '#FF6B6B'
        ]);

        $updateData = [
            'title_vi' => 'Dịch vụ cập nhật',
            'title_en' => 'Updated Service',
            'description_vi' => 'Mô tả cập nhật',
            'description_en' => 'Updated description',
            'icon' => 'updated-icon',
            'color' => '#4ECDC4',
            'bg_color' => '#E5F9F6'
        ];

        // Act
        $response = $this->putJson("/api/admin/services/{$service->id}", $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service updated successfully',
                    'data' => [
                        'title_en' => 'Updated Service',
                        'color' => '#4ECDC4'
                    ]
                ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'title_en' => 'Updated Service',
            'color' => '#4ECDC4'
        ]);
    }

    /** @test */
    public function authenticated_admin_can_delete_service(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service = Service::factory()->create();

        // Act
        $response = $this->deleteJson("/api/admin/services/{$service->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service deleted successfully'
                ]);

        $this->assertDatabaseMissing('services', [
            'id' => $service->id
        ]);
    }

    /** @test */
    public function authenticated_admin_can_reorder_services(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service1 = Service::factory()->create(['order' => 1]);
        $service2 = Service::factory()->create(['order' => 2]);
        $service3 = Service::factory()->create(['order' => 3]);

        $newOrder = [$service3->id, $service1->id, $service2->id];

        // Act
        $response = $this->putJson('/api/admin/services/reorder', [
            'order' => $newOrder
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Services reordered successfully'
                ]);

        // Verify new order in database
        $this->assertDatabaseHas('services', ['id' => $service3->id, 'order' => 1]);
        $this->assertDatabaseHas('services', ['id' => $service1->id, 'order' => 2]);
        $this->assertDatabaseHas('services', ['id' => $service2->id, 'order' => 3]);
    }

    /** @test */
    public function authenticated_admin_can_perform_bulk_actions(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();
        $service3 = Service::factory()->create();

        // Act - Bulk delete
        $response = $this->postJson('/api/admin/services/bulk-action', [
            'action' => 'delete',
            'ids' => [$service1->id, $service2->id]
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Bulk action completed successfully'
                ]);

        $this->assertDatabaseMissing('services', ['id' => $service1->id]);
        $this->assertDatabaseMissing('services', ['id' => $service2->id]);
        $this->assertDatabaseHas('services', ['id' => $service3->id]);
    }

    /** @test */
    public function service_creation_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/services', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'title_vi',
                    'title_en',
                    'description_vi',
                    'description_en',
                    'icon',
                    'color',
                    'bg_color'
                ]);
    }

    /** @test */
    public function service_creation_validates_color_format(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => 'Test',
            'title_en' => 'Test',
            'description_vi' => 'Description',
            'description_en' => 'Description',
            'icon' => 'test-icon',
            'color' => 'invalid-color', // Invalid hex color
            'bg_color' => '#FFE5E5'
        ];

        // Act
        $response = $this->postJson('/api/admin/services', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['color']);
    }

    /** @test */
    public function service_creation_validates_field_lengths(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'title_vi' => str_repeat('a', 256), // Exceeds 255 char limit
            'title_en' => 'Valid title',
            'description_vi' => 'Valid description',
            'description_en' => 'Valid description',
            'icon' => str_repeat('b', 101), // Exceeds 100 char limit
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5'
        ];

        // Act
        $response = $this->postJson('/api/admin/services', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'title_vi',
                    'icon'
                ]);
    }

    /** @test */
    public function reorder_validates_order_array(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->putJson('/api/admin/services/reorder', [
            'order' => 'not-an-array' // Should be array
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['order']);
    }

    /** @test */
    public function bulk_action_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/services/bulk-action', []);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'action',
                    'ids'
                ]);
    }

    /** @test */
    public function bulk_action_validates_action_values(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service = Service::factory()->create();

        // Act
        $response = $this->postJson('/api/admin/services/bulk-action', [
            'action' => 'invalid-action', // Invalid action
            'ids' => [$service->id]
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['action']);
    }

    /** @test */
    public function services_are_returned_in_order(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $service1 = Service::factory()->create(['order' => 3, 'title_en' => 'Third']);
        $service2 = Service::factory()->create(['order' => 1, 'title_en' => 'First']);
        $service3 = Service::factory()->create(['order' => 2, 'title_en' => 'Second']);

        // Act
        $response = $this->getJson('/api/admin/services');

        // Assert
        $response->assertStatus(200);

        $services = $response->json('data');
        $this->assertEquals('First', $services[0]['title_en']);
        $this->assertEquals('Second', $services[1]['title_en']);
        $this->assertEquals('Third', $services[2]['title_en']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_services(): void
    {
        // Act
        $response = $this->getJson('/api/admin/services');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function returns_404_for_non_existent_service(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/services/999');

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Service not found'
                ]);
    }
}
