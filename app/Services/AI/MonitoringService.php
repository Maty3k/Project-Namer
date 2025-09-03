<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\AIModelPerformance;
use App\Models\GenerationSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for monitoring AI usage and performance analysis.
 *
 * Provides comprehensive monitoring, analytics, and alerting
 * for AI generation services and performance metrics.
 */
final class MonitoringService
{
    private const ALERT_THRESHOLDS = [
        'error_rate' => 0.10, // 10% error rate threshold
        'response_time' => 30000, // 30 seconds response time threshold
        'queue_depth' => 100, // Maximum queue depth
        'cost_per_hour' => 5.00, // $5 per hour cost threshold
    ];

    /**
     * Log AI generation attempt.
     *
     * @param  array<string>  $models
     */
    public function logGenerationAttempt(
        User $user,
        GenerationSession $session,
        array $models,
        string $mode
    ): void {
        Log::info('AI generation attempt started', [
            'user_id' => $user->id,
            'session_id' => $session->session_id,
            'models' => $models,
            'mode' => $mode,
            'deep_thinking' => $session->deep_thinking,
            'timestamp' => now()->toISOString(),
        ]);

        // Update usage counters
        $this->incrementUsageCounter('generation_attempts', $user->id);
        foreach ($models as $model) {
            $this->incrementUsageCounter("model_attempts:{$model}", $user->id);
        }
    }

    /**
     * Log AI generation success.
     *
     * @param  array<string, array<string>>  $results
     */
    public function logGenerationSuccess(
        GenerationSession $session,
        array $results,
        int $responseTimeMs
    ): void {
        $totalNames = array_sum(array_map('count', $results));

        Log::info('AI generation completed successfully', [
            'session_id' => $session->session_id,
            'user_id' => $session->user_id,
            'models_used' => array_keys($results),
            'total_names' => $totalNames,
            'response_time_ms' => $responseTimeMs,
            'mode' => $session->generation_mode,
            'timestamp' => now()->toISOString(),
        ]);

        // Update success metrics
        $this->incrementUsageCounter('generation_successes', $session->user_id);
        $this->recordResponseTime($responseTimeMs);

        // Check for performance alerts
        $this->checkPerformanceAlerts($responseTimeMs, $results);
    }

    /**
     * Log AI generation failure.
     */
    public function logGenerationFailure(
        GenerationSession $session,
        string $error,
        ?string $model = null
    ): void {
        Log::error('AI generation failed', [
            'session_id' => $session->session_id,
            'user_id' => $session->user_id,
            'error' => $error,
            'failed_model' => $model,
            'mode' => $session->generation_mode,
            'timestamp' => now()->toISOString(),
        ]);

        // Update failure metrics
        $this->incrementUsageCounter('generation_failures', $session->user_id);
        if ($model) {
            $this->incrementUsageCounter("model_failures:{$model}", $session->user_id);
        }

        // Check error rate alerts
        $this->checkErrorRateAlerts();
    }

    /**
     * Log rate limiting events.
     */
    public function logRateLimitHit(User $user, string $action, int $currentCount, int $limit): void
    {
        Log::warning('Rate limit exceeded', [
            'user_id' => $user->id,
            'action' => $action,
            'current_count' => $currentCount,
            'limit' => $limit,
            'timestamp' => now()->toISOString(),
        ]);

        $this->incrementUsageCounter('rate_limit_hits', $user->id);
    }

    /**
     * Log cache hit/miss events.
     */
    public function logCacheEvent(string $type, string $key, ?int $size = null): void
    {
        Log::debug('Cache event', [
            'type' => $type, // 'hit' or 'miss'
            'cache_key' => $key,
            'size_bytes' => $size,
            'timestamp' => now()->toISOString(),
        ]);

        $this->incrementUsageCounter("cache_{$type}s");
    }

    /**
     * Get real-time monitoring dashboard data.
     *
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(): array
    {
        return [
            'current_stats' => $this->getCurrentStats(),
            'error_rates' => $this->getErrorRates(),
            'response_times' => $this->getResponseTimeMetrics(),
            'model_performance' => $this->getModelPerformanceMetrics(),
            'queue_status' => $this->getQueueStatus(),
            'cost_analysis' => $this->getCostAnalysis(),
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    /**
     * Get current real-time statistics.
     *
     * @return array<string, mixed>
     */
    public function getCurrentStats(): array
    {
        return Cache::remember('ai_monitoring:current_stats', 60, fn () => [
            'active_sessions' => GenerationSession::where('status', 'processing')->count(),
            'pending_sessions' => GenerationSession::where('status', 'pending')->count(),
            'completed_today' => GenerationSession::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'failed_today' => GenerationSession::where('status', 'failed')
                ->whereDate('created_at', today())
                ->count(),
            'total_names_generated_today' => AIGeneration::whereDate('completed_at', today())
                ->sum('total_names_generated'),
        ]);
    }

    /**
     * Get error rate analysis.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getErrorRates(): array
    {
        return Cache::remember('ai_monitoring:error_rates', 300, function () {
            $timeRanges = [
                '1h' => now()->subHour(),
                '24h' => now()->subDay(),
                '7d' => now()->subWeek(),
            ];

            $errorRates = [];

            foreach ($timeRanges as $period => $since) {
                $total = AIGeneration::where('created_at', '>=', $since)->count();
                $failed = AIGeneration::where('created_at', '>=', $since)
                    ->where('status', 'failed')
                    ->count();

                $errorRates[$period] = [
                    'total' => $total,
                    'failed' => $failed,
                    'rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
                ];
            }

            return $errorRates;
        });
    }

    /**
     * Get response time metrics.
     */
    public function getResponseTimeMetrics(): ?object
    {
        return Cache::remember('ai_monitoring:response_times', 300, fn () => DB::table('ai_generations')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('total_response_time_ms')
            ->selectRaw('
                    AVG(total_response_time_ms) as avg_response_time,
                    MIN(total_response_time_ms) as min_response_time,
                    MAX(total_response_time_ms) as max_response_time,
                    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY total_response_time_ms) as p50_response_time,
                    PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY total_response_time_ms) as p95_response_time
                ')
            ->first());
    }

    /**
     * Get model performance comparison.
     *
     * @return array<array<string, mixed>>
     */
    public function getModelPerformanceMetrics(): array
    {
        return Cache::remember('ai_monitoring:model_performance', 600, fn () => AIModelPerformance::select([
            'model',
            'total_requests',
            'successful_requests',
            'failed_requests',
            DB::raw('(successful_requests * 100.0 / NULLIF(total_requests, 0)) as success_rate'),
            DB::raw('(total_response_time * 1.0 / NULLIF(successful_requests, 0)) as avg_response_time'),
            'names_generated',
            'last_used_at',
        ])
            ->orderByDesc('total_requests')
            ->get()
            ->toArray());
    }

    /**
     * Get queue status information.
     *
     * @return array<string, int>
     */
    public function getQueueStatus(): array
    {
        // This would require queue driver-specific implementation
        return [
            'ai-high' => 0,
            'ai-normal' => 0,
            'ai-low' => 0,
            'failed_jobs' => DB::table('failed_jobs')
                ->where('payload', 'like', '%ProcessAIGeneration%')
                ->count(),
        ];
    }

    /**
     * Get cost analysis.
     *
     * @return array<string, mixed>
     */
    public function getCostAnalysis(): array
    {
        return Cache::remember('ai_monitoring:cost_analysis', 900, function () {
            $today = today();
            $thisMonth = now()->startOfMonth();

            return [
                'today' => [
                    'total_cost' => AIGeneration::whereDate('completed_at', $today)
                        ->sum('total_cost_cents') / 100,
                    'total_tokens' => AIGeneration::whereDate('completed_at', $today)
                        ->sum('total_tokens_used'),
                ],
                'this_month' => [
                    'total_cost' => AIGeneration::where('completed_at', '>=', $thisMonth)
                        ->sum('total_cost_cents') / 100,
                    'total_tokens' => AIGeneration::where('completed_at', '>=', $thisMonth)
                        ->sum('total_tokens_used'),
                ],
                'by_model' => DB::table('ai_generations')
                    ->where('completed_at', '>=', $thisMonth)
                    ->select(
                        DB::raw("JSON_EXTRACT(models_requested, '$[0]') as model"),
                        DB::raw('SUM(total_cost_cents) / 100 as total_cost'),
                        DB::raw('COUNT(*) as usage_count')
                    )
                    ->groupBy('model')
                    ->orderByDesc('total_cost')
                    ->get(),
            ];
        });
    }

    /**
     * Get active alerts.
     *
     * @return array<array<string, mixed>>
     */
    public function getActiveAlerts(): array
    {
        $alerts = [];

        // Check error rate alerts
        $errorRates = $this->getErrorRates();
        if ($errorRates['1h']['rate'] > self::ALERT_THRESHOLDS['error_rate'] * 100) {
            $alerts[] = [
                'type' => 'error_rate',
                'severity' => 'high',
                'message' => "High error rate: {$errorRates['1h']['rate']}% in the last hour",
                'threshold' => self::ALERT_THRESHOLDS['error_rate'] * 100,
                'current_value' => $errorRates['1h']['rate'],
            ];
        }

        // Check response time alerts
        $responseMetrics = $this->getResponseTimeMetrics();
        if ($responseMetrics && $responseMetrics->avg_response_time > self::ALERT_THRESHOLDS['response_time']) {
            $alerts[] = [
                'type' => 'response_time',
                'severity' => 'medium',
                'message' => 'High average response time: '.round($responseMetrics->avg_response_time).'ms',
                'threshold' => self::ALERT_THRESHOLDS['response_time'],
                'current_value' => $responseMetrics->avg_response_time,
            ];
        }

        // Check cost alerts
        $costAnalysis = $this->getCostAnalysis();
        $hourlyRate = $costAnalysis['today']['total_cost'] / max(1, now()->hour ?: 1);
        if ($hourlyRate > self::ALERT_THRESHOLDS['cost_per_hour']) {
            $alerts[] = [
                'type' => 'cost',
                'severity' => 'medium',
                'message' => 'High cost rate: $'.round($hourlyRate, 2).' per hour',
                'threshold' => self::ALERT_THRESHOLDS['cost_per_hour'],
                'current_value' => $hourlyRate,
            ];
        }

        return $alerts;
    }

    /**
     * Generate monitoring report.
     *
     * @return array<string, mixed>
     */
    public function generateReport(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => now()->toDateString(),
                'days' => $days,
            ],
            'summary' => [
                'total_generations' => GenerationSession::where('created_at', '>=', $startDate)->count(),
                'successful_generations' => GenerationSession::where('created_at', '>=', $startDate)
                    ->where('status', 'completed')->count(),
                'total_names_generated' => AIGeneration::where('completed_at', '>=', $startDate)
                    ->sum('total_names_generated'),
                'unique_users' => GenerationSession::where('created_at', '>=', $startDate)
                    ->distinct('user_id')->count(),
                'average_response_time' => AIGeneration::where('completed_at', '>=', $startDate)
                    ->avg('total_response_time_ms'),
                'total_cost' => AIGeneration::where('completed_at', '>=', $startDate)
                    ->sum('total_cost_cents') / 100,
            ],
            'daily_breakdown' => $this->getDailyBreakdown($startDate),
            'model_usage' => $this->getModelUsageReport($startDate),
            'user_activity' => $this->getUserActivityReport($startDate),
        ];
    }

    /**
     * Increment usage counter.
     */
    private function incrementUsageCounter(string $metric, ?int $userId = null): void
    {
        $key = "ai_usage:{$metric}";
        if ($userId) {
            $key .= ":user:{$userId}";
        }

        Cache::increment($key);
        Cache::put($key, Cache::get($key, 0), 86400); // Set expiration
    }

    /**
     * Record response time for analysis.
     */
    private function recordResponseTime(int $responseTimeMs): void
    {
        $key = 'ai_response_times:'.now()->format('Y-m-d-H');
        $responseTimes = Cache::get($key, []);
        $responseTimes[] = $responseTimeMs;

        // Keep only last 1000 response times per hour
        if (count($responseTimes) > 1000) {
            $responseTimes = array_slice($responseTimes, -1000);
        }

        Cache::put($key, $responseTimes, 3600); // 1 hour
    }

    /**
     * Check for performance alerts.
     *
     * @param  array<string, array<string>>  $results
     */
    private function checkPerformanceAlerts(int $responseTimeMs, array $results): void
    {
        if ($responseTimeMs > self::ALERT_THRESHOLDS['response_time']) {
            Log::warning('High response time detected', [
                'response_time_ms' => $responseTimeMs,
                'threshold' => self::ALERT_THRESHOLDS['response_time'],
                'results_count' => array_sum(array_map('count', $results)),
            ]);
        }
    }

    /**
     * Check for error rate alerts.
     */
    private function checkErrorRateAlerts(): void
    {
        $errorRates = $this->getErrorRates();

        if ($errorRates['1h']['rate'] > self::ALERT_THRESHOLDS['error_rate'] * 100) {
            Log::alert('High error rate detected', [
                'error_rate' => $errorRates['1h']['rate'],
                'threshold' => self::ALERT_THRESHOLDS['error_rate'] * 100,
                'period' => '1 hour',
            ]);
        }
    }

    /**
     * Get daily breakdown of activity.
     *
     * @return array<array<string, mixed>>
     */
    private function getDailyBreakdown(\DateTimeInterface $startDate): array
    {
        return GenerationSession::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_sessions'),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions"),
                DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_sessions")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get model usage report.
     *
     * @return array<array<string, mixed>>
     */
    private function getModelUsageReport(\DateTimeInterface $startDate): array
    {
        return DB::table('ai_generations')
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("JSON_EXTRACT(models_requested, '$[0]') as model"),
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('AVG(total_response_time_ms) as avg_response_time'),
                DB::raw('SUM(total_names_generated) as total_names'),
                DB::raw('SUM(total_cost_cents) / 100 as total_cost')
            )
            ->groupBy('model')
            ->orderByDesc('usage_count')
            ->get()
            ->toArray();
    }

    /**
     * Get user activity report.
     *
     * @return array<array<string, mixed>>
     */
    private function getUserActivityReport(\DateTimeInterface $startDate): array
    {
        return GenerationSession::where('created_at', '>=', $startDate)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_sessions'),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions"),
                DB::raw('MIN(created_at) as first_session'),
                DB::raw('MAX(created_at) as last_session')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sessions')
            ->limit(50)
            ->get()
            ->toArray();
    }
}
