<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DomainCache;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for checking domain name availability.
 *
 * Provides functionality to check domain availability across multiple TLDs
 * with caching support and error handling.
 */
final class DomainCheckService
{
    private const SUPPORTED_TLDS = ['com', 'io', 'co', 'net'];

    private const TIMEOUT_SECONDS = 5;

    private const CACHE_HOURS = 24;

    /**
     * Check availability for a single domain.
     *
     * @param  string  $domain  The domain name to check (e.g., example.com)
     * @return array<string, mixed> Domain availability information
     */
    public function checkDomain(string $domain): array
    {
        $domain = $this->formatDomain($domain);
        $this->validateDomain($domain);

        // Check cache first
        $cached = $this->getCachedResult($domain);
        if ($cached !== null) {
            return [
                'domain' => $domain,
                'available' => $cached->available,
                'status' => $cached->available ? 'available' : 'taken',
                'cached' => true,
                'checked_at' => $cached->checked_at->toISOString(),
            ];
        }

        // Check via API
        try {
            $result = $this->checkDomainViaAPI($domain);

            // Cache the result
            $this->cacheResult($domain, $result['available']);

            return array_merge($result, ['cached' => false]);
        } catch (Exception $e) {
            Log::warning('Domain check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'domain' => $domain,
                'available' => null,
                'status' => 'error',
                'error' => $e->getMessage(),
                'cached' => false,
            ];
        }
    }

    /**
     * Check availability for multiple domains concurrently.
     *
     * @param  array<int, string>  $domains  Array of domain names to check
     * @return array<string, array<string, mixed>> Associative array with domain as key and result as value
     */
    public function checkMultipleDomains(array $domains): array
    {
        $results = [];

        foreach ($domains as $domain) {
            $results[$domain] = $this->checkDomain($domain);
        }

        return $results;
    }

    /**
     * Check domain availability for a business name across all supported TLDs.
     *
     * @param  string  $businessName  The business name without TLD
     * @return array<string, array<string, mixed>> Associative array with full domain as key and result as value
     */
    public function checkBusinessName(string $businessName): array
    {
        $businessName = $this->sanitizeBusinessName($businessName);
        $results = [];

        foreach (self::SUPPORTED_TLDS as $tld) {
            $domain = $businessName.'.'.$tld;
            $results[$domain] = $this->checkDomain($domain);
        }

        return $results;
    }

    /**
     * Clear expired cache entries.
     */
    public function clearExpiredCache(): int
    {
        $cutoff = now()->subHours(self::CACHE_HOURS);

        return DomainCache::where('checked_at', '<', $cutoff)->delete();
    }

    /**
     * Format domain name to standard format.
     */
    private function formatDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = strtolower($domain);

        // Remove protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);

        // Remove www if present
        $domain = preg_replace('#^www\.#i', '', (string) $domain);

        // Remove trailing slash
        $domain = rtrim((string) $domain, '/');

        return $domain;
    }

    /**
     * Validate domain format.
     */
    private function validateDomain(string $domain): void
    {
        if (empty($domain)) {
            throw new InvalidArgumentException('Domain name cannot be empty');
        }

        if (! str_contains($domain, '.')) {
            throw new InvalidArgumentException('Domain must include TLD');
        }

        // Check for invalid characters first
        if (preg_match('/[\s@!_]/', $domain)) {
            throw new InvalidArgumentException('Invalid domain format');
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*\.[a-z]{2,}$/i', $domain)) {
            throw new InvalidArgumentException('Invalid domain format');
        }
    }

    /**
     * Sanitize business name for domain usage.
     */
    private function sanitizeBusinessName(string $businessName): string
    {
        $businessName = strtolower(trim($businessName));

        // Remove special characters and spaces
        $businessName = preg_replace('/[^a-z0-9]/', '', $businessName);

        // Ensure it's not empty after sanitization
        if (empty($businessName)) {
            throw new InvalidArgumentException('Business name produces empty domain');
        }

        return $businessName;
    }

    /**
     * Get cached result for a domain.
     */
    private function getCachedResult(string $domain): ?DomainCache
    {
        $cutoff = now()->subHours(self::CACHE_HOURS);

        return DomainCache::where('domain', $domain)
            ->where('checked_at', '>=', $cutoff)
            ->first();
    }

    /**
     * Cache domain availability result.
     */
    private function cacheResult(string $domain, bool $available): void
    {
        DomainCache::updateOrCreate(
            ['domain' => $domain],
            [
                'available' => $available,
                'checked_at' => now(),
            ]
        );
    }

    /**
     * Check domain availability via external API.
     */
    /**
     * @return array<string, mixed>
     */
    private function checkDomainViaAPI(string $domain): array
    {
        try {
            // Using a simple WHOIS-based check as a fallback
            // In production, you would use a proper domain registrar API
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get('https://api.domainsdb.info/v1/domains/search', [
                    'domain' => $domain,
                    'zone' => 'com', // This would be dynamic based on TLD
                ]);

            if ($response->status() === 408) {
                throw new Exception('Timeout checking domain availability');
            }

            if (! $response->successful()) {
                $error = $response->json('error', 'Domain API request failed');
                throw new Exception($error);
            }

            $data = $response->json();

            // Check for expected response format
            if (! isset($data['available']) && ! isset($data['domains'])) {
                // Try alternative API check
                return $this->checkViaWhoisAPI($domain);
            }

            $available = $data['available'] ?? false;

            return [
                'domain' => $domain,
                'available' => $available,
                'status' => $available ? 'available' : 'taken',
            ];

        } catch (ConnectionException $e) {
            throw new Exception('Network error: '.$e->getMessage());
        }
    }

    /**
     * Fallback WHOIS API check.
     *
     * @return array<string, mixed>
     */
    private function checkViaWhoisAPI(string $domain): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get('https://api.whoisjson.com/v1/whois', [
                    'domain' => $domain,
                ]);

            if (! $response->successful()) {
                throw new Exception('WHOIS API failed');
            }

            $data = $response->json();

            if (! isset($data['available'])) {
                throw new Exception('Invalid response format from domain API');
            }

            $available = $data['available'] === true;

            return [
                'domain' => $domain,
                'available' => $available,
                'status' => $available ? 'available' : 'taken',
            ];

        } catch (ConnectionException $e) {
            throw new Exception('Network error: '.$e->getMessage());
        }
    }
}
