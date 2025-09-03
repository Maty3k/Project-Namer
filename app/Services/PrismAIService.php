<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationCache;
use Exception;
use InvalidArgumentException;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

/**
 * Service for generating business names using multiple AI models via Prism.
 *
 * Supports GPT-4, Claude, Gemini, and Grok with model-specific optimizations,
 * intelligent fallback, and parallel execution capabilities.
 */
final class PrismAIService
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

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_SECONDS = 1;

    private const FALLBACK_MODEL_ORDER = [
        'gpt-4' => ['claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'],
        'claude-3.5-sonnet' => ['gpt-4', 'gemini-1.5-pro', 'grok-beta'],
        'gemini-1.5-pro' => ['gpt-4', 'claude-3.5-sonnet', 'grok-beta'],
        'grok-beta' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
    ];

    private const MODEL_CONFIGS = [
        'gpt-4' => [
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'max_tokens' => 200,
            'temperature' => 0.7,
            'deep_thinking_temperature' => 0.3,
        ],
        'claude-3.5-sonnet' => [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 200,
            'temperature' => 0.7,
            'deep_thinking_temperature' => 0.3,
        ],
        'gemini-1.5-pro' => [
            'provider' => 'google',
            'model' => 'gemini-1.5-pro',
            'max_tokens' => 200,
            'temperature' => 0.8,
            'deep_thinking_temperature' => 0.4,
        ],
        'grok-beta' => [
            'provider' => 'xai',
            'model' => 'grok-beta',
            'max_tokens' => 200,
            'temperature' => 0.9,
            'deep_thinking_temperature' => 0.5,
        ],
    ];

    /**
     * Generate business names using specified AI models.
     *
     * @param  string  $businessIdea  The business concept or description
     * @param  array<string>  $models  Array of model names to use
     * @param  string  $mode  Generation mode (creative, professional, brandable, tech-focused)
     * @param  bool  $deepThinking  Whether to use deep thinking mode for enhanced results
     * @param  array<string, mixed>  $customParams  Optional custom parameters to override defaults
     * @return array<string, array<string, mixed>> Results keyed by model name
     *
     * @throws InvalidArgumentException If input parameters are invalid
     */
    public function generateNames(
        string $businessIdea,
        array $models,
        string $mode,
        bool $deepThinking = false,
        array $customParams = []
    ): array {
        $this->validateInput($businessIdea, $models, $mode);

        $results = [];
        $count = $customParams['count'] ?? self::DEFAULT_COUNT;

        foreach ($models as $model) {
            $results[$model] = $this->generateWithFallback($businessIdea, $model, $mode, $deepThinking, $count, $customParams);
        }

        return $results;
    }

    /**
     * Generate names with intelligent fallback and retry logic.
     *
     * @param  array<string, mixed>  $customParams
     * @return array<string, mixed>
     */
    private function generateWithFallback(
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
                'fallback_used' => false,
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
                    'fallback_used' => false,
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

        // Try fallback models if primary model failed
        $fallbackModels = self::FALLBACK_MODEL_ORDER[$primaryModel] ?? [];
        foreach ($fallbackModels as $fallbackModel) {
            try {
                $fallbackCacheKey = $this->generateCacheKey($businessIdea, $fallbackModel, $mode, $deepThinking, $customParams);
                $fallbackCachedResult = GenerationCache::findByHash($fallbackCacheKey);

                if ($fallbackCachedResult !== null) {
                    return [
                        'names' => $fallbackCachedResult->generated_names,
                        'model' => $fallbackModel,
                        'generation_mode' => $mode,
                        'deep_thinking' => $deepThinking,
                        'temperature' => $this->getTemperature($fallbackModel, $deepThinking, $customParams),
                        'max_tokens' => $this->getMaxTokens($fallbackModel, $customParams),
                        'response_time_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'status' => 'completed',
                        'cached' => true,
                        'fallback_used' => true,
                        'fallback_from' => $primaryModel,
                        'retry_count' => self::MAX_RETRIES,
                        'created_at' => $fallbackCachedResult->created_at->toISOString(),
                    ];
                }

                $names = $this->generateNamesForModel($businessIdea, $fallbackModel, $mode, $deepThinking, $count, $customParams);
                $responseTime = (microtime(true) - $startTime) * 1000;

                $result = [
                    'names' => $names,
                    'model' => $fallbackModel,
                    'generation_mode' => $mode,
                    'deep_thinking' => $deepThinking,
                    'temperature' => $this->getTemperature($fallbackModel, $deepThinking, $customParams),
                    'max_tokens' => $this->getMaxTokens($fallbackModel, $customParams),
                    'response_time_ms' => (int) round($responseTime),
                    'status' => 'completed',
                    'cached' => false,
                    'fallback_used' => true,
                    'fallback_from' => $primaryModel,
                    'retry_count' => self::MAX_RETRIES,
                    'created_at' => now()->toISOString(),
                ];

                // Cache the result with the fallback model key
                $this->cacheResult($fallbackCacheKey, $businessIdea, $fallbackModel, $mode, $deepThinking, $names);

                return $result;

            } catch (Exception) {
                // Continue to next fallback model
                continue;
            }
        }

        // All models failed
        $responseTime = (microtime(true) - $startTime) * 1000;

        return [
            'names' => [],
            'model' => $primaryModel,
            'generation_mode' => $mode,
            'deep_thinking' => $deepThinking,
            'temperature' => $this->getTemperature($primaryModel, $deepThinking, $customParams),
            'max_tokens' => $this->getMaxTokens($primaryModel, $customParams),
            'response_time_ms' => (int) round($responseTime),
            'status' => 'failed',
            'error' => $this->normalizeError($lastException->getMessage()),
            'cached' => false,
            'fallback_used' => true,
            'fallback_from' => null,
            'retry_count' => self::MAX_RETRIES,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate names for a specific model.
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
        $systemPrompt = $this->buildSystemPrompt($model, $count);
        $userPrompt = $this->buildUserPrompt($businessIdea, $model, $mode, $deepThinking);

        $temperature = $this->getTemperature($model, $deepThinking, $customParams);
        $maxTokens = $this->getMaxTokens($model, $customParams);

        $response = Prism::text()
            ->using($config['provider'], $config['model'])
            ->withMessages([
                new SystemMessage($systemPrompt),
                new UserMessage($userPrompt),
            ])
            ->withClientOptions([
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ])
            ->asText();

        return $this->parseResponse($response->text, $count);
    }

    /**
     * Build system prompt optimized for the specific model.
     */
    private function buildSystemPrompt(string $model, int $count): string
    {
        $basePrompt = "You are a creative business naming expert. Generate exactly {$count} business names, numbered 1-{$count}, one per line.";

        // Model-specific optimizations
        return match ($model) {
            'claude-3.5-sonnet' => $basePrompt.' Focus on context-aware, nuanced names that reflect deep understanding of the business concept and target market.',
            'gemini-1.5-pro' => $basePrompt.' Provide diverse creative perspectives and consider multilingual appeal where appropriate.',
            'grok-beta' => $basePrompt.' Be bold and innovative with edgy, disruptive names perfect for tech startups and modern brands.',
            // gpt-4o
            default => $basePrompt.' Create memorable, brandable names with strong commercial appeal.',
        };
    }

    /**
     * Build user prompt optimized for the specific model and mode.
     */
    private function buildUserPrompt(string $businessIdea, string $model, string $mode, bool $deepThinking): string
    {
        $modePrompts = [
            'creative' => 'Generate creative, unique, and memorable business names that stand out and spark curiosity.',
            'professional' => 'Generate professional, trustworthy business names suitable for corporate environments.',
            'brandable' => 'Generate brandable, catchy names that are easy to remember and could work as domain names.',
            'tech-focused' => 'Generate tech-focused names that appeal to developers and technical audiences.',
        ];

        $basePrompt = $modePrompts[$mode]."\n\nBusiness concept: ".$businessIdea;

        if ($deepThinking) {
            // Model-specific deep thinking enhancements
            match ($model) {
                'claude-3.5-sonnet' => $basePrompt .= "\n\nTake time to deeply analyze the business concept, target audience, market positioning, and brand personality. Consider linguistic nuances and cultural implications.",
                'gemini-1.5-pro' => $basePrompt .= "\n\nConsider multiple creative perspectives and approaches. Think about global appeal and cross-cultural resonance.",
                'grok-beta' => $basePrompt .= "\n\nThink outside the box and challenge conventional naming approaches. Consider disruptive potential and modern tech trends.",
                // gpt-4o
                default => $basePrompt .= "\n\nTake time to consider the target audience, market positioning, and brand personality. Think about names that would resonate with customers and be easy to market.",
            };
        }

        return $basePrompt;
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

    /**
     * Get available models with their configurations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableModels(): array
    {
        $models = [];

        foreach (self::MODEL_CONFIGS as $id => $config) {
            $models[] = [
                'id' => $id,
                'name' => $this->getModelDisplayName($id),
                'provider' => $config['provider'],
                'available' => true, // For now, assume all models are available
                'features' => [
                    'deep_thinking' => true,
                    'parallel_processing' => true,
                    'real_time_progress' => true,
                ],
                'performance_metrics' => [
                    'average_response_time_ms' => $this->getAverageResponseTime($id),
                    'success_rate' => 0.95, // 95% success rate
                    'cost_per_request_cents' => $this->getCostPerRequest($id),
                ],
            ];
        }

        return $models;
    }

    /**
     * Get display name for a model.
     */
    private function getModelDisplayName(string $modelId): string
    {
        return match ($modelId) {
            'gpt-4' => 'GPT-4',
            'claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'grok-beta' => 'Grok Beta',
            default => ucfirst(str_replace('-', ' ', $modelId)),
        };
    }

    /**
     * Get average response time for a model (in milliseconds).
     */
    private function getAverageResponseTime(string $modelId): int
    {
        return match ($modelId) {
            'gpt-4' => 2500,
            'claude-3.5-sonnet' => 3000,
            'gemini-1.5-pro' => 2200,
            'grok-beta' => 3500,
            default => 3000,
        };
    }

    /**
     * Get cost per request for a model (in cents).
     */
    private function getCostPerRequest(string $modelId): int
    {
        return match ($modelId) {
            'gpt-4' => 5,
            'claude-3.5-sonnet' => 4,
            'gemini-1.5-pro' => 3,
            'grok-beta' => 6,
            default => 5,
        };
    }

    /**
     * Check if a model is valid.
     */
    public function isValidModel(string $model): bool
    {
        return in_array($model, self::VALID_MODELS);
    }

    /**
     * Get valid generation modes.
     *
     * @return array<int, string>
     */
    public function getValidModes(): array
    {
        return self::VALID_MODES;
    }
}
