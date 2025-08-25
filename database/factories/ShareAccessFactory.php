<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Share;
use App\Models\ShareAccess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShareAccess>
 */
final class ShareAccessFactory extends Factory
{
    protected $model = ShareAccess::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'share_id' => Share::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'referrer' => $this->faker->optional(0.6)->url(),
            'accessed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create access from mobile device.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_agent' => $this->faker->randomElement([
                'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
                'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36',
            ]),
        ]);
    }

    /**
     * Create access with IPv6 address.
     */
    public function ipv6(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ip_address' => $this->faker->ipv6(),
        ]);
    }

    /**
     * Create recent access.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accessed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
