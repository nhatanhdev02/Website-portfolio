<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['web', 'mobile', 'desktop', 'api', 'ecommerce', 'cms'];

        $projectTemplates = [
            'web' => [
                'technologies' => [
                    ['Laravel', 'Vue.js', 'MySQL', 'Tailwind CSS'],
                    ['React', 'Node.js', 'MongoDB', 'Express'],
                    ['PHP', 'JavaScript', 'PostgreSQL', 'Bootstrap'],
                    ['Next.js', 'TypeScript', 'Prisma', 'Tailwind CSS'],
                ],
                'titles' => [
                    ['vi' => 'Hệ thống quản lý nội dung', 'en' => 'Content Management System'],
                    ['vi' => 'Website thương mại điện tử', 'en' => 'E-commerce Website'],
                    ['vi' => 'Ứng dụng web doanh nghiệp', 'en' => 'Enterprise Web Application'],
                    ['vi' => 'Portal quản lý khách hàng', 'en' => 'Customer Management Portal'],
                ]
            ],
            'mobile' => [
                'technologies' => [
                    ['Flutter', 'Dart', 'Firebase'],
                    ['React Native', 'JavaScript', 'Redux'],
                    ['Ionic', 'Angular', 'Capacitor'],
                    ['Swift', 'iOS', 'Core Data'],
                ],
                'titles' => [
                    ['vi' => 'Ứng dụng di động thương mại', 'en' => 'Mobile Commerce App'],
                    ['vi' => 'App quản lý tài chính cá nhân', 'en' => 'Personal Finance Management App'],
                    ['vi' => 'Ứng dụng học tập trực tuyến', 'en' => 'Online Learning Mobile App'],
                    ['vi' => 'App đặt dịch vụ', 'en' => 'Service Booking App'],
                ]
            ],
            'api' => [
                'technologies' => [
                    ['Laravel', 'PHP', 'MySQL', 'Redis'],
                    ['Node.js', 'Express', 'MongoDB', 'JWT'],
                    ['Python', 'FastAPI', 'PostgreSQL', 'Docker'],
                    ['ASP.NET Core', 'C#', 'SQL Server', 'Entity Framework'],
                ],
                'titles' => [
                    ['vi' => 'API RESTful cho thương mại điện tử', 'en' => 'RESTful API for E-commerce'],
                    ['vi' => 'Microservices cho hệ thống ngân hàng', 'en' => 'Banking System Microservices'],
                    ['vi' => 'API tích hợp thanh toán', 'en' => 'Payment Integration API'],
                    ['vi' => 'Hệ thống API quản lý người dùng', 'en' => 'User Management API System'],
                ]
            ]
        ];

        $category = fake()->randomElement($categories);
        $template = $projectTemplates[$category] ?? $projectTemplates['web'];

        $title = fake()->randomElement($template['titles']);
        $technologies = fake()->randomElement($template['technologies']);

        return [
            'title_vi' => $title['vi'],
            'title_en' => $title['en'],
            'description_vi' => fake()->paragraphs(2, true),
            'description_en' => fake()->paragraphs(2, true),
            'image' => fake()->imageUrl(800, 600, 'technology', true, 'Project'),
            'link' => fake()->optional(0.7)->url(),
            'technologies' => $technologies,
            'category' => $category,
            'featured' => fake()->boolean(25), // 25% chance of being featured
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the project is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
            'order' => fake()->numberBetween(1, 10), // Featured projects get higher priority
        ]);
    }

    /**
     * Indicate that the project is not featured.
     */
    public function notFeatured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => false,
        ]);
    }

    /**
     * Create a web development project.
     */
    public function webProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'web',
            'technologies' => fake()->randomElement([
                ['Laravel', 'Vue.js', 'MySQL', 'Tailwind CSS'],
                ['React', 'Node.js', 'MongoDB', 'Express'],
                ['Next.js', 'TypeScript', 'Prisma', 'Tailwind CSS'],
            ]),
        ]);
    }

    /**
     * Create a mobile development project.
     */
    public function mobileProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'mobile',
            'technologies' => fake()->randomElement([
                ['Flutter', 'Dart', 'Firebase'],
                ['React Native', 'JavaScript', 'Redux'],
                ['Ionic', 'Angular', 'Capacitor'],
            ]),
        ]);
    }

    /**
     * Create an API project.
     */
    public function apiProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'api',
            'technologies' => fake()->randomElement([
                ['Laravel', 'PHP', 'MySQL', 'Redis'],
                ['Node.js', 'Express', 'MongoDB', 'JWT'],
                ['Python', 'FastAPI', 'PostgreSQL', 'Docker'],
            ]),
        ]);
    }

    /**
     * Create project with specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }

    /**
     * Create project with specific category.
     */
    public function withCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
