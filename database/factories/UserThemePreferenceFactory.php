<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserThemePreference>
 */
class UserThemePreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'theme_name' => $this->faker->words(2, true),
            'is_custom_theme' => $this->faker->boolean(30),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'accent_color' => $this->faker->hexColor(),
            'background_color' => $this->faker->hexColor(),
            'surface_color' => $this->faker->hexColor(),
            'text_primary_color' => $this->faker->hexColor(),
            'text_secondary_color' => $this->faker->hexColor(),
            'dark_background_color' => $this->faker->hexColor(),
            'dark_surface_color' => $this->faker->hexColor(),
            'dark_text_primary_color' => $this->faker->hexColor(),
            'dark_text_secondary_color' => $this->faker->hexColor(),
            'border_radius' => $this->faker->randomElement(['none', 'small', 'medium', 'large', 'full']),
            'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
            'compact_mode' => $this->faker->boolean(25),
            'theme_config' => null,
        ];
    }

    public function customTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom_theme' => true,
            'theme_name' => $this->faker->word().'-theme',
        ]);
    }

    public function defaultTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme_name' => 'default',
            'is_custom_theme' => false,
        ]);
    }
}
