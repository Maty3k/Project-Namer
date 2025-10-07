# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-10-07-dns-domain-filtering/spec.md

> Created: 2025-10-07
> Status: Ready for Implementation

## Tasks

- [x] 1. Setup and Dependencies
  - [x] 1.1 Install spatie/dns package via composer
  - [x] 1.2 Create database migration for domain_caches table updates
  - [x] 1.3 Run migration and verify schema changes
  - [x] 1.4 Update DomainCache model with new fillable fields and casts

- [x] 2. Create DNSLookupService
  - [x] 2.1 Write tests for DNSLookupService class
  - [x] 2.2 Create App\Services\DNSLookupService class
  - [x] 2.3 Implement hasDNSRecords() method using Spatie DNS
  - [x] 2.4 Add DNS record type checking (A, AAAA, CNAME, MX)
  - [x] 2.5 Implement timeout handling (3 seconds)
  - [x] 2.6 Add error handling for DNS resolution failures
  - [x] 2.7 Add domain validation before DNS lookup
  - [x] 2.8 Verify all DNSLookupService tests pass

- [x] 3. Update DomainCheckService Integration
  - [x] 3.1 Write tests for updated DomainCheckService
  - [x] 3.2 Add DNS pre-screening to checkDomain() method
  - [x] 3.3 Skip API calls when DNS records exist
  - [x] 3.4 Update cache storage to include DNS check method
  - [x] 3.5 Implement different cache TTL per check method
  - [x] 3.6 Update checkBusinessName() to use DNS filtering
  - [x] 3.7 Add logging for DNS check results
  - [x] 3.8 Verify all DomainCheckService tests pass

- [x] 4. Background Job Processing
  - [x] 4.1 Write tests for CheckDomainDNSJob
  - [x] 4.2 Create App\Jobs\CheckDomainDNSJob class
  - [x] 4.3 Implement job handle() method with DNS checking
  - [x] 4.4 Add Livewire event dispatch on completion
  - [x] 4.5 Configure job retries (5 attempts)
  - [x] 4.6 Set job timeout (30 seconds)
  - [x] 4.7 Update NameGeneratorDashboard to dispatch DNS jobs
  - [x] 4.8 Verify CheckDomainDNSJob tests pass

- [x] 5. Domain Filtering Logic
  - [x] 5.1 Write tests for domain filtering in name generation
  - [x] 5.2 Update name generation to filter domains with DNS records
  - [x] 5.3 Add real-time status updates as DNS checks complete
  - [x] 5.4 Implement "Checking DNS..." loading state
  - [x] 5.5 Handle DNS check failures gracefully
  - [x] 5.6 Show "unknown" status for timeout/error cases
  - [x] 5.7 Verify domain filtering tests pass

- [x] 6. Update UI Components
  - [x] 6.1 Write tests for domain result card DNS status display
  - [x] 6.2 Update domain result card to show DNS check status
  - [x] 6.3 Add loading indicator during DNS checks
  - [x] 6.4 Display appropriate icons/badges for DNS status
  - [x] 6.5 Update tooltip text to explain DNS filtering
  - [x] 6.6 Verify UI component tests pass

- [x] 7. Cache Management Updates
  - [x] 7.1 Write tests for cache TTL and cleanup
  - [x] 7.2 Update clearExpiredCache() to handle DNS caches
  - [x] 7.3 Add console command for cache cleanup
  - [x] 7.4 Schedule cache cleanup in Laravel scheduler
  - [ ] 7.5 Add cache statistics to dashboard (optional)
  - [x] 7.6 Verify cache management tests pass

- [x] 8. Integration Testing and Verification
  - [x] 8.1 Write end-to-end integration tests
  - [x] 8.2 Test complete workflow from generation to filtering
  - [x] 8.3 Test background job processing
  - [x] 8.4 Verify real-time updates work correctly
  - [x] 8.5 Test cache hit/miss scenarios
  - [x] 8.6 Performance test: DNS checks complete in <1 second
  - [x] 8.7 Run full test suite and verify all tests pass

- [ ] 9. Documentation and Cleanup
  - [ ] 9.1 Update CHANGELOG.md with DNS filtering feature
  - [ ] 9.2 Add code comments for DNS-related methods
  - [ ] 9.3 Update any relevant documentation
  - [ ] 9.4 Clean up any debug logging
  - [ ] 9.5 Run composer ready to verify code quality
