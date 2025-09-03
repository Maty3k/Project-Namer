<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ProjectImage;
use App\Services\ImageProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUploadedImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(public ProjectImage $image)
    {
        $this->queue = 'image-processing';
    }

    /**
     * Execute the job.
     */
    public function handle(ImageProcessingService $processingService): void
    {
        Log::info('Starting image processing', ['image_id' => $this->image->id]);

        // Update status to processing
        $this->image->update(['processing_status' => 'processing']);

        // Process the image
        $success = $processingService->processImage($this->image);

        if ($success) {
            Log::info('Image processing completed successfully', ['image_id' => $this->image->id]);
        } else {
            Log::error('Image processing failed', ['image_id' => $this->image->id]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image processing job failed permanently', [
            'image_id' => $this->image->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark the image as failed
        $this->image->update(['processing_status' => 'failed']);
    }
}
