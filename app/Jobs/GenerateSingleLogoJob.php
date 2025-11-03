<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\LogoGenerationException;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Services\OpenAILogoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSingleLogoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public LogoGeneration $logoGeneration,
        public string $style
    ) {
        $this->onQueue('image-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAILogoService $logoService): void
    {
        Log::info('Starting single logo generation', [
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => $this->style,
        ]);

        try {
            // Check if logo already exists and is completed/failed
            $existingLogo = GeneratedLogo::where('logo_generation_id', $this->logoGeneration->id)
                ->where('style', $this->style)
                ->whereIn('status', ['completed', 'failed'])
                ->first();

            if ($existingLogo) {
                Log::info("Logo already exists with status: {$existingLogo->status}", [
                    'logo_generation_id' => $this->logoGeneration->id,
                    'style' => $this->style,
                ]);

                return;
            }

            // Delete any stuck processing logos
            $stuckLogo = GeneratedLogo::where('logo_generation_id', $this->logoGeneration->id)
                ->where('style', $this->style)
                ->where('status', 'processing')
                ->first();

            if ($stuckLogo) {
                Log::warning("Deleting stuck {$this->style} logo", [
                    'logo_generation_id' => $this->logoGeneration->id,
                    'logo_id' => $stuckLogo->id,
                ]);
                $stuckLogo->delete();
            }

            // Generate the logo using the service
            $logoService->generateSingleLogoPublic(
                $this->logoGeneration,
                $this->style
            );

            Log::info('Single logo generation completed', [
                'logo_generation_id' => $this->logoGeneration->id,
                'style' => $this->style,
            ]);
        } catch (LogoGenerationException $e) {
            Log::error("Failed to generate {$this->style} logo", [
                'logo_generation_id' => $this->logoGeneration->id,
                'error' => $e->getMessage(),
            ]);

            // Create failed logo record
            GeneratedLogo::create([
                'logo_generation_id' => $this->logoGeneration->id,
                'style' => $this->style,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
