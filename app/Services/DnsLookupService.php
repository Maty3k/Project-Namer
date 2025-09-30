<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use App\Contracts\DnsResolverInterface;
use App\DTOs\DnsLookupResult;
use App\Models\DnsLookupCache;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use NetDNS2\Resolver;

final readonly class DnsLookupService implements DnsLookupServiceInterface
{
    /**
     * @var array<string>
     */
    private array $recordTypes;

    private int $timeout;

    private int $cacheTtl;

    /**
     * @var array<string>
     */
    private array $primaryServers;

    /**
     * @var array<string>
     */
    private array $fallbackServers;

    private bool $fallbackEnabled;

    public function __construct(
        private ?DnsResolverInterface $resolver = null,
        private ?DnsPerformanceMonitorInterface $performanceMonitor = null,
        private ?DnsLoggingService $logger = null,
        private ?DnsRetryService $retryService = null
    ) {
        $this->recordTypes = config('dns.record_types', ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT']);
        $this->timeout = config('dns.timeout', 2);
        $this->cacheTtl = config('dns.cache_ttl', 86400);
        $this->primaryServers = explode(',', (string) config('dns.servers', '8.8.8.8,1.1.1.1'));
        $this->fallbackServers = config('dns.fallback_servers', [
            '8.8.8.8', '8.8.4.4', '1.1.1.1', '1.0.0.1', '208.67.222.222', '208.67.220.220',
        ]);
        $this->fallbackEnabled = config('dns.fallback.enabled', true);
    }

    public function checkDomain(string $fullDomain): DnsLookupResult
    {
        $startTime = microtime(true);

        // Validate domain format
        if (! $this->isValidDomain($fullDomain)) {
            $this->logger?->logLookupFailure($fullDomain, new Exception('Invalid domain format'), 'validation', 'local');
            Log::warning('Invalid domain format', ['domain' => $fullDomain]);
            $this->recordDnsLookup($fullDomain, $startTime, false, false, 'Invalid domain format');

            return DnsLookupResult::withError('Invalid domain format');
        }

        [$domain, $tld] = $this->parseDomain($fullDomain);

        // Check cache first
        $cachedResult = $this->getCachedResult($fullDomain);
        if ($cachedResult !== null) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->logger?->logCacheOperation('hit', $fullDomain, $cachedResult->recordTypes, $this->cacheTtl);
            $this->logger?->logLookupSuccess($fullDomain, $cachedResult->recordTypes, $responseTime, true);
            $this->recordDnsLookup($fullDomain, $startTime, true, true);

            return $cachedResult;
        }

        // Try primary servers first, then fallback servers if enabled
        $result = $this->performDnsLookup($fullDomain, false);

        if ($result->isError() && $this->fallbackEnabled && ! empty($this->fallbackServers)) {
            $this->logger?->logFallbackActivated($fullDomain, $result->error, $this->fallbackServers);
            Log::info('Primary DNS servers failed, trying fallback servers', [
                'domain' => $fullDomain,
                'primary_error' => $result->error,
            ]);

            $fallbackResult = $this->performDnsLookup($fullDomain, true);

            if ($fallbackResult->isSuccessful()) {
                Log::info('DNS lookup succeeded using fallback servers', [
                    'domain' => $fullDomain,
                ]);
                $result = $fallbackResult;
            }
        }

        // Cache the result
        if ($result->isSuccessful() || $result->isError()) {
            $cacheTtl = $result->isError() ? 300 : null; // 5 minutes for errors
            $this->cacheResult($domain, $tld, $result, $cacheTtl);
        }

        $this->recordDnsLookup($fullDomain, $startTime, $result->isSuccessful(), false, $result->error);

        return $result;
    }

    public function checkBatch(array $domains): array
    {
        $results = [];

        foreach ($domains as $domain) {
            $results[$domain] = $this->checkDomain($domain);
        }

        return $results;
    }

    public function getCachedResult(string $fullDomain): ?DnsLookupResult
    {
        [$domain, $tld] = $this->parseDomain($fullDomain);

        $cached = DnsLookupCache::findValidCache($domain, $tld);

        if ($cached === null) {
            return null;
        }

        return DnsLookupResult::fromCache(
            hasRecords: $cached->has_records,
            recordTypes: $cached->record_types ?? [],
            error: $cached->error_message,
            checkedAt: $cached->checked_at
        );
    }

    private function getResolver(): DnsResolverInterface
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        return $this->createResolverWithServers($this->primaryServers);
    }

    /**
     * @param  array<string>  $servers
     */
    private function createResolverWithServers(array $servers, bool $isFallback = false): DnsResolverInterface
    {
        $timeout = $isFallback
            ? config('dns.fallback.timeout_fallback', $this->timeout)
            : config('dns.fallback.timeout_primary', $this->timeout);

        $retries = $isFallback
            ? config('dns.fallback.max_retries_fallback', 2)
            : config('dns.fallback.max_retries_primary', 1);

        $netDns2Resolver = new Resolver([
            'nameservers' => array_map('trim', $servers),
            'timeout' => $timeout,
            'retry' => $retries,
        ]);

        return new NetDns2Resolver($netDns2Resolver);
    }

    private function performDnsLookup(string $fullDomain, bool $useFallback = false): DnsLookupResult
    {
        $operationName = $useFallback ? "dns-lookup-fallback:{$fullDomain}" : "dns-lookup-primary:{$fullDomain}";

        $lookupOperation = function () use ($fullDomain, $useFallback) {
            $resolver = $useFallback
                ? $this->createResolverWithServers($this->fallbackServers, true)
                : $this->getResolver();

            $foundRecords = [];

            // Check each record type
            foreach ($this->recordTypes as $recordType) {
                try {
                    $response = $resolver->query($fullDomain, $recordType);

                    if (! empty($response->answer)) {
                        $foundRecords[] = $recordType;
                    }
                } catch (Exception $e) {
                    Log::debug('DNS query failed for record type', [
                        'domain' => $fullDomain,
                        'type' => $recordType,
                        'server_type' => $useFallback ? 'fallback' : 'primary',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return empty($foundRecords)
                ? DnsLookupResult::withoutRecords()
                : DnsLookupResult::withRecords($foundRecords);
        };

        try {
            // Use retry service if available, otherwise execute directly
            if ($this->retryService !== null) {
                return $this->retryService->execute($operationName, $lookupOperation);
            }

            return $lookupOperation();
        } catch (Exception $e) {
            Log::error('DNS lookup failed', [
                'domain' => $fullDomain,
                'server_type' => $useFallback ? 'fallback' : 'primary',
                'servers' => $useFallback ? $this->fallbackServers : $this->primaryServers,
                'error' => $e->getMessage(),
            ]);

            return DnsLookupResult::withError($e->getMessage());
        }
    }

    private function isValidDomain(string $domain): bool
    {
        // Basic domain validation
        if (empty($domain) || strlen($domain) > 253) {
            return false;
        }

        // Check for invalid characters
        if (! preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return false;
        }

        // Check for consecutive dots
        if (str_contains($domain, '..')) {
            return false;
        }

        // Check starts/ends with dot or hyphen
        if (str_starts_with($domain, '.') || str_ends_with($domain, '.') ||
            str_starts_with($domain, '-') || str_ends_with($domain, '-')) {
            return false;
        }

        return true;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseDomain(string $fullDomain): array
    {
        // Handle empty or invalid input
        if (empty($fullDomain) || $fullDomain === '.') {
            return ['', ''];
        }

        $parts = explode('.', $fullDomain);

        // Ensure we always return exactly 2 elements
        if (count($parts) < 2) {
            // Handle invalid domain structure
            return [$fullDomain, ''];
        }

        $tld = array_pop($parts);
        $domain = implode('.', $parts);

        return [$domain, $tld];
    }

    private function cacheResult(string $domain, string $tld, DnsLookupResult $result, ?int $ttl = null): void
    {
        $ttl ??= $this->cacheTtl;
        $expiresAt = now()->addSeconds($ttl);

        try {
            // Use updateOrCreate to handle unique constraint
            DnsLookupCache::updateOrCreate(
                [
                    'domain' => $domain,
                    'tld' => $tld,
                ],
                [
                    'has_records' => $result->hasRecords,
                    'record_types' => $result->recordTypes,
                    'error_message' => $result->error,
                    'checked_at' => $result->checkedAt,
                    'expires_at' => $expiresAt,
                ]
            );
        } catch (Exception $e) {
            Log::error('Failed to cache DNS result', [
                'domain' => $domain,
                'tld' => $tld,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function recordDnsLookup(
        string $fullDomain,
        float $startTime,
        bool $successful,
        bool $cacheHit = false,
        ?string $error = null
    ): void {
        if ($this->performanceMonitor === null) {
            return;
        }

        $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        $this->performanceMonitor->recordLookup(
            $fullDomain,
            $responseTimeMs,
            $successful,
            $cacheHit,
            $error
        );
    }
}
