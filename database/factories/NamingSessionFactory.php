<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NamingSession>
 */
class NamingSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = NamingSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessTypes = [
            'An AI-powered productivity tool for remote teams',
            'A sustainable fashion marketplace for eco-conscious consumers',
            'A mobile app for learning new languages through immersion',
            'A cloud-based accounting software for small businesses',
            'A social platform connecting local artists with venues',
            'A subscription box service for gourmet coffee lovers',
            'A fitness tracking app with personalized coaching',
            'A marketplace for handmade artisanal products',
            'A project management tool for creative agencies',
            'A platform connecting freelancers with startups',
        ];

        $modes = ['creative', 'professional', 'brandable', 'tech'];

        return [
            'user_id' => User::factory(),
            'title' => null, // Will be auto-generated from business_description
            'business_description' => fake()->randomElement($businessTypes),
            'generation_mode' => fake()->randomElement($modes),
            'deep_thinking' => fake()->boolean(20), // 20% chance of being true
            'is_starred' => fake()->boolean(10), // 10% chance of being starred
            'is_active' => true,
            'last_accessed_at' => fake()->optional(0.3)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the session is starred.
     */
    public function starred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_starred' => true,
        ]);
    }

    /**
     * Indicate that the session is not active.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the session was recently accessed.
     */
    public function recentlyAccessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_accessed_at' => now(),
        ]);
    }

    /**
     * Indicate that the session uses deep thinking mode.
     */
    public function withDeepThinking(): static
    {
        return $this->state(fn (array $attributes) => [
            'deep_thinking' => true,
        ]);
    }
}
