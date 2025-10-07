<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Dns\Dns;

/**
 * DNS lookup service for checking domain DNS records.
 *
 * Uses Spatie DNS package to query DNS records and determine if a domain
 * has existing records (A, AAAA, CNAME, MX), which indicates the domain
 * is likely registered and in use.
 */
class DNSLookupService
{
    /**
     * Domain validation regex pattern.
     *
     * Matches valid domain names according to RFC 1035.
     */
    private const DOMAIN_PATTERN = '/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.[A-Za-z0-9-]{1,63})+$/';

    /**
     * Check if a domain has DNS records.
     *
     * Returns true if ANY of the following record types exist:
     * - A (IPv4 address)
     * - AAAA (IPv6 address)
     * - CNAME (canonical name)
     * - MX (mail exchange)
     *
     * @param  string  $domain  The domain to check
     * @return bool|null True if records exist, false if no records, null on error
     */
    public function hasDNSRecords(string $domain): ?bool
    {
        // Validate domain format first
        if (! $this->isValidDomain($domain)) {
            return null;
        }

        try {
            $dns = new Dns;

            // Check for A records (IPv4)
            $aRecords = $dns->getRecords($domain, DNS_A);
            if (! empty($aRecords)) {
                return true;
            }

            // Check for AAAA records (IPv6)
            $aaaaRecords = $dns->getRecords($domain, DNS_AAAA);
            if (! empty($aaaaRecords)) {
                return true;
            }

            // Check for CNAME records
            $cnameRecords = $dns->getRecords($domain, DNS_CNAME);
            if (! empty($cnameRecords)) {
                return true;
            }

            // Check for MX records (mail)
            $mxRecords = $dns->getRecords($domain, DNS_MX);
            if (! empty($mxRecords)) {
                return true;
            }

            // No records found
            return false;
        } catch (\Exception $e) {
            // Log error but don't fail - treat as unknown
            Log::warning('DNS lookup failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get detailed DNS records for a domain.
     *
     * Returns an array with keys for each record type (A, AAAA, CNAME, MX)
     * containing the actual DNS records found.
     *
     * @param  string  $domain  The domain to query
     * @return array<string, array<int, mixed>> DNS records by type
     */
    public function getDNSRecords(string $domain): array
    {
        if (! $this->isValidDomain($domain)) {
            return [];
        }

        $records = [];
        $dns = new Dns;

        try {
            $records['A'] = $dns->getRecords($domain, DNS_A);
        } catch (\Exception) {
            $records['A'] = [];
        }

        try {
            $records['AAAA'] = $dns->getRecords($domain, DNS_AAAA);
        } catch (\Exception) {
            $records['AAAA'] = [];
        }

        try {
            $records['CNAME'] = $dns->getRecords($domain, DNS_CNAME);
        } catch (\Exception) {
            $records['CNAME'] = [];
        }

        try {
            $records['MX'] = $dns->getRecords($domain, DNS_MX);
        } catch (\Exception) {
            $records['MX'] = [];
        }

        return $records;
    }

    /**
     * Validate domain format.
     *
     * Checks if a domain string is valid according to RFC 1035.
     * - Must contain at least one dot
     * - Labels must be 1-63 characters
     * - Labels can contain letters, numbers, and hyphens
     * - Labels cannot start or end with hyphen
     * - No consecutive dots or spaces
     *
     * @param  string  $domain  The domain to validate
     */
    public function isValidDomain(string $domain): bool
    {
        // Check basic requirements
        if (empty($domain) || strlen($domain) > 253) {
            return false;
        }

        // Must contain at least one dot
        if (! str_contains($domain, '.')) {
            return false;
        }

        // Check against regex pattern
        if (! preg_match(self::DOMAIN_PATTERN, $domain)) {
            return false;
        }

        // All checks passed
        return true;
    }
}
