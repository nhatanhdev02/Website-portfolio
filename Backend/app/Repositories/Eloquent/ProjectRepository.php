<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    /**
     * Find projects by category with optimized query
     *
     * @param string $category
     * @return Collection
     */
    public function findByCategory(string $category): Collection
    {
        return $this->model
            ->select(['id', 'title_vi', 'title_en', 'description_vi', 'description_en', 'image', 'link', 'technologies', 'category', 'featured', 'order', 'created_at', 'updated_at'])
            ->where('category', $category)
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find featured projects with optimized query
     *
     * @return Collection
     */
    public function findFeatured(): Collection
    {
        return $this->model
            ->select(['id', 'title_vi', 'title_en', 'description_vi', 'description_en', 'image', 'link', 'technologies', 'category', 'featured', 'order', 'created_at', 'updated_at'])
            ->where('featured', true)
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find projects with filters and pagination support
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function findWithFilters(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->select(['id', 'title_vi', 'title_en', 'description_vi', 'description_en', 'image', 'link', 'technologies', 'category', 'featured', 'order', 'created_at', 'updated_at']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Toggle featured status
     *
     * @param int $id
     * @return Project
     */
    public function toggleFeatured(int $id): Project
    {
        $project = $this->model->findOrFail($id);
        $project->update(['featured' => !$project->featured]);
        return $project->fresh();
    }

    /**
     * Update project order with optimized bulk update
     *
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        try {
            // Use database transaction for consistency
            \DB::transaction(function () use ($order) {
                // Build bulk update query for better performance
                $cases = [];
                $ids = [];

                foreach ($order as $index => $projectId) {
                    $cases[] = "WHEN id = {$projectId} THEN " . ($index + 1);
                    $ids[] = $projectId;
                }

                if (!empty($cases)) {
                    $casesString = implode(' ', $cases);
                    $idsString = implode(',', $ids);

                    \DB::statement("
                        UPDATE projects
                        SET `order` = CASE {$casesString} END,
                            updated_at = NOW()
                        WHERE id IN ({$idsString})
                    ");
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update project order', [
                'error' => $e->getMessage(),
                'order' => $order
            ]);
            return false;
        }
    }

    /**
     * Get next order position
     *
     * @return int
     */
    public function getNextOrder(): int
    {
        $maxOrder = $this->model->max('order');
        return $maxOrder ? $maxOrder + 1 : 1;
    }

    /**
     * Create a new project with automatic ordering
     *
     * @param array $data
     * @return Project
     */
    public function create(array $data): Project
    {
        if (!isset($data['order'])) {
            $data['order'] = $this->getNextOrder();
        }

        return parent::create($data);
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
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['featured'])) {
            $query->where('featured', $filters['featured']);
        }

        if (isset($filters['technologies'])) {
            $technologies = is_array($filters['technologies']) ? $filters['technologies'] : [$filters['technologies']];
            foreach ($technologies as $tech) {
                $query->whereJsonContains('technologies', $tech);
            }
        }

        // Default ordering
        $query->orderBy('order')->orderBy('created_at', 'desc');

        return $query;
    }
}
