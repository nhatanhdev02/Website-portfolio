<?php

namespace App\Repositories\Contracts;

use App\Models\About;

interface AboutRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get about content
     *
     * @return About|null
     */
    public function getContent(): ?About;

    /**
     * Update about content
     *
     * @param array $data
     * @return About
     */
    public function updateContent(array $data): About;

    /**
     * Update profile image
     *
     * @param string $imagePath
     * @return About
     */
    public function updateImage(string $imagePath): About;
}
