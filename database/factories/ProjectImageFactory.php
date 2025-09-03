<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectImage>
 */
class ProjectImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->word().'-'.$this->faker->randomNumber(4).'.jpg';

        return [
            'uuid' => (string) Str::uuid(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'original_filename' => $filename,
            'stored_filename' => $filename,
            'file_path' => 'images/'.$filename,
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'mime_type' => 'image/jpeg',
            'width' => $this->faker->numberBetween(100, 2000),
            'height' => $this->faker->numberBetween(100, 2000),
            'title' => $this->faker->optional()->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'tags' => [$this->faker->word(), $this->faker->word()],
            'processing_status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'thumbnail_path' => 'thumbnails/'.$filename,
            'dominant_colors' => [
                $this->faker->hexColor(),
                $this->faker->hexColor(),
                $this->faker->hexColor(),
            ],
            'is_public' => $this->faker->boolean(20),
            'aspect_ratio' => $this->faker->randomFloat(2, 0.5, 3.0),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'pending',
        ]);
    }

    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => $tags,
        ]);
    }
}
