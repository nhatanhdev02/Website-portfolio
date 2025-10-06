<?php

namespace App\Repositories\Eloquent;

use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    protected ContactInfo $contactInfoModel;

    public function __construct(ContactMessage $model, ContactInfo $contactInfoModel)
    {
        parent::__construct($model);
        $this->contactInfoModel = $contactInfoModel;
    }

    /**
     * Find unread messages with optimized query
     *
     * @param int $limit
     * @return Collection
     */
    public function findUnread(int $limit = null): Collection
    {
        $query = $this->model
            ->select(['id', 'name', 'email', 'subject', 'message', 'read_at', 'created_at'])
            ->unread()
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get unread messages count
     *
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->model->unread()->count();
    }

    /**
     * Mark message as read
     *
     * @param int $id
     * @return ContactMessage
     */
    public function markAsRead(int $id): ContactMessage
    {
        $message = $this->model->findOrFail($id);
        $message->markAsRead();
        return $message->fresh();
    }

    /**
     * Bulk mark as read with optimized query
     *
     * @param array $ids
     * @return bool
     */
    public function bulkMarkAsRead(array $ids): bool
    {
        try {
            // Use single query with whereIn for better performance
            $affected = $this->model
                ->whereIn('id', $ids)
                ->whereNull('read_at') // Only update unread messages
                ->update(['read_at' => now()]);

            \Log::info('Bulk marked messages as read', [
                'message_ids' => $ids,
                'affected_count' => $affected
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to bulk mark messages as read', [
                'error' => $e->getMessage(),
                'message_ids' => $ids
            ]);
            return false;
        }
    }

    /**
     * Bulk delete messages with optimized query
     *
     * @param array $ids
     * @return bool
     */
    public function bulkDelete(array $ids): bool
    {
        try {
            $affected = $this->model->whereIn('id', $ids)->delete();

            \Log::info('Bulk deleted messages', [
                'message_ids' => $ids,
                'affected_count' => $affected
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to bulk delete messages', [
                'error' => $e->getMessage(),
                'message_ids' => $ids
            ]);
            return false;
        }
    }

    /**
     * Get paginated messages with optimized query
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedMessages(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Select only necessary columns for list view
        $query = $this->model
            ->select(['id', 'name', 'email', 'subject', 'message', 'read_at', 'created_at']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get messages statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'unread' => $this->model->unread()->count(),
            'read' => $this->model->whereNotNull('read_at')->count(),
            'today' => $this->model->whereDate('created_at', today())->count(),
            'this_week' => $this->model->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => $this->model->whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Get contact info
     *
     * @return ContactInfo|null
     */
    public function getContactInfo(): ?ContactInfo
    {
        return $this->contactInfoModel->first();
    }

    /**
     * Update contact info
     *
     * @param array $data
     * @return ContactInfo
     */
    public function updateContactInfo(array $data): ContactInfo
    {
        $contactInfo = $this->getContactInfo();

        if ($contactInfo) {
            $contactInfo->update($data);
            return $contactInfo->fresh();
        }

        return $this->contactInfoModel->create($data);
    }

    /**
     * Apply filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['read_status'])) {
            if ($filters['read_status'] === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($filters['read_status'] === 'unread') {
                $query->whereNull('read_at');
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Default ordering - unread first, then by creation date
        $query->orderByRaw('read_at IS NULL DESC')
              ->orderBy('created_at', 'desc');

        return $query;
    }
}
