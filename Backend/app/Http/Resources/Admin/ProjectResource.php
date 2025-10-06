<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'description' => [
                'vi' => $this->description_vi,
                'en' => $this->description_en,
            ],
            'image' => [
                'url' => $this->image,
                'full_url' => $this->image ? $this->getFullImageUrl($this->image) : null,
            ],
            'link' => $this->link,
            'technologies' => [
                'list' => $this->technologies ?? [],
                'count' => count($this->technologies ?? []),
                'formatted' => $this->getFormattedTechnologies(),
            ],
            'category' => $this->category,
            'featured' => (bool) $this->featured,
            'order' => $this->order,
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
     * Get formatted technologies as a comma-separated string
     */
    private function getFormattedTechnologies(): string
    {
        if (!$this->technologies || !is_array($this->technologies)) {
            return '';
        }

        return implode(', ', $this->technologies);
    }
}
