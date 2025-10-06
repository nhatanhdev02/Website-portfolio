<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository extends BaseRepository implements ServiceRepositoryInterface
{
    public function __construct(Service $model)
    {
        parent::__construct($model);
    }

    /**
     * Get services ordered by position
     *
     * @return Collection
     */
    public function getOrdered(): Collection
    {
        return $this->model->orderBy('order')->get();
    }

    /**
     * Update service order
     *
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        try {
            foreach ($order as $index => $serviceId) {
                $this->model->where('id', $serviceId)->update(['order' => $index + 1]);
            }
            return true;
        } catch (\Exception $e) {
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
     * Create a new service with automatic ordering
     *
     * @param array $data
     * @return Service
     */
    public function create(array $data): Service
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
        if (isset($filters['order_by'])) {
            $query->orderBy($filters['order_by'], $filters['order_direction'] ?? 'asc');
        } else {
            $query->orderBy('order');
        }

        return $query;
    }
}
