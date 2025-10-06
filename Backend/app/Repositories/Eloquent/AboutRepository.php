<?php

namespace App\Repositories\Eloquent;

use App\Models\About;
use App\Repositories\Contracts\AboutRepositoryInterface;

class AboutRepository extends BaseRepository implements AboutRepositoryInterface
{
    public function __construct(About $model)
    {
        parent::__construct($model);
    }

    /**
     * Get about content
     *
     * @return About|null
     */
    public function getContent(): ?About
    {
        return $this->model->first();
    }

    /**
     * Update about content
     *
     * @param array $data
     * @return About
     */
    public function updateContent(array $data): About
    {
        $about = $this->getContent();

        if ($about) {
            $about->update($data);
            return $about->fresh();
        }

        return $this->create($data);
    }

    /**
     * Update profile image
     *
     * @param string $imagePath
     * @return About
     */
    public function updateImage(string $imagePath): About
    {
        $about = $this->getContent();

        if ($about) {
            $about->update(['profile_image' => $imagePath]);
            return $about->fresh();
        }

        return $this->create(['profile_image' => $imagePath]);
    }
}
