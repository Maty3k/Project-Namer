<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationCache;
use App\Models\GenerationSession;
use Exception;
use InvalidArgumentException;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

/**
 * Service for coordinating AI name generation across multiple models using direct Prism integration.
 *
 * Orchestrates parallel execution of multiple AI models for efficient
 * business name generation with intelligent load balancing and coordination.
 */
final readonly class AIGenerationService
{
    private const VALID_MODES = ['creative', 'professional', 'brandable', 'tech-focused'];

    private const VALID_MODELS = [
        'gpt-4',
        'claude-3.5-sonnet',
        'gemini-1.5-pro',
        'grok-beta',
    ];

    private const MAX_INPUT_LENGTH = 2000;

    private const DEFAULT_COUNT = 10;

    private const MAX_RETRIES = 2; // Reasonable retries for rate limits

    private const RETRY_DELAY_SECONDS = 2; // Longer delay for rate limit recovery

    private const MODEL_CONFIGS = [
        'gpt-4' => [
            'provider' => Provider::OpenAI,
            'model' => 'gpt-4o',
            'max_tokens' => 200,
            'temperature' => 0.7,
            'deep_thinking_temperature' => 0.3,
        ],
        'claude-3.5-sonnet' => [
            'provider' => Provider::Anthropic,
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 200,
            'temperature' => 0.7,
            'deep_thinking_temperature' => 0.3,
        ],
        'gemini-1.5-pro' => [
            'provider' => Provider::Gemini,
            'model' => 'gemini-1.5-pro',
            'max_tokens' => 200,
            'temperature' => 0.8,
            'deep_thinking_temperature' => 0.4,
        ],
        'grok-beta' => [
            'provider' => Provider::XAI,
            'model' => 'grok-beta',
            'max_tokens' => 200,
            'temperature' => 0.9,
            'deep_thinking_temperature' => 0.5,
        ],
    ];

    public function __construct(
        private PromptBuilder $promptBuilder,
        private VisionAnalysisService $visionService
    ) {}

    /**
     * Generate business names using multiple AI models in parallel.
     *
     * @param  string  $businessIdea  The business concept or description
     * @param  array<string>  $models  Array of model names to use
     * @param  string  $mode  Generation mode (creative, professional, brandable, tech-focused)
     * @param  bool  $deepThinking  Whether to use deep thinking mode
     * @param  array<string, mixed>  $customParams  Optional custom parameters
     * @return array<string, array<string, mixed>> Results keyed by model name with execution metadata
     *
     * @throws InvalidArgumentException If input parameters are invalid
     */
    public function generateNamesParallel(
        string $businessIdea,
        array $models,
        string $mode,
        bool $deepThinking = false,
        array $customParams = []
    ): array {
        $this->validateInput($businessIdea, $models, $mode);

        $startTime = microtime(true);
        $results = [];
        $count = $customParams['count'] ?? self::DEFAULT_COUNT;

        foreach ($models as $model) {
            $results[$model] = $this->generateWithRetry($businessIdea, $model, $mode, $deepThinking, $count, $customParams);
        }

        $totalTime = (microtime(true) - $startTime) * 1000;

        // Add coordination metadata
        return [
            'results' => $results,
            'execution_metadata' => [
                'total_models_requested' => count($models),
                'successful_models' => count(array_filter($results, fn ($result) => $result['status'] === 'completed')),
                'failed_models' => count(array_filter($results, fn ($result) => $result['status'] === 'failed')),
                'total_execution_time_ms' => (int) round($totalTime),
                'average_response_time_ms' => (int) round($totalTime / count($models)),
                'cached_results' => array_keys(array_filter($results, fn ($result) => $result['cached'] ?? false)),
                'execution_strategy' => 'sequential',
                'executed_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Generate names optimized for speed (fewer models, faster execution).
     *
     * @return array<string, mixed>
     */
    public function generateNamesQuick(
        string $businessIdea,
        string $mode = 'creative',
        bool $deepThinking = false
    ): array {
        // Use only the fastest, most reliable models for quick generation
        $quickModels = ['gpt-4', 'claude-3.5-sonnet'];

        return $this->generateNamesParallel(
            $businessIdea,
            $quickModels,
            $mode,
            $deepThinking,
            ['count' => 10, 'temperature' => 0.8] // Slightly higher temperature for faster, more creative results
        );
    }

    /**
     * Generate names optimized for quality (all models, comprehensive results).
     *
     * @return array<string, mixed>
     */
    public function generateNamesComprehensive(
        string $businessIdea,
        string $mode = 'creative'
    ): array {
        // Use all available models for maximum coverage and quality
        $allModels = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'];

        return $this->generateNamesParallel(
            $businessIdea,
            $allModels,
            $mode,
            true, // Enable deep thinking for quality
            ['count' => 10]
        );
    }

    /**
     * Generate names with custom model selection and parameters.
     *
     * @param  array<string>  $models
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generateNamesCustom(
        string $businessIdea,
        array $models,
        array $options = []
    ): array {
        $mode = $options['mode'] ?? 'creative';
        $deepThinking = $options['deep_thinking'] ?? false;
        $customParams = $options['params'] ?? [];

        return $this->generateNamesParallel($businessIdea, $models, $mode, $deepThinking, $customParams);
    }

    /**
     * Get execution statistics and recommendations.
     *
     * @param  array<string, mixed>  $generationResult
     * @return array<string, mixed>
     */
    public function getExecutionStats(array $generationResult): array
    {
        if (! isset($generationResult['execution_metadata'])) {
            throw new InvalidArgumentException('Invalid generation result format');
        }

        $metadata = $generationResult['execution_metadata'];
        $results = $generationResult['results'];

        $cacheHitRate = round((count($metadata['cached_results']) / $metadata['total_models_requested']) * 100, 1);

        $stats = [
            'performance' => [
                'success_rate' => round(($metadata['successful_models'] / $metadata['total_models_requested']) * 100, 1),
                'average_response_time' => $metadata['average_response_time_ms'],
                'total_execution_time' => $metadata['total_execution_time_ms'],
                'cache_hit_rate' => $cacheHitRate,
            ],
            'reliability' => [
                'failed_models' => $metadata['failed_models'],
            ],
            'recommendations' => $this->generateRecommendations($results, $metadata, $cacheHitRate),
        ];

        return $stats;
    }

    /**
     * Generate performance and usage recommendations.
     *
     * @param  array<string, mixed>  $results
     * @param  array<string, mixed>  $metadata
     * @return array<string>
     */
    private function generateRecommendations(array $results, array $metadata, float $cacheHitRate): array
    {
        $recommendations = [];
        $successRate = round(($metadata['successful_models'] / $metadata['total_models_requested']) * 100, 1);

        // Performance recommendations
        if ($metadata['average_response_time_ms'] > 5000) {
            $recommendations[] = 'Consider using fewer models or quick generation mode for faster results';
        }

        if ($cacheHitRate < 20) {
            $recommendations[] = 'Low cache hit rate - similar requests could be cached for better performance';
        }

        // Reliability recommendations
        if ($metadata['failed_models'] > 0) {
            $recommendations[] = 'Some models failed - check API quotas and connectivity';
        }

        // Success recommendations
        if ($successRate == 100.0) {
            $recommendations[] = 'Excellent performance - all models executed successfully';
        }

        return $recommendations;
    }

    /**
     * Get available generation strategies.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableStrategies(): array
    {
        return [
            'quick' => [
                'name' => 'Quick Generation',
                'description' => 'Fast results using reliable models',
                'models' => ['gpt-4', 'claude-3.5-sonnet'],
                'estimated_time' => '2-5 seconds',
                'best_for' => 'Rapid prototyping and iteration',
            ],
            'comprehensive' => [
                'name' => 'Comprehensive Generation',
                'description' => 'High-quality results from all models with deep thinking',
                'models' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'],
                'estimated_time' => '5-15 seconds',
                'best_for' => 'Final name selection and brand development',
            ],
            'custom' => [
                'name' => 'Custom Generation',
                'description' => 'Tailored model selection and parameters',
                'models' => 'User-defined',
                'estimated_time' => 'Variable',
                'best_for' => 'Specific requirements and advanced use cases',
            ],
        ];
    }

    /**
     * Generate names with image context from a generation session.
     *
     * @param  array<string>  $models
     * @param  array<string, mixed>  $customParams
     * @return array<string, mixed>
     */
    public function generateNamesWithContext(
        string $businessIdea,
        GenerationSession $session,
        array $models = ['gpt-4'],
        string $mode = 'creative',
        bool $deepThinking = false,
        array $customParams = []
    ): array {
        $imageContext = '';

        if ($session->image_context_ids !== null && ! empty($session->image_context_ids)) {
            $images = $session->getImageContexts();
            $imageContext = $this->visionService->getImageContextForGeneration($images->all());
        }

        $enhancedBusinessIdea = $businessIdea.$imageContext;

        return $this->generateNamesParallel(
            $enhancedBusinessIdea,
            $models,
            $mode,
            $deepThinking,
            $customParams
        );
    }

    /**
     * Generate names with retry logic using direct Prism integration.
     *
     * @param  array<string, mixed>  $customParams
     * @return array<string, mixed>
     */
    private function generateWithRetry(
        string $businessIdea,
        string $primaryModel,
        string $mode,
        bool $deepThinking,
        int $count,
        array $customParams
    ): array {
        $startTime = microtime(true);

        // Check cache first
        $cacheKey = $this->generateCacheKey($businessIdea, $primaryModel, $mode, $deepThinking, $customParams);
        $cachedResult = GenerationCache::findByHash($cacheKey);

        if ($cachedResult !== null) {
            return [
                'names' => $cachedResult->generated_names,
                'model' => $primaryModel,
                'generation_mode' => $mode,
                'deep_thinking' => $deepThinking,
                'temperature' => $this->getTemperature($primaryModel, $deepThinking, $customParams),
                'max_tokens' => $this->getMaxTokens($primaryModel, $customParams),
                'response_time_ms' => 0, // Cached response
                'status' => 'completed',
                'cached' => true,
                'retry_count' => 0,
                'created_at' => $cachedResult->created_at->toISOString(),
            ];
        }

        // Try primary model with retries
        $lastException = null;
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $names = $this->generateNamesForModel($businessIdea, $primaryModel, $mode, $deepThinking, $count, $customParams);
                $responseTime = (microtime(true) - $startTime) * 1000;

                $result = [
                    'names' => $names,
                    'model' => $primaryModel,
                    'generation_mode' => $mode,
                    'deep_thinking' => $deepThinking,
                    'temperature' => $this->getTemperature($primaryModel, $deepThinking, $customParams),
                    'max_tokens' => $this->getMaxTokens($primaryModel, $customParams),
                    'response_time_ms' => (int) round($responseTime),
                    'status' => 'completed',
                    'cached' => false,
                    'retry_count' => $attempt - 1,
                    'created_at' => now()->toISOString(),
                ];

                // Cache the result
                $this->cacheResult($cacheKey, $businessIdea, $primaryModel, $mode, $deepThinking, $names);

                return $result;

            } catch (Exception $e) {
                $lastException = $e;
                $errorType = $this->categorizeError($e->getMessage());

                // Don't retry for non-transient errors
                if (! $this->isTransientError($errorType)) {
                    break;
                }

                // Wait before retry (except on last attempt)
                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY_SECONDS * $attempt); // Exponential backoff
                }
            }
        }

        // No fallback models - if primary fails, we throw exception

        // All AI models failed, throw exception
        throw new Exception('AI generation failed: '.$this->normalizeError($lastException->getMessage()));
    }

    /**
     * Generate names for a specific model using direct Prism integration.
     *
     * @param  array<string, mixed>  $customParams
     * @return array<int, string>
     */
    private function generateNamesForModel(
        string $businessIdea,
        string $model,
        string $mode,
        bool $deepThinking,
        int $count,
        array $customParams
    ): array {
        $config = self::MODEL_CONFIGS[$model];
        $systemPrompt = $this->promptBuilder->buildSystemPrompt($model, $count, $mode, $deepThinking);
        $userPrompt = $this->promptBuilder->buildUserPrompt($businessIdea, $model, $mode, $deepThinking);

        $temperature = $this->getTemperature($model, $deepThinking, $customParams);
        $maxTokens = $this->getMaxTokens($model, $customParams);

        $response = Prism::text()
            ->using($config['provider'], $config['model'])
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userPrompt)
            ->withClientOptions([
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'timeout' => 15, // Reasonable timeout for AI API calls
            ])
            ->asText();

        return $this->parseResponse($response->text, $count);
    }

    /**
     * Parse response text into array of names.
     *
     * @return array<int, string>
     */
    private function parseResponse(string $responseText, int $expectedCount): array
    {
        $lines = explode("\n", trim($responseText));
        $names = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Remove numbering (1., 2., etc.) and clean up
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                $name = trim($matches[1]);
                if (! empty($name)) {
                    $names[] = $name;
                }
            } elseif (! empty($line) && ! preg_match('/^\d+$/', $line)) {
                // Handle cases where names aren't numbered
                $names[] = $line;
            }
        }

        // Ensure we have the expected number of names
        return array_slice($names, 0, $expectedCount);
    }

    /**
     * Get temperature for a model with custom overrides.
     *
     * @param  array<string, mixed>  $customParams
     */
    private function getTemperature(string $model, bool $deepThinking, array $customParams): float
    {
        if (isset($customParams['temperature'])) {
            return (float) $customParams['temperature'];
        }

        $config = self::MODEL_CONFIGS[$model];

        return $deepThinking ? $config['deep_thinking_temperature'] : $config['temperature'];
    }

    /**
     * Get max tokens for a model with custom overrides.
     *
     * @param  array<string, mixed>  $customParams
     */
    private function getMaxTokens(string $model, array $customParams): int
    {
        if (isset($customParams['max_tokens'])) {
            return (int) $customParams['max_tokens'];
        }

        return self::MODEL_CONFIGS[$model]['max_tokens'];
    }

    /**
     * Generate cache key for the request.
     *
     * @param  array<string, mixed>  $customParams
     */
    private function generateCacheKey(
        string $businessIdea,
        string $model,
        string $mode,
        bool $deepThinking,
        array $customParams
    ): string {
        $combinedDescription = $businessIdea.'|model:'.$model.'|params:'.json_encode($customParams);

        return GenerationCache::generateHash($combinedDescription, $mode, $deepThinking);
    }

    /**
     * Cache generation result.
     *
     * @param  array<int, string>  $names
     */
    private function cacheResult(
        string $cacheKey,
        string $businessIdea,
        string $model,
        string $mode,
        bool $deepThinking,
        array $names
    ): void {
        $combinedDescription = $businessIdea.'|model:'.$model;

        GenerationCache::updateOrCreate(
            ['input_hash' => $cacheKey],
            [
                'business_description' => $combinedDescription,
                'mode' => $mode,
                'deep_thinking' => $deepThinking,
                'generated_names' => $names,
                'cached_at' => now(),
            ]
        );
    }

    /**
     * Categorize error types for intelligent handling.
     */
    private function categorizeError(string $message): string
    {
        $lowerMessage = strtolower($message);

        if (str_contains($lowerMessage, 'timeout') || str_contains($lowerMessage, 'connection timed out')) {
            return 'timeout';
        }

        if (str_contains($lowerMessage, 'rate limit') || str_contains($lowerMessage, '429')) {
            return 'rate_limit';
        }

        if (str_contains($lowerMessage, 'unauthorized') || str_contains($lowerMessage, '401')) {
            return 'unauthorized';
        }

        if (str_contains($lowerMessage, 'insufficient_quota') || str_contains($lowerMessage, 'quota')) {
            return 'quota_exceeded';
        }

        if (str_contains($lowerMessage, 'server error') || str_contains($lowerMessage, '500') || str_contains($lowerMessage, '502') || str_contains($lowerMessage, '503')) {
            return 'server_error';
        }

        if (str_contains($lowerMessage, 'network') || str_contains($lowerMessage, 'connection')) {
            return 'network_error';
        }

        return 'unknown';
    }

    /**
     * Check if error type is transient and should be retried.
     */
    private function isTransientError(string $errorType): bool
    {
        return in_array($errorType, [
            'timeout',
            'rate_limit',
            'server_error',
            'network_error',
        ]);
    }

    /**
     * Normalize error messages for consistent handling.
     */
    private function normalizeError(string $message): string
    {
        $errorType = $this->categorizeError($message);

        return match ($errorType) {
            'timeout' => 'API timeout',
            'rate_limit' => 'Rate limit exceeded',
            'unauthorized' => 'Invalid API key',
            'quota_exceeded' => 'API quota exceeded',
            'server_error' => 'Server error',
            'network_error' => 'Network error',
            default => $message,
        };
    }

    /**
     * Validate input parameters.
     *
     * @param  array<string>  $models
     */
    private function validateInput(string $businessIdea, array $models, string $mode): void
    {
        if (empty(trim($businessIdea))) {
            throw new InvalidArgumentException('Business idea cannot be empty');
        }

        if (strlen($businessIdea) > self::MAX_INPUT_LENGTH) {
            throw new InvalidArgumentException('Business idea is too long');
        }

        if (empty($models)) {
            throw new InvalidArgumentException('At least one model must be specified');
        }

        foreach ($models as $model) {
            if (! in_array($model, self::VALID_MODELS)) {
                throw new InvalidArgumentException("Invalid model: {$model}");
            }
        }

        if (! in_array($mode, self::VALID_MODES)) {
            throw new InvalidArgumentException("Invalid generation mode: {$mode}");
        }
    }
}
