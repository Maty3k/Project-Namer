<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\DnsLookupServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use App\Models\NameSuggestion;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class CheckDomainDnsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $backoff = 5;

    public function __construct(
        public readonly int $suggestionId
    ) {
        $this->onQueue(config('queue.dns_queue', 'dns'));
    }

    public function handle(DnsLookupServiceInterface $dnsService, DnsPerformanceMonitorInterface $performanceMonitor): void
    {
        $startTime = microtime(true);
        $batchId = null;

        try {
            $suggestion = NameSuggestion::find($this->suggestionId);

            if ($suggestion === null) {
                Log::warning('DNS job: Suggestion not found', [
                    'suggestion_id' => $this->suggestionId,
                    'job_id' => $this->job->getJobId(),
                ]);
                return;
            }

            // Skip if already processed
            if ($suggestion->dns_checked) {
                Log::debug('DNS job: Suggestion already processed', [
                    'suggestion_id' => $this->suggestionId,
                    'name' => $suggestion->name,
                ]);
                return;
            }

            // Start performance monitoring batch
            $batchId = $performanceMonitor->startBatch("job-{$this->suggestionId}");

            Log::info('DNS job: Starting DNS check', [
                'suggestion_id' => $this->suggestionId,
                'name' => $suggestion->name,
                'attempt' => $this->attempts(),
                'batch_id' => $batchId,
            ]);

            // Perform DNS lookup
            $result = $dnsService->checkDomain($suggestion->name);

            // Update suggestion with results
            $suggestion->update([
                'dns_checked' => true,
                'dns_has_records' => $result->isError() ? null : $result->hasRecords,
                'dns_checked_at' => now(),
            ]);

            // Complete performance monitoring
            $metrics = $performanceMonitor->completeBatch();

            // Dispatch completion event for progressive enhancement
            Event::dispatch('livewire:dispatch', [
                'component' => null,
                'event' => 'dns-check-completed',
                'data' => ['suggestionId' => $this->suggestionId],
            ]);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('DNS job: Completed successfully', [
                'suggestion_id' => $this->suggestionId,
                'name' => $suggestion->name,
                'has_records' => $result->hasRecords,
                'is_error' => $result->isError(),
                'processing_time_ms' => $processingTime,
                'attempt' => $this->attempts(),
                'batch_id' => $batchId,
                'metrics_id' => $metrics?->id,
            ]);

        } catch (Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            // Complete performance monitoring with error
            if ($batchId !== null) {
                $performanceMonitor->completeBatch();
            }

            Log::error('DNS job: Failed with exception', [
                'suggestion_id' => $this->suggestionId,
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'batch_id' => $batchId,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(?Exception $exception = null): void
    {
        Log::error('DNS job: Failed permanently', [
            'suggestion_id' => $this->suggestionId,
            'error' => $exception?->getMessage(),
            'attempts_made' => $this->attempts(),
        ]);

        // Mark suggestion as processed with error state
        $suggestion = NameSuggestion::find($this->suggestionId);
        if ($suggestion !== null && !$suggestion->dns_checked) {
            $suggestion->update([
                'dns_checked' => true,
                'dns_has_records' => null,
                'dns_checked_at' => now(),
            ]);

            // Dispatch failure event for permanent failures
            Event::dispatch('livewire:dispatch', [
                'component' => null,
                'event' => 'dns-check-failed',
                'data' => [
                    'suggestionId' => $this->suggestionId,
                    'error' => $exception?->getMessage() ?? 'DNS check failed after maximum retries',
                ],
            ]);
        }
    }

    public function retryUntil()
    {
        return now()->addMinutes(10);
    }

    public function tags(): array
    {
        return [
            'dns',
            'suggestion:' . $this->suggestionId,
        ];
    }
}
