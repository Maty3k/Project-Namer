<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AIModelPerformance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIModelPerformance>
 */
final class AIModelPerformanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalRequests = $this->faker->numberBetween(10, 100);
        $successfulRequests = $this->faker->numberBetween(5, $totalRequests);
        $failedRequests = $totalRequests - $successfulRequests;

        return [
            'user_id' => User::factory(),
            'model_name' => $this->faker->randomElement(['gpt-4o', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta']),
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $failedRequests,
            'average_response_time_ms' => $this->faker->numberBetween(1000, 5000),
            'total_tokens_used' => $this->faker->numberBetween(1000, 10000),
            'total_cost_cents' => $this->faker->numberBetween(100, 1000),
            'last_used_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'performance_metrics' => [
                'cache_hits' => $this->faker->numberBetween(0, 10),
                'fallback_used' => $this->faker->numberBetween(0, 5),
            ],
        ];
    }

    /**
     * Indicate the model was recently used.
     */
    public function recentlyUsed(): self
    {
        return $this->state([
            'last_used_at' => now()->subHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate the model has high success rate.
     */
    public function highSuccessRate(): self
    {
        $totalRequests = $this->faker->numberBetween(50, 100);
        $successfulRequests = $this->faker->numberBetween((int) ($totalRequests * 0.9), $totalRequests);
        
        return $this->state([
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $totalRequests - $successfulRequests,
        ]);
    }

    /**
     * Indicate the model has low success rate.
     */
    public function lowSuccessRate(): self
    {
        $totalRequests = $this->faker->numberBetween(20, 50);
        $successfulRequests = $this->faker->numberBetween(1, (int) ($totalRequests * 0.3));
        
        return $this->state([
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $totalRequests - $successfulRequests,
        ]);
    }
}