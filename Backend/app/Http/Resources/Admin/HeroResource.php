<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeroResource extends JsonResource
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
            'greeting' => [
                'vi' => $this->greeting_vi,
                'en' => $this->greeting_en,
            ],
            'name' => $this->name,
            'title' => [
                'vi' => $this->title_vi,
                'en' => $this->title_en,
            ],
            'subtitle' => [
                'vi' => $this->subtitle_vi,
                'en' => $this->subtitle_en,
            ],
            'cta' => [
                'text' => [
                    'vi' => $this->cta_text_vi,
                    'en' => $this->cta_text_en,
                ],
                'link' => $this->cta_link,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
