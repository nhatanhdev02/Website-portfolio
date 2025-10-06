<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogPost;
use App\Repositories\Contracts\BlogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogRepository extends BaseRepository implements BlogRepositoryInterface
{
    public function __construct(BlogPost $model)
    {
        parent::__construct($model);
    }

    /**
     * Find published posts with optimized query
     *
     * @param int $limit
     * @return Collection
     */
    public function findPublished(int $limit = null): Collection
    {
        $query = $this->model
            ->select(['id', 'title_vi', 'title_en', 'excerpt_vi', 'excerpt_en', 'thumbnail', 'status', 'published_at', 'tags', 'created_at', 'updated_at'])
            ->published()
            ->orderBy('published_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Find draft posts with optimized query
     *
     * @param int $limit
     * @return Collection
     */
    public function findDrafts(int $limit = null): Collection
    {
        $query = $this->model
            ->select(['id', 'title_vi', 'title_en', 'excerpt_vi', 'excerpt_en', 'thumbnail', 'status', 'published_at', 'tags', 'created_at', 'updated_at'])
            ->draft()
            ->orderBy('updated_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Find posts by status with optimized query
     *
     * @param string $status
     * @param int $limit
     * @return Collection
     */
    public function findByStatus(string $status, int $limit = null): Collection
    {
        $query = $this->model
            ->select(['id', 'title_vi', 'title_en', 'excerpt_vi', 'excerpt_en', 'thumbnail', 'status', 'published_at', 'tags', 'created_at', 'updated_at'])
            ->where('status', $status)
            ->orderBy($status === 'published' ? 'published_at' : 'updated_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Publish a post
     *
     * @param int $id
     * @return BlogPost
     */
    public function publish(int $id): BlogPost
    {
        $post = $this->model->findOrFail($id);
        $post->update([
            'status' => 'published',
            'published_at' => now()
        ]);
        return $post->fresh();
    }

    /**
     * Unpublish a post
     *
     * @param int $id
     * @return BlogPost
     */
    public function unpublish(int $id): BlogPost
    {
        $post = $this->model->findOrFail($id);
        $post->update([
            'status' => 'draft',
            'published_at' => null
        ]);
        return $post->fresh();
    }

    /**
     * Get paginated posts with optimized query
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Select only necessary columns for list view
        $query = $this->model
            ->select(['id', 'title_vi', 'title_en', 'excerpt_vi', 'excerpt_en', 'thumbnail', 'status', 'published_at', 'tags', 'created_at', 'updated_at']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get recent posts for dashboard
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 5): Collection
    {
        return $this->model
            ->select(['id', 'title_vi', 'title_en', 'status', 'published_at', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get posts count by status
     *
     * @return array
     */
    public function getCountByStatus(): array
    {
        return [
            'total' => $this->model->count(),
            'published' => $this->model->where('status', 'published')->count(),
            'draft' => $this->model->where('status', 'draft')->count(),
        ];
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
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title_vi', 'like', "%{$search}%")
                  ->orWhere('title_en', 'like', "%{$search}%")
                  ->orWhere('content_vi', 'like', "%{$search}%")
                  ->orWhere('content_en', 'like', "%{$search}%");
            });
        }

        if (isset($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Default ordering
        if (isset($filters['status']) && $filters['status'] === 'published') {
            $query->orderBy('published_at', 'desc');
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return $query;
    }
}
