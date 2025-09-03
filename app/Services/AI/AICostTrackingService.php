<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI Cost Tracking Service.
 *
 * Tracks API usage costs, monitors budget limits, and manages
 * usage quotas for AI generation services.
 */
class AICostTrackingService
{
    /** @var array<string, array<string, float>> */
    protected array $tokenCosts = [
        'openai-gpt-4' => ['input' => 0.03, 'output' => 0.06],
        'openai-gpt-3.5-turbo' => ['input' => 0.001, 'output' => 0.002],
        'anthropic-claude' => ['input' => 0.008, 'output' => 0.024],
        'google-gemini' => ['input' => 0.000125, 'output' => 0.000375],
    ];

    /**
     * Record API usage and calculate cost.
     */
    public function recordUsage(
        ?User $user,
        string $modelId,
        int $inputTokens,
        int $outputTokens,
        float $responseTime,
        bool $successful = true
    ): float {
        $cost = $this->calculateCost($modelId, $inputTokens, $outputTokens);

        // Store usage record
        DB::table('ai_usage_logs')->insert([
            'user_id' => $user?->id,
            'model_id' => $modelId,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
            'cost' => $cost,
            'response_time' => $responseTime,
            'successful' => $successful,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update user's usage statistics
        if ($user) {
            $this->updateUserUsageStats($user, $cost, $successful);
        }

        // Update system-wide statistics
        $this->updateSystemStats($modelId, $cost, $successful);

        Log::info('AI usage recorded', [
            'user_id' => $user?->id,
            'model_id' => $modelId,
            'tokens' => $inputTokens + $outputTokens,
            'cost' => $cost,
            'successful' => $successful,
        ]);

        return $cost;
    }

    /**
     * Calculate cost for API usage.
     */
    public function calculateCost(string $modelId, int $inputTokens, int $outputTokens): float
    {
        if (! isset($this->tokenCosts[$modelId])) {
            Log::warning("Unknown model for cost calculation: {$modelId}");

            return 0.0;
        }

        $costs = $this->tokenCosts[$modelId];
        $inputCost = ($inputTokens / 1000) * $costs['input'];
        $outputCost = ($outputTokens / 1000) * $costs['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get user's current usage statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserUsageStats(User $user, string $period = 'day'): array
    {
        $cacheKey = "user_usage_stats:{$user->id}:{$period}";

        return Cache::remember($cacheKey, 300, function () use ($user, $period) {
            $startDate = $this->getPeriodStartDate($period);

            $stats = DB::table('ai_usage_logs')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) as successful_requests,
                    SUM(total_tokens) as total_tokens,
                    SUM(cost) as total_cost,
                    AVG(response_time) as avg_response_time
                ')
                ->first();

            $modelBreakdown = DB::table('ai_usage_logs')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->select('model_id')
                ->selectRaw('
                    COUNT(*) as requests,
                    SUM(total_tokens) as tokens,
                    SUM(cost) as cost
                ')
                ->groupBy('model_id')
                ->get()
                ->keyBy('model_id')
                ->map(fn ($item) => [
                    'requests' => (int) $item->requests,
                    'tokens' => (int) $item->tokens,
                    'cost' => (float) $item->cost,
                ])
                ->toArray();

            return [
                'period' => $period,
                'start_date' => $startDate,
                'total_requests' => (int) ($stats->total_requests ?? 0),
                'successful_requests' => (int) ($stats->successful_requests ?? 0),
                'failed_requests' => (int) ($stats->total_requests ?? 0) - (int) ($stats->successful_requests ?? 0),
                'success_rate' => $stats->total_requests > 0
                    ? round(($stats->successful_requests / $stats->total_requests) * 100, 2)
                    : 0,
                'total_tokens' => (int) ($stats->total_tokens ?? 0),
                'total_cost' => (float) ($stats->total_cost ?? 0),
                'average_response_time' => round((float) ($stats->avg_response_time ?? 0), 3),
                'model_breakdown' => $modelBreakdown,
            ];
        });
    }

    /**
     * Check if user has reached their usage limits.
     *
     * @return array<string, array<string, mixed>>
     */
    public function checkUserLimits(User $user): array
    {
        $hourlyStats = $this->getUserUsageStats($user, 'hour');
        $dailyStats = $this->getUserUsageStats($user, 'day');

        $config = Config::get('ai.settings', []);
        $hourlyLimit = $config['max_generations_per_user_per_hour'] ?? 50;
        $dailyLimit = $config['max_generations_per_user_per_day'] ?? 200;

        return [
            'hourly' => [
                'limit' => $hourlyLimit,
                'used' => $hourlyStats['total_requests'],
                'remaining' => max(0, $hourlyLimit - $hourlyStats['total_requests']),
                'exceeded' => $hourlyStats['total_requests'] >= $hourlyLimit,
                'percentage' => $hourlyLimit > 0 ? round(($hourlyStats['total_requests'] / $hourlyLimit) * 100, 1) : 0,
            ],
            'daily' => [
                'limit' => $dailyLimit,
                'used' => $dailyStats['total_requests'],
                'remaining' => max(0, $dailyLimit - $dailyStats['total_requests']),
                'exceeded' => $dailyStats['total_requests'] >= $dailyLimit,
                'percentage' => $dailyLimit > 0 ? round(($dailyStats['total_requests'] / $dailyLimit) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Get system-wide cost statistics.
     *
     * @return array<string, mixed>
     */
    public function getSystemCostStats(string $period = 'day'): array
    {
        $cacheKey = "system_cost_stats:{$period}";

        return Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = $this->getPeriodStartDate($period);

            $stats = DB::table('ai_usage_logs')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) as successful_requests,
                    SUM(total_tokens) as total_tokens,
                    SUM(cost) as total_cost,
                    AVG(response_time) as avg_response_time,
                    COUNT(DISTINCT user_id) as active_users
                ')
                ->first();

            $modelBreakdown = DB::table('ai_usage_logs')
                ->where('created_at', '>=', $startDate)
                ->select('model_id')
                ->selectRaw('
                    COUNT(*) as requests,
                    SUM(total_tokens) as tokens,
                    SUM(cost) as cost,
                    AVG(response_time) as avg_response_time,
                    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) as successful_requests
                ')
                ->groupBy('model_id')
                ->get()
                ->keyBy('model_id')
                ->map(fn ($item) => [
                    'requests' => (int) $item->requests,
                    'successful_requests' => (int) $item->successful_requests,
                    'success_rate' => $item->requests > 0
                        ? round(($item->successful_requests / $item->requests) * 100, 2)
                        : 0,
                    'tokens' => (int) $item->tokens,
                    'cost' => (float) $item->cost,
                    'avg_response_time' => round((float) $item->avg_response_time, 3),
                ])
                ->toArray();

            return [
                'period' => $period,
                'start_date' => $startDate,
                'total_requests' => (int) ($stats->total_requests ?? 0),
                'successful_requests' => (int) ($stats->successful_requests ?? 0),
                'failed_requests' => (int) ($stats->total_requests ?? 0) - (int) ($stats->successful_requests ?? 0),
                'success_rate' => $stats->total_requests > 0
                    ? round(($stats->successful_requests / $stats->total_requests) * 100, 2)
                    : 0,
                'total_tokens' => (int) ($stats->total_tokens ?? 0),
                'total_cost' => (float) ($stats->total_cost ?? 0),
                'average_response_time' => round((float) ($stats->avg_response_time ?? 0), 3),
                'active_users' => (int) ($stats->active_users ?? 0),
                'model_breakdown' => $modelBreakdown,
            ];
        });
    }

    /**
     * Check system-wide budget limits.
     *
     * @return array<string, array<string, mixed>>
     */
    public function checkSystemBudgetLimits(): array
    {
        $config = Config::get('ai.cost_tracking', []);
        $dailyBudget = $config['daily_budget_limit'] ?? 100.0;
        $monthlyBudget = $config['monthly_budget_limit'] ?? 2000.0;
        $alertThreshold = $config['alert_threshold_percentage'] ?? 80;

        $dailyStats = $this->getSystemCostStats('day');
        $monthlyStats = $this->getSystemCostStats('month');

        $dailyPercentage = $dailyBudget > 0 ? ($dailyStats['total_cost'] / $dailyBudget) * 100 : 0;
        $monthlyPercentage = $monthlyBudget > 0 ? ($monthlyStats['total_cost'] / $monthlyBudget) * 100 : 0;

        return [
            'daily' => [
                'budget' => $dailyBudget,
                'spent' => $dailyStats['total_cost'],
                'remaining' => max(0, $dailyBudget - $dailyStats['total_cost']),
                'percentage' => round($dailyPercentage, 1),
                'exceeded' => $dailyStats['total_cost'] >= $dailyBudget,
                'alert_needed' => $dailyPercentage >= $alertThreshold,
            ],
            'monthly' => [
                'budget' => $monthlyBudget,
                'spent' => $monthlyStats['total_cost'],
                'remaining' => max(0, $monthlyBudget - $monthlyStats['total_cost']),
                'percentage' => round($monthlyPercentage, 1),
                'exceeded' => $monthlyStats['total_cost'] >= $monthlyBudget,
                'alert_needed' => $monthlyPercentage >= $alertThreshold,
            ],
            'alert_threshold' => $alertThreshold,
        ];
    }

    /**
     * Get top spending users.
     *
     * @return array<int, mixed>
     */
    public function getTopSpendingUsers(string $period = 'month', int $limit = 10): array
    {
        $startDate = $this->getPeriodStartDate($period);

        return DB::table('ai_usage_logs')
            ->join('users', 'ai_usage_logs.user_id', '=', 'users.id')
            ->where('ai_usage_logs.created_at', '>=', $startDate)
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('
                SUM(ai_usage_logs.cost) as total_cost,
                COUNT(ai_usage_logs.id) as total_requests,
                SUM(ai_usage_logs.total_tokens) as total_tokens
            ')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_cost')
            ->limit($limit)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_cost' => (float) $user->total_cost,
                'total_requests' => (int) $user->total_requests,
                'total_tokens' => (int) $user->total_tokens,
                'avg_cost_per_request' => $user->total_requests > 0
                    ? round($user->total_cost / $user->total_requests, 4)
                    : 0,
            ])
            ->toArray();
    }

    /**
     * Get cost trends over time.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCostTrends(string $period = 'week'): array
    {
        $days = match ($period) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            default => 7,
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $endDate = $date->copy()->endOfDay();

            $stats = DB::table('ai_usage_logs')
                ->whereBetween('created_at', [$date, $endDate])
                ->selectRaw('
                    COUNT(*) as requests,
                    SUM(cost) as cost,
                    SUM(total_tokens) as tokens
                ')
                ->first();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'requests' => (int) ($stats->requests ?? 0),
                'cost' => (float) ($stats->cost ?? 0),
                'tokens' => (int) ($stats->tokens ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Estimate cost for a planned request.
     *
     * @return array<string, mixed>
     */
    public function estimateCost(string $modelId, int $estimatedInputTokens): array
    {
        if (! isset($this->tokenCosts[$modelId])) {
            return [
                'estimated_cost' => 0.0,
                'error' => 'Unknown model',
            ];
        }

        $costs = $this->tokenCosts[$modelId];
        $estimatedOutputTokens = $estimatedInputTokens * 0.5; // Rough estimate

        $inputCost = ($estimatedInputTokens / 1000) * $costs['input'];
        $outputCost = ($estimatedOutputTokens / 1000) * $costs['output'];
        $totalCost = $inputCost + $outputCost;

        return [
            'model_id' => $modelId,
            'estimated_input_tokens' => $estimatedInputTokens,
            'estimated_output_tokens' => (int) $estimatedOutputTokens,
            'estimated_total_tokens' => $estimatedInputTokens + (int) $estimatedOutputTokens,
            'input_cost' => round($inputCost, 6),
            'output_cost' => round($outputCost, 6),
            'estimated_cost' => round($totalCost, 6),
            'cost_per_1k_tokens' => [
                'input' => $costs['input'],
                'output' => $costs['output'],
            ],
        ];
    }

    /**
     * Clean up old usage logs.
     */
    public function cleanupOldLogs(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);

        $deletedCount = DB::table('ai_usage_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info('Cleaned up AI usage logs', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate,
        ]);

        return $deletedCount;
    }

    /**
     * Get period start date.
     */
    protected function getPeriodStartDate(string $period): \Carbon\Carbon
    {
        return match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfDay(),
        };
    }

    /**
     * Update user usage statistics cache.
     */
    protected function updateUserUsageStats(User $user, float $cost, bool $successful): void
    {
        // Clear relevant cache entries to force refresh
        Cache::forget("user_usage_stats:{$user->id}:hour");
        Cache::forget("user_usage_stats:{$user->id}:day");
    }

    /**
     * Update system statistics cache.
     */
    protected function updateSystemStats(string $modelId, float $cost, bool $successful): void
    {
        // Clear system-wide cache entries
        Cache::forget('system_cost_stats:hour');
        Cache::forget('system_cost_stats:day');
    }
}
