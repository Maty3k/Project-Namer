<?php

declare(strict_types=1);

use App\Contracts\DnsLookupServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use App\DTOs\DnsLookupResult;
use App\Jobs\CheckDomainDnsJob;
use App\Models\DnsLookupMetrics;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->dnsService = \Mockery::mock(DnsLookupServiceInterface::class);
    $this->performanceMonitor = \Mockery::mock(DnsPerformanceMonitorInterface::class);

    Event::fake();
});

describe('CheckDomainDnsJob Basic Performance Integration', function (): void {
    it('integrates with performance monitoring successfully', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'performance-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-'.$suggestion->id;
        $metrics = DnsLookupMetrics::factory()->make([
            'id' => 1,
            'batch_id' => $batchId,
        ]);

        // Mock performance monitoring workflow
        $this->performanceMonitor->shouldReceive('startBatch')
            ->once()
            ->with($batchId)
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->once()
            ->andReturn($metrics);

        // Mock DNS service
        $this->dnsService->shouldReceive('checkDomain')
            ->once()
            ->with('performance-test.com')
            ->andReturn(DnsLookupResult::withoutRecords());

        // Create a job instance that won't access $this->job
        $job = new class($suggestion->id) extends CheckDomainDnsJob
        {
            public function __construct(int $suggestionId)
            {
                parent::__construct($suggestionId);
            }
        };

        $job->handle($this->dnsService, $this->performanceMonitor);

        // Verify suggestion was updated
        $suggestion->refresh();
        expect($suggestion->dns_checked)->toBeTrue()
            ->and($suggestion->dns_has_records)->toBeFalse();
    });

    it('handles performance monitoring for already processed suggestions', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'already-processed.com',
            'dns_checked' => true,
            'dns_has_records' => false,
        ]);

        // Performance monitor should not be called for already processed suggestions
        $this->performanceMonitor->shouldNotReceive('startBatch');
        $this->performanceMonitor->shouldNotReceive('completeBatch');

        $job = new class($suggestion->id) extends CheckDomainDnsJob
        {
            public function __construct(int $suggestionId)
            {
                parent::__construct($suggestionId);
            }
        };

        $job->handle($this->dnsService, $this->performanceMonitor);

        // Suggestion should remain unchanged
        $suggestion->refresh();
        expect($suggestion->dns_checked)->toBeTrue();
    });

    it('completes performance batch even when DNS lookup fails', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'error-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-'.$suggestion->id;

        // Mock performance monitoring workflow
        $this->performanceMonitor->shouldReceive('startBatch')
            ->once()
            ->with($batchId)
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->once()
            ->andReturn(null);

        // Mock DNS service to throw exception
        $this->dnsService->shouldReceive('checkDomain')
            ->once()
            ->andThrow(new Exception('DNS lookup failed'));

        $job = new class($suggestion->id) extends CheckDomainDnsJob
        {
            public function __construct(int $suggestionId)
            {
                parent::__construct($suggestionId);
            }
        };

        expect(fn () => $job->handle($this->dnsService, $this->performanceMonitor))
            ->toThrow(Exception::class, 'DNS lookup failed');
    });
});
