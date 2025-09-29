# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-09-29-dns-domain-filtering/spec.md

> Created: 2025-09-29
> Version: 1.0.0

## Test Coverage

### Unit Tests

**DnsLookupService**
- Test successful DNS lookup with records found
- Test successful DNS lookup with no records found
- Test DNS lookup timeout handling
- Test DNS lookup network error handling
- Test batch DNS lookup functionality
- Test cache hit scenarios
- Test cache miss scenarios
- Test cache expiration handling
- Test invalid domain handling
- Test different DNS record types (A, AAAA, CNAME, MX, NS)

**DnsLookupResult DTO**
- Test DTO creation with valid data
- Test DTO serialization/deserialization
- Test DTO validation rules
- Test default values and nullables

**DnsLookupCache Model**
- Test cache expiration logic
- Test findValidCache method
- Test record type JSON casting
- Test timestamp handling

**CheckDomainDnsJob**
- Test job execution with valid domain
- Test job failure handling
- Test job retry logic
- Test batch processing within job
- Test job timeout scenarios

### Integration Tests

**Domain Filtering Workflow**
- Test complete domain generation to filtered display flow
- Test DNS checking integration with existing DomainCheckService
- Test cache integration with Laravel cache system
- Test queue job processing integration
- Test database persistence of DNS results

**API Integration**
- Test domain suggestions API returns filtered results
- Test real-time updates as DNS checks complete
- Test API response format includes DNS status
- Test API error handling when DNS service unavailable

**Frontend Integration**
- Test progressive enhancement of domain display
- Test loading states during DNS checks
- Test error state display for failed DNS lookups
- Test cache behavior in frontend

### Feature Tests

**End-to-End Domain Filtering**
- Test user generates names and sees only domains without DNS records
- Test cached results are served on subsequent requests
- Test system gracefully handles DNS service outages
- Test performance under high load of DNS lookups

**Cache Behavior**
- Test DNS results are cached for 24 hours
- Test expired cache entries are refreshed
- Test cache warming for popular domains
- Test cache invalidation strategies

**Error Recovery**
- Test circuit breaker activation during DNS service failures
- Test fallback to showing all domains when DNS unavailable
- Test retry logic for transient DNS failures
- Test logging of DNS lookup failures

## Mocking Requirements

### External DNS Services
- **Strategy:** Mock netdns2 resolver responses
- **Scenarios:** Success with records, success without records, timeout, network error
- **Data:** Predefined DNS response objects for consistent testing

### Laravel Cache System
- **Strategy:** Use array cache driver for testing
- **Scenarios:** Cache hits, misses, expiration
- **Data:** Controlled cache state for predictable tests

### Queue System
- **Strategy:** Use sync queue driver with job monitoring
- **Scenarios:** Job success, failure, retry, timeout
- **Data:** Mock job payloads and execution contexts

## Test Data

### Sample Domains for Testing
```php
// Domains with DNS records (should be filtered)
'google.com', 'github.com', 'stackoverflow.com'

// Domains without DNS records (should be shown)
'thisdoesnotexist12345.com', 'unavailabledomain999.net'

// Invalid domains (should be handled gracefully)
'invalid..domain', '..invalid', 'domain.toolong'
```

### DNS Response Mocks
```php
// Mock DNS response with A record
$mockResponseWithRecords = [
    'rdata' => [
        new MockARecord('192.168.1.1'),
        new MockCNAMERecord('alias.example.com')
    ]
];

// Mock DNS response with no records
$mockResponseEmpty = [
    'rdata' => []
];

// Mock DNS timeout error
$mockTimeoutError = new Net_DNS2_Exception('Timeout');
```

### Test Configuration
```php
// Override DNS configuration for testing
config([
    'dns.timeout' => 1, // Faster timeouts for testing
    'dns.cache_ttl' => 60, // Shorter cache for testing
    'dns.batch_size' => 3, // Smaller batches for testing
]);
```

## Performance Testing

### Load Testing
- Test DNS lookup performance under concurrent requests
- Test cache performance with high hit rates
- Test queue processing performance with large batches
- Test database performance with large cache tables

### Stress Testing
- Test system behavior with DNS service completely unavailable
- Test memory usage with large DNS result sets
- Test timeout behavior under network latency
- Test error recovery time after DNS service restoration

## Test Environment Setup

### Required Test Dependencies
```php
// composer.json test dependencies
"pestphp/pest": "^2.0",
"mockery/mockery": "^1.5",
"orchestra/testbench": "^8.0"
```

### Test Database Setup
```php
// Use in-memory SQLite for fast testing
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
];
```

### Mock DNS Resolver
```php
// Create test double for Net_DNS2_Resolver
class MockDnsResolver extends Net_DNS2_Resolver
{
    public function query(string $name, string $type): Net_DNS2_Packet_Response
    {
        // Return predefined responses based on test data
    }
}
```

## Continuous Integration

### Test Pipeline
1. Unit tests with coverage requirements (>90%)
2. Integration tests with real database
3. Feature tests with browser testing
4. Performance benchmarks
5. Security vulnerability scanning

### Test Data Management
- Seed test database with known DNS cache entries
- Use factories for generating test domain suggestions
- Implement test helpers for DNS mock setup
- Clean test environment between test runs

### Monitoring Test Health
- Track test execution time trends
- Monitor flaky test identification
- Validate test coverage reports
- Ensure test data consistency