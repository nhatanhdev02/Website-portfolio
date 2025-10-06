<?php

namespace App\Repositories\Eloquent;

use App\Models\Hero;
use App\Repositories\Contracts\HeroRepositoryInterface;

class HeroRepository extends BaseRepository implements HeroRepositoryInterface
{
    public function __construct(Hero $model)
    {
        parent::__construct($model);
    }

    /**
     * Get hero content
     *
     * @return Hero|null
     */
    public function getContent(): ?Hero
    {
        return $this->model->first();
    }

    /**
     * Update hero content
     *
     * @param array $data
     * @return Hero
     */
    public function updateContent(array $data): Hero
    {
        $hero = $this->getContent();

        if ($hero) {
            $hero->update($data);
            return $hero->fresh();
        }

        return $this->create($data);
    }
}
