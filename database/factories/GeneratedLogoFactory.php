<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeneratedLogo>
 */
final class GeneratedLogoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = GeneratedLogo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $styles = ['minimalist', 'modern', 'playful', 'corporate'];

        return [
            'logo_generation_id' => LogoGeneration::factory(),
            'file_path' => 'logos/'.$this->faker->numberBetween(1, 1000).'/'.$this->faker->slug().'.png',
            'style' => $this->faker->randomElement($styles),
            'prompt' => $this->faker->sentence(12),
            'status' => 'completed',
        ];
    }

    /**
     * Indicate that the logo is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'file_path' => null,
        ]);
    }

    /**
     * Indicate that the logo is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'file_path' => null,
        ]);
    }

    /**
     * Indicate that the logo generation failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'file_path' => null,
            'error_message' => 'Failed to generate logo',
        ]);
    }

    /**
     * Create a logo for a specific style.
     */
    public function style(string $style): static
    {
        return $this->state(fn (array $attributes) => [
            'style' => $style,
        ]);
    }
}
