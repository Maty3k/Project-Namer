# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-09-29-dns-domain-filtering/spec.md

> Created: 2025-09-29
> Version: 1.0.0

## Technical Requirements

### DNS Lookup Implementation
- Use mikepultz/netdns2 package for PHP DNS resolution
- Check multiple DNS record types: A, AAAA, CNAME, MX, NS, TXT
- Implement timeout handling (2 seconds per lookup)
- Support concurrent/batch lookups for performance
- Handle both IPv4 and IPv6 lookups

### Filtering Logic
- Domains with ANY DNS records are considered "taken"
- Domains with NO DNS records are considered "potentially available"
- Domains with lookup failures get "unknown" status
- Maintain original data structure, only filter display

### Caching Strategy
- Cache DNS lookup results for 24 hours
- Use Laravel's cache system with Redis/database driver
- Cache key format: `dns_lookup:{domain}:{tld}`
- Store: result (boolean), checked_at (timestamp), record_types (array)
- Implement cache warming for frequently checked domains

### Performance Requirements
- DNS lookups must not block UI rendering
- Batch process domains in groups of 10
- Use queue jobs for background processing
- Maximum 5 second total timeout for batch lookups
- Implement circuit breaker pattern for DNS service failures

## Approach Options

**Option A: Synchronous Filtering**
- Pros: Simple implementation, immediate results
- Cons: Slower initial page load, potential timeouts

**Option B: Asynchronous Queue-Based** (Selected)
- Pros: Non-blocking UI, better user experience, scalable
- Cons: More complex implementation, requires queue infrastructure

**Rationale:** Asynchronous processing provides better UX and allows for graceful degradation. Users see results immediately with progressive enhancement as DNS checks complete.

## External Dependencies

- **mikepultz/netdns2** - PHP DNS resolver library
  - **Justification:** Pure PHP implementation, no system dependencies, well-maintained, supports all needed record types
  - **Version:** ^1.5
  - **License:** BSD-3-Clause

## Implementation Architecture

### Service Layer
```php
namespace App\Services;

class DnsLookupService
{
    public function checkDomain(string $domain): DnsLookupResult
    public function checkBatch(array $domains): array
    public function getCachedResult(string $domain): ?DnsLookupResult
}
```

### Queue Jobs
```php
namespace App\Jobs;

class CheckDomainDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(DnsLookupService $service): void
}
```

### Data Transfer Objects
```php
namespace App\DTOs;

class DnsLookupResult
{
    public bool $hasRecords;
    public array $recordTypes;
    public ?string $error;
    public Carbon $checkedAt;
}
```

## Integration Points

### DomainCheckService Integration
- Modify existing `DomainCheckService` to use DNS pre-filtering
- Add `hasDnsRecords` property to domain results
- Filter domains in service before returning to controllers

### Frontend Updates
- Add loading states for DNS checks
- Display only domains without DNS records
- Show badge/indicator for DNS-filtered results
- Progressive enhancement as results complete

### Monitoring Integration
- Log DNS lookup metrics to monitoring service
- Track success rate, average lookup time, cache hit rate
- Alert on high failure rates or timeouts

## Error Handling

### DNS Lookup Failures
- Timeout: Mark as "unknown", allow display
- Network error: Retry with exponential backoff
- Invalid domain: Log error, skip domain
- Service unavailable: Circuit breaker activation

### Fallback Strategy
1. Primary: Use configured DNS servers
2. Secondary: Fallback to public DNS (8.8.8.8, 1.1.1.1)
3. Tertiary: Skip DNS check, show all domains

## Configuration

```php
// config/dns.php
return [
    'servers' => env('DNS_SERVERS', '8.8.8.8,1.1.1.1'),
    'timeout' => env('DNS_TIMEOUT', 2),
    'cache_ttl' => env('DNS_CACHE_TTL', 86400), // 24 hours
    'batch_size' => env('DNS_BATCH_SIZE', 10),
    'max_retries' => env('DNS_MAX_RETRIES', 2),
    'circuit_breaker_threshold' => env('DNS_CB_THRESHOLD', 5),
];
```