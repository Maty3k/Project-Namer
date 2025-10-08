<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AIGeneration;
use App\Services\PromptBuilder;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

/**
 * Job for generating names with a specific AI model in parallel.
 */
class GenerateNamesWithModelJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 120; // 2 minutes timeout

    private const MODEL_CONFIGS = [
        'gpt-4' => [
            'provider' => Provider::OpenAI,
            'model' => 'gpt-4o',
        ],
        'claude-3.5-sonnet' => [
            'provider' => Provider::Anthropic,
            'model' => 'claude-3-5-sonnet-20241022',
        ],
        'gemini-1.5-pro' => [
            'provider' => Provider::Gemini,
            'model' => 'gemini-1.5-pro',
        ],
        'grok-beta' => [
            'provider' => Provider::XAI,
            'model' => 'grok-beta',
        ],
    ];

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
    public function handle(PromptBuilder $promptBuilder): void
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

            // Progress: 0% - Job started
            $this->updateProgress(0);

            // Update model status to running
            $this->updateModelStatus('running');

            // Check if model configuration exists
            if (! isset(self::MODEL_CONFIGS[$this->modelId])) {
                throw new Exception("AI model {$this->modelId} is not configured");
            }

            $config = self::MODEL_CONFIGS[$this->modelId];
            $mode = $this->parameters['mode'] ?? 'creative';
            $deepThinking = $this->parameters['deep_thinking'] ?? false;
            $count = $this->parameters['count'] ?? 10;

            // Build prompts
            $prompts = $promptBuilder->build($this->prompt, $this->modelId, $count, $mode, $deepThinking);

            // Progress: 25% - Prompts built, API request starting
            $this->updateProgress(25);

            // Generate names using Prism directly
            $response = Prism::text()
                ->using($config['provider'], $config['model'])
                ->withSystemPrompt($prompts['system'])
                ->withPrompt($prompts['user'])
                ->withClientOptions([
                    'max_tokens' => 200,
                    'temperature' => $deepThinking ? 0.3 : 0.7,
                ])
                ->asText();

            // Progress: 50% - API response received
            $this->updateProgress(50);

            // Parse results
            $results = $this->parseResponse($response->text, $count);

            // Progress: 75% - Results parsed
            $this->updateProgress(75);

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

            // Progress: 100% - Completed
            $this->updateProgress(100);

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

    /**
     * Update progress percentage and dispatch Livewire event.
     */
    protected function updateProgress(int $progress): void
    {
        try {
            // Refresh the model to get latest data
            $this->aiGeneration->refresh();

            // Update progress in database
            $this->aiGeneration->update(['progress' => $progress]);

            // Dispatch Livewire event for real-time UI updates
            event(new \Illuminate\Broadcasting\BroadcastEvent('ai-generation-progress', [
                'generation_id' => $this->aiGeneration->id,
                'progress' => $progress,
            ]));

            Log::debug('Progress updated', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'progress' => $progress,
            ]);
        } catch (Exception $e) {
            // Don't fail the job if progress update fails
            Log::warning('Failed to update progress', [
                'model' => $this->modelId,
                'generation_id' => $this->aiGeneration->id,
                'progress' => $progress,
                'error' => $e->getMessage(),
            ]);
        }
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
}
