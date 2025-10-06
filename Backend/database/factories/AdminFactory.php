<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'), // Default password for testing
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the admin has never logged in.
     */
    public function neverLoggedIn(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_login_at' => null,
        ]);
    }

    /**
     * Indicate that the admin has logged in recently.
     */
    public function recentLogin(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_login_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create admin with specific credentials for testing.
     */
    public function withCredentials(string $username, string $email, string $password = 'password123'): static
    {
        return $this->state(fn(array $attributes) => [
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}
