<?php

namespace App\Repositories\Contracts;

use App\Models\Hero;

interface HeroRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get hero content
     *
     * @return Hero|null
     */
    public function getContent(): ?Hero;

    /**
     * Update hero content
     *
     * @param array $data
     * @return Hero
     */
    public function updateContent(array $data): Hero;
}
