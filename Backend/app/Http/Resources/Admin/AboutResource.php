<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutResource extends JsonResource
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
            'content' => [
                'vi' => $this->content_vi,
                'en' => $this->content_en,
            ],
            'skills' => [
                'list' => $this->skills ?? [],
                'count' => count($this->skills ?? []),
                'formatted' => $this->getFormattedSkills(),
            ],
            'statistics' => [
                'experience_years' => $this->experience_years,
                'projects_completed' => $this->projects_completed,
            ],
            'image' => [
                'url' => $this->image,
                'full_url' => $this->image ? $this->getFullImageUrl($this->image) : null,
            ],
            'content_stats' => [
                'content_vi_length' => strlen($this->content_vi ?? ''),
                'content_en_length' => strlen($this->content_en ?? ''),
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
     * Get formatted skills as a comma-separated string
     */
    private function getFormattedSkills(): string
    {
        if (!$this->skills || !is_array($this->skills)) {
            return '';
        }

        return implode(', ', $this->skills);
    }
}
