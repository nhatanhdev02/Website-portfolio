<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\ContactMessage;
use App\Models\ContactInfo;

interface ContactRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find unread messages
     *
     * @return Collection
     */
    public function findUnread(): Collection;

    /**
     * Mark message as read
     *
     * @param int $id
     * @return ContactMessage
     */
    public function markAsRead(int $id): ContactMessage;

    /**
     * Bulk mark as read
     *
     * @param array $ids
     * @return bool
     */
    public function bulkMarkAsRead(array $ids): bool;

    /**
     * Bulk delete messages
     *
     * @param array $ids
     * @return bool
     */
    public function bulkDelete(array $ids): bool;

    /**
     * Get paginated messages
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedMessages(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get contact info
     *
     * @return ContactInfo|null
     */
    public function getContactInfo(): ?ContactInfo;

    /**
     * Update contact info
     *
     * @param array $data
     * @return ContactInfo
     */
    public function updateContactInfo(array $data): ContactInfo;
}
