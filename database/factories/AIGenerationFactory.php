<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIGeneration>
 */
final class AIGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'generation_session_id' => 'session_' . $this->faker->uuid(),
            'models_requested' => $this->faker->randomElements(['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'], 2),
            'generation_mode' => $this->faker->randomElement(['creative', 'professional', 'brandable', 'tech-focused']),
            'deep_thinking' => $this->faker->boolean(30),
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'prompt_used' => $this->faker->sentence(),
            'results_data' => [
                'names' => $this->faker->words(5),
                'metadata' => ['model' => 'gpt-4o'],
            ],
            'execution_metadata' => [
                'total_time_ms' => $this->faker->numberBetween(1000, 5000),
                'cache_hits' => $this->faker->numberBetween(0, 3),
            ],
            'total_names_generated' => $this->faker->numberBetween(1, 10),
            'total_response_time_ms' => $this->faker->numberBetween(1000, 5000),
            'total_tokens_used' => $this->faker->numberBetween(100, 1000),
            'total_cost_cents' => $this->faker->numberBetween(10, 100),
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Indicate the generation is pending.
     */
    public function pending(): self
    {
        return $this->state([
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate the generation is running.
     */
    public function running(): self
    {
        return $this->state([
            'status' => 'running',
            'started_at' => now()->subMinutes($this->faker->numberBetween(1, 10)),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate the generation is completed.
     */
    public function completed(): self
    {
        $startTime = now()->subMinutes($this->faker->numberBetween(5, 30));
        $endTime = $startTime->copy()->addMinutes($this->faker->numberBetween(1, 5));

        return $this->state([
            'status' => 'completed',
            'started_at' => $startTime,
            'completed_at' => $endTime,
            'failed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate the generation has failed.
     */
    public function failed(): self
    {
        $startTime = now()->subMinutes($this->faker->numberBetween(5, 30));
        $failTime = $startTime->copy()->addMinutes($this->faker->numberBetween(1, 3));

        return $this->state([
            'status' => 'failed',
            'started_at' => $startTime,
            'completed_at' => null,
            'failed_at' => $failTime,
            'error_message' => $this->faker->sentence(),
        ]);
    }
}