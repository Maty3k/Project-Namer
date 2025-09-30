<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DnsLookupMetrics>
 */
class DnsLookupMetricsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batch_id' => $this->faker->uuid(),
            'domains_checked' => $this->faker->numberBetween(1, 50),
            'successful_lookups' => $this->faker->numberBetween(1, 50),
            'failed_lookups' => $this->faker->numberBetween(0, 10),
            'cache_hits' => $this->faker->numberBetween(0, 20),
            'average_lookup_time' => $this->faker->randomFloat(3, 50.0, 2000.0),
            'total_processing_time' => $this->faker->randomFloat(3, 100.0, 5000.0),
            'started_at' => $this->faker->dateTime(),
            'completed_at' => $this->faker->dateTime(),
        ];
    }
}
