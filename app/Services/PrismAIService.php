<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationCache;
use Exception;
use InvalidArgumentException;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

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
        $systemPrompt = $this->buildSystemPrompt($model, $count, $mode, $deepThinking);
        $userPrompt = $this->buildUserPrompt($businessIdea, $model, $mode, $deepThinking);

        $temperature = $this->getTemperature($model, $deepThinking, $customParams);
        $maxTokens = $this->getMaxTokens($model, $customParams);

        $provider = $this->getProviderEnum($model);

        $response = Prism::text()
            ->using($provider, $config['model'])
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userPrompt)
            ->withClientOptions([
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ])
            ->asText();

        return $this->parseResponse($response->text, $count);
    }

    /**
     * Build system prompt optimized for the generation mode.
     */
    private function buildSystemPrompt(string $model, int $count, string $mode, bool $deepThinking = false): string
    {
        // Core rules that apply to all modes
        $coreRules = '
CRITICAL RULES:
- Names must be directly relevant to the business concept
- NO generic tech suffixes (App, Tech, Labs, Solutions, Systems, Digital, Hub, Pro, etc.)
- NO unnecessary prefixes (My, Get, The, etc.) unless they add meaningful value
- Names should sound like actual business names, not product features
- Focus on the CORE business value, not the technology behind it
- Make names memorable, pronounceable, and brandable
- Avoid overused words like "Smart", "Cloud", "AI", "Sync", "Connect"';

        $modeSystemPrompts = [
            'creative' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

CREATIVE MODE: Generate creative and artistic names that evoke emotion and curiosity. Think of names like \"Spotify\", \"Airbnb\", or \"Etsy\" - unique, memorable, and meaningful without being literal. Use wordplay, metaphors, or invented words that capture the essence of the business.",

            'professional' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

PROFESSIONAL MODE: Generate sophisticated, trustworthy names suitable for B2B environments. Think of names like \"Goldman Sachs\", \"McKinsey\", or \"Deloitte\" - authoritative, credible, and corporate-appropriate. Use strong, confident language that conveys expertise and reliability.",

            'brandable' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

BRANDABLE MODE: Generate catchy, market-ready names perfect for consumer brands. Think of names like \"Google\", \"Amazon\", or \"Nike\" - short, punchy, and easy to remember. Focus on names that would work well in advertising, social media, and word-of-mouth marketing.",

            'tech-focused' => "You are an expert business naming consultant who creates compelling brand names. Generate exactly {$count} unique business names, numbered 1-{$count}, one per line.

{$coreRules}

TECH-FOCUSED MODE: Generate modern names that appeal to technical audiences without using obvious tech jargon. Think of names like \"GitHub\", \"Stripe\", or \"Slack\" - developer-friendly but not generic. Focus on the problem being solved, not the technology used.",
        ];

        $systemPrompt = $modeSystemPrompts[$mode] ?? $modeSystemPrompts['creative'];

        if ($deepThinking) {
            $systemPrompt .= "\n\nDEEP THINKING MODE: Take extra time to analyze the business concept deeply. Consider market positioning, target demographics, competitive landscape, and long-term brand potential. Think about how each name would work across different marketing channels and customer touchpoints. Ensure names have strong commercial viability and avoid generic terms.";
        }

        return $systemPrompt;
    }

    /**
     * Build user prompt with the business concept.
     */
    private function buildUserPrompt(string $businessIdea, string $model, string $mode, bool $deepThinking): string
    {
        // Analyze business type for better context
        $businessType = 'General Business';
        $audience = 'General Public';
        $examples = '';

        $lowerIdea = strtolower($businessIdea);

        if (str_contains($lowerIdea, 'app') || str_contains($lowerIdea, 'software') || str_contains($lowerIdea, 'platform')) {
            $businessType = 'Technology/Software';
            $audience = 'Tech Users';
            $examples = "\n\nGood examples for tech businesses: GitHub, Stripe, Slack, Figma\nNotice how these avoid generic tech terms.";
        } elseif (str_contains($lowerIdea, 'food') || str_contains($lowerIdea, 'restaurant') || str_contains($lowerIdea, 'bakery')) {
            $businessType = 'Food & Beverage';
            $audience = 'Food Lovers';
            $examples = "\n\nGood examples for food businesses: Sweetgreen, Chipotle, Panera, Starbucks\nNotice how these focus on the food experience, not generic terms.";
        } elseif (str_contains($lowerIdea, 'shop') || str_contains($lowerIdea, 'store') || str_contains($lowerIdea, 'retail')) {
            $businessType = 'Retail/E-commerce';
            $audience = 'Shoppers';
            $examples = "\n\nGood examples for retail: Amazon, Etsy, Warby Parker, Casper\nNotice how these are brandable and memorable.";
        } elseif (str_contains($lowerIdea, 'consult') || str_contains($lowerIdea, 'service') || str_contains($lowerIdea, 'agency')) {
            $businessType = 'Professional Services';
            $audience = 'Business Clients';
            $examples = "\n\nGood examples for services: McKinsey, Deloitte, Accenture, IDEO\nNotice how these convey expertise and authority.";
        }

        $contextualGuidance = "\n\nBusiness Analysis:
- Type: {$businessType}
- Target Audience: {$audience}

Consider these factors:
- What problem does this business solve?
- What makes it different from competitors?
- What emotion should the brand evoke?
- Who is the primary customer?{$examples}";

        return "Business concept: {$businessIdea}{$contextualGuidance}";
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

    /**
     * Generate names using a single OpenAI model (simplified method).
     *
     * @param  string  $businessIdea  The business concept
     * @param  string  $model  The model ID (e.g., 'gpt-4')
     * @param  string  $mode  Generation mode
     * @param  bool  $deepThinking  Whether to use deep thinking
     * @return array<int, string> Array of generated names
     */
    public function generateNamesForSingleModel(
        string $businessIdea,
        string $model,
        string $mode,
        bool $deepThinking = false
    ): array {
        $this->validateInput($businessIdea, [$model], $mode);

        $count = 10; // Always generate 10 names

        try {
            $result = $this->generateWithFallback($businessIdea, $model, $mode, $deepThinking, $count, []);

            return $result['names'] ?? [];
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::warning('PrismAIService failed to generate names', [
                'error' => $e->getMessage(),
                'model' => $model,
                'mode' => $mode,
            ]);

            // Return empty array, component will use fallback
            return [];
        }
    }

    /**
     * Get Provider enum for a given model.
     */
    private function getProviderEnum(string $model): Provider
    {
        $config = self::MODEL_CONFIGS[$model];

        $provider = $config['provider'];

        if ($provider === 'openai') {
            return Provider::OpenAI;
        }

        if ($provider === 'anthropic') {
            return Provider::Anthropic;
        }

        if ($provider === 'google') {
            return Provider::Gemini;
        }

        if ($provider === 'xai') {
            return Provider::XAI;
        }

        // @phpstan-ignore-next-line deadCode.unreachable
        throw new InvalidArgumentException("Unknown provider: {$provider}");
    }

    /**
     * Optimize prompt for specific model and mode.
     */
    public function optimizePrompt(string $modelId, string $basePrompt, string $mode, bool $deepThinking): string
    {
        $systemPrompt = 'You are an expert business naming consultant who creates compelling brand names. Generate exactly 10 unique business names, numbered 1-10, one per line.

CRITICAL RULES:
- Names must be directly relevant to the business concept
- NO generic tech suffixes (App, Tech, Labs, Solutions, Systems, Digital, Hub, Pro, etc.)
- NO unnecessary prefixes (My, Get, The, etc.) unless they add meaningful value
- Names should sound like actual business names, not product features
- Focus on the CORE business value, not the technology behind it
- Make names memorable, pronounceable, and brandable
- Avoid overused words like "Smart", "Cloud", "AI", "Sync", "Connect"';

        $modeInstructions = match ($mode) {
            'creative' => 'Generate creative and artistic names that evoke emotion and curiosity. Think of names like "Spotify", "Airbnb", or "Etsy" - unique, memorable, and meaningful without being literal. Use wordplay, metaphors, or invented words that capture the essence of the business.',

            'professional' => 'Generate sophisticated, trustworthy names suitable for B2B environments. Think of names like "Goldman Sachs", "McKinsey", or "Deloitte" - authoritative, credible, and corporate-appropriate. Use strong, confident language that conveys expertise and reliability.',

            'brandable' => 'Generate catchy, market-ready names perfect for consumer brands. Think of names like "Google", "Amazon", or "Nike" - short, punchy, and easy to remember. Focus on names that would work well in advertising, social media, and word-of-mouth marketing.',

            'tech-focused' => 'Generate modern names that appeal to technical audiences without using obvious tech jargon. Think of names like "GitHub", "Stripe", or "Slack" - developer-friendly but not generic. Focus on the problem being solved, not the technology used.',

            default => 'Generate creative and memorable business names that capture the essence of the business concept.'
        };

        // Basic business analysis based on keywords
        $businessType = 'General Business';
        $audience = 'General Public';
        if (str_contains(strtolower($basePrompt), 'app') || str_contains(strtolower($basePrompt), 'software') || str_contains(strtolower($basePrompt), 'platform')) {
            $businessType = 'Technology/Software';
            $audience = 'Tech Users';
        } elseif (str_contains(strtolower($basePrompt), 'food') || str_contains(strtolower($basePrompt), 'restaurant') || str_contains(strtolower($basePrompt), 'bakery')) {
            $businessType = 'Food & Beverage';
            $audience = 'Food Lovers';
        }

        $contextualGuidance = "Business Type: {$businessType}
Target Audience: {$audience}

Consider these factors when creating names:
- What problem does this business solve?
- What makes it different from competitors?
- What emotion should the brand evoke?
- Who is the primary customer?";

        $thinkingInstruction = $deepThinking
            ? 'Take extra time to analyze the business concept deeply. Consider market positioning, target demographics, competitive landscape, and long-term brand potential. Think about how each name would work across different marketing channels and customer touchpoints.'
            : 'Focus on creating names that immediately communicate the business value and appeal to the target audience.';

        // Examples based on mode
        $examples = match ($mode) {
            'creative' => 'Good examples: Spotify, Airbnb, Etsy, Headspace, Sweetgreen
Notice how these names are memorable, unique, and avoid obvious descriptive terms.',
            'professional' => 'Good examples: Goldman Sachs, McKinsey, Deloitte, Salesforce
Notice how these names convey authority, expertise, and trustworthiness.',
            'brandable' => 'Good examples: Google, Amazon, Nike, Apple, Tesla
Notice how these names are short, punchy, and perfect for marketing.',
            'tech-focused' => 'Good examples: GitHub, Stripe, Slack, Figma
Notice how these appeal to developers without using generic tech terms.',
            default => 'Good examples: Apple, Google, Amazon, Nike, Tesla'
        };

        return "{$systemPrompt}\n\n{$modeInstructions}\n\n{$contextualGuidance}\n\n{$thinkingInstruction}\n\n{$examples}\n\nBusiness concept: {$basePrompt}";
    }
}
