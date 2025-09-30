<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DnsHealthCheckService;
use App\Services\DnsRecoveryService;
use Illuminate\Console\Command;

class DnsRecoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dns:recover
                            {--emergency : Perform emergency restart (clears all caches)}
                            {--status : Show recovery status and recommendations}
                            {--force : Force recovery even if recently run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute DNS service recovery procedures to restore functionality';

    /**
     * Execute the console command.
     */
    public function handle(
        DnsRecoveryService $recoveryService,
        DnsHealthCheckService $healthCheck
    ): int {
        // Show status if requested
        if ($this->option('status')) {
            return $this->showRecoveryStatus($recoveryService);
        }

        // Perform emergency restart if requested
        if ($this->option('emergency')) {
            return $this->performEmergencyRestart($recoveryService);
        }

        // Regular recovery procedure
        return $this->performRecovery($recoveryService, $healthCheck);
    }

    /**
     * Show recovery status and recommendations.
     */
    private function showRecoveryStatus(DnsRecoveryService $recoveryService): int
    {
        $this->info('DNS Recovery Status');
        $this->line('==================');

        $status = $recoveryService->getRecoveryStatus();

        // Show current health
        $overallStatus = $status['health_status']['overall_status'] ?? 'unknown';
        $healthColor = match ($overallStatus) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'gray',
        };

        $this->line("Current Health: <fg={$healthColor};options=bold>{$overallStatus}</>");

        // Show recommendations
        if (! empty($status['recommendations'])) {
            $this->line('');
            $this->warn('Recommendations:');
            foreach ($status['recommendations'] as $recommendation) {
                $this->line(" • $recommendation");
            }
        } else {
            $this->info('No recommendations - service appears healthy.');
        }

        // Show last recovery time
        if ($status['last_recovery']) {
            $this->line('');
            $this->line('Last Recovery: '.json_encode($status['last_recovery']));
        }

        // Show auto recovery status
        $autoRecoveryStatus = $status['auto_recovery_enabled'] ? 'Enabled' : 'Disabled';
        $this->line('');
        $this->line("Auto Recovery: {$autoRecoveryStatus}");

        return Command::SUCCESS;
    }

    /**
     * Perform emergency restart.
     */
    private function performEmergencyRestart(DnsRecoveryService $recoveryService): int
    {
        $this->error('⚠️  EMERGENCY DNS SERVICE RESTART');
        $this->warn('This will clear ALL DNS caches and reset the service.');

        if (! $this->confirm('Are you sure you want to proceed?')) {
            $this->info('Emergency restart cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Executing emergency restart...');

        $result = $recoveryService->emergencyRestart();

        $this->newLine();
        $this->info('✓ Emergency restart completed');
        $this->line("  Time: {$result['restart_time']}");
        $this->line('  Actions performed:');
        foreach ($result['actions_performed'] as $action) {
            $this->line("    • {$action}");
        }
        $this->line('  Success: '.($result['recovery_successful'] ? 'Yes' : 'No'));

        return Command::SUCCESS;
    }

    /**
     * Perform regular recovery procedure.
     */
    private function performRecovery(
        DnsRecoveryService $recoveryService,
        DnsHealthCheckService $healthCheck
    ): int {
        // Check current health
        $currentHealth = $healthCheck->getHealthStatus();

        $this->info('DNS Service Recovery');
        $this->line('===================');
        $this->line("Current Status: {$currentHealth['overall_status']}");
        $this->line('');

        // Check if recovery was run recently
        if (! $this->option('force')) {
            $lastRecovery = $recoveryService->getLastRecoveryTime();
            if ($lastRecovery && $lastRecovery->diffInMinutes(now()) < 5) {
                $this->warn('Recovery was run recently. Use --force to override.');

                return Command::FAILURE;
            }
        }

        // Confirm recovery
        if (! $this->option('no-interaction')) {
            if (! $this->confirm('Do you want to proceed with DNS recovery?')) {
                $this->info('Recovery cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info('Starting recovery procedure...');
        $this->line('');

        // Execute recovery with progress display
        $results = $recoveryService->executeRecovery();

        // Display recovery steps
        foreach ($results['steps'] as $step) {
            $icon = $step['success'] ? '✓' : '✗';
            $color = $step['success'] ? 'green' : 'red';

            $this->line("<fg={$color}>{$icon}</> {$step['step']}");
            $this->line("  {$step['message']}");
        }

        // Mark recovery as completed
        $recoveryService->markRecoveryCompleted();

        // Show summary
        $this->newLine();
        $this->line('Recovery Summary');
        $this->line('---------------');
        $duration = $results['completed_at']->diffInSeconds($results['started_at']);
        $this->line("Duration: {$duration} seconds");
        $this->line("Health Before: {$results['health_before']['overall_status']}");
        $this->line("Health After: {$results['health_after']['overall_status']}");

        // Determine success
        if ($results['success']) {
            $this->newLine();
            $this->info('✓ Recovery completed successfully');

            // Check if health improved
            if ($results['health_after']['overall_status'] === 'healthy') {
                $this->info('DNS service is now healthy!');
            } elseif ($results['health_after']['overall_status'] === 'warning') {
                $this->warn('DNS service has some warnings. Monitor closely.');
            } else {
                $this->error('DNS service still has critical issues. Consider emergency restart.');
            }
        } else {
            $this->error('✗ Recovery completed with errors');
            $this->warn('Some recovery steps failed. Review the logs for details.');
        }

        return $results['success'] ? Command::SUCCESS : Command::FAILURE;
    }
}
