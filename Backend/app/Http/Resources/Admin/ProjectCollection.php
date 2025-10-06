<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => ProjectResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'featured_count' => $this->collection->where('featured', true)->count(),
                'categories' => $this->collection->pluck('category')->unique()->values(),
                'technologies' => $this->getAllTechnologies(),
                'has_items' => $this->collection->isNotEmpty(),
            ],
        ];
    }

    /**
     * Get all unique technologies from projects
     */
    private function getAllTechnologies(): array
    {
        $technologies = [];

        foreach ($this->collection as $project) {
            if ($project->technologies && is_array($project->technologies)) {
                $technologies = array_merge($technologies, $project->technologies);
            }
        }

        return array_values(array_unique($technologies));
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Projects retrieved successfully',
        ];
    }
}
