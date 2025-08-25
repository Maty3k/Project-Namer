<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Export>
 */
final class ExportFactory extends Factory
{
    protected $model = Export::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exportType = $this->faker->randomElement(['pdf', 'csv', 'json']);

        return [
            'uuid' => (string) Str::uuid(),
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => LogoGeneration::factory(),
            'user_id' => User::factory(),
            'export_type' => $exportType,
            'file_path' => "exports/{$exportType}/".Str::random(40).".{$exportType}",
            'file_size' => $this->faker->numberBetween(1024, 5242880), // 1KB to 5MB
            'download_count' => $this->faker->numberBetween(0, 20),
            'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+7 days'),
        ];
    }

    /**
     * Create a PDF export.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'export_type' => 'pdf',
            'file_path' => 'exports/pdf/'.Str::random(40).'.pdf',
            'file_size' => $this->faker->numberBetween(51200, 2097152), // 50KB to 2MB
        ]);
    }

    /**
     * Create a CSV export.
     */
    public function csv(): static
    {
        return $this->state(fn (array $attributes): array => [
            'export_type' => 'csv',
            'file_path' => 'exports/csv/'.Str::random(40).'.csv',
            'file_size' => $this->faker->numberBetween(1024, 102400), // 1KB to 100KB
        ]);
    }

    /**
     * Create a JSON export.
     */
    public function json(): static
    {
        return $this->state(fn (array $attributes): array => [
            'export_type' => 'json',
            'file_path' => 'exports/json/'.Str::random(40).'.json',
            'file_size' => $this->faker->numberBetween(2048, 204800), // 2KB to 200KB
        ]);
    }

    /**
     * Create an expired export.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => $this->faker->dateTimeBetween('-7 days', '-1 day'),
        ]);
    }

    /**
     * Create a recently downloaded export.
     */
    public function recentlyDownloaded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'download_count' => $this->faker->numberBetween(5, 50),
        ]);
    }
}
