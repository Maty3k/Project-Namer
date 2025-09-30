<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupServiceInterface;
use App\Jobs\CheckDomainDnsJob;
use App\Models\DomainCache;
use App\Models\NameSuggestion;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

/**
 * Service for checking domain name availability.
 *
 * Provides functionality to check domain availability across multiple TLDs
 * with caching support and error handling.
 */
final readonly class DomainCheckService
{
    private const SUPPORTED_TLDS = ['com', 'net', 'org', 'io', 'co', 'app', 'dev', 'ai', 'tech', 'studio'];

    private const TIMEOUT_SECONDS = 5;

    private const CACHE_HOURS = 24;

    public function __construct(
        private ?DnsLookupServiceInterface $dnsService = null,
        private ?DnsDegradationService $degradationService = null
    ) {}

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
            $available = is_bool($result['available']) ? $result['available'] : false;
            $this->cacheResult($domain, $available);

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

        $deleted = DomainCache::where('checked_at', '<', $cutoff)->delete();

        return is_int($deleted) ? $deleted : 0;
    }

    /**
     * Check domain availability with DNS pre-filtering.
     *
     * @param  string  $domain  The domain name to check
     * @return array<string, mixed> Domain availability information with DNS metadata
     */
    public function checkDomainWithDnsFilter(string $domain): array
    {
        $domain = $this->formatDomain($domain);
        $this->validateDomain($domain);

        // Get regular domain check result
        $result = $this->checkDomain($domain);
        $result['dns_filtering_enabled'] = true;

        // Find associated name suggestion
        $suggestion = NameSuggestion::where('name', $domain)->first();

        if ($suggestion) {
            // Check if we have DNS data for this suggestion
            if ($suggestion->dns_checked) {
                $result['dns_checked'] = true;
                $result['dns_has_records'] = $suggestion->dns_has_records;
                $result['dns_metadata'] = [
                    'checked' => true,
                    'has_records' => $suggestion->dns_has_records,
                    'checked_at' => $suggestion->dns_checked_at?->toISOString(),
                    'source' => 'database',
                ];

                // If DNS shows records exist, domain is not available
                if ($suggestion->dns_has_records) {
                    $result['available'] = false;
                    $result['status'] = 'taken';
                    $result['dns_source'] = 'cache';
                }
            } else {
                // No DNS data yet, queue DNS check
                $result['dns_checked'] = false;
                $result['dns_check_queued'] = true;

                Queue::push(new CheckDomainDnsJob($suggestion->id));
            }
        } else {
            // No suggestion found, just regular domain check
            $result['dns_checked'] = false;
        }

        // Check if DNS service is in degraded mode
        if ($this->degradationService && $this->degradationService->isDegradedMode()) {
            try {
                $degradedResult = $this->degradationService->checkDomainInDegradedMode($domain);
                $result = array_merge($result, $degradedResult);

                Log::info('Domain checked in DNS degraded mode', [
                    'domain' => $domain,
                    'strategy' => $degradedResult['fallback_strategy'] ?? 'unknown',
                ]);
            } catch (Exception $e) {
                Log::error('DNS degradation service failed', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
                $result['dns_error'] = 'DNS degradation service failed';
                $result['fallback_to_whois'] = true;
            }
        } elseif ($this->dnsService) {
            // Normal DNS service operation
            try {
                $dnsResult = $this->dnsService->getCachedResult($domain);
                if ($dnsResult && $dnsResult->isSuccessful()) {
                    $result['dns_metadata'] = [
                        'checked' => true,
                        'has_records' => $dnsResult->hasRecords,
                        'record_types' => $dnsResult->recordTypes,
                        'checked_at' => $dnsResult->checkedAt->toISOString(),
                        'source' => 'dns_cache',
                    ];

                    if ($dnsResult->hasRecords) {
                        $result['available'] = false;
                        $result['status'] = 'taken';
                        $result['dns_has_records'] = true;
                        $result['dns_source'] = 'cache';
                    }
                } elseif ($dnsResult && $dnsResult->isError()) {
                    $result['dns_error'] = $dnsResult->error;
                    $result['fallback_to_whois'] = true;
                }
            } catch (Exception $e) {
                Log::warning('DNS lookup failed during domain check', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
                $result['dns_error'] = $e->getMessage();
                $result['fallback_to_whois'] = true;
            }
        }

        return $result;
    }

    /**
     * Check multiple domains with DNS pre-filtering.
     *
     * @param  array<int, string>  $domains  Array of domain names to check
     * @return array<string, array<string, mixed>> Filtered results excluding domains with DNS records
     */
    public function checkDomainsWithDnsPreFilter(array $domains): array
    {
        $results = [];
        $dnsCheckQueue = [];

        foreach ($domains as $domain) {
            $domain = $this->formatDomain($domain);

            try {
                $this->validateDomain($domain);
            } catch (InvalidArgumentException) {
                continue; // Skip invalid domains
            }

            // Find associated name suggestion
            $suggestion = NameSuggestion::where('name', $domain)->first();

            if ($suggestion && $suggestion->dns_checked && $suggestion->dns_has_records) {
                // Skip domains that have DNS records (pre-filtering)
                continue;
            }

            // Include domain in results
            $result = $this->checkDomain($domain);
            $result['dns_filtering_enabled'] = true;

            if ($suggestion) {
                if ($suggestion->dns_checked) {
                    $result['dns_checked'] = true;
                    $result['dns_has_records'] = $suggestion->dns_has_records;
                } else {
                    $result['dns_checked'] = false;
                    $result['dns_check_queued'] = true;
                    $dnsCheckQueue[] = $suggestion->id;
                }
            } else {
                // Create a temporary suggestion for DNS checking
                $newSuggestion = NameSuggestion::create([
                    'project_id' => 1, // Default project ID - this could be made configurable
                    'name' => $domain,
                    'dns_checked' => false,
                ]);

                $result['dns_checked'] = false;
                $result['dns_check_queued'] = true;
                $dnsCheckQueue[] = $newSuggestion->id;
            }

            $results[$domain] = $result;
        }

        // Dispatch DNS check jobs for unchecked domains
        foreach ($dnsCheckQueue as $suggestionId) {
            Queue::push(new CheckDomainDnsJob($suggestionId));
        }

        return $results;
    }

    /**
     * Check business name across TLDs with DNS pre-filtering.
     *
     * @param  string  $businessName  The business name without TLD
     * @return array<string, array<string, mixed>> Filtered results excluding domains with DNS records
     */
    public function checkBusinessNameWithDnsFilter(string $businessName): array
    {
        $businessName = $this->sanitizeBusinessName($businessName);
        $domains = [];

        foreach (self::SUPPORTED_TLDS as $tld) {
            $domains[] = $businessName.'.'.$tld;
        }

        return $this->checkDomainsWithDnsPreFilter($domains);
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
                $errorMessage = is_string($error) ? $error : 'Domain API request failed';
                throw new Exception($errorMessage);
            }

            $data = $response->json();

            // Validate response data is array
            if (! is_array($data)) {
                throw new Exception('Invalid response format from domain API');
            }

            // Check for expected response format
            if (! isset($data['available']) && ! isset($data['domains'])) {
                // Try alternative API check
                return $this->checkViaWhoisAPI($domain);
            }

            $available = is_bool($data['available']) ? $data['available'] : false;

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

            // Validate response data is array
            if (! is_array($data)) {
                throw new Exception('Invalid response format from domain API');
            }

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
