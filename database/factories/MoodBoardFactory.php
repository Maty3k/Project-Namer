<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoodBoard>
 */
class MoodBoardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true).' Board',
            'description' => $this->faker->optional()->sentence(),
            'layout_type' => $this->faker->randomElement(['grid', 'collage', 'masonry', 'freeform']),
            'layout_config' => [
                'columns' => $this->faker->numberBetween(2, 6),
                'spacing' => $this->faker->numberBetween(10, 30),
            ],
            'is_public' => $this->faker->boolean(20),
            'share_token' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function withLayoutType(string $layoutType): static
    {
        return $this->state(fn (array $attributes) => [
            'layout_type' => $layoutType,
        ]);
    }
}
