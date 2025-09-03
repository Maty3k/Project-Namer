<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\AIModelPerformance;
use App\Models\NameSuggestion;
use App\Models\User;
use App\Models\UserAIPreferences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * AI Analytics Service for tracking and reporting AI generation usage and performance.
 */
class AIAnalyticsService
{
    /**
     * Get comprehensive AI usage analytics for a user.
     *
     * @param  string  $period  ('day', 'week', 'month', 'year', 'all')
     * @return array<string, mixed>
     */
    public function getUserAnalytics(User $user, string $period = 'month'): array
    {
        $cacheKey = "ai_analytics_user_{$user->id}_{$period}";

        return Cache::remember($cacheKey, 300, function () use ($user, $period) {
            $dateRange = $this->getDateRange($period);

            return [
                'overview' => $this->getUserOverviewStats($user, $dateRange),
                'model_usage' => $this->getUserModelUsage($user, $dateRange),
                'generation_trends' => $this->getUserGenerationTrends($user, $dateRange),
                'performance_metrics' => $this->getUserPerformanceMetrics($user, $dateRange),
                'cost_analysis' => $this->getUserCostAnalysis($user, $dateRange),
                'success_rates' => $this->getUserSuccessRates($user, $dateRange),
                'preferences_evolution' => $this->getUserPreferencesEvolution($user, $dateRange),
            ];
        });
    }

    /**
     * Get system-wide AI analytics (admin only).
     *
     * @return array<string, mixed>
     */
    public function getSystemAnalytics(string $period = 'month'): array
    {
        $cacheKey = "ai_analytics_system_{$period}";

        return Cache::remember($cacheKey, 600, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            return [
                'overview' => $this->getSystemOverviewStats($dateRange),
                'model_performance' => $this->getSystemModelPerformance($dateRange),
                'usage_patterns' => $this->getSystemUsagePatterns($dateRange),
                'cost_breakdown' => $this->getSystemCostBreakdown($dateRange),
                'error_analysis' => $this->getSystemErrorAnalysis($dateRange),
                'user_engagement' => $this->getSystemUserEngagement($dateRange),
                'capacity_metrics' => $this->getSystemCapacityMetrics($dateRange),
            ];
        });
    }

    /**
     * Get real-time AI generation metrics.
     *
     * @return array<string, mixed>
     */
    public function getRealTimeMetrics(): array
    {
        return Cache::remember('ai_realtime_metrics', 60, fn () => [
            'active_generations' => AIGeneration::whereIn('status', ['pending', 'running'])->count(),
            'generations_last_hour' => AIGeneration::where('created_at', '>=', now()->subHour())->count(),
            'average_response_time' => $this->calculateAverageResponseTime(),
            'current_success_rate' => $this->calculateCurrentSuccessRate(),
            'models_status' => $this->getModelsStatus(),
            'queue_length' => $this->getQueueLength(),
            'queue_depth' => $this->getQueueLength(), // Alias for queue_length
            'error_rate_last_hour' => $this->getErrorRateLastHour(),
            'error_rate' => $this->getErrorRateLastHour(), // Alias for backward compatibility
            'top_models' => $this->getTopModelsLastHour(),
        ]);
    }

    /**
     * Generate AI performance report for a specific model.
     *
     * @return array<string, mixed>
     */
    public function getModelPerformanceReport(string $modelId, string $period = 'month'): array
    {
        $cacheKey = "ai_model_report_{$modelId}_{$period}";

        return Cache::remember($cacheKey, 300, function () use ($modelId, $period) {
            $dateRange = $this->getDateRange($period);

            return [
                'model_info' => $this->getModelInfo($modelId),
                'usage_stats' => $this->getModelUsageStats($modelId, $dateRange),
                'performance_metrics' => $this->getModelPerformanceMetrics($modelId, $dateRange),
                'cost_efficiency' => $this->getModelCostEfficiency($modelId, $dateRange),
                'user_satisfaction' => $this->getModelUserSatisfaction($modelId, $dateRange),
                'reliability_metrics' => $this->getModelReliabilityMetrics($modelId, $dateRange),
                'trending_analysis' => $this->getModelTrendingAnalysis($modelId, $dateRange),
            ];
        });
    }

    /**
     * Track AI generation event for analytics.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function trackGenerationEvent(AIGeneration $generation, string $event, array $metadata = []): void
    {
        $eventData = [
            'generation_id' => $generation->id,
            'user_id' => $generation->user_id,
            'models' => $generation->models_requested,
            'event' => $event,
            'timestamp' => now(),
            'metadata' => $metadata,
        ];

        // Store in cache for real-time analytics
        $cacheKey = 'ai_events_'.now()->format('Y-m-d-H');
        $events = Cache::get($cacheKey, []);
        $events[] = $eventData;
        Cache::put($cacheKey, $events, 3600);

        // Update model performance metrics if relevant
        if (in_array($event, ['completed', 'failed'])) {
            $this->updateModelPerformanceMetrics($generation, $event, $metadata);
        }
    }

    /**
     * Get user overview statistics.
     *
     * @param  array<string, Carbon>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserOverviewStats(User $user, array $dateRange): array
    {
        $generations = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalNames = NameSuggestion::where('user_id', $user->id)
            ->where('is_ai_generated', true)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return [
            'total_generations' => $generations->count(),
            'total_names_generated' => $totalNames,
            'successful_generations' => $generations->where('status', 'completed')->count(),
            'failed_generations' => $generations->where('status', 'failed')->count(),
            'average_names_per_generation' => $generations->count() > 0 ? $totalNames / $generations->count() : 0,
            'most_used_model' => $this->getMostUsedModel($generations),
            'favorite_generation_mode' => $this->getFavoriteGenerationMode($generations),
            'total_processing_time' => $this->getTotalProcessingTime($generations),
        ];
    }

    /**
     * Get user model usage statistics.
     *
     * @param  array<string, Carbon>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserModelUsage(User $user, array $dateRange): array
    {
        $modelUsage = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->flatMap(fn ($gen) => $gen->models_requested)
            ->countBy()
            ->toArray();

        $modelPerformance = [];
        foreach ($modelUsage as $model => $count) {
            $performance = AIModelPerformance::where('user_id', $user->id)
                ->where('model_name', $model)
                ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                ->first();

            $modelPerformance[$model] = [
                'usage_count' => $count,
                'average_response_time' => $performance ? $performance->average_response_time_ms ?? 0 : 0,
                'success_rate' => $performance ? $performance->getSuccessRate() : 0.0,
                'total_cost' => $performance ? $performance->total_cost_cents ?? 0 : 0,
                'average_rating' => 0, // Field doesn't exist in model
            ];
        }

        return [
            'model_usage_counts' => $modelUsage,
            'model_performance' => $modelPerformance,
            'preferred_models' => array_keys(array_slice($modelUsage, 0, 3, true)),
            'model_adoption_rate' => $this->calculateModelAdoptionRate($user, $dateRange),
        ];
    }

    /**
     * Get user generation trends over time.
     *
     * @param  array<string, Carbon>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserGenerationTrends(User $user, array $dateRange): array
    {
        $dailyStats = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(total_names_generated) as avg_names')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        $trendData = [];
        $current = $dateRange['start']->copy();
        while ($current <= $dateRange['end']) {
            $dateKey = $current->format('Y-m-d');
            $trendData[] = [
                'date' => $dateKey,
                'generations' => $dailyStats[$dateKey]['count'] ?? 0,
                'average_names' => $dailyStats[$dateKey]['avg_names'] ?? 0,
            ];
            $current->addDay();
        }

        return [
            'daily_trends' => $trendData,
            'peak_usage_day' => $this->getPeakUsageDay($dailyStats),
            'usage_consistency' => $this->calculateUsageConsistency($trendData),
            'growth_rate' => $this->calculateGrowthRate($trendData),
        ];
    }

    /**
     * Get system overview statistics.
     *
     * @param  array<string, Carbon>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemOverviewStats(array $dateRange): array
    {
        return [
            'total_generations' => AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_users' => AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->distinct('user_id')->count(),
            'total_names_generated' => NameSuggestion::where('is_ai_generated', true)->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'system_uptime' => $this->calculateSystemUptime($dateRange),
            'average_response_time' => $this->calculateAverageResponseTime($dateRange),
            'peak_concurrent_users' => $this->getPeakConcurrentUsers($dateRange),
            'error_rate' => $this->calculateErrorRate($dateRange),
        ];
    }

    /**
     * Get date range for analytics period.
     *
     * @return array<string, Carbon>
     */
    protected function getDateRange(string $period): array
    {
        $end = now();

        $start = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            'all' => Carbon::create(2020, 1, 1), // Arbitrary early date
            default => now()->startOfMonth(),
        };

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get the most used model for a user.
     *
     * @param  Collection<int, AIGeneration>  $generations
     */
    protected function getMostUsedModel(Collection $generations): ?string
    {
        $modelCounts = $generations
            ->flatMap(function ($gen) {
                /** @var AIGeneration $gen */
                return $gen->models_requested ?? [];
            })
            ->countBy();

        return $modelCounts->keys()->first();
    }

    /**
     * Calculate average response time.
     *
     * @param  array<string, Carbon>|null  $dateRange
     */
    protected function calculateAverageResponseTime(?array $dateRange = null): float
    {
        $query = AIGeneration::whereNotNull('completed_at')->whereNotNull('started_at');

        if ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        } else {
            $query->where('created_at', '>=', now()->subHour());
        }

        // SQLite-compatible version of time difference calculation
        return (float) $query->selectRaw('AVG((julianday(completed_at) - julianday(started_at)) * 86400 * 1000) as avg_time')->value('avg_time');
    }

    /**
     * Calculate current success rate.
     */
    protected function calculateCurrentSuccessRate(): float
    {
        $total = AIGeneration::where('created_at', '>=', now()->subHour())->count();

        if ($total === 0) {
            return 100.0;
        }

        $successful = AIGeneration::where('created_at', '>=', now()->subHour())
            ->where('status', 'completed')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get current models status.
     *
     * @return array<string, mixed>
     */
    protected function getModelsStatus(): array
    {
        $models = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'];
        $status = [];

        foreach ($models as $model) {
            $recentGenerations = AIGeneration::where('created_at', '>=', now()->subMinutes(15))
                ->whereJsonContains('models_requested', $model)
                ->get();

            $total = $recentGenerations->count();
            $successful = $recentGenerations->where('status', 'completed')->count();
            $failed = $recentGenerations->where('status', 'failed')->count();

            $status[$model] = [
                'status' => $this->determineModelStatus($successful, $failed, $total),
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 100.0,
                'recent_requests' => $total,
            ];
        }

        return $status;
    }

    /**
     * Determine model status based on recent performance.
     */
    protected function determineModelStatus(int $successful, int $failed, int $total): string
    {
        if ($total === 0) {
            return 'idle';
        }

        $successRate = ($successful / $total) * 100;

        if ($successRate >= 95) {
            return 'healthy';
        } elseif ($successRate >= 80) {
            return 'degraded';
        } else {
            return 'unhealthy';
        }
    }

    /**
     * Get estimated queue length.
     */
    protected function getQueueLength(): int
    {
        return AIGeneration::where('status', 'pending')->count();
    }

    /**
     * Get error rate for the last hour.
     */
    protected function getErrorRateLastHour(): float
    {
        $total = AIGeneration::where('created_at', '>=', now()->subHour())->count();

        if ($total === 0) {
            return 0.0;
        }

        $failed = AIGeneration::where('created_at', '>=', now()->subHour())
            ->where('status', 'failed')
            ->count();

        return round(($failed / $total) * 100, 2);
    }

    /**
     * Update model performance metrics.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function updateModelPerformanceMetrics(AIGeneration $generation, string $event, array $metadata): void
    {
        foreach ($generation->models_requested as $model) {
            $performance = AIModelPerformance::firstOrCreate([
                'user_id' => $generation->user_id,
                'model_name' => $model,
            ]);

            if ($event === 'completed') {
                $responseTime = $metadata['response_time'] ?? 0;
                $tokensUsed = $metadata['tokens_used'] ?? 0;
                $costCents = $metadata['cost_cents'] ?? 0;

                $performance->updateMetrics(
                    (int) $responseTime,
                    (int) $tokensUsed,
                    (int) $costCents,
                    true
                );
            } elseif ($event === 'failed') {
                $performance->updateMetrics(0, 0, 0, false);
            }
        }
    }

    /**
     * Calculate system uptime percentage.
     *
     * @param  array<string, Carbon>  $dateRange
     */
    protected function calculateSystemUptime(array $dateRange): float
    {
        // This is a simplified calculation - in production you'd track actual downtime
        $totalMinutes = $dateRange['start']->diffInMinutes($dateRange['end']);

        // Count minutes with failed generations as "downtime" (SQLite compatible)
        $downtimeMinutes = AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'failed')
            ->selectRaw('COUNT(DISTINCT strftime("%Y-%m-%d %H:%M", created_at)) as minutes')
            ->value('minutes');

        return round((($totalMinutes - ($downtimeMinutes ?? 0)) / $totalMinutes) * 100, 2);
    }

    /**
     * Get top models used in the last hour.
     *
     * @return array<string, mixed>
     */
    protected function getTopModelsLastHour(): array
    {
        $modelUsage = AIGeneration::where('created_at', '>=', now()->subHour())
            ->get()
            ->flatMap(fn ($gen) => $gen->models_requested)
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();

        return $modelUsage;
    }

    /**
     * Get additional helper methods for completeness...
     */
    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserPerformanceMetrics(User $user, array $dateRange): array
    {
        return [
            'average_response_time' => $this->calculateAverageResponseTime($dateRange),
            'success_rate' => $this->calculateUserSuccessRate($user, $dateRange),
            'efficiency_score' => $this->calculateUserEfficiencyScore($user, $dateRange),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserCostAnalysis(User $user, array $dateRange): array
    {
        $totalCost = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_cost_cents');

        return [
            'total_cost_cents' => $totalCost,
            'total_cost' => (float) ($totalCost / 100), // Convert cents to dollars
            'cost_per_generation' => $this->calculateCostPerGeneration($user, $dateRange),
            'cost_by_model' => $this->getCostByModel($user, $dateRange),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserSuccessRates(User $user, array $dateRange): array
    {
        return [
            'overall_success_rate' => $this->calculateUserSuccessRate($user, $dateRange),
            'model_success_rates' => $this->getModelSuccessRates($user, $dateRange),
        ];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getUserPreferencesEvolution(User $user, array $dateRange): array
    {
        return [
            'model_preference_changes' => $this->getModelPreferenceChanges($user, $dateRange),
            'generation_mode_trends' => $this->getGenerationModeTrends($user, $dateRange),
        ];
    }

    // Implement additional helper methods as needed
    /**
     * @param  Collection<int, AIGeneration>  $generations
     */
    protected function getFavoriteGenerationMode(Collection $generations): ?string
    {
        return $generations->groupBy('generation_mode')->sortByDesc(fn ($group) => $group->count())->keys()->first();
    }

    /**
     * @param  Collection<int, AIGeneration>  $generations
     */
    protected function getTotalProcessingTime(Collection $generations): int
    {
        return $generations->sum(function ($gen) {
            /** @var AIGeneration $gen */
            if ($gen->total_response_time_ms) {
                return (int) ($gen->total_response_time_ms / 1000);
            }

            return 0;
        });
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function calculateModelAdoptionRate(User $user, array $dateRange): float
    {
        // Implementation depends on specific business logic
        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $dailyStats
     */
    protected function getPeakUsageDay(array $dailyStats): ?string
    {
        if (empty($dailyStats)) {
            return null;
        }

        $maxCount = 0;
        $peakDay = null;

        foreach ($dailyStats as $date => $stats) {
            if ($stats['count'] > $maxCount) {
                $maxCount = $stats['count'];
                $peakDay = $date;
            }
        }

        return $peakDay;
    }

    /**
     * @param  list<array<string, mixed>>  $trendData
     */
    protected function calculateUsageConsistency(array $trendData): float
    {
        if (count($trendData) < 2) {
            return 100.0;
        }

        $values = array_column($trendData, 'generations');
        $mean = array_sum($values) / count($values);

        if ($mean == 0) {
            return 100.0;
        }

        $variance = array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $values)) / count($values);
        $standardDeviation = sqrt($variance);

        $coefficientOfVariation = ($standardDeviation / $mean) * 100;

        return max(0, 100 - $coefficientOfVariation);
    }

    /**
     * @param  list<array<string, mixed>>  $trendData
     */
    protected function calculateGrowthRate(array $trendData): float
    {
        if (count($trendData) < 2) {
            return 0.0;
        }

        $firstWeek = array_slice($trendData, 0, min(7, count($trendData)));
        $lastWeek = array_slice($trendData, -min(7, count($trendData)));

        $firstWeekAvg = array_sum(array_column($firstWeek, 'generations')) / count($firstWeek);
        $lastWeekAvg = array_sum(array_column($lastWeek, 'generations')) / count($lastWeek);

        if ($firstWeekAvg == 0) {
            return $lastWeekAvg > 0 ? 100.0 : 0.0;
        }

        return round((($lastWeekAvg - $firstWeekAvg) / $firstWeekAvg) * 100, 2);
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function calculateUserSuccessRate(User $user, array $dateRange): float
    {
        $total = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $successful = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function calculateUserEfficiencyScore(User $user, array $dateRange): float
    {
        // Combine multiple factors into an efficiency score
        $successRate = $this->calculateUserSuccessRate($user, $dateRange);
        $avgResponseTime = $this->calculateAverageResponseTime($dateRange);
        $maxResponseTime = 30000; // 30 seconds as max acceptable response time

        $timeEfficiency = max(0, (1 - ($avgResponseTime / $maxResponseTime)) * 100);

        return round(($successRate * 0.7) + ($timeEfficiency * 0.3), 2);
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function calculateCostPerGeneration(User $user, array $dateRange): float
    {
        $totalCost = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_cost_cents');

        $totalGenerations = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return $totalGenerations > 0 ? $totalCost / $totalGenerations : 0.0;
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getCostByModel(User $user, array $dateRange): array
    {
        // Get costs by model from AIGeneration records
        $generations = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('total_cost_cents')
            ->get();

        $costsByModel = [];
        foreach ($generations as $generation) {
            foreach ($generation->models_requested as $model) {
                if (! isset($costsByModel[$model])) {
                    $costsByModel[$model] = 0;
                }
                // Distribute cost evenly across models if multiple were used
                $costsByModel[$model] += $generation->total_cost_cents / count($generation->models_requested);
            }
        }

        return $costsByModel;
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelSuccessRates(User $user, array $dateRange): array
    {
        $generations = AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $modelStats = [];

        foreach ($generations as $generation) {
            foreach ($generation->models_requested as $model) {
                if (! isset($modelStats[$model])) {
                    $modelStats[$model] = ['total' => 0, 'successful' => 0];
                }

                $modelStats[$model]['total']++;
                if ($generation->status === 'completed') {
                    $modelStats[$model]['successful']++;
                }
            }
        }

        $successRates = [];
        foreach ($modelStats as $model => $stats) {
            $successRates[$model] = round(($stats['successful'] / $stats['total']) * 100, 2);
        }

        return $successRates;
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelPreferenceChanges(User $user, array $dateRange): array
    {
        // This would track how user preferences change over time
        return UserAIPreferences::where('user_id', $user->id)
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('updated_at')
            ->get()
            ->map(fn ($pref) => [
                'date' => $pref->updated_at->format('Y-m-d'),
                'preferred_models' => $pref->preferred_models,
                'generation_mode' => $pref->default_generation_mode,
            ])
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getGenerationModeTrends(User $user, array $dateRange): array
    {
        return AIGeneration::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('generation_mode, COUNT(*) as count')
            ->groupBy('generation_mode')
            ->pluck('count', 'generation_mode')
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function calculateErrorRate(array $dateRange): float
    {
        $total = AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

        if ($total === 0) {
            return 0.0;
        }

        $failed = AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'failed')
            ->count();

        return round(($failed / $total) * 100, 2);
    }

    /**
     * @param  array<string, mixed>  $dateRange
     */
    protected function getPeakConcurrentUsers(array $dateRange): int
    {
        // This would require more sophisticated tracking in production
        $maxUsers = AIGeneration::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT user_id) as concurrent_users')
            ->groupBy('date')
            ->max('concurrent_users');

        return (int) ($maxUsers ?? 0);
    }

    // Additional system-wide analytics methods would be implemented here
    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemModelPerformance(array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemUsagePatterns(array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemCostBreakdown(array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemErrorAnalysis(array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemUserEngagement(array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getSystemCapacityMetrics(array $dateRange): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getModelInfo(string $modelId): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelUsageStats(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelPerformanceMetrics(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelCostEfficiency(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelUserSatisfaction(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelReliabilityMetrics(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $dateRange
     * @return array<string, mixed>
     */
    protected function getModelTrendingAnalysis(string $modelId, array $dateRange): array
    {
        return [];
    }

    /**
     * Export user analytics data for reporting or external analysis.
     *
     * @return array<string, mixed>
     */
    public function exportUserAnalytics(User $user, string $period = 'month'): array
    {
        $analytics = $this->getUserAnalytics($user, $period);

        return [
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->toISOString(),
            ],
            'period' => $period,
            'analytics' => $analytics,
            'generated_at' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Generate analytics summary report for user.
     *
     * @return array<string, mixed>
     */
    public function generateUserSummaryReport(User $user, string $period = 'month'): array
    {
        $analytics = $this->getUserAnalytics($user, $period);

        return [
            'summary' => [
                'total_generations' => $analytics['overview']['total_generations'] ?? 0,
                'total_names_generated' => $analytics['overview']['total_names_generated'] ?? 0,
                'most_used_model' => $analytics['model_usage']['most_used_model'] ?? 'N/A',
                'average_response_time' => $analytics['performance_metrics']['average_response_time'] ?? 0,
                'total_cost' => $analytics['cost_analysis']['total_cost'] ?? 0.0,
                'success_rate' => $analytics['success_rates']['overall_success_rate'] ?? 0,
            ],
            'insights' => $this->generateUserInsights($analytics),
            'recommendations' => $this->generateUserRecommendations($analytics),
        ];
    }

    /**
     * Generate insights based on user analytics.
     *
     * @param  array<string, mixed>  $analytics
     * @return array<string>
     */
    protected function generateUserInsights(array $analytics): array
    {
        $insights = [];

        $totalGenerations = $analytics['overview']['total_generations'] ?? 0;
        if ($totalGenerations > 10) {
            $insights[] = "You're an active user with {$totalGenerations} AI generations this period!";
        }

        $avgResponseTime = $analytics['performance_metrics']['average_response_time'] ?? 0;
        if ($avgResponseTime < 2000) {
            $insights[] = 'Your AI generations are processing quickly with excellent response times.';
        }

        $successRate = $analytics['success_rates']['overall_success_rate'] ?? 0;
        if ($successRate > 90) {
            $insights[] = 'Excellent success rate! Your AI generations are very reliable.';
        }

        if (empty($insights)) {
            $insights[] = 'Keep using AI generation to unlock more personalized insights!';
        }

        return $insights;
    }

    /**
     * Generate recommendations based on user analytics.
     *
     * @param  array<string, mixed>  $analytics
     * @return array<string>
     */
    protected function generateUserRecommendations(array $analytics): array
    {
        $recommendations = [];

        $mostUsedModel = $analytics['model_usage']['most_used_model'] ?? null;
        if ($mostUsedModel && isset($analytics['model_usage']['models_comparison'])) {
            $recommendations[] = 'Try experimenting with different AI models to discover new creative possibilities!';
        }

        $avgCost = $analytics['cost_analysis']['average_cost_per_generation'] ?? 0;
        if ($avgCost > 0.10) {
            $recommendations[] = 'Consider using model comparison mode less frequently to optimize costs.';
        }

        $deepThinkingUsage = $analytics['preferences_evolution']['deep_thinking_usage'] ?? 0;
        if ($deepThinkingUsage < 20) {
            $recommendations[] = 'Try Deep Thinking mode for more sophisticated and creative name suggestions.';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your AI usage patterns look great! Keep exploring creative name generation.';
        }

        return $recommendations;
    }
}
