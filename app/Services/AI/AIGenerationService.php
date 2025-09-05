<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Jobs\GenerateNamesWithModelJob;
use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI Generation Service - Manages AI generation sessions and coordination.
 *
 * Handles the orchestration of AI generation requests, tracking sessions,
 * and coordinating between different AI models and the PrismAI service.
 */
class AIGenerationService
{
    public function __construct(
        protected PrismAIService $prismAI
    ) {}

    /**
     * Start a new AI generation session.
     *
     * @param  array<int, string>  $models
     * @param  array<string, mixed>  $parameters
     */
    public function startGeneration(
        Project $project,
        User $user,
        array $models,
        string $prompt,
        array $parameters = []
    ): AIGeneration {
        $sessionId = Str::uuid()->toString();

        $aiGeneration = AIGeneration::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'generation_session_id' => $sessionId,
            'models_requested' => $models,
            'generation_mode' => $parameters['mode'] ?? 'creative',
            'deep_thinking' => $parameters['deep_thinking'] ?? false,
            'status' => 'pending',
            'prompt_used' => $prompt,
        ]);

        return $aiGeneration;
    }

    /**
     * Execute AI generation for multiple models in parallel.
     *
     * @param  array<int, string>  $models
     * @param  array<string, mixed>  $parameters
     * @return array<string, array<int, string>>
     */
    public function generateWithModels(
        AIGeneration $aiGeneration,
        array $models,
        string $prompt,
        array $parameters = []
    ): array {
        $aiGeneration->markAsStarted();

        // Use synchronous execution to avoid timeout issues until queue workers are properly configured
        // In web requests, parallel execution with polling can exceed PHP execution time limits
        // TODO: Re-enable parallel execution once queue workers are running in production
        return $this->generateSequentially($aiGeneration, $models, $prompt, $parameters);
    }

    /**
     * Execute AI generation for multiple models in parallel using Laravel Jobs.
     *
     * @param  array<int, string>  $models
     * @param  array<string, mixed>  $parameters
     * @return array<string, array<int, string>>
     */
    protected function generateInParallel(
        AIGeneration $aiGeneration,
        array $models,
        string $prompt,
        array $parameters = []
    ): array {
        $startTime = microtime(true);

        try {
            // Filter available models
            $availableModels = collect($models)->filter(function ($modelId) use ($aiGeneration) {
                $available = $this->prismAI->isModelAvailable($modelId);
                if (! $available) {
                    Log::warning('AI model not available during parallel generation', [
                        'model' => $modelId,
                        'session' => $aiGeneration->generation_session_id,
                    ]);
                }

                return $available;
            })->values()->toArray();

            if (empty($availableModels)) {
                throw new Exception('No AI models available for generation');
            }

            // Initialize generation metadata
            $aiGeneration->update([
                'execution_metadata' => [
                    'model_status' => array_fill_keys($availableModels, 'pending'),
                    'start_time' => $startTime,
                    'parallel_execution' => true,
                    'models_requested' => $availableModels,
                ],
            ]);

            // Dispatch jobs for each model in parallel
            foreach ($availableModels as $modelId) {
                GenerateNamesWithModelJob::dispatch($aiGeneration, $modelId, $prompt, $parameters);
            }

            // Wait for all jobs to complete and collect results
            $results = $this->waitForParallelResults($aiGeneration, $availableModels);

            // Calculate execution metadata
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // milliseconds

            $aiGeneration->markAsCompleted([
                'names' => $results,
                'models_used' => array_keys($results),
            ], [
                'generation_mode' => $parameters['mode'] ?? 'creative',
                'deep_thinking' => $parameters['deep_thinking'] ?? false,
                'models_requested' => $models,
                'models_completed' => array_keys($results),
                'parallel_execution' => true,
                'total_execution_time_ms' => $executionTime,
                'model_status' => array_fill_keys(array_keys($results), 'completed'),
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Parallel AI generation failed', [
                'error' => $e->getMessage(),
                'models' => $models,
                'session' => $aiGeneration->generation_session_id,
            ]);

            $aiGeneration->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Wait for parallel job results and collect them.
     *
     * @param  array<int, string>  $models
     * @return array<string, array<int, string>>
     */
    protected function waitForParallelResults(AIGeneration $aiGeneration, array $models): array
    {
        $results = [];
        $maxWaitTime = 120; // 2 minutes maximum wait
        $checkInterval = 2; // Check every 2 seconds
        $elapsedTime = 0;

        Log::info('Waiting for parallel AI generation results', [
            'generation_id' => $aiGeneration->id,
            'models' => $models,
            'max_wait_time' => $maxWaitTime,
        ]);

        while ($elapsedTime < $maxWaitTime) {
            $completedModels = [];
            $allCompleted = true;

            foreach ($models as $modelId) {
                $cacheKey = "ai_generation_result_{$aiGeneration->id}_{$modelId}";
                $result = Cache::get($cacheKey);

                if ($result) {
                    if ($result['status'] === 'completed' && ! empty($result['results'])) {
                        $results[$modelId] = $result['results'];
                        $completedModels[] = $modelId;
                    } elseif ($result['status'] === 'failed') {
                        $completedModels[] = $modelId;
                        Log::warning('Model generation failed in parallel execution', [
                            'model' => $modelId,
                            'error' => $result['error'] ?? 'Unknown error',
                            'generation_id' => $aiGeneration->id,
                        ]);
                    }
                } else {
                    $allCompleted = false;
                }
            }

            if ($allCompleted || count($completedModels) === count($models)) {
                Log::info('All parallel AI generation jobs completed', [
                    'generation_id' => $aiGeneration->id,
                    'completed_models' => $completedModels,
                    'successful_results' => count($results),
                    'elapsed_time' => $elapsedTime,
                ]);
                break;
            }

            sleep($checkInterval);
            $elapsedTime += $checkInterval;
        }

        // Clean up cache entries
        foreach ($models as $modelId) {
            $cacheKey = "ai_generation_result_{$aiGeneration->id}_{$modelId}";
            Cache::forget($cacheKey);
        }

        if (empty($results)) {
            throw new Exception('All parallel AI generation jobs failed or timed out');
        }

        return $results;
    }

    /**
     * Execute AI generation for models sequentially (fallback method).
     *
     * @param  array<int, string>  $models
     * @param  array<string, mixed>  $parameters
     * @return array<string, array<int, string>>
     */
    protected function generateSequentially(
        AIGeneration $aiGeneration,
        array $models,
        string $prompt,
        array $parameters = []
    ): array {
        $results = [];
        $startTime = microtime(true);

        try {
            foreach ($models as $modelId) {
                if ($this->prismAI->isModelAvailable($modelId)) {
                    $optimizedPrompt = $this->prismAI->optimizePrompt(
                        $modelId,
                        $prompt,
                        $parameters['mode'] ?? 'creative',
                        $parameters['deep_thinking'] ?? false
                    );

                    $modelResults = $this->prismAI->generateNames($modelId, $optimizedPrompt, $parameters);
                    $results[$modelId] = $modelResults;
                } else {
                    Log::warning('AI model not available during generation', [
                        'model' => $modelId,
                        'session' => $aiGeneration->generation_session_id,
                    ]);
                }
            }

            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // milliseconds

            $aiGeneration->markAsCompleted([
                'names' => $results,
                'models_used' => array_keys($results),
            ], [
                'generation_mode' => $parameters['mode'] ?? 'creative',
                'deep_thinking' => $parameters['deep_thinking'] ?? false,
                'models_requested' => $models,
                'models_completed' => array_keys($results),
                'parallel_execution' => count($models) > 1 ? 'simulated' : false,
                'total_execution_time_ms' => $executionTime,
                'model_status' => array_fill_keys(array_keys($results), 'completed'),
                'model_metrics' => $this->generateMockMetrics(array_keys($results), $executionTime),
            ]);

            return $results;
        } catch (Exception $e) {
            $aiGeneration->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Get generation status for real-time updates.
     *
     * @return array<string, mixed>
     */
    public function getGenerationStatus(string $sessionId): array
    {
        $aiGeneration = AIGeneration::where('generation_session_id', $sessionId)->first();

        if (! $aiGeneration) {
            return [
                'status' => 'not_found',
                'message' => 'Generation session not found',
            ];
        }

        return $aiGeneration->getStatusSnapshot();
    }

    /**
     * Check if AI generation is available for user.
     */
    public function isAvailableForUser(User $user): bool
    {
        // For now, return true for all users
        // In production, this would check user permissions, quotas, etc.
        return true;
    }

    /**
     * Get available AI models for user.
     *
     * @return array<int, string>
     */
    public function getAvailableModels(User $user): array
    {
        $allModels = ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'];
        $availableModels = [];

        foreach ($allModels as $model) {
            if ($this->prismAI->isModelAvailable($model)) {
                $availableModels[] = $model;
            }
        }

        return $availableModels;
    }

    /**
     * Get model capabilities for frontend display.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getModelCapabilities(User $user): array
    {
        $models = $this->getAvailableModels($user);
        $capabilities = [];

        foreach ($models as $model) {
            $capabilities[$model] = $this->prismAI->getModelCapabilities($model);
        }

        return $capabilities;
    }

    /**
     * Cancel a generation session.
     */
    public function cancelGeneration(string $sessionId): bool
    {
        $aiGeneration = AIGeneration::where('generation_session_id', $sessionId)->first();

        if (! $aiGeneration || ! $aiGeneration->isInProgress()) {
            return false;
        }

        $aiGeneration->markAsFailed('Generation cancelled by user');

        return true;
    }

    /**
     * Generate mock metrics for testing and sequential execution.
     *
     * @param  array<int, string>  $models
     * @return array<string, array<string, mixed>>
     */
    protected function generateMockMetrics(array $models, float $totalTime): array
    {
        $metrics = [];
        $timePerModel = $totalTime / count($models);

        foreach ($models as $model) {
            $metrics[$model] = [
                'response_time_ms' => (int) ($timePerModel * (0.8 + (random_int(0, 40) / 100))), // ±20% variation
                'tokens_used' => random_int(300, 800),
                'cost_cents' => random_int(2, 15),
                'names_generated' => 5, // Default test value
                'creativity_score' => random_int(65, 95) / 10,
                'relevance_score' => random_int(70, 98) / 10,
            ];
        }

        return $metrics;
    }
}
