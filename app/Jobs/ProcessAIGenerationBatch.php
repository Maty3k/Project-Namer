<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GenerationSession;
use App\Models\NameSuggestion;
use App\Services\AI\CachingService;
use App\Services\PrismAIService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process AI generation requests in batches for better performance.
 *
 * This job handles multiple AI generation requests efficiently,
 * with caching, rate limiting, and error handling.
 */
final class ProcessAIGenerationBatch implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300; // 5 minutes

    public int $maxExceptions = 3;

    public int $backoff = 30; // 30 seconds between retries

    /**
     * Create a new job instance.
     *
     * @param  array<string>  $generationSessionIds
     */
    public function __construct(
        private array $generationSessionIds,
        private string $priority = 'normal'
    ) {
        $this->queue = match ($priority) {
            'high' => 'ai-high',
            'low' => 'ai-low',
            default => 'ai-normal'
        };
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<\Illuminate\Queue\Middleware\WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('ai-generation-batch'),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        PrismAIService $prismService,
        CachingService $cachingService
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('Processing AI generation batch', [
            'session_count' => count($this->generationSessionIds),
            'priority' => $this->priority,
            'batch_id' => $this->batch()?->id,
        ]);

        $processed = 0;
        $errors = 0;

        foreach ($this->generationSessionIds as $sessionId) {
            if ($this->batch()?->cancelled()) {
                break;
            }

            try {
                $this->processSession($sessionId, $prismService, $cachingService);
                $processed++;
            } catch (Exception $e) {
                $errors++;
                Log::error('Failed to process AI generation session', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->markSessionFailed($sessionId, $e->getMessage());
            }
        }

        Log::info('AI generation batch completed', [
            'processed' => $processed,
            'errors' => $errors,
            'batch_id' => $this->batch()?->id,
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('AI generation batch job failed', [
            'session_ids' => $this->generationSessionIds,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark all sessions as failed
        foreach ($this->generationSessionIds as $sessionId) {
            $this->markSessionFailed($sessionId, 'Batch job failed: '.$exception->getMessage());
        }
    }

    /**
     * Process a single generation session.
     */
    private function processSession(
        string $sessionId,
        PrismAIService $prismService,
        CachingService $cachingService
    ): void {
        $session = GenerationSession::where('session_id', $sessionId)->first();

        if (! $session) {
            throw new Exception("Generation session not found: {$sessionId}");
        }

        if ($session->status !== 'pending') {
            Log::warning('Skipping session with non-pending status', [
                'session_id' => $sessionId,
                'status' => $session->status,
            ]);

            return;
        }

        // Mark session as processing
        $session->update([
            'status' => 'processing',
            'started_at' => now(),
            'current_step' => 'Initializing AI models',
            'progress_percentage' => 10,
        ]);

        // Check cache first
        $cachedResults = $cachingService->getCachedGenerationResult(
            $session->business_description,
            $session->generation_mode,
            $session->requested_models,
            $session->deep_thinking
        );

        if ($cachedResults) {
            Log::info('Using cached results for generation session', [
                'session_id' => $sessionId,
            ]);

            $this->processCachedResults($session, $cachedResults);

            return;
        }

        // Process with AI
        $this->processWithAI($session, $prismService, $cachingService);
    }

    /**
     * Process session with cached results.
     *
     * @param  array<string, array<string>>  $cachedResults
     */
    private function processCachedResults(GenerationSession $session, array $cachedResults): void
    {
        $session->update([
            'current_step' => 'Using cached results',
            'progress_percentage' => 80,
        ]);

        $this->createNameSuggestions($session, $cachedResults);

        $session->markAsCompleted($cachedResults, [
            'source' => 'cache',
            'cached_at' => now(),
        ]);
    }

    /**
     * Process session with AI generation.
     */
    private function processWithAI(
        GenerationSession $session,
        PrismAIService $prismService,
        CachingService $cachingService
    ): void {
        $results = [];
        $totalModels = count($session->requested_models);
        $processedModels = 0;

        foreach ($session->requested_models as $model) {
            try {
                $session->update([
                    'current_step' => "Processing with {$model}",
                    'progress_percentage' => 20 + (($processedModels / $totalModels) * 60),
                ]);

                // Check individual model cache
                $modelResults = $cachingService->getCachedAPIResponse(
                    $model,
                    $session->business_description,
                    $session->generation_mode,
                    $session->deep_thinking
                );

                if (! $modelResults) {
                    // Generate with AI
                    $modelResults = $prismService->generateNames(
                        $session->business_description,
                        [$model],
                        $session->generation_mode,
                        $session->deep_thinking
                    );

                    // Cache the results
                    if (isset($modelResults[$model])) {
                        $cachingService->cacheAPIResponse(
                            $model,
                            $session->business_description,
                            $session->generation_mode,
                            $session->deep_thinking,
                            $modelResults[$model]
                        );
                    }
                }

                if (isset($modelResults[$model])) {
                    $results[$model] = $modelResults[$model];
                } else {
                    Log::warning('No results from model', [
                        'model' => $model,
                        'session_id' => $session->session_id,
                    ]);
                }

                $processedModels++;

            } catch (Exception $e) {
                Log::error('Model processing failed', [
                    'model' => $model,
                    'session_id' => $session->session_id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with other models
                $processedModels++;
            }
        }

        if (empty($results)) {
            throw new Exception('No results generated from any model');
        }

        // Cache the combined results
        $cachingService->cacheGenerationResult(
            $session->business_description,
            $session->generation_mode,
            $session->requested_models,
            $session->deep_thinking,
            $results
        );

        $session->update([
            'current_step' => 'Creating name suggestions',
            'progress_percentage' => 90,
        ]);

        $this->createNameSuggestions($session, $results);

        $session->markAsCompleted($results, [
            'source' => 'ai_generation',
            'models_processed' => array_keys($results),
            'total_names' => array_sum(array_map('count', $results)),
        ]);
    }

    /**
     * Create name suggestions from results.
     *
     * @param  array<string, array<string>>  $results
     */
    private function createNameSuggestions(GenerationSession $session, array $results): void
    {
        foreach ($results as $model => $names) {
            foreach ($names as $name) {
                NameSuggestion::create([
                    'project_id' => $session->project_id,
                    'name' => $name,
                    'domains' => $this->generateDomainPlaceholders($name),
                    'generation_metadata' => [
                        'ai_model' => $model,
                        'generation_mode' => $session->generation_mode,
                        'deep_thinking' => $session->deep_thinking,
                        'generated_at' => now()->toISOString(),
                    ],
                ]);
            }
        }
    }

    /**
     * Generate domain placeholders for domain checking.
     *
     * @return array<array<string, mixed>>
     */
    private function generateDomainPlaceholders(string $name): array
    {
        $extensions = ['.com', '.io', '.co', '.net'];
        $domains = [];

        foreach ($extensions as $ext) {
            $domains[] = [
                'extension' => $ext,
                'available' => null, // Will be checked later
                'last_checked' => null,
            ];
        }

        return $domains;
    }

    /**
     * Mark session as failed.
     */
    private function markSessionFailed(string $sessionId, string $error): void
    {
        $session = GenerationSession::where('session_id', $sessionId)->first();

        if ($session) {
            $session->update([
                'status' => 'failed',
                'error_message' => $error,
                'failed_at' => now(),
            ]);
        }
    }
}
