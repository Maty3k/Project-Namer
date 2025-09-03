<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GenerationSession;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImageGenerationContext>
 */
class ImageGenerationContextFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visionAnalysis = [
            'colors' => $this->faker->randomElements(
                ['red', 'blue', 'green', 'yellow', 'purple', 'orange', 'black', 'white'],
                $this->faker->numberBetween(1, 4)
            ),
            'style' => $this->faker->randomElement(['modern', 'vintage', 'minimalist', 'bold', 'elegant']),
            'objects' => $this->faker->randomElements(
                ['logo', 'text', 'icon', 'illustration', 'photo', 'pattern'],
                $this->faker->numberBetween(1, 3)
            ),
            'mood' => $this->faker->randomElement(['professional', 'playful', 'serious', 'creative']),
            'complexity' => $this->faker->randomElement(['simple', 'moderate', 'complex']),
        ];

        return [
            'project_image_id' => ProjectImage::factory(),
            'generation_session_id' => GenerationSession::factory(),
            'generation_type' => $this->faker->randomElement(['name', 'logo']),
            'vision_analysis' => $visionAnalysis,
            'influence_score' => $this->faker->randomFloat(2, 0.1, 1.0),
        ];
    }

    public function nameGeneration(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_type' => 'name',
        ]);
    }

    public function logoGeneration(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_type' => 'logo',
        ]);
    }

    public function highInfluence(): static
    {
        return $this->state(fn (array $attributes) => [
            'influence_score' => $this->faker->randomFloat(2, 0.7, 1.0),
        ]);
    }

    public function lowInfluence(): static
    {
        return $this->state(fn (array $attributes) => [
            'influence_score' => $this->faker->randomFloat(2, 0.1, 0.5),
        ]);
    }
}
