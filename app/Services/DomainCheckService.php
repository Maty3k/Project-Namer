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
 * with caching support and error handling. Uses DNS pre-screening to filter
 * out domains with existing DNS records before making expensive API calls.
 */
final class DomainCheckService
{
    private const SUPPORTED_TLDS = ['com', 'net', 'org', 'io', 'co', 'app', 'dev', 'ai', 'tech', 'studio'];

    private const TIMEOUT_SECONDS = 5;

    private const CACHE_HOURS_API = 24;

    private const CACHE_DAYS_DNS = 7;

    /**
     * DNS lookup service for pre-screening domains.
     */
    public function __construct(
        private readonly DNSLookupService $dnsLookup
    ) {}

    /**
     * Check availability for a single domain.
     *
     * Uses DNS pre-screening to filter domains with DNS records before
     * making expensive API calls to registrars.
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

        // DNS pre-screening: Check if domain has DNS records
        $hasDNS = $this->dnsLookup->hasDNSRecords($domain);

        // If DNS records found, domain is definitely taken
        if ($hasDNS === true) {
            Log::info('Domain has DNS records - marking as taken', ['domain' => $domain]);

            $dnsRecords = $this->dnsLookup->getDNSRecords($domain);

            // Cache the DNS result
            $this->cacheResult($domain, false, 'dns', true, $dnsRecords);

            return [
                'domain' => $domain,
                'available' => false,
                'status' => 'taken',
                'cached' => false,
                'check_method' => 'dns',
            ];
        }

        // If no DNS records (or DNS check failed), fall back to API
        try {
            $result = $this->checkDomainViaAPI($domain);

            // Cache the result with DNS info
            $this->cacheResult($domain, $result['available'], 'dns', $hasDNS === false);

            return array_merge($result, ['cached' => false, 'check_method' => 'dns']);
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
     *
     * Respects different TTLs for DNS and API checks:
     * - DNS checks expire after 7 days
     * - API checks expire after 24 hours
     */
    public function clearExpiredCache(): int
    {
        $apiCutoff = now()->subHours(self::CACHE_HOURS_API);
        $dnsCutoff = now()->subDays(self::CACHE_DAYS_DNS);

        $deleted = 0;

        // Clear expired API caches
        $deleted += DomainCache::where('check_method', 'api')
            ->where('checked_at', '<', $apiCutoff)
            ->delete();

        // Clear expired DNS caches
        $deleted += DomainCache::where('check_method', 'dns')
            ->where('checked_at', '<', $dnsCutoff)
            ->delete();

        return $deleted;
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
     *
     * Respects different cache TTLs based on check method:
     * - DNS checks: 7 days (records change infrequently)
     * - API checks: 24 hours (availability can change quickly)
     */
    private function getCachedResult(string $domain): ?DomainCache
    {
        $cache = DomainCache::where('domain', $domain)->first();

        if ($cache === null) {
            return null;
        }

        // Determine cutoff based on check method
        $cutoff = match ($cache->check_method) {
            'dns' => now()->subDays(self::CACHE_DAYS_DNS),
            'api' => now()->subHours(self::CACHE_HOURS_API),
            default => now()->subHours(self::CACHE_HOURS_API),
        };

        // Check if cache is still valid
        if ($cache->checked_at->lt($cutoff)) {
            return null;
        }

        return $cache;
    }

    /**
     * Cache domain availability result.
     *
     * @param  string  $domain  The domain name
     * @param  bool  $available  Whether the domain is available
     * @param  string  $checkMethod  The method used ('dns' or 'api')
     * @param  bool|null  $hasDNS  Whether DNS records were found
     * @param  array<string, array<int, mixed>>|null  $dnsRecords  Optional DNS record details
     */
    private function cacheResult(
        string $domain,
        bool $available,
        string $checkMethod = 'api',
        ?bool $hasDNS = null,
        ?array $dnsRecords = null
    ): void {
        DomainCache::updateOrCreate(
            ['domain' => $domain],
            [
                'available' => $available,
                'has_dns_records' => $hasDNS,
                'check_method' => $checkMethod,
                'dns_records' => $dnsRecords,
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
