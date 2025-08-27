<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NamingSession;
use App\Models\SessionResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionResult>
 */
class SessionResultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SessionResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'TechNova', 'CodeCraft', 'DevHub', 'ByteForge', 'CloudSync',
            'DataFlow', 'WebForce', 'AppBoost', 'DigitalCore', 'SoftEdge',
            'IdeaLab', 'StartupHub', 'VentureFlow', 'BusinessBridge', 'GrowthPath',
        ];

        $selectedNames = fake()->randomElements($names, fake()->numberBetween(3, 10));

        $domainResults = collect($selectedNames)->map(function ($name) {
            return [
                'name' => strtolower($name),
                'available' => fake()->boolean(60), // 60% chance available
                'extensions' => fake()->randomElements(['.com', '.io', '.co', '.net', '.app'], fake()->numberBetween(1, 3)),
                'checked_at' => now()->toISOString(),
            ];
        })->toArray();

        $logoSelections = fake()->boolean(40)
            ? fake()->randomElements($selectedNames, fake()->numberBetween(1, 3))
            : null;

        return [
            'session_id' => NamingSession::factory(),
            'generated_names' => $selectedNames,
            'domain_results' => $domainResults,
            'selected_for_logos' => $logoSelections,
            'generation_timestamp' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a result with all domains available.
     */
    public function withAvailableDomains(): static
    {
        return $this->state(function (array $attributes) {
            $domainResults = collect($attributes['domain_results'])->map(function ($domain) {
                return array_merge($domain, ['available' => true]);
            })->toArray();

            return [
                'domain_results' => $domainResults,
            ];
        });
    }

    /**
     * Create a result with no domains available.
     */
    public function withUnavailableDomains(): static
    {
        return $this->state(function (array $attributes) {
            $domainResults = collect($attributes['domain_results'])->map(function ($domain) {
                return array_merge($domain, ['available' => false]);
            })->toArray();

            return [
                'domain_results' => $domainResults,
            ];
        });
    }

    /**
     * Create a result with logo selections.
     */
    public function withLogoSelections(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'selected_for_logos' => fake()->randomElements($attributes['generated_names'], 3),
            ];
        });
    }

    /**
     * Create a result with no logo selections.
     */
    public function withoutLogoSelections(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'selected_for_logos' => null,
            ];
        });
    }
}
