<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * Command for analyzing DNS logs to identify patterns, errors, and performance issues.
 */
final class DnsLogAnalyzeCommand extends Command
{
    protected $signature = 'dns:analyze-logs
                            {--period=24h : Time period to analyze (1h, 24h, 7d, 30d)}
                            {--type= : Filter by log type (dns_lookup, dns_error, dns_performance)}
                            {--domain= : Filter by specific domain}
                            {--errors : Show only error logs}
                            {--performance : Show performance summary}
                            {--export= : Export results to file}';

    protected $description = 'Analyze DNS logs for patterns, errors, and performance metrics';

    public function handle(): int
    {
        $period = $this->option('period');
        $type = $this->option('type');
        $domain = $this->option('domain');
        $errorsOnly = $this->option('errors');
        $performance = $this->option('performance');
        $exportFile = $this->option('export');

        $this->info('DNS Log Analysis');
        $this->info('================');

        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            $this->error('Log file not found: ' . $logFile);
            return self::FAILURE;
        }

        $startTime = $this->parseTimePeriod($period);
        $logs = $this->parseLogs($logFile, $startTime, $type, $domain, $errorsOnly);

        if (empty($logs)) {
            $this->warn('No DNS logs found for the specified criteria.');
            return self::SUCCESS;
        }

        $this->displaySummary($logs, $period);

        if ($errorsOnly) {
            $this->displayErrorAnalysis($logs);
        }

        if ($performance) {
            $this->displayPerformanceAnalysis($logs);
        }

        $this->displayTopDomains($logs);
        $this->displayErrorPatterns($logs);

        if ($exportFile) {
            $this->exportResults($logs, $exportFile);
        }

        return self::SUCCESS;
    }

    private function parseTimePeriod(string $period): Carbon
    {
        $now = Carbon::now();

        return match ($period) {
            '1h' => $now->subHour(),
            '24h' => $now->subDay(),
            '7d' => $now->subWeek(),
            '30d' => $now->subMonth(),
            default => $now->subDay(),
        };
    }

    private function parseLogs(string $logFile, Carbon $startTime, ?string $type, ?string $domain, bool $errorsOnly): array
    {
        $logs = [];
        $handle = fopen($logFile, 'r');

        if (!$handle) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            if (!$this->isDnsLog($line)) {
                continue;
            }

            $logEntry = $this->parseLogLine($line);

            if (!$logEntry) {
                continue;
            }

            // Filter by time
            if ($logEntry['timestamp']->lt($startTime)) {
                continue;
            }

            // Filter by type
            if ($type && !str_contains($logEntry['message'], $type)) {
                continue;
            }

            // Filter by domain
            if ($domain && !str_contains($logEntry['context'], $domain)) {
                continue;
            }

            // Filter errors only
            if ($errorsOnly && !in_array($logEntry['level'], ['ERROR', 'CRITICAL'])) {
                continue;
            }

            $logs[] = $logEntry;
        }

        fclose($handle);

        return $logs;
    }

    private function isDnsLog(string $line): bool
    {
        return str_contains($line, 'DNS') || str_contains($line, 'dns_');
    }

    private function parseLogLine(string $line): ?array
    {
        // Parse Laravel log format: [timestamp] level: message context
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/';

        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }

        $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
        $level = strtoupper($matches[2]);
        $content = $matches[3];

        // Extract JSON context if present
        $context = [];
        if (preg_match('/\{.*\}$/', $content, $contextMatch)) {
            $contextJson = $contextMatch[0];
            $context = json_decode($contextJson, true) ?? [];
            $content = trim(str_replace($contextJson, '', $content));
        }

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $content,
            'context' => $context,
            'raw_line' => $line,
        ];
    }

    private function displaySummary(array $logs, string $period): void
    {
        $total = count($logs);
        $levels = array_count_values(array_column($logs, 'level'));

        $this->newLine();
        $this->info("Summary for period: {$period}");
        $this->info("Total DNS log entries: {$total}");

        $this->newLine();
        $this->info('Log levels:');
        foreach ($levels as $level => $count) {
            $percentage = round(($count / $total) * 100, 1);
            $this->line("  {$level}: {$count} ({$percentage}%)");
        }
    }

    private function displayErrorAnalysis(array $logs): void
    {
        $errors = array_filter($logs, fn($log) => in_array($log['level'], ['ERROR', 'CRITICAL']));

        if (empty($errors)) {
            $this->info("\nNo errors found in the specified period.");
            return;
        }

        $this->newLine();
        $this->error('Error Analysis:');

        $errorMessages = array_count_values(array_column($errors, 'message'));
        arsort($errorMessages);

        $this->info('Top error messages:');
        $count = 0;
        foreach ($errorMessages as $message => $occurrences) {
            if ($count++ >= 10) break;
            $this->line("  {$occurrences}x: " . substr($message, 0, 80) . '...');
        }
    }

    private function displayPerformanceAnalysis(array $logs): void
    {
        $performanceLogs = array_filter($logs, function($log) {
            return isset($log['context']['response_time_ms']);
        });

        if (empty($performanceLogs)) {
            $this->info("\nNo performance data found.");
            return;
        }

        $responseTimes = array_column(array_column($performanceLogs, 'context'), 'response_time_ms');

        $avg = array_sum($responseTimes) / count($responseTimes);
        $min = min($responseTimes);
        $max = max($responseTimes);

        sort($responseTimes);
        $median = $responseTimes[intval(count($responseTimes) / 2)];

        $this->newLine();
        $this->info('Performance Analysis:');
        $this->line("  Average response time: " . round($avg, 2) . "ms");
        $this->line("  Median response time: " . round($median, 2) . "ms");
        $this->line("  Min response time: " . round($min, 2) . "ms");
        $this->line("  Max response time: " . round($max, 2) . "ms");

        // Response time distribution
        $slow = count(array_filter($responseTimes, fn($time) => $time > 5000));
        $medium = count(array_filter($responseTimes, fn($time) => $time > 1000 && $time <= 5000));
        $fast = count($responseTimes) - $slow - $medium;

        $this->line("  Fast (<1s): {$fast}");
        $this->line("  Medium (1-5s): {$medium}");
        $this->line("  Slow (>5s): {$slow}");
    }

    private function displayTopDomains(array $logs): void
    {
        $domains = [];

        foreach ($logs as $log) {
            if (isset($log['context']['domain'])) {
                $domain = $log['context']['domain'];
                $domains[$domain] = ($domains[$domain] ?? 0) + 1;
            }
        }

        if (empty($domains)) {
            return;
        }

        arsort($domains);

        $this->newLine();
        $this->info('Top domains by lookup count:');
        $count = 0;
        foreach ($domains as $domain => $lookups) {
            if ($count++ >= 10) break;
            $this->line("  {$lookups}x: {$domain}");
        }
    }

    private function displayErrorPatterns(array $logs): void
    {
        $errors = array_filter($logs, fn($log) => in_array($log['level'], ['ERROR', 'CRITICAL']));

        if (empty($errors)) {
            return;
        }

        // Group errors by hour to find patterns
        $errorsByHour = [];
        foreach ($errors as $error) {
            $hour = $error['timestamp']->format('H');
            $errorsByHour[$hour] = ($errorsByHour[$hour] ?? 0) + 1;
        }

        $this->newLine();
        $this->info('Error patterns by hour:');
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad((string)$hour, 2, '0', STR_PAD_LEFT);
            $count = $errorsByHour[$hour] ?? 0;
            $bar = str_repeat('█', min(50, $count));
            $this->line("  {$hourStr}:00 {$count} {$bar}");
        }
    }

    private function exportResults(array $logs, string $filename): void
    {
        $data = [
            'analysis_date' => Carbon::now()->toISOString(),
            'total_entries' => count($logs),
            'logs' => $logs,
        ];

        $content = json_encode($data, JSON_PRETTY_PRINT);
        File::put($filename, $content);

        $this->info("\nResults exported to: {$filename}");
    }
}