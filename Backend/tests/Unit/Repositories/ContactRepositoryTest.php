<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\ContactRepository;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class ContactRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ContactRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ContactRepository(new ContactMessage(), new ContactInfo());
    }

    /** @test */
    public function it_can_find_unread_messages(): void
    {
        // Arrange
        $unreadMessage1 = ContactMessage::factory()->create([
            'read_at' => null,
            'created_at' => Carbon::now()->subHours(2)
        ]);
        $unreadMessage2 = ContactMessage::factory()->create([
            'read_at' => null,
            'created_at' => Carbon::now()->subHours(1)
        ]);
        $readMessage = ContactMessage::factory()->create([
            'read_at' => Carbon::now()->subMinutes(30)
        ]);

        // Act
        $result = $this->repository->findUnread();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($unreadMessage1));
        $this->assertTrue($result->contains($unreadMessage2));
        $this->assertFalse($result->contains($readMessage));

        // Verify ordering by created_at desc
        $this->assertEquals($unreadMessage2->id, $result->first()->id);
        $this->assertEquals($unreadMessage1->id, $result->last()->id);
    }

    /** @test */
    public function it_can_mark_message_as_read(): void
    {
        // Arrange
        $message = ContactMessage::factory()->create(['read_at' => null]);

        // Act
        $result = $this->repository->markAsRead($message->id);

        // Assert
        $this->assertInstanceOf(ContactMessage::class, $result);
        $this->assertNotNull($result->read_at);

        // Verify in database
        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id
        ]);

        $updatedMessage = ContactMessage::find($message->id);
        $this->assertNotNull($updatedMessage->read_at);
    }

    /** @test */
    public function it_can_bulk_mark_as_read(): void
    {
        // Arrange
        $message1 = ContactMessage::factory()->create(['read_at' => null]);
        $message2 = ContactMessage::factory()->create(['read_at' => null]);
        $message3 = ContactMessage::factory()->create(['read_at' => null]);
        $ids = [$message1->id, $message2->id, $message3->id];

        // Act
        $result = $this->repository->bulkMarkAsRead($ids);

        // Assert
        $this->assertTrue($result);

        // Verify in database
        foreach ($ids as $id) {
            $message = ContactMessage::find($id);
            $this->assertNotNull($message->read_at);
        }
    }

    /** @test */
    public function it_handles_bulk_mark_as_read_with_invalid_ids(): void
    {
        // Arrange - using non-existent IDs
        $ids = [999, 998, 997];

        // Act
        $result = $this->repository->bulkMarkAsRead($ids);

        // Assert - should still return true as no exception is thrown
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_bulk_delete_messages(): void
    {
        // Arrange
        $message1 = ContactMessage::factory()->create();
        $message2 = ContactMessage::factory()->create();
        $message3 = ContactMessage::factory()->create();
        $ids = [$message1->id, $message2->id, $message3->id];

        // Act
        $result = $this->repository->bulkDelete($ids);

        // Assert
        $this->assertTrue($result);

        // Verify in database
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('contact_messages', ['id' => $id]);
        }
    }

    /** @test */
    public function it_handles_bulk_delete_with_invalid_ids(): void
    {
        // Arrange - using non-existent IDs
        $ids = [999, 998, 997];

        // Act
        $result = $this->repository->bulkDelete($ids);

        // Assert - should still return true as no exception is thrown
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_paginated_messages(): void
    {
        // Arrange
        ContactMessage::factory()->count(20)->create();

        // Act
        $result = $this->repository->getPaginatedMessages();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
        $this->assertCount(15, $result->items());
    }

    /** @test */
    public function it_can_get_paginated_messages_with_custom_per_page(): void
    {
        // Arrange
        ContactMessage::factory()->count(20)->create();

        // Act
        $result = $this->repository->getPaginatedMessages(10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(20, $result->total());
        $this->assertCount(10, $result->items());
    }

    /** @test */
    public function it_applies_read_status_filter_unread(): void
    {
        // Arrange
        $unreadMessage = ContactMessage::factory()->create(['read_at' => null]);
        $readMessage = ContactMessage::factory()->create(['read_at' => Carbon::now()]);

        $filters = ['read_status' => 'unread'];

        // Act
        $result = $this->repository->getPaginatedMessages(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($unreadMessage->id, $resultIds);
        $this->assertNotContains($readMessage->id, $resultIds);
    }

    /** @test */
    public function it_applies_read_status_filter_read(): void
    {
        // Arrange
        $unreadMessage = ContactMessage::factory()->create(['read_at' => null]);
        $readMessage = ContactMessage::factory()->create(['read_at' => Carbon::now()]);

        $filters = ['read_status' => 'read'];

        // Act
        $result = $this->repository->getPaginatedMessages(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($readMessage->id, $resultIds);
        $this->assertNotContains($unreadMessage->id, $resultIds);
    }

    /** @test */
    public function it_applies_search_filter(): void
    {
        // Arrange
        $matchingMessage1 = ContactMessage::factory()->create([
            'name' => 'John Test',
            'email' => 'john@example.com',
            'message' => 'Hello world'
        ]);
        $matchingMessage2 = ContactMessage::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'test@example.com',
            'message' => 'Hello world'
        ]);
        $matchingMessage3 = ContactMessage::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'message' => 'This is a test message'
        ]);
        $nonMatchingMessage = ContactMessage::factory()->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'message' => 'Hello world'
        ]);

        $filters = ['search' => 'test'];

        // Act
        $result = $this->repository->getPaginatedMessages(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingMessage1->id, $resultIds);
        $this->assertContains($matchingMessage2->id, $resultIds);
        $this->assertContains($matchingMessage3->id, $resultIds);
        $this->assertNotContains($nonMatchingMessage->id, $resultIds);
    }

    /** @test */
    public function it_can_get_contact_info(): void
    {
        // Arrange
        $contactInfo = ContactInfo::factory()->create();

        // Act
        $result = $this->repository->getContactInfo();

        // Assert
        $this->assertInstanceOf(ContactInfo::class, $result);
        $this->assertEquals($contactInfo->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_contact_info_exists(): void
    {
        // Act
        $result = $this->repository->getContactInfo();

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_existing_contact_info(): void
    {
        // Arrange
        $contactInfo = ContactInfo::factory()->create([
            'email' => 'old@example.com',
            'phone' => '111111111'
        ]);

        $data = [
            'email' => 'new@example.com',
            'phone' => '222222222',
            'address' => 'New Address'
        ];

        // Act
        $result = $this->repository->updateContactInfo($data);

        // Assert
        $this->assertInstanceOf(ContactInfo::class, $result);
        $this->assertEquals('new@example.com', $result->email);
        $this->assertEquals('222222222', $result->phone);
        $this->assertEquals('New Address', $result->address);

        // Verify in database
        $this->assertDatabaseHas('contact_info', [
            'id' => $contactInfo->id,
            'email' => 'new@example.com',
            'phone' => '222222222'
        ]);
    }

    /** @test */
    public function it_creates_contact_info_when_none_exists(): void
    {
        // Arrange
        $data = [
            'email' => 'test@example.com',
            'phone' => '123456789',
            'address' => 'Test Address'
        ];

        // Act
        $result = $this->repository->updateContactInfo($data);

        // Assert
        $this->assertInstanceOf(ContactInfo::class, $result);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals('123456789', $result->phone);
        $this->assertEquals('Test Address', $result->address);

        // Verify in database
        $this->assertDatabaseHas('contact_info', [
            'email' => 'test@example.com',
            'phone' => '123456789',
            'address' => 'Test Address'
        ]);
    }

    /** @test */
    public function it_applies_date_filters(): void
    {
        // Arrange
        $oldMessage = ContactMessage::factory()->create(['created_at' => Carbon::parse('2023-01-01')]);
        $newMessage = ContactMessage::factory()->create(['created_at' => Carbon::parse('2023-06-15')]);
        $futureMessage = ContactMessage::factory()->create(['created_at' => Carbon::parse('2024-01-01')]);

        $filters = [
            'date_from' => '2023-06-01',
            'date_to' => '2023-12-31'
        ];

        // Act
        $result = $this->repository->getPaginatedMessages(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($newMessage->id, $resultIds);
        $this->assertNotContains($oldMessage->id, $resultIds);
        $this->assertNotContains($futureMessage->id, $resultIds);
    }

    /** @test */
    public function it_applies_multiple_filters(): void
    {
        // Arrange
        $matchingMessage = ContactMessage::factory()->create([
            'name' => 'John Test',
            'read_at' => null,
            'created_at' => Carbon::parse('2023-06-15')
        ]);

        $nonMatchingMessage1 = ContactMessage::factory()->create([
            'name' => 'John Test',
            'read_at' => Carbon::now(), // Read message
            'created_at' => Carbon::parse('2023-06-15')
        ]);

        $nonMatchingMessage2 = ContactMessage::factory()->create([
            'name' => 'Jane Doe', // Different name
            'read_at' => null,
            'created_at' => Carbon::parse('2023-06-15')
        ]);

        $filters = [
            'read_status' => 'unread',
            'search' => 'test',
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31'
        ];

        // Act
        $result = $this->repository->getPaginatedMessages(15, $filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());

        $resultIds = collect($result->items())->pluck('id')->toArray();
        $this->assertContains($matchingMessage->id, $resultIds);
        $this->assertNotContains($nonMatchingMessage1->id, $resultIds);
        $this->assertNotContains($nonMatchingMessage2->id, $resultIds);
    }

    /** @test */
    public function it_orders_messages_with_unread_first(): void
    {
        // Arrange
        $readMessage = ContactMessage::factory()->create([
            'read_at' => Carbon::now(),
            'created_at' => Carbon::now()->subHours(1)
        ]);
        $unreadMessage = ContactMessage::factory()->create([
            'read_at' => null,
            'created_at' => Carbon::now()->subHours(2) // Older but unread
        ]);

        // Act
        $result = $this->repository->getPaginatedMessages();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());

        $items = $result->items();
        // Unread message should come first despite being older
        $this->assertEquals($unreadMessage->id, $items[0]->id);
        $this->assertEquals($readMessage->id, $items[1]->id);
    }

    /** @test */
    public function it_tests_base_repository_crud_operations(): void
    {
        // Test findById
        $message = ContactMessage::factory()->create();
        $found = $this->repository->findById($message->id);
        $this->assertInstanceOf(ContactMessage::class, $found);
        $this->assertEquals($message->id, $found->id);

        // Test findById with non-existent ID
        $notFound = $this->repository->findById(999);
        $this->assertNull($notFound);

        // Test update
        $updateData = ['name' => 'Updated Name'];
        $updated = $this->repository->update($message->id, $updateData);
        $this->assertEquals('Updated Name', $updated->name);

        // Test delete
        $deleted = $this->repository->delete($message->id);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    /** @test */
    public function it_tests_model_scopes(): void
    {
        // Arrange
        $unreadMessage = ContactMessage::factory()->create(['read_at' => null]);
        $readMessage = ContactMessage::factory()->create(['read_at' => Carbon::now()]);

        // Test unread scope through repository methods
        $unreadMessages = $this->repository->findUnread();
        $this->assertCount(1, $unreadMessages);
        $this->assertTrue($unreadMessages->contains($unreadMessage));
        $this->assertFalse($unreadMessages->contains($readMessage));
    }
}
