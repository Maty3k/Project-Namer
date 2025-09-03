<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ProjectImage;
use App\Services\VisionAnalysisService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeImageWithAIJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute AI vision analysis on uploaded project images.
     */
    public function __construct(
        public ProjectImage $image
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->image->ai_analysis !== null) {
            return;
        }

        try {
            $visionService = app(VisionAnalysisService::class);
            $visionService->analyzeImageWithContext($this->image);
        } catch (Exception $e) {
            Log::warning('Image analysis failed for job', [
                'image_id' => $this->image->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
