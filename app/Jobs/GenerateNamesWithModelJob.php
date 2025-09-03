<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AIGeneration;
use App\Services\AI\PrismAIService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job for generating names with a specific AI model in parallel.
 */
class GenerateNamesWithModelJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 120; // 2 minutes timeout

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public AIGeneration $aiGeneration,
        public string $modelId,
        public string $prompt,
        public array $parameters = []
    ) {
        $this->onQueue('ai-generation');
    }

    /**
     * Execute the job.
     */
    public function handle(PrismAIService $prismAI): void
    {
        $startTime = microtime(true);
        $cacheKey = "ai_generation_result_{$this->aiGeneration->id}_{$this->modelId}";

        // Check if generation was cancelled before starting
        $existingCache = Cache::get($cacheKey);
        if ($existingCache && $existingCache['status'] === 'cancelled') {
            Log::info('Skipping cancelled AI generation job', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
            ]);

            return;
        }

        try {
            Log::info('Starting parallel AI generation for model', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'session' => $this->aiGeneration->generation_session_id,
            ]);

            // Update model status to running
            $this->updateModelStatus('running');

            // Check if model is available
            if (! $prismAI->isModelAvailable($this->modelId)) {
                throw new Exception("AI model {$this->modelId} is not available");
            }

            // Optimize prompt for this specific model
            $optimizedPrompt = $prismAI->optimizePrompt(
                $this->modelId,
                $this->prompt,
                $this->parameters['mode'] ?? 'creative',
                $this->parameters['deep_thinking'] ?? false
            );

            // Generate names
            $results = $prismAI->generateNames($this->modelId, $optimizedPrompt, $this->parameters);

            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // milliseconds

            // Check if generation was cancelled during execution
            $currentCache = Cache::get($cacheKey);
            if ($currentCache && $currentCache['status'] === 'cancelled') {
                Log::info('Generation was cancelled during execution', [
                    'model' => $this->modelId,
                    'generation_id' => $this->aiGeneration->id,
                ]);

                return;
            }

            // Cache the results for the coordinator to collect
            $resultData = [
                'model_id' => $this->modelId,
                'results' => $results,
                'execution_time_ms' => $executionTime,
                'names_generated' => count($results),
                'status' => 'completed',
                'completed_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $resultData, 600); // Cache for 10 minutes

            // Update model status to completed
            $this->updateModelStatus('completed', [
                'execution_time_ms' => $executionTime,
                'names_generated' => count($results),
            ]);

            Log::info('Parallel AI generation completed for model', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'names_generated' => count($results),
                'execution_time_ms' => $executionTime,
            ]);

        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            Log::error('Parallel AI generation failed for model', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ]);

            // Cache the error result
            $errorData = [
                'model_id' => $this->modelId,
                'results' => [],
                'execution_time_ms' => $executionTime,
                'names_generated' => 0,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $errorData, 600);

            // Update model status to failed
            $this->updateModelStatus('failed', [
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
            ]);

            // Don't throw the exception to prevent job retry for certain errors
            if ($this->shouldRetry($e)) {
                throw $e;
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('AI generation job failed permanently', [
            'model' => $this->modelId,
            'generation_id' => $this->aiGeneration->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Update model status to failed
        $this->updateModelStatus('failed', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Cache the permanent failure
        $cacheKey = "ai_generation_result_{$this->aiGeneration->id}_{$this->modelId}";
        Cache::put($cacheKey, [
            'model_id' => $this->modelId,
            'results' => [],
            'execution_time_ms' => 0,
            'names_generated' => 0,
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'failed_at' => now()->toISOString(),
        ], 600);
    }

    /**
     * Update model status in generation metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function updateModelStatus(string $status, array $metadata = []): void
    {
        try {
            // Refresh the model to get latest data
            $this->aiGeneration->refresh();

            $currentMetadata = $this->aiGeneration->execution_metadata ?? [];
            $currentMetadata['model_status'][$this->modelId] = $status;

            if (! empty($metadata)) {
                $currentMetadata['model_metrics'][$this->modelId] = array_merge(
                    $currentMetadata['model_metrics'][$this->modelId] ?? [],
                    $metadata
                );
            }

            $this->aiGeneration->update(['execution_metadata' => $currentMetadata]);
        } catch (Exception $e) {
            Log::warning('Failed to update model status', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine if the job should retry based on the exception.
     */
    protected function shouldRetry(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());

        // Don't retry for these permanent errors
        $permanentErrors = [
            'invalid api key',
            'insufficient quota',
            'model not found',
            'unauthorized',
            'forbidden',
        ];

        foreach ($permanentErrors as $error) {
            if (str_contains($message, $error)) {
                return false;
            }
        }

        // Retry for network errors, rate limits, etc.
        return true;
    }
}
