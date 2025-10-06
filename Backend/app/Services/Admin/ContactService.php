<?php

namespace App\Services\Admin;

use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContactService
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository
    ) {}

    /**
     * Get all contact messages with pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllMessages(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->contactRepository->getPaginatedMessages($perPage, $filters);
    }

    /**
     * Get unread messages
     *
     * @return Collection
     */
    public function getUnreadMessages(): Collection
    {
        return $this->contactRepository->findUnread();
    }

    /**
     * Get message by ID
     *
     * @param int $id
     * @return ContactMessage|null
     */
    public function getMessageById(int $id): ?ContactMessage
    {
        return $this->contactRepository->findById($id);
    }

    /**
     * Mark message as read
     *
     * @param int $id
     * @param int|null $adminId
     * @return ContactMessage
     */
    public function markAsRead(int $id, ?int $adminId = null): ContactMessage
    {
        $message = $this->contactRepository->markAsRead($id);

        $this->logAction('message_marked_as_read', [
            'message_id' => $id,
            'sender_email' => $message->email
        ], $adminId);

        return $message;
    }

    /**
     * Bulk mark messages as read
     *
     * @param array $ids
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function bulkMarkAsRead(array $ids, ?int $adminId = null): bool
    {
        $this->validateMessageIds($ids);

        $result = $this->contactRepository->bulkMarkAsRead($ids);

        if ($result) {
            $this->logAction('messages_bulk_marked_as_read', [
                'message_ids' => $ids,
                'count' => count($ids)
            ], $adminId);
        }

        return $result;
    }

    /**
     * Delete message
     *
     * @param int $id
     * @param int|null $adminId
     * @return bool
     */
    public function deleteMessage(int $id, ?int $adminId = null): bool
    {
        $message = $this->contactRepository->findById($id);

        if (!$message) {
            return false;
        }

        $result = $this->contactRepository->delete($id);

        if ($result) {
            $this->logAction('message_deleted', [
                'message_id' => $id,
                'sender_email' => $message->email,
                'sender_name' => $message->name
            ], $adminId);
        }

        return $result;
    }

    /**
     * Bulk delete messages
     *
     * @param array $ids
     * @param int|null $adminId
     * @return bool
     * @throws ValidationException
     */
    public function bulkDeleteMessages(array $ids, ?int $adminId = null): bool
    {
        $this->validateMessageIds($ids);

        // Get messages before deletion for logging
        $messages = $this->contactRepository->findAll()->whereIn('id', $ids);
        $messageData = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'email' => $message->email,
                'name' => $message->name
            ];
        })->toArray();

        $result = $this->contactRepository->bulkDelete($ids);

        if ($result) {
            $this->logAction('messages_bulk_deleted', [
                'message_ids' => $ids,
                'count' => count($ids),
                'messages' => $messageData
            ], $adminId);
        }

        return $result;
    }

    /**
     * Get contact info
     *
     * @return array
     */
    public function getContactInfo(): array
    {
        $contactInfo = $this->contactRepository->getContactInfo();

        if (!$contactInfo) {
            // Return default structure if no contact info exists
            return [
                'email' => '',
                'phone' => '',
                'address' => '',
                'social_links' => [],
                'business_hours' => '',
                'timezone' => 'UTC'
            ];
        }

        return [
            'id' => $contactInfo->id,
            'email' => $contactInfo->email,
            'phone' => $contactInfo->phone,
            'address' => $contactInfo->address,
            'social_links' => $contactInfo->social_links,
            'business_hours' => $contactInfo->business_hours,
            'timezone' => $contactInfo->timezone,
            'updated_at' => $contactInfo->updated_at
        ];
    }

    /**
     * Update contact info
     *
     * @param array $data
     * @param int|null $adminId
     * @return ContactInfo
     * @throws ValidationException
     */
    public function updateContactInfo(array $data, ?int $adminId = null): ContactInfo
    {
        $this->validateContactInfoData($data);

        $contactInfo = $this->contactRepository->updateContactInfo($data);

        $this->logAction('contact_info_updated', [
            'data' => $data
        ], $adminId);

        return $contactInfo;
    }

    /**
     * Get message statistics
     *
     * @return array
     */
    public function getMessageStatistics(): array
    {
        $allMessages = $this->contactRepository->findAll();
        $unreadMessages = $this->contactRepository->findUnread();

        return [
            'total_messages' => $allMessages->count(),
            'unread_messages' => $unreadMessages->count(),
            'read_messages' => $allMessages->count() - $unreadMessages->count(),
            'messages_today' => $allMessages->where('created_at', '>=', now()->startOfDay())->count(),
            'messages_this_week' => $allMessages->where('created_at', '>=', now()->startOfWeek())->count(),
            'messages_this_month' => $allMessages->where('created_at', '>=', now()->startOfMonth())->count()
        ];
    }

    /**
     * Validate message IDs
     *
     * @param array $ids
     * @throws ValidationException
     */
    private function validateMessageIds(array $ids): void
    {
        $validator = Validator::make(['ids' => $ids], [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:contact_messages,id'
        ], [
            'ids.required' => 'Message IDs are required',
            'ids.array' => 'Message IDs must be an array',
            'ids.min' => 'At least one message ID is required',
            'ids.*.required' => 'Each message ID must have a value',
            'ids.*.integer' => 'Each message ID must be a number',
            'ids.*.exists' => 'Message ID does not exist'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check for duplicates
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'ids' => ['Duplicate message IDs are not allowed']
            ]);
        }
    }

    /**
     * Validate contact info data
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateContactInfoData(array $data): void
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required_with:social_links|string|max:50',
            'social_links.*.url' => 'required_with:social_links|url|max:500',
            'business_hours' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|max:50'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'address.max' => 'Address must not exceed 500 characters',
            'social_links.array' => 'Social links must be an array',
            'social_links.*.platform.required_with' => 'Platform is required for each social link',
            'social_links.*.platform.max' => 'Platform name must not exceed 50 characters',
            'social_links.*.url.required_with' => 'URL is required for each social link',
            'social_links.*.url.url' => 'Each social link URL must be valid',
            'social_links.*.url.max' => 'Social link URL must not exceed 500 characters',
            'business_hours.max' => 'Business hours must not exceed 500 characters',
            'timezone.max' => 'Timezone must not exceed 50 characters'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Log admin action
     *
     * @param string $action
     * @param array $data
     * @param int|null $adminId
     */
    private function logAction(string $action, array $data, ?int $adminId = null): void
    {
        Log::info('Contact service action', [
            'action' => $action,
            'admin_id' => $adminId,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
