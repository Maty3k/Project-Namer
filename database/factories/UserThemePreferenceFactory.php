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
            'theme_name' => $this->faker->randomElement([
                'default', 'dark', 'ocean', 'sunset', 'forest',
                'cosmic-violet', 'coral-reef', 'midnight-teal',
                'summer', 'winter', 'halloween', 'spring', 'autumn',
                'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
                'lime-punch', 'gold-rush', 'matrix-green',
            ]),
            'is_dark_mode' => $this->faker->boolean(),
            'border_radius' => $this->faker->randomElement(['none', 'small', 'medium', 'large', 'full']),
            'font_size' => $this->faker->randomElement(['small', 'medium', 'large']),
            'compact_mode' => $this->faker->boolean(25),
        ];
    }

    public function defaultTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme_name' => 'default',
            'is_dark_mode' => false,
        ]);
    }

    public function darkMode(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dark_mode' => true,
        ]);
    }

    public function lightMode(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dark_mode' => false,
        ]);
    }
}
