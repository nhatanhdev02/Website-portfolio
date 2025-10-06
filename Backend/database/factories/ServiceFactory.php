<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            [
                'title_vi' => 'Phát triển Web',
                'title_en' => 'Web Development',
                'description_vi' => 'Tạo ra các ứng dụng web hiện đại và responsive với công nghệ tiên tiến',
                'description_en' => 'Create modern and responsive web applications with cutting-edge technology',
                'icon' => 'web-icon',
                'color' => '#FF6B6B',
                'bg_color' => '#FFE5E5'
            ],
            [
                'title_vi' => 'Phát triển Mobile',
                'title_en' => 'Mobile Development',
                'description_vi' => 'Xây dựng ứng dụng di động đa nền tảng với hiệu suất cao',
                'description_en' => 'Build high-performance cross-platform mobile applications',
                'icon' => 'mobile-icon',
                'color' => '#4ECDC4',
                'bg_color' => '#E5F9F7'
            ],
            [
                'title_vi' => 'API Development',
                'title_en' => 'API Development',
                'description_vi' => 'Thiết kế và phát triển RESTful APIs bảo mật và có thể mở rộng',
                'description_en' => 'Design and develop secure and scalable RESTful APIs',
                'icon' => 'api-icon',
                'color' => '#45B7D1',
                'bg_color' => '#E5F4FD'
            ],
            [
                'title_vi' => 'Tư vấn Công nghệ',
                'title_en' => 'Technology Consulting',
                'description_vi' => 'Tư vấn giải pháp công nghệ phù hợp cho doanh nghiệp',
                'description_en' => 'Provide technology solutions consulting for businesses',
                'icon' => 'consulting-icon',
                'color' => '#9B59B6',
                'bg_color' => '#F4E5F7'
            ],
            [
                'title_vi' => 'Bảo trì & Hỗ trợ',
                'title_en' => 'Maintenance & Support',
                'description_vi' => 'Dịch vụ bảo trì và hỗ trợ kỹ thuật 24/7',
                'description_en' => '24/7 maintenance and technical support services',
                'icon' => 'support-icon',
                'color' => '#E67E22',
                'bg_color' => '#FDF2E9'
            ],
            [
                'title_vi' => 'UI/UX Design',
                'title_en' => 'UI/UX Design',
                'description_vi' => 'Thiết kế giao diện người dùng trực quan và thân thiện',
                'description_en' => 'Design intuitive and user-friendly interfaces',
                'icon' => 'design-icon',
                'color' => '#F39C12',
                'bg_color' => '#FEF9E7'
            ]
        ];

        $service = fake()->randomElement($services);

        return [
            'title_vi' => $service['title_vi'],
            'title_en' => $service['title_en'],
            'description_vi' => $service['description_vi'],
            'description_en' => $service['description_en'],
            'icon' => $service['icon'],
            'color' => $service['color'],
            'bg_color' => $service['bg_color'],
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Create service with specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Create web development service.
     */
    public function webDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_vi' => 'Phát triển Web',
            'title_en' => 'Web Development',
            'description_vi' => 'Tạo ra các ứng dụng web hiện đại và responsive với công nghệ tiên tiến',
            'description_en' => 'Create modern and responsive web applications with cutting-edge technology',
            'icon' => 'web-icon',
            'color' => '#FF6B6B',
            'bg_color' => '#FFE5E5',
        ]);
    }

    /**
     * Create mobile development service.
     */
    public function mobileDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_vi' => 'Phát triển Mobile',
            'title_en' => 'Mobile Development',
            'description_vi' => 'Xây dựng ứng dụng di động đa nền tảng với hiệu suất cao',
            'description_en' => 'Build high-performance cross-platform mobile applications',
            'icon' => 'mobile-icon',
            'color' => '#4ECDC4',
            'bg_color' => '#E5F9F7',
        ]);
    }

    /**
     * Create API development service.
     */
    public function apiDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_vi' => 'API Development',
            'title_en' => 'API Development',
            'description_vi' => 'Thiết kế và phát triển RESTful APIs bảo mật và có thể mở rộng',
            'description_en' => 'Design and develop secure and scalable RESTful APIs',
            'icon' => 'api-icon',
            'color' => '#45B7D1',
            'bg_color' => '#E5F4FD',
        ]);
    }
}
