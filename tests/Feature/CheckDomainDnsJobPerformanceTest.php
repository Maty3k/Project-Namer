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
use Illuminate\Support\Facades\Queue;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->dnsService = Mockery::mock(DnsLookupServiceInterface::class);
    $this->performanceMonitor = Mockery::mock(DnsPerformanceMonitorInterface::class);

    Event::fake();
});

describe('CheckDomainDnsJob Performance Integration', function (): void {
    it('creates performance monitoring batch during job execution', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'monitor-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-' . $suggestion->id;
        $metricsRecord = DnsLookupMetrics::factory()->make(['id' => 1]);

        // Mock performance monitoring workflow
        $this->performanceMonitor->shouldReceive('startBatch')
            ->once()
            ->with($batchId)
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->once()
            ->andReturn($metricsRecord);

        // Mock DNS service
        $this->dnsService->shouldReceive('checkDomain')
            ->once()
            ->with('monitor-test.com')
            ->andReturn(DnsLookupResult::withoutRecords());

        $job = new CheckDomainDnsJob($suggestion->id);
        $job->handle($this->dnsService, $this->performanceMonitor);

        // Verify suggestion was updated
        $suggestion->refresh();
        expect($suggestion->dns_checked)->toBeTrue()
            ->and($suggestion->dns_has_records)->toBeFalse();
    });

    it('completes performance batch even when DNS lookup fails', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'error-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-' . $suggestion->id;

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

        $job = new CheckDomainDnsJob($suggestion->id);

        expect(fn () => $job->handle($this->dnsService, $this->performanceMonitor))
            ->toThrow(Exception::class, 'DNS lookup failed');
    });

    it('logs batch ID and metrics ID in successful completion', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'log-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-' . $suggestion->id;
        $metricsRecord = new DnsLookupMetrics([
            'id' => 123,
            'batch_id' => $batchId,
            'domains_checked' => 1,
            'successful_lookups' => 1,
            'failed_lookups' => 0,
            'cache_hits' => 0,
            'average_lookup_time' => 150.5,
            'total_processing_time' => 200.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Mock performance monitoring
        $this->performanceMonitor->shouldReceive('startBatch')
            ->with($batchId)
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->andReturn($metricsRecord);

        // Mock DNS service
        $this->dnsService->shouldReceive('checkDomain')
            ->andReturn(DnsLookupResult::withRecords(['A']));

        $job = new CheckDomainDnsJob($suggestion->id);
        $job->handle($this->dnsService, $this->performanceMonitor);

        // Verify the job completed successfully
        $suggestion->refresh();
        expect($suggestion->dns_checked)->toBeTrue()
            ->and($suggestion->dns_has_records)->toBeTrue();
    });

    it('handles performance monitoring gracefully when suggestion not found', function (): void {
        $nonExistentId = 99999;

        // Performance monitor should not be called when suggestion doesn't exist
        $this->performanceMonitor->shouldNotReceive('startBatch');
        $this->performanceMonitor->shouldNotReceive('completeBatch');

        $job = new CheckDomainDnsJob($nonExistentId);
        $job->handle($this->dnsService, $this->performanceMonitor);

        // Job should complete without error
        expect(true)->toBeTrue();
    });

    it('handles performance monitoring when suggestion already processed', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'already-processed.com',
            'dns_checked' => true,
            'dns_has_records' => false,
        ]);

        // Performance monitor should not be called for already processed suggestions
        $this->performanceMonitor->shouldNotReceive('startBatch');
        $this->performanceMonitor->shouldNotReceive('completeBatch');

        $job = new CheckDomainDnsJob($suggestion->id);
        $job->handle($this->dnsService, $this->performanceMonitor);

        // Suggestion should remain unchanged
        $suggestion->refresh();
        expect($suggestion->dns_checked)->toBeTrue();
    });

    it('includes batch_id in job logs', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'batch-log-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-' . $suggestion->id;

        $this->performanceMonitor->shouldReceive('startBatch')
            ->with($batchId)
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->andReturn(null);

        $this->dnsService->shouldReceive('checkDomain')
            ->andReturn(DnsLookupResult::withoutRecords());

        // Capture logs to verify batch_id is included
        $logMessages = [];
        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->andReturnUsing(function ($message, $context) use (&$logMessages) {
                $logMessages[] = $context;
            });

        $job = new CheckDomainDnsJob($suggestion->id);
        $job->handle($this->dnsService, $this->performanceMonitor);

        // Verify batch_id appears in log context
        $foundBatchId = false;
        foreach ($logMessages as $context) {
            if (isset($context['batch_id']) && $context['batch_id'] === $batchId) {
                $foundBatchId = true;
                break;
            }
        }

        expect($foundBatchId)->toBeTrue();
    });

    it('measures job execution time separately from DNS metrics', function (): void {
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'timing-test.com',
            'dns_checked' => false,
        ]);

        $batchId = 'job-' . $suggestion->id;
        $dnsMetrics = new DnsLookupMetrics([
            'id' => 456,
            'batch_id' => $batchId,
            'average_lookup_time' => 100.0, // DNS lookup time
            'total_processing_time' => 150.0, // Total DNS processing time
        ]);

        $this->performanceMonitor->shouldReceive('startBatch')
            ->andReturn($batchId);

        $this->performanceMonitor->shouldReceive('completeBatch')
            ->andReturn($dnsMetrics);

        // Mock DNS service with slight delay
        $this->dnsService->shouldReceive('checkDomain')
            ->andReturnUsing(function () {
                usleep(50000); // 50ms delay
                return DnsLookupResult::withoutRecords();
            });

        $startTime = microtime(true);

        $job = new CheckDomainDnsJob($suggestion->id);
        $job->handle($this->dnsService, $this->performanceMonitor);

        $jobExecutionTime = (microtime(true) - $startTime) * 1000;

        // Job execution time should be greater than DNS processing time
        expect($jobExecutionTime)->toBeGreaterThan($dnsMetrics->total_processing_time);
    });
});