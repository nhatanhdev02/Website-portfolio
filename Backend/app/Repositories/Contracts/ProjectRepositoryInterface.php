<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Project;

interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find projects by category
     *
     * @param string $category
     * @return Collection
     */
    public function findByCategory(string $category): Collection;

    /**
     * Find featured projects
     *
     * @return Collection
     */
    public function findFeatured(): Collection;

    /**
     * Toggle featured status
     *
     * @param int $id
     * @return Project
     */
    public function toggleFeatured(int $id): Project;

    /**
     * Update project order
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
