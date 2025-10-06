<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactInfo>
 */
class ContactInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessHours = [
            'Monday - Friday: 9:00 AM - 6:00 PM',
            'Monday - Friday: 8:00 AM - 5:00 PM, Saturday: 9:00 AM - 1:00 PM',
            'Available 24/7 for urgent projects',
            'Monday - Saturday: 9:00 AM - 7:00 PM',
        ];

        return [
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'social_links' => [
                'facebook' => 'https://facebook.com/' . fake()->userName(),
                'twitter' => 'https://twitter.com/' . fake()->userName(),
                'linkedin' => 'https://linkedin.com/in/' . fake()->userName(),
                'github' => 'https://github.com/' . fake()->userName(),
                'instagram' => 'https://instagram.com/' . fake()->userName(),
                'youtube' => fake()->optional(0.5)->passthrough('https://youtube.com/@' . fake()->userName()),
                'telegram' => fake()->optional(0.7)->passthrough('https://t.me/' . fake()->userName()),
            ],
            'business_hours' => fake()->randomElement($businessHours),
        ];
    }

    /**
     * Create contact info for Nhật Anh Dev specifically.
     */
    public function nhatAnhDev(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => 'contact@nhatanhdev.com',
            'phone' => '+84 123 456 789',
            'address' => 'Ho Chi Minh City, Vietnam',
            'social_links' => [
                'facebook' => 'https://facebook.com/nhatanhdev',
                'twitter' => 'https://twitter.com/nhatanhdev',
                'linkedin' => 'https://linkedin.com/in/nhatanhdev',
                'github' => 'https://github.com/nhatanhdev',
                'instagram' => 'https://instagram.com/nhatanhdev',
                'telegram' => 'https://t.me/nhatanhdev',
            ],
            'business_hours' => 'Monday - Friday: 9:00 AM - 6:00 PM (GMT+7)',
        ]);
    }

    /**
     * Create contact info with minimal social links.
     */
    public function minimalSocial(): static
    {
        return $this->state(fn (array $attributes) => [
            'social_links' => [
                'linkedin' => 'https://linkedin.com/in/' . fake()->userName(),
                'github' => 'https://github.com/' . fake()->userName(),
            ],
        ]);
    }

    /**
     * Create contact info with all social platforms.
     */
    public function allSocialPlatforms(): static
    {
        return $this->state(fn (array $attributes) => [
            'social_links' => [
                'facebook' => 'https://facebook.com/' . fake()->userName(),
                'twitter' => 'https://twitter.com/' . fake()->userName(),
                'linkedin' => 'https://linkedin.com/in/' . fake()->userName(),
                'github' => 'https://github.com/' . fake()->userName(),
                'instagram' => 'https://instagram.com/' . fake()->userName(),
                'youtube' => 'https://youtube.com/@' . fake()->userName(),
                'telegram' => 'https://t.me/' . fake()->userName(),
                'discord' => 'https://discord.gg/' . fake()->userName(),
                'tiktok' => 'https://tiktok.com/@' . fake()->userName(),
            ],
        ]);
    }
}
