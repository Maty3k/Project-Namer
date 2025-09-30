<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DnsLookupCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DnsLookupCache>
 */
final class DnsLookupCacheFactory extends Factory
{
    protected $model = DnsLookupCache::class;

    public function definition(): array
    {
        $tlds = ['com', 'io', 'org', 'net', 'co', 'app'];
        $hasRecords = $this->faker->boolean(70); // 70% chance of having records

        return [
            'domain' => $this->faker->domainWord(),
            'tld' => $this->faker->randomElement($tlds),
            'has_records' => $hasRecords,
            'record_types' => $hasRecords ? $this->faker->randomElements(['A', 'AAAA', 'CNAME', 'MX', 'NS'], $this->faker->numberBetween(1, 3)) : null,
            'error_message' => $this->faker->boolean(10) ? $this->faker->sentence() : null, // 10% chance of error
            'checked_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ];
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
            ];
        });
    }

    public function withRecords(array $recordTypes = ['A']): static
    {
        return $this->state(function (array $attributes) use ($recordTypes) {
            return [
                'has_records' => true,
                'record_types' => $recordTypes,
                'error_message' => null,
            ];
        });
    }

    public function withoutRecords(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'has_records' => false,
                'record_types' => null,
                'error_message' => null,
            ];
        });
    }

    public function withError(?string $error = null): static
    {
        return $this->state(function (array $attributes) use ($error) {
            return [
                'has_records' => false,
                'record_types' => null,
                'error_message' => $error ?? $this->faker->sentence(),
            ];
        });
    }

    public function forDomain(string $domain, string $tld): static
    {
        return $this->state(function (array $attributes) use ($domain, $tld) {
            return [
                'domain' => $domain,
                'tld' => $tld,
            ];
        });
    }
}
