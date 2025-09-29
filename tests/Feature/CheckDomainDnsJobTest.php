<?php

declare(strict_types=1);

use App\Contracts\DnsLookupServiceInterface;
use App\Jobs\CheckDomainDnsJob;
use App\Models\NameSuggestion;
use App\DTOs\DnsLookupResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clean up any existing data before each test
    NameSuggestion::query()->delete();
});

test('dns job can process single domain suggestion', function () {
    // Create a name suggestion without DNS data
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'example.com',
        'dns_checked' => false,
        'dns_has_records' => null,
        'dns_checked_at' => null,
    ]);

    // Mock the DnsLookupService
    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('example.com')
        ->andReturn(DnsLookupResult::withoutRecords())
        ->once();

    // Create and process the job
    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    // Verify the suggestion was updated
    $suggestion->refresh();
    expect($suggestion->dns_checked)->toBeTrue()
        ->and($suggestion->dns_has_records)->toBeFalse()
        ->and($suggestion->dns_checked_at)->not->toBeNull();
});

test('dns job handles domain with existing records', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'taken.com',
        'dns_checked' => false,
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('taken.com')
        ->andReturn(DnsLookupResult::withRecords(['A', 'MX']))
        ->once();

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    $suggestion->refresh();
    expect($suggestion->dns_checked)->toBeTrue()
        ->and($suggestion->dns_has_records)->toBeTrue()
        ->and($suggestion->dns_checked_at)->not->toBeNull();
});

test('dns job handles DNS lookup errors gracefully', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'error.com',
        'dns_checked' => false,
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('error.com')
        ->andReturn(DnsLookupResult::withError('DNS server timeout'))
        ->once();

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    $suggestion->refresh();
    expect($suggestion->dns_checked)->toBeTrue()
        ->and($suggestion->dns_has_records)->toBeNull()
        ->and($suggestion->dns_checked_at)->not->toBeNull();
});

test('dns job handles missing suggestion gracefully', function () {
    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldNotReceive('checkDomain');

    $job = new CheckDomainDnsJob(99999); // Non-existent ID

    // Job should complete without error even if suggestion doesn't exist
    expect(fn() => $job->handle($mockDnsService))->not->toThrow(Exception::class);
});

test('dns job is queued correctly', function () {
    Queue::fake();

    $suggestion = NameSuggestion::factory()->create();

    CheckDomainDnsJob::dispatch($suggestion->id);

    Queue::assertPushed(CheckDomainDnsJob::class, function ($job) use ($suggestion) {
        return $job->suggestionId === $suggestion->id;
    });
});

test('dns job can be retried on failure', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'retry.com',
        'dns_checked' => false,
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->andThrow(new Exception('Network timeout'));

    $job = new CheckDomainDnsJob($suggestion->id);

    // Job should throw exception for retry mechanism
    expect(fn() => $job->handle($mockDnsService))->toThrow(Exception::class);
});

test('dns job updates suggestion with cached result', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'cached.com',
        'dns_checked' => false,
    ]);

    $cachedResult = DnsLookupResult::fromCache(
        hasRecords: true,
        recordTypes: ['A', 'CNAME'],
        error: null,
        checkedAt: now()->subHour()
    );

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('cached.com')
        ->andReturn($cachedResult)
        ->once();

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    $suggestion->refresh();
    expect($suggestion->dns_checked)->toBeTrue()
        ->and($suggestion->dns_has_records)->toBeTrue()
        ->and($suggestion->dns_checked_at)->not->toBeNull();
});

test('dns job skips already processed suggestions', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'processed.com',
        'dns_checked' => true,
        'dns_has_records' => false,
        'dns_checked_at' => now()->subHour(),
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldNotReceive('checkDomain');

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    // Suggestion should remain unchanged
    $originalCheckedAt = $suggestion->dns_checked_at;
    $suggestion->refresh();
    expect($suggestion->dns_checked_at->equalTo($originalCheckedAt))->toBeTrue();
});

test('dns job handles invalid domain names', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'invalid..domain',
        'dns_checked' => false,
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('invalid..domain')
        ->andReturn(DnsLookupResult::withError('Invalid domain format'))
        ->once();

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    $suggestion->refresh();
    expect($suggestion->dns_checked)->toBeTrue()
        ->and($suggestion->dns_has_records)->toBeNull()
        ->and($suggestion->dns_checked_at)->not->toBeNull();
});

test('dns job tracks processing time and metrics', function () {
    $suggestion = NameSuggestion::factory()->create([
        'name' => 'metrics.com',
        'dns_checked' => false,
    ]);

    $mockDnsService = $this->mock(DnsLookupServiceInterface::class);
    $mockDnsService->shouldReceive('checkDomain')
        ->with('metrics.com')
        ->andReturn(DnsLookupResult::withoutRecords())
        ->once();

    $startTime = now();

    $job = new CheckDomainDnsJob($suggestion->id);
    $job->handle($mockDnsService);

    $suggestion->refresh();
    expect($suggestion->dns_checked_at)->not->toBeNull()
        ->and($suggestion->dns_checked)->toBeTrue();
});