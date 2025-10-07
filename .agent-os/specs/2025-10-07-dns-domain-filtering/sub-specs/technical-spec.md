# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-10-07-dns-domain-filtering/spec.md

> Created: 2025-10-07
> Version: 1.0.0

## Technical Requirements

### DNS Lookup Implementation

- Use PHP's native DNS functions (`dns_get_record()`, `checkdnsrr()`)
- Check for A, AAAA, CNAME, and MX records
- If ANY of these records exist, consider domain as "taken"
- Timeout for DNS lookups: 3 seconds per domain
- Handle DNS resolution failures gracefully (treat as "unknown" status)

### Integration Points

- Integrate with existing `DomainCheckService`
- Add DNS pre-screening before expensive API calls
- Modify `checkDomain()` method to check DNS first
- Update `checkBusinessName()` to filter based on DNS results
- Maintain backward compatibility with existing code

### Caching Strategy

- Reuse existing `DomainCache` model and table
- Add `dns_check_method` column to track check type (dns vs api)
- Add `has_dns_records` boolean column
- Cache DNS results for 7 days (longer than API results)
- Separate cache TTL: DNS checks = 7 days, API checks = 24 hours

### Background Processing

- Create `CheckDomainDNSJob` for asynchronous DNS checks
- Dispatch job immediately after name generation
- Update domain status in real-time via Livewire events
- Queue: default queue with 5 retry attempts
- Timeout: 30 seconds per job (checking multiple TLDs)

## Approach Options

### Option A: Spatie DNS Package (Recommended)

**Library:** `spatie/dns` (https://github.com/spatie/dns)

**Pros:**
- Clean, fluent API: `Dns::query($domain)->getRecords(DNS_A)`
- Built on top of native PHP DNS functions
- Handles edge cases and errors gracefully
- Actively maintained by Spatie
- Laravel-friendly design
- Supports all DNS record types we need

**Cons:**
- Additional dependency (minimal, only ~10KB)
- Requires PHP 8.1+ (already met)

**Example Usage:**
```php
use Spatie\Dns\Dns;

$records = Dns::query('example.com')->getRecords(DNS_A);
if (count($records) > 0) {
    // Domain has A records, likely registered
}
```

### Option B: Native PHP DNS Functions

**Approach:** Use `dns_get_record()` and `checkdnsrr()` directly

**Pros:**
- No additional dependencies
- Built into PHP core
- Fast and lightweight

**Cons:**
- Less elegant API
- More error handling code needed
- Edge case handling is manual
- Less testable without mocking PHP functions

**Example Usage:**
```php
$records = dns_get_record($domain, DNS_A | DNS_AAAA | DNS_CNAME | DNS_MX);
if (!empty($records)) {
    // Domain has DNS records
}
```

### Option C: Third-Party DNS API

**Approach:** Use external service like Google DNS API or Cloudflare DNS

**Pros:**
- More reliable than direct DNS queries
- Better for international domains
- Rate limiting and caching built-in

**Cons:**
- Requires API key and external service
- Network latency
- Potential costs
- Adds external dependency

**Rationale:** We are going with **Option A (Spatie DNS)** because it provides the best balance of reliability, maintainability, and developer experience. The package is well-tested, actively maintained, and provides a clean API that will make our code more readable and testable.

## External Dependencies

### spatie/dns

**Purpose:** DNS record lookup with clean API

**Version:** ^2.0

**Justification:**
- Simplifies DNS query implementation
- Provides reliable error handling
- Well-maintained by trusted Laravel ecosystem contributor
- Minimal overhead (small package size)
- Makes code more testable with clear interfaces
- Handles cross-platform DNS differences

**Installation:**
```bash
composer require spatie/dns
```

## Performance Considerations

### DNS Query Optimization

- Batch DNS checks: Check all TLDs for a name in parallel
- Use connection pooling where possible
- Set reasonable timeouts (3 seconds per query)
- Cache results aggressively (7 days)

### Load Impact

- DNS queries are I/O bound, not CPU intensive
- Expected: ~50-100ms per domain check
- With 10 names × 10 TLDs = 100 DNS queries per generation
- Total time: ~5-10 seconds if sequential, ~1 second if parallel
- Use queue jobs to avoid blocking user interface

### Caching Benefits

- First request: Full DNS check (~10 seconds)
- Subsequent requests: Instant (cache hit)
- Cache hit rate expected: >80% after initial use
- Storage impact: Minimal (~500 bytes per domain cached)

## Error Handling

### DNS Resolution Failures

- Timeout after 3 seconds
- Treat failed DNS queries as "unknown" status
- Log errors but don't block user experience
- Show domain with "(Unable to verify)" status
- Retry failed checks in background

### Network Issues

- Catch all DNS-related exceptions
- Provide graceful fallback (show domain anyway)
- Queue retry jobs for transient failures
- Alert monitoring system for persistent failures

## Security Considerations

### DNS Spoofing Protection

- Use multiple DNS servers if available
- Validate DNS response format
- Don't rely solely on DNS for final availability

### Rate Limiting

- Limit DNS queries per user: 100 per minute
- Global limit: 1000 queries per minute
- Use existing Laravel rate limiting middleware

### Input Validation

- Sanitize domain names before DNS lookup
- Prevent DNS injection attacks
- Validate domain format before querying
