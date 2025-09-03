<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\AIModelPerformance;
use App\Models\GenerationSession;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for optimizing AI-related database queries.
 *
 * Provides methods for efficient data retrieval with eager loading,
 * query caching, and optimized aggregations.
 */
final class QueryOptimizationService
{
    private const CACHE_TTL = 3600; // 1 hour default cache

    private const PERFORMANCE_CACHE_KEY = 'ai_model_performance:';

    private const USER_STATS_CACHE_KEY = 'user_ai_stats:';

    /**
     * Get generation sessions with optimized eager loading.
     *
     * @param  array<string>  $with
     * @return \Illuminate\Database\Eloquent\Collection<int, GenerationSession>
     */
    public function getOptimizedGenerationSessions(
        User $user,
        int $limit = 10,
        array $with = []
    ): Collection {
        $defaultRelations = ['aiGenerations', 'nameSuggestions'];
        $relations = array_unique(array_merge($defaultRelations, $with));

        return GenerationSession::with($relations)
            ->where('user_id', $user->id)
            ->select([
                'id',
                'session_id',
                'user_id',
                'project_id',
                'status',
                'business_description',
                'generation_mode',
                'deep_thinking',
                'progress_percentage',
                'created_at',
                'completed_at',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get AI generations with performance metrics using a single query.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AIGeneration>
     */
    public function getGenerationsWithMetrics(
        ?User $user = null,
        ?Project $project = null,
        int $days = 7
    ): Collection {
        $query = AIGeneration::query()
            ->select([
                'ai_generations.*',
                DB::raw('COUNT(ns.id) as suggestion_count'),
                DB::raw('AVG(ai_generations.total_response_time_ms) as avg_response_time'),
                DB::raw('SUM(ai_generations.total_tokens_used) as total_tokens'),
                DB::raw('SUM(ai_generations.total_cost_cents) as total_cost'),
            ])
            ->leftJoin('name_suggestions as ns', function ($join): void {
                $join->on('ns.ai_generation_id', '=', 'ai_generations.id')
                    ->whereNull('ns.deleted_at');
            })
            ->where('ai_generations.created_at', '>=', now()->subDays($days))
            ->groupBy('ai_generations.id');

        if ($user) {
            $query->where('ai_generations.user_id', $user->id);
        }

        if ($project) {
            $query->where('ai_generations.project_id', $project->id);
        }

        return $query->get();
    }

    /**
     * Get cached model performance metrics.
     */
    public function getCachedModelPerformance(string $model): ?AIModelPerformance
    {
        return Cache::remember(
            self::PERFORMANCE_CACHE_KEY.$model,
            self::CACHE_TTL,
            fn () => AIModelPerformance::where('model', $model)
                ->select([
                    'model',
                    'total_requests',
                    'successful_requests',
                    'failed_requests',
                    'total_response_time',
                    'names_generated',
                    'last_used_at',
                    'updated_at',
                ])
                ->first()
        );
    }

    /**
     * Get user AI usage statistics with caching.
     *
     * @return array<string, mixed>
     */
    public function getUserAIStats(User $user): array
    {
        return Cache::remember(
            self::USER_STATS_CACHE_KEY.$user->id,
            300, // 5 minutes cache
            fn () => [
                'total_generations' => AIGeneration::where('user_id', $user->id)->count(),
                'total_names_generated' => AIGeneration::where('user_id', $user->id)
                    ->sum('total_names_generated'),
                'favorite_model' => $this->getUserFavoriteModel($user),
                'average_response_time' => AIGeneration::where('user_id', $user->id)
                    ->whereNotNull('total_response_time_ms')
                    ->avg('total_response_time_ms'),
                'total_cost' => AIGeneration::where('user_id', $user->id)
                    ->sum('total_cost_cents') / 100,
                'recent_sessions' => GenerationSession::where('user_id', $user->id)
                    ->select(['session_id', 'status', 'created_at'])
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(),
            ]
        );
    }

    /**
     * Get name suggestions with optimized loading.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, NameSuggestion>
     */
    public function getOptimizedNameSuggestions(
        Project $project,
        bool $includeHidden = false
    ): Collection {
        $query = NameSuggestion::where('project_id', $project->id)
            ->select([
                'id',
                'project_id',
                'name',
                'domains',
                'logos',
                'is_hidden',
                'ai_generation_id',
                'generation_metadata',
                'created_at',
            ]);

        if (! $includeHidden) {
            $query->where('is_hidden', false);
        }

        // Use chunk loading for large datasets
        if ($project->nameSuggestions()->count() > 1000) {
            $results = new Collection;
            $query->chunk(500, function ($chunk) use (&$results): void {
                $results = $results->merge($chunk);
            });

            return $results;
        }

        return $query->get();
    }

    /**
     * Batch update model performance metrics.
     *
     * @param  array<string, array<string, int>>  $updates
     */
    public function batchUpdateModelPerformance(array $updates): void
    {
        DB::transaction(function () use ($updates): void {
            foreach ($updates as $model => $metrics) {
                AIModelPerformance::updateOrCreate(
                    ['model' => $model],
                    [
                        'total_requests' => DB::raw("total_requests + {$metrics['requests']}"),
                        'successful_requests' => DB::raw("successful_requests + {$metrics['successful']}"),
                        'failed_requests' => DB::raw("failed_requests + {$metrics['failed']}"),
                        'total_response_time' => DB::raw("total_response_time + {$metrics['response_time']}"),
                        'names_generated' => DB::raw("names_generated + {$metrics['names_count']}"),
                        'last_used_at' => now(),
                    ]
                );

                // Clear cache for this model
                Cache::forget(self::PERFORMANCE_CACHE_KEY.$model);
            }
        });
    }

    /**
     * Get aggregated generation statistics.
     *
     * @return array<string, mixed>
     */
    public function getAggregatedStats(int $days = 30): array
    {
        return Cache::remember(
            'ai_aggregated_stats:'.$days,
            3600,
            function () use ($days) {
                $startDate = now()->subDays($days);

                return [
                    'daily_generations' => AIGeneration::where('created_at', '>=', $startDate)
                        ->select(
                            DB::raw('DATE(created_at) as date'),
                            DB::raw('COUNT(*) as count'),
                            DB::raw('AVG(total_response_time_ms) as avg_response_time')
                        )
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get(),

                    'model_distribution' => DB::table('ai_generations')
                        ->where('created_at', '>=', $startDate)
                        ->select(
                            DB::raw("JSON_EXTRACT(models_requested, '$[0]') as model"),
                            DB::raw('COUNT(*) as count')
                        )
                        ->groupBy('model')
                        ->get(),

                    'mode_distribution' => AIGeneration::where('created_at', '>=', $startDate)
                        ->select('generation_mode', DB::raw('COUNT(*) as count'))
                        ->groupBy('generation_mode')
                        ->get(),

                    'success_rate' => $this->calculateSuccessRate($startDate),
                ];
            }
        );
    }

    /**
     * Optimize session queries with selective field loading.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GenerationSession>
     */
    public function getSessionsForDashboard(User $user, int $limit = 20): Collection
    {
        return GenerationSession::where('user_id', $user->id)
            ->select([
                'id',
                'session_id',
                'business_description',
                'generation_mode',
                'status',
                'progress_percentage',
                'created_at',
            ])
            ->withCount('nameSuggestions')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear all AI-related caches.
     */
    public function clearCaches(?User $user = null): void
    {
        // Clear performance caches
        $models = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok'];
        foreach ($models as $model) {
            Cache::forget(self::PERFORMANCE_CACHE_KEY.$model);
        }

        // Clear user stats cache
        if ($user) {
            Cache::forget(self::USER_STATS_CACHE_KEY.$user->id);
        }

        // Clear aggregated stats
        Cache::forget('ai_aggregated_stats:30');
        Cache::forget('ai_aggregated_stats:7');
    }

    /**
     * Get user's favorite AI model based on usage.
     */
    private function getUserFavoriteModel(User $user): ?string
    {
        $result = DB::table('ai_generations')
            ->where('user_id', $user->id)
            ->select(
                DB::raw("JSON_EXTRACT(models_requested, '$[0]') as model"),
                DB::raw('COUNT(*) as usage_count')
            )
            ->groupBy('model')
            ->orderByDesc('usage_count')
            ->first();

        return $result?->model ? trim((string) $result->model, '"') : null;
    }

    /**
     * Calculate success rate for AI generations.
     */
    private function calculateSuccessRate(\DateTime $startDate): float
    {
        $stats = AIGeneration::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful"),
                DB::raw('COUNT(*) as total')
            )
            ->first();

        if (! $stats || ! property_exists($stats, 'total') || $stats->total == 0) {
            return 0.0;
        }

        return round(($stats->successful / $stats->total) * 100, 2);
    }
}
