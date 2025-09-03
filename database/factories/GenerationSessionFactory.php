<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GenerationSession>
 */
class GenerationSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => GenerationSession::generateSessionId(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'business_description' => $this->faker->paragraph(),
            'generation_mode' => $this->faker->randomElement(['creative', 'professional', 'brandable', 'tech']),
            'deep_thinking' => $this->faker->boolean(30),
            'requested_models' => [$this->faker->randomElement(['gpt-4', 'claude-3', 'gemini-pro'])],
            'custom_parameters' => [
                'length' => $this->faker->randomElement(['short', 'medium', 'long']),
                'style' => $this->faker->randomElement(['modern', 'classic', 'playful']),
            ],
            'results' => null,
            'execution_metadata' => null,
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'current_step' => $this->faker->sentence(),
            'started_at' => $this->faker->optional()->dateTimeBetween('-1 hour'),
            'completed_at' => $this->faker->optional()->dateTimeBetween('-1 hour'),
            'error_message' => null,
            'generation_strategy' => $this->faker->randomElement(['parallel', 'sequential', 'hybrid']),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
            'results' => [
                'names' => [$this->faker->company(), $this->faker->company()],
                'total_generated' => 10,
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'API rate limit exceeded',
            'completed_at' => now(),
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
            'progress_percentage' => $this->faker->numberBetween(10, 90),
        ]);
    }
}
