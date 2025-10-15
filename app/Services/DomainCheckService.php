<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DomainCache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for checking domain name availability.
 *
 * Provides functionality to check domain availability across multiple TLDs
 * with caching support and error handling. Uses DNS pre-screening to filter
 * out domains with existing DNS records before making expensive API calls.
 */
final readonly class DomainCheckService
{
    private const SUPPORTED_TLDS = ['com', 'net', 'org', 'io', 'co', 'app', 'dev', 'ai', 'tech', 'studio'];

    private const CACHE_HOURS_API = 24;

    private const CACHE_DAYS_DNS = 7;

    /**
     * DNS lookup service for pre-screening domains.
     */
    public function __construct(
        private DNSLookupService $dnsLookup
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

        // DNS-only checking for speed (no slow API calls)
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
                'has_dns_records' => true,
            ];
        }

        // If no DNS records found, domain is likely available
        // (We skip slow API calls for performance)
        if ($hasDNS === false) {
            Log::info('No DNS records found - marking as likely available', ['domain' => $domain]);

            // Cache the DNS result
            $this->cacheResult($domain, true, 'dns', false);

            return [
                'domain' => $domain,
                'available' => true,
                'status' => 'available',
                'cached' => false,
                'check_method' => 'dns',
                'has_dns_records' => false,
            ];
        }

        // DNS check failed - mark as unknown
        Log::warning('DNS check failed for domain', ['domain' => $domain]);

        return [
            'domain' => $domain,
            'available' => null,
            'status' => 'unknown',
            'cached' => false,
            'check_method' => 'dns',
            'has_dns_records' => null,
        ];
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

        // Clear expired API caches
        $apiDeleted = DomainCache::where('check_method', 'api')
            ->where('checked_at', '<', $apiCutoff)
            ->delete();

        // Clear expired DNS caches
        $dnsDeleted = DomainCache::where('check_method', 'dns')
            ->where('checked_at', '<', $dnsCutoff)
            ->delete();

        return is_int($apiDeleted) && is_int($dnsDeleted)
            ? $apiDeleted + $dnsDeleted
            : 0;
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
}
