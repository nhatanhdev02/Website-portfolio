<?php

namespace Tests\Feature\Api\Admin;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ContactControllerTest extends TestCase
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
    public function authenticated_admin_can_get_all_contact_messages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactMessage::factory()->count(5)->create();

        // Act
        $response = $this->getJson('/api/admin/contacts/messages');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'message',
                                'read_at',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ])
                ->assertJson([
                    'success' => true
                ]);
    }

    /** @test */
    public function authenticated_admin_can_filter_unread_messages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactMessage::factory()->create(['read_at' => now()]);
        ContactMessage::factory()->create(['read_at' => null]);
        ContactMessage::factory()->create(['read_at' => null]);

        // Act
        $response = $this->getJson('/api/admin/contacts/messages?status=unread');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    /** @test */
    public function authenticated_admin_can_search_messages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactMessage::factory()->create(['name' => 'John Doe', 'message' => 'Hello world']);
        ContactMessage::factory()->create(['name' => 'Jane Smith', 'message' => 'Laravel question']);
        ContactMessage::factory()->create(['name' => 'Bob Johnson', 'message' => 'Vue.js help']);

        // Act
        $response = $this->getJson('/api/admin/contacts/messages?search=Laravel');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertCount(1, $response->json('data.data'));
    }

    /** @test */
    public function authenticated_admin_can_get_single_contact_message(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message = ContactMessage::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message content'
        ]);

        // Act
        $response = $this->getJson("/api/admin/contacts/messages/{$message->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'message',
                        'read_at',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'message' => 'Test message content'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_mark_message_as_read(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message = ContactMessage::factory()->create(['read_at' => null]);

        // Act
        $response = $this->putJson("/api/admin/contacts/messages/{$message->id}/read");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message marked as read'
                ]);

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id
        ]);

        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    /** @test */
    public function authenticated_admin_can_mark_message_as_unread(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message = ContactMessage::factory()->create(['read_at' => now()]);

        // Act
        $response = $this->putJson("/api/admin/contacts/messages/{$message->id}/unread");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message marked as unread'
                ]);

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'read_at' => null
        ]);
    }

    /** @test */
    public function authenticated_admin_can_delete_contact_message(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message = ContactMessage::factory()->create();

        // Act
        $response = $this->deleteJson("/api/admin/contacts/messages/{$message->id}");

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Message deleted successfully'
                ]);

        $this->assertDatabaseMissing('contact_messages', [
            'id' => $message->id
        ]);
    }

    /** @test */
    public function authenticated_admin_can_get_unread_count(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactMessage::factory()->create(['read_at' => now()]);
        ContactMessage::factory()->create(['read_at' => null]);
        ContactMessage::factory()->create(['read_at' => null]);

        // Act
        $response = $this->getJson('/api/admin/contacts/messages/unread-count');

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'unread_count' => 2
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_perform_bulk_actions_on_messages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message1 = ContactMessage::factory()->create(['read_at' => null]);
        $message2 = ContactMessage::factory()->create(['read_at' => null]);
        $message3 = ContactMessage::factory()->create(['read_at' => null]);

        // Act - Bulk mark as read
        $response = $this->postJson('/api/admin/contacts/messages/bulk-action', [
            'action' => 'mark_read',
            'ids' => [$message1->id, $message2->id]
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Bulk action completed successfully'
                ]);

        $message1->refresh();
        $message2->refresh();
        $message3->refresh();

        $this->assertNotNull($message1->read_at);
        $this->assertNotNull($message2->read_at);
        $this->assertNull($message3->read_at);
    }

    /** @test */
    public function authenticated_admin_can_bulk_delete_messages(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $message1 = ContactMessage::factory()->create();
        $message2 = ContactMessage::factory()->create();
        $message3 = ContactMessage::factory()->create();

        // Act - Bulk delete
        $response = $this->postJson('/api/admin/contacts/messages/bulk-action', [
            'action' => 'delete',
            'ids' => [$message1->id, $message2->id]
        ]);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Bulk action completed successfully'
                ]);

        $this->assertDatabaseMissing('contact_messages', ['id' => $message1->id]);
        $this->assertDatabaseMissing('contact_messages', ['id' => $message2->id]);
        $this->assertDatabaseHas('contact_messages', ['id' => $message3->id]);
    }

    /** @test */
    public function authenticated_admin_can_get_contact_info(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $contactInfo = ContactInfo::factory()->create([
            'email' => 'contact@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, Country'
        ]);

        // Act
        $response = $this->getJson('/api/admin/contacts/info');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'email',
                        'phone',
                        'address',
                        'social_links',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'email' => 'contact@example.com',
                        'phone' => '+1234567890',
                        'address' => '123 Main St, City, Country'
                    ]
                ]);
    }

    /** @test */
    public function authenticated_admin_can_update_contact_info(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactInfo::factory()->create();

        $updateData = [
            'email' => 'updated@example.com',
            'phone' => '+9876543210',
            'address' => '456 Updated St, New City, Country',
            'social_links' => [
                'facebook' => 'https://facebook.com/profile',
                'twitter' => 'https://twitter.com/profile',
                'linkedin' => 'https://linkedin.com/in/profile'
            ]
        ];

        // Act
        $response = $this->putJson('/api/admin/contacts/info', $updateData);

        // Assert
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Contact info updated successfully',
                    'data' => [
                        'email' => 'updated@example.com',
                        'phone' => '+9876543210',
                        'address' => '456 Updated St, New City, Country'
                    ]
                ]);

        $this->assertDatabaseHas('contact_infos', [
            'email' => 'updated@example.com',
            'phone' => '+9876543210'
        ]);
    }

    /** @test */
    public function contact_info_update_validates_email_format(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'email' => 'invalid-email', // Invalid email format
            'phone' => '+1234567890',
            'address' => 'Valid address'
        ];

        // Act
        $response = $this->putJson('/api/admin/contacts/info', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function contact_info_update_validates_social_links(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        $invalidData = [
            'email' => 'valid@example.com',
            'phone' => '+1234567890',
            'address' => 'Valid address',
            'social_links' => [
                'facebook' => 'invalid-url', // Invalid URL
                'twitter' => 'https://twitter.com/valid'
            ]
        ];

        // Act
        $response = $this->putJson('/api/admin/contacts/info', $invalidData);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['social_links.facebook']);
    }

    /** @test */
    public function bulk_action_validates_required_fields(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->postJson('/api/admin/contacts/messages/bulk-action', []);

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

        $message = ContactMessage::factory()->create();

        // Act
        $response = $this->postJson('/api/admin/contacts/messages/bulk-action', [
            'action' => 'invalid-action', // Invalid action
            'ids' => [$message->id]
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['action']);
    }

    /** @test */
    public function messages_are_paginated(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        ContactMessage::factory()->count(20)->create();

        // Act
        $response = $this->getJson('/api/admin/contacts/messages?per_page=5');

        // Assert
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'current_page',
                        'per_page',
                        'total',
                        'last_page'
                    ]
                ]);

        $this->assertCount(5, $response->json('data.data'));
        $this->assertEquals(5, $response->json('data.per_page'));
        $this->assertEquals(20, $response->json('data.total'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_contact_messages(): void
    {
        // Act
        $response = $this->getJson('/api/admin/contacts/messages');

        // Assert
        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ]);
    }

    /** @test */
    public function returns_404_for_non_existent_message(): void
    {
        // Arrange
        Sanctum::actingAs($this->admin, ['admin']);

        // Act
        $response = $this->getJson('/api/admin/contacts/messages/999');

        // Assert
        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Message not found'
                ]);
    }
}
