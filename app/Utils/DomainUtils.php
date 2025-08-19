<?php

declare(strict_types=1);

namespace App\Utils;

use Carbon\Carbon;

/**
 * Utility class for domain-related operations.
 *
 * Provides static methods for domain formatting, validation,
 * business name sanitization, and availability status management.
 */
final class DomainUtils
{
    private const DEFAULT_TLDS = ['com', 'io', 'co', 'net'];

    private const CACHE_HOURS = 24;

    /**
     * Format domain name to standard format.
     *
     * Removes protocol, www prefix, trailing slashes, converts to lowercase,
     * and trims whitespace.
     */
    public static function formatDomain(string $domain): string
    {
        $domain = trim($domain);
        $domain = strtolower($domain);

        // Remove protocol if present
        $domain = preg_replace('#^https?://#i', '', $domain);

        // Remove www if present
        $domain = preg_replace('#^www\.#i', '', (string) $domain);

        // Remove path, query, and fragment
        $domain = parse_url('http://'.$domain, PHP_URL_HOST) ?: $domain;

        // Remove trailing slashes (in case parse_url didn't handle it)
        $domain = rtrim((string) $domain, '/');

        return $domain;
    }

    /**
     * Validate domain format using regex.
     */
    public static function isValidDomain(string $domain): bool
    {
        if (empty($domain)) {
            return false;
        }

        // Must contain at least one dot
        if (! str_contains($domain, '.')) {
            return false;
        }

        // Check overall format: letters, numbers, hyphens, dots
        // Must not start/end with hyphen, no consecutive dots
        $pattern = '/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)*\.[a-z]{2,}$/i';

        if (! preg_match($pattern, $domain)) {
            return false;
        }

        // Additional checks
        $parts = explode('.', $domain);

        // Must have at least 2 parts (domain + tld)
        if (count($parts) < 2) {
            return false;
        }

        // TLD must be at least 2 characters
        $tld = end($parts);
        if (strlen($tld) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize business name for domain usage.
     *
     * Converts to lowercase, removes special characters and spaces.
     */
    public static function sanitizeBusinessName(string $businessName): string
    {
        $businessName = strtolower(trim($businessName));

        // Remove everything except letters and numbers
        $businessName = preg_replace('/[^a-z0-9]/', '', $businessName);

        return $businessName ?? '';
    }

    /**
     * Extract TLD from a domain name.
     */
    public static function extractTLD(string $domain): ?string
    {
        if (empty($domain) || ! str_contains($domain, '.')) {
            return null;
        }

        $parts = explode('.', $domain);

        return end($parts);
    }

    /**
     * Build array of domains from business name and TLDs.
     *
     * @param  array<int, string>|null  $tlds
     * @return array<int, string>
     */
    public static function buildDomainsFromName(string $businessName, ?array $tlds = null): array
    {
        $tlds ??= self::DEFAULT_TLDS;
        $sanitizedName = self::sanitizeBusinessName($businessName);

        $domains = [];
        foreach ($tlds as $tld) {
            $domains[] = $sanitizedName.'.'.$tld;
        }

        return $domains;
    }

    /**
     * Check if domain cache entry is still fresh.
     */
    public static function isDomainCacheFresh(Carbon $checkedAt): bool
    {
        return $checkedAt->gt(now()->subHours(self::CACHE_HOURS));
    }

    /**
     * Get availability status string based on domain check results.
     */
    public static function getAvailabilityStatus(?bool $available, ?string $error = null, ?string $override = null): string
    {
        if ($override !== null) {
            return $override;
        }

        if ($available === null) {
            return 'error';
        }

        return $available ? 'available' : 'taken';
    }

    /**
     * Get the default TLDs supported by the application.
     *
     * @return array<int, string>
     */
    public static function getDefaultTLDs(): array
    {
        return self::DEFAULT_TLDS;
    }

    /**
     * Check if a TLD is supported by default.
     */
    public static function isSupportedTLD(string $tld): bool
    {
        return in_array(strtolower($tld), self::DEFAULT_TLDS);
    }

    /**
     * Generate domain variations for a business name.
     *
     * Returns common variations like with/without hyphens, numbers, etc.
     *
     * @return array<int, string>
     */
    public static function generateDomainVariations(string $businessName, int $limit = 5): array
    {
        $sanitized = self::sanitizeBusinessName($businessName);
        $variations = [$sanitized];

        // Add variations with common prefixes/suffixes
        $prefixes = ['get', 'my', 'the'];
        $suffixes = ['app', 'io', 'hub', 'pro', 'lab'];

        foreach ($prefixes as $prefix) {
            if (count($variations) >= $limit) {
                break;
            }
            $variations[] = $prefix.$sanitized;
        }

        foreach ($suffixes as $suffix) {
            if (count($variations) >= $limit) {
                break;
            }
            $variations[] = $sanitized.$suffix;
        }

        return array_slice(array_unique($variations), 0, $limit);
    }
}
