<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogoColorVariant>
 */
final class LogoColorVariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = LogoColorVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 'royal_purple'];

        return [
            'generated_logo_id' => GeneratedLogo::factory(),
            'color_scheme' => $this->faker->randomElement($colorSchemes),
            'file_path' => 'logos/'.$this->faker->numberBetween(1, 1000).'/customized/'.$this->faker->slug().'.svg',
            'file_size' => $this->faker->numberBetween(5000, 50000),
        ];
    }

    /**
     * Create variant for specific color scheme.
     */
    public function colorScheme(string $scheme): static
    {
        return $this->state(fn (array $attributes) => [
            'color_scheme' => $scheme,
        ]);
    }
}
