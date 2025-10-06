<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactInfoResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'social_links' => [
                'linkedin' => [
                    'url' => $this->linkedin,
                    'is_valid' => $this->isValidUrl($this->linkedin),
                ],
                'github' => [
                    'url' => $this->github,
                    'is_valid' => $this->isValidUrl($this->github),
                ],
                'facebook' => [
                    'url' => $this->facebook,
                    'is_valid' => $this->isValidUrl($this->facebook),
                ],
                'twitter' => [
                    'url' => $this->twitter,
                    'is_valid' => $this->isValidUrl($this->twitter),
                ],
                'instagram' => [
                    'url' => $this->instagram,
                    'is_valid' => $this->isValidUrl($this->instagram),
                ],
                'website' => [
                    'url' => $this->website,
                    'is_valid' => $this->isValidUrl($this->website),
                ],
            ],
            'validation' => [
                'email_is_valid' => filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false,
                'has_phone' => !empty($this->phone),
                'has_address' => !empty($this->address),
                'social_links_count' => $this->getSocialLinksCount(),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Check if URL is valid
     */
    private function isValidUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Count non-empty social links
     */
    private function getSocialLinksCount(): int
    {
        $links = [
            $this->linkedin,
            $this->github,
            $this->facebook,
            $this->twitter,
            $this->instagram,
            $this->website,
        ];

        return count(array_filter($links, fn($link) => !empty($link)));
    }
}
