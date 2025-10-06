<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BlogCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => BlogResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'published_count' => $this->collection->where('status', 'published')->count(),
                'draft_count' => $this->collection->where('status', 'draft')->count(),
                'scheduled_count' => $this->collection->filter(function ($post) {
                    return $post->published_at && $post->published_at->isFuture();
                })->count(),
                'tags' => $this->getAllTags(),
                'has_items' => $this->collection->isNotEmpty(),
            ],
        ];
    }

    /**
     * Get all unique tags from blog posts
     */
    private function getAllTags(): array
    {
        $tags = [];

        foreach ($this->collection as $post) {
            if ($post->tags && is_array($post->tags)) {
                $tags = array_merge($tags, $post->tags);
            }
        }

        return array_values(array_unique($tags));
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
            'message' => 'Blog posts retrieved successfully',
        ];
    }
}
