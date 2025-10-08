# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-10-07-dns-domain-filtering/spec.md

> Created: 2025-10-07
> Version: 1.0.0

## Test Coverage

### Unit Tests

**DNSLookupService**
- Test DNS lookup returns true when A records exist
- Test DNS lookup returns true when AAAA records exist
- Test DNS lookup returns true when CNAME records exist
- Test DNS lookup returns true when MX records exist
- Test DNS lookup returns false when no records exist
- Test DNS lookup handles invalid domain gracefully
- Test DNS lookup handles timeout gracefully
- Test DNS lookup handles DNS resolution failure
- Test DNS lookup validates domain format before query
- Test DNS lookup caches results correctly

**DomainCheckService (Updated)**
- Test checkDomain uses DNS before API
- Test checkDomain skips API if DNS records found
- Test checkDomain falls back to API if DNS check fails
- Test checkBusinessName filters domains with DNS records
- Test multiple domain checking with DNS filtering
- Test cache stores DNS check method and results
- Test cache lookup prefers DNS results over API
- Test getCacheTTL returns correct value per method

**DomainCache Model (Updated)**
- Test has_dns_records column stores boolean correctly
- Test check_method column stores string correctly
- Test dns_records column stores JSON correctly
- Test isDNSCached scope returns only DNS-checked domains
- Test isAvailableByDNS scope returns domains without DNS
- Test scopeExpiredDNS filters by 7-day TTL

### Integration Tests

**Domain Checking Workflow**
- Test full workflow: generate names → check DNS → filter results
- Test domains with DNS records are excluded from results
- Test domains without DNS records appear in results
- Test mixed results (some with DNS, some without)
- Test DNS check happens before expensive API calls
- Test results update in real-time as DNS checks complete

**Background Job Processing**
- Test CheckDomainDNSJob dispatches correctly
- Test job processes DNS check asynchronously
- Test job updates cache with DNS results
- Test job emits Livewire event on completion
- Test job retries on failure (max 5 attempts)
- Test job timeout is enforced (30 seconds)
- Test multiple jobs process in parallel

**Caching Integration**
- Test DNS results cache for 7 days
- Test API results cache for 24 hours
- Test cache hit returns stored DNS results
- Test cache miss triggers new DNS check
- Test expired cache is ignored
- Test clearExpiredCache removes old DNS entries

### Feature Tests

**Name Generation with DNS Filtering**
- Test generated names exclude domains with DNS records
- Test user sees only potentially available domains
- Test loading state shows during DNS checks
- Test results update as checks complete
- Test error messages display for failed DNS checks
- Test "unknown" status for DNS timeout

**Domain Result Display**
- Test domain card shows DNS check status
- Test domain card shows "Checking DNS..." during lookup
- Test domain card shows "No DNS records" for available
- Test domain card shows "Has DNS records" for taken (filtered)
- Test domain card updates status in real-time

**Export Functionality**
- Test exported results only include domains without DNS
- Test export shows DNS check timestamp
- Test export includes DNS check method used

### Mocking Requirements

**DNS Queries**
- Mock `Spatie\Dns\Dns::query()` to return test DNS records
- Mock DNS timeout scenarios
- Mock DNS resolution failures
- Mock different DNS record types (A, AAAA, CNAME, MX)
- Mock empty DNS responses (no records)

**Background Jobs**
- Use `Queue::fake()` for job dispatching tests
- Use `Queue::assertPushed()` to verify job dispatch
- Use `Queue::assertNotPushed()` for filtered domains

**Cache**
- Use `Cache::shouldReceive()` for cache interaction tests
- Mock cache hits and misses
- Test cache expiration logic

## Test Examples

### Unit Test: DNS Lookup Service

```php
use App\Services\DNSLookupService;
use Spatie\Dns\Dns;

test('DNS lookup returns true when domain has A records', function () {
    // Mock Spatie DNS to return A records
    $mockDns = Mockery::mock('overload:' . Dns::class);
    $mockDns->shouldReceive('query')
        ->with('example.com')
        ->andReturnSelf();
    $mockDns->shouldReceive('getRecords')
        ->with(DNS_A)
        ->andReturn([['ip' => '192.0.2.1']]);

    $service = new DNSLookupService();
    $result = $service->hasDNSRecords('example.com');

    expect($result)->toBeTrue();
});

test('DNS lookup returns false when domain has no records', function () {
    $mockDns = Mockery::mock('overload:' . Dns::class);
    $mockDns->shouldReceive('query')
        ->with('newdomain.com')
        ->andReturnSelf();
    $mockDns->shouldReceive('getRecords')
        ->andReturn([]);

    $service = new DNSLookupService();
    $result = $service->hasDNSRecords('newdomain.com');

    expect($result)->toBeFalse();
});
```

### Integration Test: Name Generation

```php
use App\Livewire\NameGeneratorDashboard;
use Illuminate\Support\Facades\Queue;

test('generated names exclude domains with DNS records', function () {
    Queue::fake();

    // Mock DNS service to return some domains with records
    $this->mock(DNSLookupService::class, function ($mock) {
        $mock->shouldReceive('hasDNSRecords')
            ->with('techflow.com')->andReturn(true)  // Has DNS
            ->with('datasync.com')->andReturn(false) // No DNS
            ->with('cloudcore.com')->andReturn(true); // Has DNS
    });

    $component = Livewire::actingAs($this->user)
        ->test(NameGeneratorDashboard::class)
        ->set('businessIdea', 'Tech startup')
        ->call('generateNames');

    // Only domains without DNS should appear
    expect($component->get('generatedNames'))
        ->toContain('datasync')
        ->not->toContain('techflow')
        ->not->toContain('cloudcore');
});
```

### Feature Test: Background Job

```php
use App\Jobs\CheckDomainDNSJob;
use Illuminate\Support\Facades\Queue;

test('DNS check job processes domain and updates cache', function () {
    Queue::fake();

    // Dispatch job
    CheckDomainDNSJob::dispatch('example.com');

    // Assert job was dispatched
    Queue::assertPushed(CheckDomainDNSJob::class, function ($job) {
        return $job->domain === 'example.com';
    });

    // Process the job
    Queue::fake(); // Reset to execute
    $job = new CheckDomainDNSJob('example.com');
    $job->handle();

    // Verify cache was updated
    $this->assertDatabaseHas('domain_caches', [
        'domain' => 'example.com',
        'check_method' => 'dns',
    ]);
});
```

## Performance Benchmarks

Tests should verify performance metrics:

- DNS lookup completes in <100ms (single domain)
- Batch DNS check (10 domains) completes in <1 second
- Cache hit returns result in <10ms
- Background job processes in <5 seconds (10 TLDs)

## Test Data

### Test Domains

**Domains with DNS records (should be filtered):**
- google.com
- github.com
- laravel.com

**Domains without DNS records (should pass):**
- xkjsdhfkjsdhf.com (random string, unlikely to exist)
- test-domain-12345.com (test pattern)
- temporary-test-name.com (temporary pattern)

**Note:** Use mocked DNS responses in tests to avoid actual DNS queries
