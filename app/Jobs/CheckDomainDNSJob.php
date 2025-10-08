<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DomainCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Background job for checking domain DNS records.
 *
 * Performs asynchronous DNS lookups to determine if a domain has existing
 * DNS records, which indicates the domain is likely registered and in use.
 * Dispatches Livewire events on completion for real-time UI updates.
 */
class CheckDomainDNSJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @param  string  $domain  The domain to check DNS records for
     */
    public function __construct(
        public string $domain
    ) {}

    /**
     * Execute the job.
     *
     * Checks DNS records for the domain and caches the result.
     * Dispatches a Livewire event when complete for real-time UI updates.
     */
    public function handle(DomainCheckService $domainCheckService): void
    {
        try {
            // Check domain DNS records via the domain check service
            $result = $domainCheckService->checkDomain($this->domain);

            // Dispatch Livewire event for real-time updates
            Event::dispatch('domain-dns-checked', [
                'domain' => $this->domain,
                'available' => $result['available'] ?? null,
                'has_dns_records' => $result['has_dns_records'] ?? null,
                'status' => $result['status'] ?? 'unknown',
                'check_method' => $result['check_method'] ?? 'dns',
            ]);

            Log::info('DNS check completed', [
                'domain' => $this->domain,
                'available' => $result['available'] ?? null,
                'has_dns_records' => $result['has_dns_records'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('DNS check failed in job', [
                'domain' => $this->domain,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the job - dispatch event with error status
            Event::dispatch('domain-dns-checked', [
                'domain' => $this->domain,
                'available' => null,
                'has_dns_records' => null,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
