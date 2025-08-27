<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Share>
 */
final class ShareFactory extends Factory
{
    protected $model = Share::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => LogoGeneration::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->words(4, true),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'share_type' => $this->faker->randomElement(['public', 'password_protected']),
            'password_hash' => null,
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'view_count' => $this->faker->numberBetween(0, 100),
            'last_viewed_at' => $this->faker->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'is_active' => true,
            'settings' => $this->faker->optional(0.5)->randomElements([
                'theme' => $this->faker->randomElement(['light', 'dark']),
                'layout' => $this->faker->randomElement(['list', 'grid']),
                'show_domains' => $this->faker->boolean(),
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    /**
     * Create a public share.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'share_type' => 'public',
            'password_hash' => null,
        ]);
    }

    /**
     * Create a password-protected share.
     */
    public function passwordProtected(?string $password = 'secret123'): static
    {
        return $this->state(fn (array $attributes): array => [
            'share_type' => 'password_protected',
            'password' => $password,
        ]);
    }

    /**
     * Create an expired share.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create an inactive share.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a recently accessed share.
     */
    public function recentlyAccessed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'view_count' => $this->faker->numberBetween(5, 50),
            'last_viewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a share with custom settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes): array => [
            'settings' => $settings,
        ]);
    }
}
