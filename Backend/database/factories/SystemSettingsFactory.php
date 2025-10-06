<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemSettings>
 */
class SystemSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settingTemplates = [
            [
                'key' => 'site_name',
                'value' => fake()->randomElement([
                    'Nhật Anh Dev Portfolio',
                    'Developer Portfolio',
                    'Full-stack Developer Hub'
                ]),
                'type' => 'string',
                'description' => 'Website name displayed in header and title'
            ],
            [
                'key' => 'default_language',
                'value' => fake()->randomElement(['vi', 'en']),
                'type' => 'string',
                'description' => 'Default language for the website'
            ],
            [
                'key' => 'maintenance_mode',
                'value' => fake()->randomElement(['0', '1']),
                'type' => 'boolean',
                'description' => 'Enable/disable maintenance mode'
            ],
            [
                'key' => 'theme_colors',
                'value' => json_encode([
                    'primary' => fake()->hexColor(),
                    'secondary' => fake()->hexColor(),
                    'accent' => fake()->hexColor()
                ]),
                'type' => 'json',
                'description' => 'Theme color palette for the website'
            ],
            [
                'key' => 'contact_email',
                'value' => fake()->companyEmail(),
                'type' => 'string',
                'description' => 'Primary contact email address'
            ],
            [
                'key' => 'analytics_id',
                'value' => 'GA-' . fake()->randomNumber(8),
                'type' => 'string',
                'description' => 'Google Analytics tracking ID'
            ],
            [
                'key' => 'max_upload_size',
                'value' => fake()->randomElement(['5', '10', '20']),
                'type' => 'integer',
                'description' => 'Maximum file upload size in MB'
            ],
            [
                'key' => 'cache_duration',
                'value' => fake()->randomElement(['3600', '7200', '86400']),
                'type' => 'integer',
                'description' => 'Cache duration in seconds'
            ]
        ];

        $setting = fake()->randomElement($settingTemplates);

        return [
            'key' => $setting['key'],
            'value' => $setting['value'],
            'type' => $setting['type'],
            'description' => $setting['description'],
        ];
    }

    /**
     * Create a string type setting.
     */
    public function stringType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'string',
            'value' => fake()->sentence(),
        ]);
    }

    /**
     * Create a boolean type setting.
     */
    public function booleanType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->randomElement(['0', '1']),
        ]);
    }

    /**
     * Create an integer type setting.
     */
    public function integerType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 100),
        ]);
    }

    /**
     * Create a JSON type setting.
     */
    public function jsonType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'json',
            'value' => json_encode([
                'option1' => fake()->word(),
                'option2' => fake()->word(),
                'enabled' => fake()->boolean(),
            ]),
        ]);
    }

    /**
     * Create site configuration settings.
     */
    public function siteConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => fake()->randomElement([
                'site_name',
                'site_description',
                'site_keywords',
                'contact_email',
                'default_language'
            ]),
            'type' => 'string',
        ]);
    }

    /**
     * Create theme settings.
     */
    public function themeSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'theme_colors',
            'value' => json_encode([
                'primary' => '#FF6B6B',
                'secondary' => '#4ECDC4',
                'accent' => '#45B7D1',
                'background' => '#FFFFFF',
                'text' => '#333333'
            ]),
            'type' => 'json',
            'description' => 'Complete theme color palette',
        ]);
    }
}
