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
            'style' => $this->faker->randomElement($styles),
            'variation_number' => $this->faker->numberBetween(1, 3),
            'prompt_used' => $this->faker->sentence(12),
            'original_file_path' => 'logos/'.$this->faker->numberBetween(1, 1000).'/originals/'.$this->faker->slug().'.png',
            'file_size' => $this->faker->numberBetween(50000, 500000),
            'image_width' => 1024,
            'image_height' => 1024,
            'generation_time_ms' => $this->faker->numberBetween(15000, 45000),
            'api_image_url' => $this->faker->imageUrl(1024, 1024, 'logo'),
        ];
    }

    /**
     * Indicate that the logo has a local file.
     */
    public function withFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_file_path' => 'logos/'.$this->faker->numberBetween(1, 1000).'/originals/'.$this->faker->slug().'.png',
            'file_size' => $this->faker->numberBetween(50000, 500000),
        ]);
    }

    /**
     * Indicate that the logo download failed.
     */
    public function downloadFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_file_path' => null,
            'file_size' => 0,
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
