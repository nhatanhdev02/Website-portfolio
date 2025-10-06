<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Admin\ContactService;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Models\ContactMessage;
use App\Models\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;

class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContactService $contactService;
    private ContactRepositoryInterface $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $this->contactService = new ContactService($this->contactRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_messages_with_pagination(): void
    {
        // Arrange
        $messages = new LengthAwarePaginator([], 0, 15);

        $this->contactRepository
            ->shouldReceive('getPaginatedMessages')
            ->once()
            ->with(15, [])
            ->andReturn($messages);

        // Act
        $result = $this->contactService->getAllMessages();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_can_get_unread_messages(): void
    {
        // Arrange
        $messages = new Collection([]);

        $this->contactRepository
            ->shouldReceive('findUnread')
            ->once()
            ->andReturn($messages);

        // Act
        $result = $this->contactService->getUnreadMessages();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function it_can_mark_message_as_read(): void
    {
        // Arrange
        $messageId = 1;
        $adminId = 1;
        $message = new ContactMessage([
            'id' => $messageId,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'message' => 'Test message'
        ]);

        $this->contactRepository
            ->shouldReceive('markAsRead')
            ->once()
            ->with($messageId)
            ->andReturn($message);

        // Act
        $result = $this->contactService->markAsRead($messageId, $adminId);

        // Assert
        $this->assertInstanceOf(ContactMessage::class, $result);
        $this->assertEquals('test@example.com', $result->email);
    }

    /** @test */
    public function it_can_bulk_mark_messages_as_read(): void
    {
        // Arrange
        $messageIds = [1, 2, 3];
        $adminId = 1;

        $this->contactRepository
            ->shouldReceive('bulkMarkAsRead')
            ->once()
            ->with($messageIds)
            ->andReturn(true);

        // Act
        $result = $this->contactService->bulkMarkAsRead($messageIds, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_message_ids_for_bulk_operations(): void
    {
        // Arrange
        $invalidIds = ['invalid', 'ids'];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->contactService->bulkMarkAsRead($invalidIds);
    }

    /** @test */
    public function it_can_delete_message(): void
    {
        // Arrange
        $messageId = 1;
        $adminId = 1;
        $message = new ContactMessage([
            'id' => $messageId,
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        $this->contactRepository
            ->shouldReceive('findById')
            ->once()
            ->with($messageId)
            ->andReturn($message);

        $this->contactRepository
            ->shouldReceive('delete')
            ->once()
            ->with($messageId)
            ->andReturn(true);

        // Act
        $result = $this->contactService->deleteMessage($messageId, $adminId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_message(): void
    {
        // Arrange
        $messageId = 999;

        $this->contactRepository
            ->shouldReceive('findById')
            ->once()
            ->with($messageId)
            ->andReturn(null);

        // Act
        $result = $this->contactService->deleteMessage($messageId);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_contact_info(): void
    {
        // Arrange
        $contactInfo = new ContactInfo([
            'id' => 1,
            'email' => 'contact@example.com',
            'phone' => '+1234567890',
            'address' => '123 Test St',
            'social_links' => [],
            'business_hours' => '9-5',
            'timezone' => 'UTC'
        ]);

        $this->contactRepository
            ->shouldReceive('getContactInfo')
            ->once()
            ->andReturn($contactInfo);

        // Act
        $result = $this->contactService->getContactInfo();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('contact@example.com', $result['email']);
        $this->assertEquals('+1234567890', $result['phone']);
    }

    /** @test */
    public function it_returns_default_structure_when_no_contact_info_exists(): void
    {
        // Arrange
        $this->contactRepository
            ->shouldReceive('getContactInfo')
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->contactService->getContactInfo();

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('', $result['email']);
        $this->assertEquals('UTC', $result['timezone']);
    }

    /** @test */
    public function it_can_update_contact_info(): void
    {
        // Arrange
        $data = [
            'email' => 'new@example.com',
            'phone' => '+9876543210',
            'address' => '456 New St'
        ];
        $adminId = 1;
        $contactInfo = new ContactInfo($data);

        $this->contactRepository
            ->shouldReceive('updateContactInfo')
            ->once()
            ->with($data)
            ->andReturn($contactInfo);

        // Act
        $result = $this->contactService->updateContactInfo($data, $adminId);

        // Assert
        $this->assertInstanceOf(ContactInfo::class, $result);
    }

    /** @test */
    public function it_validates_contact_info_data(): void
    {
        // Arrange
        $invalidData = [
            'email' => 'invalid-email',
            'phone' => str_repeat('1', 25) // Too long
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->contactService->updateContactInfo($invalidData);
    }

    /** @test */
    public function it_can_get_message_statistics(): void
    {
        // Arrange
        $allMessages = new Collection([
            new ContactMessage(['created_at' => now()]),
            new ContactMessage(['created_at' => now()->subDays(2)])
        ]);
        $unreadMessages = new Collection([
            new ContactMessage(['created_at' => now()])
        ]);

        $this->contactRepository
            ->shouldReceive('findAll')
            ->twice()
            ->andReturn($allMessages);

        $this->contactRepository
            ->shouldReceive('findUnread')
            ->once()
            ->andReturn($unreadMessages);

        // Act
        $result = $this->contactService->getMessageStatistics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_messages', $result);
        $this->assertArrayHasKey('unread_messages', $result);
        $this->assertArrayHasKey('read_messages', $result);
    }
}
