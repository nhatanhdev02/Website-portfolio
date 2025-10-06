<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => [
                'vi' => $this->title_vi,
                'en' => $this->title_en,
            ],
            'content' => [
                'vi' => $this->content_vi,
                'en' => $this->content_en,
            ],
            'excerpt' => [
                'vi' => $this->excerpt_vi,
                'en' => $this->excerpt_en,
            ],
            'thumbnail' => [
                'url' => $this->thumbnail,
                'full_url' => $this->thumbnail ? $this->getFullImageUrl($this->thumbnail) : null,
            ],
            'status' => [
                'value' => $this->status,
                'label' => $this->getStatusLabel(),
                'is_published' => $this->status === 'published',
                'is_draft' => $this->status === 'draft',
            ],
            'publishing' => [
                'published_at' => $this->published_at?->toISOString(),
                'published_date' => $this->published_at?->format('Y-m-d'),
                'published_time' => $this->published_at?->format('H:i:s'),
                'is_scheduled' => $this->published_at && $this->published_at->isFuture(),
            ],
            'tags' => [
                'list' => $this->tags ?? [],
                'count' => count($this->tags ?? []),
                'formatted' => $this->getFormattedTags(),
            ],
            'content_stats' => [
                'content_vi_length' => strlen($this->content_vi ?? ''),
                'content_en_length' => strlen($this->content_en ?? ''),
                'excerpt_vi_length' => strlen($this->excerpt_vi ?? ''),
                'excerpt_en_length' => strlen($this->excerpt_en ?? ''),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get full image URL
     */
    private function getFullImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($image, 'http')) {
            return $image;
        }

        // If it starts with storage/, add the full URL
        if (str_starts_with($image, 'storage/')) {
            return url($image);
        }

        // Otherwise, assume it's a storage path
        return url('storage/' . $image);
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'published' => 'Published',
            'draft' => 'Draft',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted tags as a comma-separated string
     */
    private function getFormattedTags(): string
    {
        if (!$this->tags || !is_array($this->tags)) {
            return '';
        }

        return implode(', ', $this->tags);
    }
}
