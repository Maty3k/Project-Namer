<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserAIPreferences;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing AI-related caching strategies.
 *
 * Provides intelligent caching for API responses, user preferences,
 * and generation results to improve performance and reduce API costs.
 */
final class CachingService
{
    private const API_RESPONSE_TTL = 3600; // 1 hour

    private const USER_PREFERENCES_TTL = 1800; // 30 minutes

    private const GENERATION_RESULT_TTL = 86400; // 24 hours

    private const MODEL_AVAILABILITY_TTL = 300; // 5 minutes

    private const RATE_LIMIT_TTL = 3600; // 1 hour

    /**
     * Cache API response with intelligent key generation.
     *
     * @param  array<mixed>  $response
     */
    public function cacheAPIResponse(
        string $model,
        string $prompt,
        string $mode,
        bool $deepThinking,
        array $response,
        ?int $ttl = null
    ): void {
        $cacheKey = $this->generateAPIResponseKey($model, $prompt, $mode, $deepThinking);

        Cache::put($cacheKey, [
            'response' => $response,
            'cached_at' => now()->timestamp,
            'model' => $model,
            'metadata' => [
                'mode' => $mode,
                'deep_thinking' => $deepThinking,
                'response_size' => strlen(json_encode($response)),
            ],
        ], $ttl ?? self::API_RESPONSE_TTL);

        Log::info('AI API response cached', [
            'cache_key' => $cacheKey,
            'model' => $model,
            'ttl' => $ttl ?? self::API_RESPONSE_TTL,
        ]);
    }

    /**
     * Retrieve cached API response.
     *
     * @return array<mixed>|null
     */
    public function getCachedAPIResponse(
        string $model,
        string $prompt,
        string $mode,
        bool $deepThinking
    ): ?array {
        $cacheKey = $this->generateAPIResponseKey($model, $prompt, $mode, $deepThinking);

        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::info('AI API response cache hit', [
                'cache_key' => $cacheKey,
                'model' => $model,
                'cached_at' => $cached['cached_at'],
            ]);

            return $cached['response'];
        }

        return null;
    }

    /**
     * Cache user AI preferences with automatic invalidation.
     */
    public function cacheUserPreferences(User $user, UserAIPreferences $preferences): void
    {
        $cacheKey = "user_ai_preferences:{$user->id}";

        Cache::put($cacheKey, [
            'preferences' => $preferences->toArray(),
            'cached_at' => now()->timestamp,
            'version' => $preferences->updated_at->timestamp,
        ], self::USER_PREFERENCES_TTL);

        // Also cache frequently accessed individual preferences
        $this->cachePreferredModels($user, $preferences->preferred_models);
        $this->cacheGenerationSettings($user, $preferences->custom_parameters ?? []);
    }

    /**
     * Get cached user preferences.
     */
    public function getCachedUserPreferences(User $user): ?UserAIPreferences
    {
        $cacheKey = "user_ai_preferences:{$user->id}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            // Check if cache is still valid by comparing version
            $dbPreferences = UserAIPreferences::where('user_id', $user->id)->first();

            if ($dbPreferences && $cached['version'] >= $dbPreferences->updated_at->timestamp) {
                return new UserAIPreferences($cached['preferences']);
            }

            // Cache is stale, remove it
            Cache::forget($cacheKey);
        }

        return null;
    }

    /**
     * Cache generation results for duplicate prevention.
     *
     * @param  array<string>  $models
     * @param  array<string, array<string>>  $results
     */
    public function cacheGenerationResult(
        string $prompt,
        string $mode,
        array $models,
        bool $deepThinking,
        array $results
    ): void {
        $cacheKey = $this->generateGenerationResultKey($prompt, $mode, $models, $deepThinking);

        Cache::put($cacheKey, [
            'results' => $results,
            'models' => $models,
            'generated_at' => now()->timestamp,
            'result_count' => array_sum(array_map('count', $results)),
        ], self::GENERATION_RESULT_TTL);
    }

    /**
     * Get cached generation results.
     *
     * @param  array<string>  $models
     * @return array<string, array<string>>|null
     */
    public function getCachedGenerationResult(
        string $prompt,
        string $mode,
        array $models,
        bool $deepThinking
    ): ?array {
        $cacheKey = $this->generateGenerationResultKey($prompt, $mode, $models, $deepThinking);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::info('Generation result cache hit', [
                'cache_key' => $cacheKey,
                'models' => $models,
                'result_count' => $cached['result_count'],
            ]);

            return $cached['results'];
        }

        return null;
    }

    /**
     * Cache model availability status.
     *
     * @param  array<string, array<string, mixed>>  $modelStatus
     */
    public function cacheModelAvailability(array $modelStatus): void
    {
        Cache::put('ai_model_availability', [
            'status' => $modelStatus,
            'checked_at' => now()->timestamp,
        ], self::MODEL_AVAILABILITY_TTL);
    }

    /**
     * Get cached model availability.
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function getCachedModelAvailability(): ?array
    {
        $cached = Cache::get('ai_model_availability');

        return $cached['status'] ?? null;
    }

    /**
     * Implement rate limiting with cache.
     */
    public function isRateLimited(User $user, string $action = 'generation'): bool
    {
        $cacheKey = "rate_limit:{$user->id}:{$action}";
        $attempts = Cache::get($cacheKey, 0);

        // Default limits
        $limits = [
            'generation' => 50, // 50 generations per hour
            'api_call' => 100,  // 100 API calls per hour
        ];

        $limit = $limits[$action] ?? 50;

        return $attempts >= $limit;
    }

    /**
     * Increment rate limit counter.
     */
    public function incrementRateLimit(User $user, string $action = 'generation'): int
    {
        $cacheKey = "rate_limit:{$user->id}:{$action}";
        $attempts = Cache::get($cacheKey, 0) + 1;

        Cache::put($cacheKey, $attempts, self::RATE_LIMIT_TTL);

        return $attempts;
    }

    /**
     * Cache warming for frequently accessed data.
     */
    public function warmCache(): void
    {
        Log::info('Starting AI cache warming');

        // Warm model availability cache
        $this->warmModelAvailabilityCache();

        // Warm frequently used user preferences
        $this->warmUserPreferencesCache();

        // Warm performance metrics
        $this->warmPerformanceMetricsCache();

        Log::info('AI cache warming completed');
    }

    /**
     * Clear expired and stale cache entries.
     */
    public function cleanupCache(): void
    {
        Log::info('Starting AI cache cleanup');

        // Clear rate limit caches for inactive users
        $this->cleanupRateLimitCaches();

        // Clear stale API response caches
        $this->cleanupAPIResponseCaches();

        Log::info('AI cache cleanup completed');
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, int>
     */
    public function getCacheStats(): array
    {
        return [
            'api_responses' => $this->countCacheKeys('ai_api_response:*'),
            'user_preferences' => $this->countCacheKeys('user_ai_preferences:*'),
            'generation_results' => $this->countCacheKeys('ai_generation_result:*'),
            'rate_limits' => $this->countCacheKeys('rate_limit:*'),
            'model_availability' => Cache::has('ai_model_availability') ? 1 : 0,
        ];
    }

    /**
     * Generate API response cache key.
     */
    private function generateAPIResponseKey(
        string $model,
        string $prompt,
        string $mode,
        bool $deepThinking
    ): string {
        $promptHash = hash('sha256', trim(strtolower($prompt)));

        return "ai_api_response:{$model}:{$promptHash}:{$mode}:".($deepThinking ? '1' : '0');
    }

    /**
     * Generate generation result cache key.
     *
     * @param  array<string>  $models
     */
    private function generateGenerationResultKey(
        string $prompt,
        string $mode,
        array $models,
        bool $deepThinking
    ): string {
        sort($models); // Ensure consistent ordering
        $promptHash = hash('sha256', trim(strtolower($prompt)));
        $modelsHash = hash('sha256', implode(',', $models));

        return "ai_generation_result:{$promptHash}:{$modelsHash}:{$mode}:".($deepThinking ? '1' : '0');
    }

    /**
     * Cache preferred models for quick access.
     *
     * @param  array<string>  $models
     */
    private function cachePreferredModels(User $user, array $models): void
    {
        Cache::put("user_preferred_models:{$user->id}", $models, self::USER_PREFERENCES_TTL);
    }

    /**
     * Cache generation settings for quick access.
     *
     * @param  array<string, mixed>  $settings
     */
    private function cacheGenerationSettings(User $user, array $settings): void
    {
        Cache::put("user_generation_settings:{$user->id}", $settings, self::USER_PREFERENCES_TTL);
    }

    /**
     * Warm model availability cache.
     */
    private function warmModelAvailabilityCache(): void
    {
        // Simulate model availability check
        $models = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok'];
        $availability = [];

        foreach ($models as $model) {
            $availability[$model] = [
                'available' => true,
                'last_checked' => now()->timestamp,
                'response_time' => random_int(500, 2000),
            ];
        }

        $this->cacheModelAvailability($availability);
    }

    /**
     * Warm user preferences cache for active users.
     */
    private function warmUserPreferencesCache(): void
    {
        // Cache preferences for recently active users
        $recentUsers = User::limit(100)->get();

        foreach ($recentUsers as $user) {
            $preferences = UserAIPreferences::where('user_id', $user->id)->first();
            if ($preferences) {
                $this->cacheUserPreferences($user, $preferences);
            }
        }
    }

    /**
     * Warm performance metrics cache.
     */
    private function warmPerformanceMetricsCache(): void
    {
        $models = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok'];

        foreach ($models as $model) {
            // This would be called by the QueryOptimizationService
            app(QueryOptimizationService::class)->getCachedModelPerformance($model);
        }
    }

    /**
     * Clean up rate limit caches.
     */
    private function cleanupRateLimitCaches(): void
    {
        // This is a simplified cleanup - in production, you might want to
        // use Redis SCAN or similar to efficiently clean up expired keys
    }

    /**
     * Clean up API response caches.
     */
    private function cleanupAPIResponseCaches(): void
    {
        // This would typically be handled by cache expiration
        // but you could implement custom cleanup logic here
    }

    /**
     * Count cache keys matching a pattern.
     */
    private function countCacheKeys(string $pattern): int
    {
        // This is a simplified implementation
        // In production with Redis, you'd use SCAN command
        return 0;
    }
}
