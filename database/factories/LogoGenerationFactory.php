<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LogoGeneration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogoGeneration>
 */
final class LogoGenerationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = LogoGeneration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'session_id' => $this->faker->uuid(),
            'business_name' => $this->faker->sentence(6),
            'status' => 'pending',
            'total_logos_requested' => 12,
            'logos_completed' => 0,
            'api_provider' => 'openai',
            'cost_cents' => 0,
        ];
    }

    /**
     * Indicate that the generation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'logos_completed' => $attributes['total_logos_requested'],
            'cost_cents' => $attributes['total_logos_requested'] * 400, // $4 per logo
        ]);
    }

    /**
     * Indicate that the generation is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'logos_completed' => $this->faker->numberBetween(1, $attributes['total_logos_requested'] - 1),
        ]);
    }

    /**
     * Indicate that the generation has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'logos_completed' => $this->faker->numberBetween(0, $attributes['total_logos_requested'] / 2),
        ]);
    }
}
