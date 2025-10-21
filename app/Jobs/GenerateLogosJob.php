<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LogoGeneration;
use App\Services\OpenAILogoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateLogosJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LogoGeneration $logoGeneration
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenAILogoService $logoService): void
    {
        try {
            // Mark as processing
            $this->logoGeneration->markAsProcessing();

            // Generate all 4 logos
            $logoService->generateLogos($this->logoGeneration);

            Log::info('Logo generation completed', [
                'logo_generation_id' => $this->logoGeneration->id,
                'logos_completed' => $this->logoGeneration->logos_completed,
            ]);
        } catch (\Exception $e) {
            Log::error('Logo generation job failed', [
                'logo_generation_id' => $this->logoGeneration->id,
                'error' => $e->getMessage(),
            ]);

            // Mark the generation as failed
            $this->logoGeneration->markAsFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Logo generation job failed permanently', [
            'logo_generation_id' => $this->logoGeneration->id,
            'error' => $exception->getMessage(),
        ]);

        $this->logoGeneration->markAsFailed($exception->getMessage());
    }
}
