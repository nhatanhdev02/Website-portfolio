<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messageTemplates = [
            [
                'subject' => 'Yêu cầu báo giá dự án website',
                'message' => 'Chào bạn, tôi muốn tìm hiểu về dịch vụ phát triển website. Dự án của tôi cần...'
            ],
            [
                'subject' => 'Hợp tác phát triển ứng dụng mobile',
                'message' => 'Xin chào, công ty chúng tôi đang tìm kiếm đối tác để phát triển ứng dụng mobile...'
            ],
            [
                'subject' => 'Tư vấn giải pháp công nghệ',
                'message' => 'Chào anh/chị, tôi cần tư vấn về giải pháp công nghệ phù hợp cho doanh nghiệp...'
            ],
            [
                'subject' => 'Project Inquiry - E-commerce Platform',
                'message' => 'Hello, I am interested in developing an e-commerce platform. Could you please provide...'
            ],
            [
                'subject' => 'API Development Services',
                'message' => 'Hi there, we need to develop RESTful APIs for our mobile application. Would you be able to...'
            ],
            [
                'subject' => 'Website Maintenance Request',
                'message' => 'Good day, we have an existing website that needs regular maintenance and updates...'
            ]
        ];

        $template = fake()->optional(0.7)->randomElement($messageTemplates);

        if ($template) {
            $subject = $template['subject'];
            $message = $template['message'] . ' ' . fake()->paragraphs(2, true);
        } else {
            $subject = fake()->sentence();
            $message = fake()->paragraphs(3, true);
        }

        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => $subject,
            'message' => $message,
            'read_at' => fake()->optional(0.6)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the message is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the message is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create a recent unread message.
     */
    public function recentUnread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create an urgent message.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => 'URGENT: ' . fake()->sentence(),
            'read_at' => null,
        ]);
    }

    /**
     * Create a business inquiry message.
     */
    public function businessInquiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => fake()->randomElement([
                'Business Partnership Opportunity',
                'Project Development Inquiry',
                'Service Quote Request',
                'Yêu cầu báo giá dự án',
                'Hợp tác phát triển dự án'
            ]),
            'message' => fake()->paragraphs(3, true),
        ]);
    }

    /**
     * Create message from specific email domain.
     */
    public function fromDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => fake()->userName() . '@' . $domain,
        ]);
    }
}
