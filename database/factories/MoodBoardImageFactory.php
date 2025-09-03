<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MoodBoard;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoodBoardImage>
 */
class MoodBoardImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mood_board_id' => MoodBoard::factory(),
            'project_image_id' => ProjectImage::factory(),
            'position' => $this->faker->numberBetween(1, 20),
            'x_position' => $this->faker->optional()->numberBetween(0, 1200),
            'y_position' => $this->faker->optional()->numberBetween(0, 800),
            'width' => $this->faker->optional()->numberBetween(100, 500),
            'height' => $this->faker->optional()->numberBetween(100, 400),
            'z_index' => $this->faker->numberBetween(1, 10),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function positioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'x_position' => $this->faker->numberBetween(50, 1000),
            'y_position' => $this->faker->numberBetween(50, 700),
            'width' => $this->faker->numberBetween(200, 400),
            'height' => $this->faker->numberBetween(150, 300),
        ]);
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $this->faker->sentence(),
        ]);
    }
}
