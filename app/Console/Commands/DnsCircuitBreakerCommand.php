<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\DnsLookupServiceInterface;
use App\Services\DnsCircuitBreakerService;
use Illuminate\Console\Command;

final class DnsCircuitBreakerCommand extends Command
{
    protected $signature = 'dns:circuit-breaker
                            {action : Action to perform (status|reset|stats)}
                            {--format=table : Output format (table|json)}';

    protected $description = 'Manage and monitor DNS circuit breaker status';

    public function handle(): int
    {
        $action = $this->argument('action');
        $format = $this->option('format');

        $dnsService = app(DnsLookupServiceInterface::class);

        if (! $dnsService instanceof DnsCircuitBreakerService) {
            $this->error('DNS circuit breaker is not enabled or configured');

            return self::FAILURE;
        }

        return match ($action) {
            'status' => $this->showStatus($dnsService, $format),
            'reset' => $this->resetCircuitBreaker($dnsService),
            'stats' => $this->showStats($dnsService, $format),
            default => $this->showHelp(),
        };
    }

    private function showStatus(DnsCircuitBreakerService $service, string $format): int
    {
        $stats = $service->getCircuitBreakerStats();
        $isHealthy = $service->isCircuitBreakerHealthy();
        $isOpen = $service->isCircuitBreakerOpen();

        if ($format === 'json') {
            $this->line(json_encode([
                'state' => $stats['state'],
                'healthy' => $isHealthy,
                'open' => $isOpen,
                'failure_count' => $stats['failure_count'],
                'service_name' => $stats['service_name'],
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('DNS Circuit Breaker Status');
        $this->line('');

        $statusColor = match ($stats['state']) {
            'closed' => 'green',
            'half_open' => 'yellow',
            'open' => 'red',
            default => 'white',
        };

        $this->line("Status: <fg={$statusColor}>{$stats['state']}</>");
        $this->line("Service: {$stats['service_name']}");
        $this->line('Healthy: '.($isHealthy ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        $this->line("Failure Count: {$stats['failure_count']}");
        $this->line("Success Count: {$stats['success_count']}");

        if ($isOpen) {
            $this->warn('⚠️  Circuit breaker is OPEN - DNS lookups are being blocked!');
        } elseif ($stats['state'] === 'half_open') {
            $this->comment('🔄 Circuit breaker is HALF-OPEN - testing recovery...');
        } else {
            $this->info('✅ Circuit breaker is operating normally');
        }

        return self::SUCCESS;
    }

    private function showStats(DnsCircuitBreakerService $service, string $format): int
    {
        $stats = $service->getCircuitBreakerStats();

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('DNS Circuit Breaker Statistics');
        $this->line('');

        $data = [
            ['Service Name', $stats['service_name']],
            ['Current State', $stats['state']],
            ['Failure Count', $stats['failure_count']],
            ['Success Count', $stats['success_count']],
            ['Failure Threshold', $stats['failure_threshold']],
            ['Timeout (minutes)', $stats['timeout_minutes']],
            ['Success Threshold', $stats['success_threshold']],
            ['Last Failure', $stats['last_failure_time'] ?? 'None'],
        ];

        $this->table(['Property', 'Value'], $data);

        return self::SUCCESS;
    }

    private function resetCircuitBreaker(DnsCircuitBreakerService $service): int
    {
        $beforeState = $service->getCircuitBreakerStats()['state'];

        if ($this->confirm("Are you sure you want to reset the DNS circuit breaker? Current state: {$beforeState}")) {
            $service->resetCircuitBreaker();

            $afterState = $service->getCircuitBreakerStats()['state'];

            $this->info('Circuit breaker reset successfully!');
            $this->line("State changed from <fg=red>{$beforeState}</> to <fg=green>{$afterState}</>");

            return self::SUCCESS;
        }

        $this->comment('Reset cancelled.');

        return self::SUCCESS;
    }

    private function showHelp(): int
    {
        $this->error('Invalid action. Available actions:');
        $this->line('  status  - Show current circuit breaker status');
        $this->line('  stats   - Show detailed circuit breaker statistics');
        $this->line('  reset   - Reset the circuit breaker to closed state');
        $this->line('');
        $this->line('Options:');
        $this->line('  --format=table|json  - Output format (default: table)');

        return self::FAILURE;
    }
}
