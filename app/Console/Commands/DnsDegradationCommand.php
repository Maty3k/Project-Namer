<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DnsDegradationService;
use Illuminate\Console\Command;

/**
 * Command for managing DNS service degradation mode.
 *
 * Allows administrators to enable/disable degradation mode manually
 * and check the current degradation status.
 */
final class DnsDegradationCommand extends Command
{
    protected $signature = 'dns:degradation
                            {action : Action to perform (status|enable|disable)}
                            {--reason= : Reason for enabling degradation mode}
                            {--strategy= : Override default degradation strategy}';

    protected $description = 'Manage DNS service degradation mode';

    public function handle(DnsDegradationService $degradationService): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'status' => $this->showStatus($degradationService),
            'enable' => $this->enableDegradation($degradationService),
            'disable' => $this->disableDegradation($degradationService),
            default => (function () use ($action) {
                $this->error("Invalid action: {$action}. Use: status, enable, or disable");

                return self::FAILURE;
            })(),
        };
    }

    private function showStatus(DnsDegradationService $degradationService): int
    {
        $status = $degradationService->getDegradationStatus();
        $metrics = $degradationService->getDegradationMetrics();

        $this->info('DNS Service Degradation Status');
        $this->info('================================');

        if ($status['degraded']) {
            $this->error('Status: DEGRADED MODE ACTIVE');
            $this->line("Reason: {$status['reason']}");
            $this->line("Strategy: {$status['fallback_strategy']}");

            if ($status['estimated_recovery']) {
                $estimatedRecovery = $status['estimated_recovery'];
                if ($estimatedRecovery instanceof \DateTime || $estimatedRecovery instanceof \DateTimeInterface) {
                    $this->line("Estimated Recovery: {$estimatedRecovery->format('Y-m-d H:i:s')}");
                } else {
                    $this->line("Estimated Recovery: {$estimatedRecovery}");
                }
            } else {
                $this->line('Estimated Recovery: Manual intervention required');
            }
        } else {
            $this->info('Status: NORMAL OPERATION');
        }

        $this->newLine();
        $this->info('Health Metrics:');

        if (isset($status['health_status'])) {
            $health = $status['health_status'];
            $this->line("Error Rate: {$health['error_rate']}%");
            $this->line("Cache Hit Rate: {$health['cache_hit_rate']}%");
            $this->line("Average Response Time: {$health['avg_response_time']}ms");
            $this->line("Total Requests: {$health['total_requests']}");

            if (! empty($health['issues'])) {
                $this->newLine();
                $this->warn('Current Issues:');
                foreach ($health['issues'] as $issue) {
                    $this->line("- {$issue}");
                }
            }
        }

        return self::SUCCESS;
    }

    private function enableDegradation(DnsDegradationService $degradationService): int
    {
        $reason = $this->option('reason') ?: 'Manual override via command';

        if ($degradationService->isDegradedMode()) {
            $this->warn('DNS service is already in degradation mode');

            return self::SUCCESS;
        }

        $degradationService->enableDegradationMode($reason);

        $this->info('DNS degradation mode enabled');
        $this->line("Reason: {$reason}");

        // Show new status
        $this->newLine();

        return $this->showStatus($degradationService);
    }

    private function disableDegradation(DnsDegradationService $degradationService): int
    {
        if (! $degradationService->isDegradedMode()) {
            $this->info('DNS service is not in degradation mode');

            return self::SUCCESS;
        }

        $degradationService->disableDegradationMode();

        $this->info('DNS degradation mode disabled');

        // Show new status
        $this->newLine();

        return $this->showStatus($degradationService);
    }
}
