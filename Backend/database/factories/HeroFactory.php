<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hero>
 */
class HeroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $greetings = [
            ['vi' => 'Xin chào, tôi là', 'en' => 'Hello, I am'],
            ['vi' => 'Chào bạn, tôi là', 'en' => 'Hi there, I am'],
            ['vi' => 'Tôi là', 'en' => 'I am'],
        ];

        $titles = [
            ['vi' => 'Nhà phát triển Full-stack', 'en' => 'Full-stack Developer'],
            ['vi' => 'Kỹ sư phần mềm', 'en' => 'Software Engineer'],
            ['vi' => 'Nhà phát triển Web', 'en' => 'Web Developer'],
        ];

        $subtitles = [
            ['vi' => 'Tôi tạo ra những ứng dụng web hiện đại và thân thiện với người dùng', 'en' => 'I create modern and user-friendly web applications'],
            ['vi' => 'Chuyên xây dựng giải pháp công nghệ sáng tạo', 'en' => 'Specialized in building innovative technology solutions'],
            ['vi' => 'Đam mê tạo ra những sản phẩm số chất lượng cao', 'en' => 'Passionate about creating high-quality digital products'],
        ];

        $ctas = [
            ['vi' => 'Xem dự án của tôi', 'en' => 'View My Projects'],
            ['vi' => 'Khám phá portfolio', 'en' => 'Explore Portfolio'],
            ['vi' => 'Xem công việc', 'en' => 'See My Work'],
        ];

        $greeting = fake()->randomElement($greetings);
        $title = fake()->randomElement($titles);
        $subtitle = fake()->randomElement($subtitles);
        $cta = fake()->randomElement($ctas);

        return [
            'greeting_vi' => $greeting['vi'],
            'greeting_en' => $greeting['en'],
            'name' => fake()->name(),
            'title_vi' => $title['vi'],
            'title_en' => $title['en'],
            'subtitle_vi' => $subtitle['vi'],
            'subtitle_en' => $subtitle['en'],
            'cta_text_vi' => $cta['vi'],
            'cta_text_en' => $cta['en'],
            'cta_link' => fake()->randomElement(['#projects', '#portfolio', '#work']),
        ];
    }

    /**
     * Create hero content for Nhật Anh Dev specifically.
     */
    public function nhatAnhDev(): static
    {
        return $this->state(fn (array $attributes) => [
            'greeting_vi' => 'Xin chào, tôi là',
            'greeting_en' => 'Hello, I am',
            'name' => 'Nhật Anh',
            'title_vi' => 'Freelance Full-stack Developer',
            'title_en' => 'Freelance Full-stack Developer',
            'subtitle_vi' => 'Tôi tạo ra những ứng dụng web hiện đại và thân thiện với người dùng',
            'subtitle_en' => 'I create modern and user-friendly web applications',
            'cta_text_vi' => 'Xem dự án của tôi',
            'cta_text_en' => 'View My Projects',
            'cta_link' => '#projects',
        ]);
    }
}
