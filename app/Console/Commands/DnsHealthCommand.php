<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DnsHealthAlertService;
use Illuminate\Console\Command;

final class DnsHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dns:health
                           {action=status : Action to perform (status|check|test-alert|history|clear-history)}
                           {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage DNS health monitoring and alerts';

    /**
     * Execute the console command.
     */
    public function handle(DnsHealthAlertService $alertService): int
    {
        $action = $this->argument('action');
        $format = $this->option('format');

        return match ($action) {
            'status' => $this->showHealthStatus($alertService, $format),
            'check' => $this->performHealthCheck($alertService, $format),
            'test-alert' => $this->sendTestAlert($alertService),
            'history' => $this->showAlertHistory($alertService, $format),
            'clear-history' => $this->clearAlertHistory($alertService),
            default => $this->showHelp(),
        };
    }

    /**
     * Show current DNS health status
     */
    protected function showHealthStatus(DnsHealthAlertService $alertService, string $format): int
    {
        $this->info('Checking DNS health status...');

        $healthStatus = $alertService->checkDnsHealth();
        $config = $alertService->getAlertConfig();

        if ($format === 'json') {
            $this->line(json_encode([
                'health' => $healthStatus,
                'config' => $config,
            ], JSON_PRETTY_PRINT));
            return 0;
        }

        // Display health status in table format
        $this->newLine();
        $this->info('🏥 DNS Health Status');
        $this->newLine();

        $status = $healthStatus['is_healthy'] ? '✅ Healthy' : '❌ Unhealthy';
        $this->line("Overall Status: {$status}");
        $this->newLine();

        $this->table(['Metric', 'Current Value', 'Threshold', 'Status'], [
            [
                'Error Rate',
                $healthStatus['error_rate'] . '%',
                '< ' . $config['thresholds']['error_rate'] . '%',
                $healthStatus['error_rate'] > $config['thresholds']['error_rate'] ? '❌' : '✅'
            ],
            [
                'Cache Hit Rate',
                $healthStatus['cache_hit_rate'] . '%',
                '> ' . $config['thresholds']['cache_hit_rate'] . '%',
                $healthStatus['cache_hit_rate'] < $config['thresholds']['cache_hit_rate'] ? '❌' : '✅'
            ],
            [
                'Avg Response Time',
                $healthStatus['avg_response_time'] . 'ms',
                '< ' . $config['thresholds']['response_time'] . 'ms',
                $healthStatus['avg_response_time'] > $config['thresholds']['response_time'] ? '❌' : '✅'
            ],
            [
                'Total Requests',
                number_format($healthStatus['total_requests']),
                '-',
                '📊'
            ]
        ]);

        if (!empty($healthStatus['issues'])) {
            $this->newLine();
            $this->warn('⚠️  Current Issues:');
            foreach ($healthStatus['issues'] as $issue) {
                $this->line("   • {$issue}");
            }
        }

        $this->newLine();
        $this->line("Last Check: {$healthStatus['last_check']->format('Y-m-d H:i:s')}");

        return $healthStatus['is_healthy'] ? 0 : 1;
    }

    /**
     * Perform health check and trigger alerts if needed
     */
    protected function performHealthCheck(DnsHealthAlertService $alertService, string $format): int
    {
        $this->info('Performing DNS health check...');

        $alerts = $alertService->checkAndTriggerAlerts();

        if ($format === 'json') {
            $this->line(json_encode(['alerts' => $alerts], JSON_PRETTY_PRINT));
            return 0;
        }

        if (empty($alerts)) {
            $this->info('✅ No alerts triggered - DNS service is healthy');
            return 0;
        }

        $this->warn("⚠️  {count($alerts)} alerts triggered:");
        $this->newLine();

        $this->table(['Type', 'Severity', 'Message'], array_map(function ($alert) {
            return [
                $alert['type'],
                $alert['severity'],
                $alert['message'] ?? 'Alert triggered',
            ];
        }, $alerts));

        return 1;
    }

    /**
     * Send test alert
     */
    protected function sendTestAlert(DnsHealthAlertService $alertService): int
    {
        $this->info('Sending test alert...');

        $result = $alertService->sendTestAlert();

        if ($result['success']) {
            $this->info('✅ Test alert sent successfully');
            $this->line("Message: {$result['message']}");
        } else {
            $this->error('❌ Failed to send test alert');
        }

        return $result['success'] ? 0 : 1;
    }

    /**
     * Show alert history
     */
    protected function showAlertHistory(DnsHealthAlertService $alertService, string $format): int
    {
        $history = $alertService->getAlertHistory();

        if ($format === 'json') {
            $this->line(json_encode(['history' => $history], JSON_PRETTY_PRINT));
            return 0;
        }

        if (empty($history)) {
            $this->info('No alert history found');
            return 0;
        }

        $this->info('📋 DNS Alert History');
        $this->newLine();

        $this->table(
            ['Type', 'Severity', 'Triggered At', 'Status'],
            array_map(function ($alert) {
                return [
                    $alert['type'],
                    $alert['severity'],
                    $alert['triggered_at'],
                    $alert['resolved_at'] ? '✅ Resolved' : '🔴 Active',
                ];
            }, array_slice($history, -10)) // Show last 10 alerts
        );

        $this->newLine();
        $this->line('Total alerts in history: ' . count($history));

        return 0;
    }

    /**
     * Clear alert history
     */
    protected function clearAlertHistory(DnsHealthAlertService $alertService): int
    {
        if (!$this->confirm('Are you sure you want to clear all alert history?')) {
            $this->info('Alert history clearing cancelled');
            return 0;
        }

        $alertService->clearAlertHistory();
        $this->info('✅ Alert history cleared successfully');

        return 0;
    }

    /**
     * Show command help
     */
    protected function showHelp(): int
    {
        $this->info('DNS Health Management Commands:');
        $this->newLine();
        $this->line('  dns:health status       - Show current health status');
        $this->line('  dns:health check        - Perform health check and trigger alerts');
        $this->line('  dns:health test-alert   - Send a test alert');
        $this->line('  dns:health history      - Show alert history');
        $this->line('  dns:health clear-history - Clear alert history');
        $this->newLine();
        $this->line('Options:');
        $this->line('  --format=json          - Output in JSON format');

        return 0;
    }
}
