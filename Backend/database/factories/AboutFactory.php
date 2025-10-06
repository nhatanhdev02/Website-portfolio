<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\About>
 */
class AboutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skillSets = [
            'backend' => ['PHP', 'Laravel', 'Node.js', 'Python', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis'],
            'frontend' => ['JavaScript', 'TypeScript', 'Vue.js', 'React', 'Angular', 'HTML5', 'CSS3', 'Tailwind CSS'],
            'mobile' => ['Flutter', 'React Native', 'Ionic', 'Swift', 'Kotlin'],
            'devops' => ['Docker', 'AWS', 'Linux', 'Git', 'CI/CD', 'Nginx'],
        ];

        $allSkills = array_merge(...array_values($skillSets));
        $selectedSkills = fake()->randomElements($allSkills, fake()->numberBetween(8, 15));

        $experiences = [
            [
                'company' => 'Tech Solutions Ltd.',
                'position' => 'Senior Full-stack Developer',
                'duration' => '2021 - Present',
                'description' => 'Leading development of enterprise web applications using Laravel and Vue.js'
            ],
            [
                'company' => 'Digital Agency Co.',
                'position' => 'Full-stack Developer',
                'duration' => '2019 - 2021',
                'description' => 'Developed multiple client projects including e-commerce platforms and CMS systems'
            ],
            [
                'company' => 'Startup Inc.',
                'position' => 'Junior Developer',
                'duration' => '2018 - 2019',
                'description' => 'Built RESTful APIs and responsive web interfaces for startup products'
            ]
        ];

        return [
            'content_vi' => $this->generateVietnameseContent(),
            'content_en' => $this->generateEnglishContent(),
            'profile_image' => fake()->imageUrl(400, 400, 'people', true, 'Developer'),
            'skills' => $selectedSkills,
            'experience' => fake()->randomElements($experiences, fake()->numberBetween(2, 3)),
            'resume_url' => fake()->optional(0.8)->url(),
        ];
    }

    /**
     * Generate realistic Vietnamese content.
     */
    private function generateVietnameseContent(): string
    {
        $paragraphs = [
            'Tôi là một nhà phát triển Full-stack với hơn 5 năm kinh nghiệm trong việc xây dựng các ứng dụng web và mobile hiện đại.',
            'Chuyên môn của tôi bao gồm phát triển backend với Laravel/PHP, frontend với Vue.js/React, và các công nghệ cloud như AWS.',
            'Tôi đam mê tạo ra những sản phẩm công nghệ chất lượng cao, tập trung vào trải nghiệm người dùng và hiệu suất hệ thống.',
            'Với kinh nghiệm làm việc cho nhiều doanh nghiệp khác nhau, tôi hiểu rõ nhu cầu và thách thức trong việc phát triển phần mềm.'
        ];

        return implode("\n\n", fake()->randomElements($paragraphs, fake()->numberBetween(2, 4)));
    }

    /**
     * Generate realistic English content.
     */
    private function generateEnglishContent(): string
    {
        $paragraphs = [
            'I am a Full-stack Developer with over 5 years of experience building modern web and mobile applications.',
            'My expertise includes backend development with Laravel/PHP, frontend with Vue.js/React, and cloud technologies like AWS.',
            'I am passionate about creating high-quality technology products, focusing on user experience and system performance.',
            'With experience working for various businesses, I understand the needs and challenges in software development.'
        ];

        return implode("\n\n", fake()->randomElements($paragraphs, fake()->numberBetween(2, 4)));
    }

    /**
     * Create about content for Nhật Anh Dev specifically.
     */
    public function nhatAnhDev(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_vi' => 'Tôi là Nhật Anh, một Freelance Full-stack Developer với đam mê tạo ra những ứng dụng web hiện đại và thân thiện với người dùng. Với hơn 5 năm kinh nghiệm trong ngành công nghệ thông tin, tôi chuyên phát triển các giải pháp web từ frontend đến backend, đảm bảo hiệu suất cao và trải nghiệm người dùng tốt nhất.',
            'content_en' => 'I am Nhật Anh, a Freelance Full-stack Developer passionate about creating modern and user-friendly web applications. With over 5 years of experience in the IT industry, I specialize in developing web solutions from frontend to backend, ensuring high performance and the best user experience.',
            'skills' => [
                'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Vue.js', 'React', 'Node.js',
                'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'AWS', 'Git'
            ],
            'experience' => [
                [
                    'company' => 'Freelance',
                    'position' => 'Full-stack Developer',
                    'duration' => '2020 - Present',
                    'description' => 'Developing custom web applications and APIs for various clients'
                ],
                [
                    'company' => 'Tech Company',
                    'position' => 'Senior Developer',
                    'duration' => '2018 - 2020',
                    'description' => 'Led development team for enterprise web applications'
                ]
            ],
        ]);
    }

    /**
     * Create about content with specific skills.
     */
    public function withSkills(array $skills): static
    {
        return $this->state(fn (array $attributes) => [
            'skills' => $skills,
        ]);
    }

    /**
     * Create about content focused on backend development.
     */
    public function backendFocused(): static
    {
        return $this->state(fn (array $attributes) => [
            'skills' => ['PHP', 'Laravel', 'Node.js', 'Python', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Docker', 'AWS'],
        ]);
    }

    /**
     * Create about content focused on frontend development.
     */
    public function frontendFocused(): static
    {
        return $this->state(fn (array $attributes) => [
            'skills' => ['JavaScript', 'TypeScript', 'Vue.js', 'React', 'Angular', 'HTML5', 'CSS3', 'Tailwind CSS', 'Webpack', 'Vite'],
        ]);
    }
}
