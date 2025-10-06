<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\BlogPost;

interface BlogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find published posts
     *
     * @return Collection
     */
    public function findPublished(): Collection;

    /**
     * Find draft posts
     *
     * @return Collection
     */
    public function findDrafts(): Collection;

    /**
     * Find posts by status
     *
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $status): Collection;

    /**
     * Publish a post
     *
     * @param int $id
     * @return BlogPost
     */
    public function publish(int $id): BlogPost;

    /**
     * Unpublish a post
     *
     * @param int $id
     * @return BlogPost
     */
    public function unpublish(int $id): BlogPost;

    /**
     * Get paginated posts
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}
