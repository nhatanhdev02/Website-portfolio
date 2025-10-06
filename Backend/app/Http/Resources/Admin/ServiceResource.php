<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'icon' => $this->icon,
            'styling' => [
                'color' => $this->color,
                'bg_color' => $this->bg_color,
                'is_valid_color' => $this->isValidHexColor($this->color),
                'is_valid_bg_color' => $this->isValidHexColor($this->bg_color),
            ],
            'order' => $this->order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Check if a color is a valid hex color
     */
    private function isValidHexColor(?string $color): bool
    {
        if (!$color) {
            return false;
        }

        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
}
