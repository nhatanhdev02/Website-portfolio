<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
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
            'site' => [
                'name' => $this->site_name,
                'description' => $this->site_description,
                'keywords' => $this->site_keywords,
            ],
            'language' => [
                'default' => $this->default_language,
                'available' => $this->available_languages ?? [],
                'count' => count($this->available_languages ?? []),
            ],
            'theme' => [
                'colors' => [
                    'primary' => $this->primary_color,
                    'secondary' => $this->secondary_color,
                    'accent' => $this->accent_color,
                    'background' => $this->background_color,
                    'text' => $this->text_color,
                ],
                'dark_mode' => (bool) $this->dark_mode,
                'color_validation' => [
                    'primary_valid' => $this->isValidHexColor($this->primary_color),
                    'secondary_valid' => $this->isValidHexColor($this->secondary_color),
                    'accent_valid' => $this->isValidHexColor($this->accent_color),
                    'background_valid' => $this->isValidHexColor($this->background_color),
                    'text_valid' => $this->isValidHexColor($this->text_color),
                ],
            ],
            'maintenance' => [
                'enabled' => (bool) $this->maintenance_mode,
                'message' => $this->maintenance_message,
            ],
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
                'social_image' => $this->social_image,
                'analytics_code' => $this->analytics_code,
            ],
            'contact' => [
                'email' => $this->contact_email,
                'admin_email' => $this->admin_email,
                'email_validation' => [
                    'contact_valid' => $this->isValidEmail($this->contact_email),
                    'admin_valid' => $this->isValidEmail($this->admin_email),
                ],
            ],
            'system' => [
                'items_per_page' => $this->items_per_page,
                'session_timeout' => $this->session_timeout,
                'file_upload_max_size' => $this->file_upload_max_size,
                'allowed_file_types' => $this->allowed_file_types ?? [],
            ],
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

    /**
     * Check if email is valid
     */
    private function isValidEmail(?string $email): bool
    {
        if (!$email) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
