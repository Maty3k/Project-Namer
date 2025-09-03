<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationSession;
use InvalidArgumentException;

/**
 * Service for coordinating AI name generation across multiple models.
 *
 * Orchestrates parallel execution of multiple AI models for efficient
 * business name generation with intelligent load balancing and coordination.
 */
final readonly class AIGenerationService
{
    public function __construct(
        private PrismAIService $prismService,
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
        if (empty($models)) {
            throw new InvalidArgumentException('At least one model must be specified');
        }

        $startTime = microtime(true);

        // Execute all models through PrismAIService (which handles each model sequentially but with intelligent fallback)
        $results = $this->prismService->generateNames($businessIdea, $models, $mode, $deepThinking, $customParams);

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
                'models_with_fallback' => array_keys(array_filter($results, fn ($result) => $result['fallback_used'] ?? false)),
                'cached_results' => array_keys(array_filter($results, fn ($result) => $result['cached'] ?? false)),
                'execution_strategy' => 'sequential_with_fallback',
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
        $fallbackRate = round((count($metadata['models_with_fallback']) / $metadata['total_models_requested']) * 100, 1);

        $stats = [
            'performance' => [
                'success_rate' => round(($metadata['successful_models'] / $metadata['total_models_requested']) * 100, 1),
                'average_response_time' => $metadata['average_response_time_ms'],
                'total_execution_time' => $metadata['total_execution_time_ms'],
                'cache_hit_rate' => $cacheHitRate,
            ],
            'reliability' => [
                'models_with_fallback' => count($metadata['models_with_fallback']),
                'fallback_rate' => $fallbackRate,
                'failed_models' => $metadata['failed_models'],
            ],
            'recommendations' => $this->generateRecommendations($results, $metadata, $cacheHitRate, $fallbackRate),
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
    private function generateRecommendations(array $results, array $metadata, float $cacheHitRate, float $fallbackRate): array
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
        if ($fallbackRate > 50) {
            $recommendations[] = 'High fallback rate detected - primary models may be experiencing issues';
        }

        if ($metadata['failed_models'] > 0) {
            $recommendations[] = 'Some models failed - check API quotas and connectivity';
        }

        // Success recommendations
        if ($successRate == 100.0 && $fallbackRate == 0.0) {
            $recommendations[] = 'Excellent performance - all models executed successfully without fallback';
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
}
