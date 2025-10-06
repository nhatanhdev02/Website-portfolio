<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tags = [
            'Laravel', 'PHP', 'JavaScript', 'Vue.js', 'React', 'Node.js',
            'Web Development', 'Mobile Development', 'API Development',
            'Tutorial', 'Tips', 'Best Practices', 'Performance', 'Security',
            'Database', 'MySQL', 'MongoDB', 'Redis', 'Docker', 'AWS'
        ];

        $blogTopics = [
            [
                'title_vi' => 'Hướng dẫn xây dựng API với Laravel',
                'title_en' => 'Building APIs with Laravel Tutorial',
                'excerpt_vi' => 'Tìm hiểu cách xây dựng RESTful API mạnh mẽ và bảo mật với Laravel framework.',
                'excerpt_en' => 'Learn how to build powerful and secure RESTful APIs with Laravel framework.',
                'tags' => ['Laravel', 'PHP', 'API Development', 'Tutorial']
            ],
            [
                'title_vi' => 'Tối ưu hiệu suất ứng dụng Vue.js',
                'title_en' => 'Optimizing Vue.js Application Performance',
                'excerpt_vi' => 'Các kỹ thuật và best practices để tối ưu hiệu suất ứng dụng Vue.js.',
                'excerpt_en' => 'Techniques and best practices for optimizing Vue.js application performance.',
                'tags' => ['Vue.js', 'JavaScript', 'Performance', 'Best Practices']
            ],
            [
                'title_vi' => 'Bảo mật ứng dụng web hiện đại',
                'title_en' => 'Modern Web Application Security',
                'excerpt_vi' => 'Tổng quan về các mối đe dọa bảo mật và cách bảo vệ ứng dụng web.',
                'excerpt_en' => 'Overview of security threats and how to protect web applications.',
                'tags' => ['Security', 'Web Development', 'Best Practices']
            ]
        ];

        $topic = fake()->optional(0.6)->randomElement($blogTopics);

        if ($topic) {
            $title_vi = $topic['title_vi'];
            $title_en = $topic['title_en'];
            $excerpt_vi = $topic['excerpt_vi'];
            $excerpt_en = $topic['excerpt_en'];
            $selectedTags = $topic['tags'];
        } else {
            $title_vi = fake()->sentence();
            $title_en = fake()->sentence();
            $excerpt_vi = fake()->paragraph();
            $excerpt_en = fake()->paragraph();
            $selectedTags = fake()->randomElements($tags, fake()->numberBetween(2, 5));
        }

        return [
            'title_vi' => $title_vi,
            'title_en' => $title_en,
            'content_vi' => $this->generateMarkdownContent(),
            'content_en' => $this->generateMarkdownContent(),
            'excerpt_vi' => $excerpt_vi,
            'excerpt_en' => $excerpt_en,
            'thumbnail' => fake()->imageUrl(800, 400, 'technology', true, 'Blog'),
            'status' => fake()->randomElement(['draft', 'published']),
            'published_at' => fake()->optional(0.7)->dateTimeBetween('-6 months', 'now'),
            'tags' => $selectedTags,
        ];
    }

    /**
     * Generate realistic markdown content for blog posts.
     */
    private function generateMarkdownContent(): string
    {
        $sections = [
            "## Giới thiệu\n\n" . fake()->paragraphs(2, true),
            "## Cài đặt và thiết lập\n\n```bash\nnpm install\n```\n\n" . fake()->paragraph(),
            "## Ví dụ code\n\n```php\n<?php\necho 'Hello World';\n```\n\n" . fake()->paragraph(),
            "## Kết luận\n\n" . fake()->paragraph(),
        ];

        return implode("\n\n", fake()->randomElements($sections, fake()->numberBetween(3, 4)));
    }

    /**
     * Indicate that the blog post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the blog post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Create a recent published post.
     */
    public function recentPublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a scheduled post (published in future).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Create post with specific tags.
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => $tags,
        ]);
    }

    /**
     * Create Laravel tutorial post.
     */
    public function laravelTutorial(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_vi' => 'Hướng dẫn Laravel: ' . fake()->sentence(3),
            'title_en' => 'Laravel Tutorial: ' . fake()->sentence(3),
            'tags' => ['Laravel', 'PHP', 'Tutorial', 'Web Development'],
        ]);
    }
}
