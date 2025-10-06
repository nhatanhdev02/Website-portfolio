<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Service;

interface ServiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get services ordered by position
     *
     * @return Collection
     */
    public function getOrdered(): Collection;

    /**
     * Update service order
     *
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool;

    /**
     * Get next order position
     *
     * @return int
     */
    public function getNextOrder(): int;
}
