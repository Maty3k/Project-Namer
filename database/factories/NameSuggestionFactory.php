<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NameSuggestion>
 */
final class NameSuggestionFactory extends Factory
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
            'name' => $this->faker->company(),
            'domains' => [
                ['extension' => '.com', 'available' => $this->faker->boolean()],
                ['extension' => '.io', 'available' => $this->faker->boolean()],
            ],
            'logos' => null,
            'is_hidden' => false,
            'generation_metadata' => [
                'ai_model' => 'gpt-4',
                'temperature' => 0.7,
            ],
        ];
    }

    /**
     * Indicate that the name suggestion is hidden.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NameSuggestion>
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    /**
     * Indicate that the name suggestion has logos.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NameSuggestion>
     */
    public function withLogos(): static
    {
        return $this->state(fn (array $attributes) => [
            'logos' => [
                ['url' => $this->faker->imageUrl(), 'style' => 'modern'],
                ['url' => $this->faker->imageUrl(), 'style' => 'minimalist'],
            ],
        ]);
    }
}
