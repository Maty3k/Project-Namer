<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Services\OpenAILogoService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generate Logos Job.
 *
 * Background job for generating multiple logo variations using AI,
 * downloading images, storing them locally, and tracking progress.
 */
final class GenerateLogosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * Logo styles to generate.
     *
     * @var array<string>
     */
    private const LOGO_STYLES = ['minimalist', 'modern', 'playful', 'corporate'];

    /**
     * Number of variations per style.
     */
    private const VARIATIONS_PER_STYLE = 3;

    /**
     * Create a new job instance.
     *
     * @param  LogoGeneration<\Database\Factories\LogoGenerationFactory>  $logoGeneration
     */
    public function __construct(
        public LogoGeneration $logoGeneration
    ) {
        $this->onQueue('logo-generation');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Debug removed for now

        Log::info('Starting logo generation job', [
            'generation_id' => $this->logoGeneration->id,
            'business_name' => $this->logoGeneration->business_name,
        ]);

        try {
            $this->updateStatus('processing');

            $openAIService = app(OpenAILogoService::class);
            $totalLogos = 0;
            $totalCost = 0;
            $successful = 0;

            foreach (self::LOGO_STYLES as $style) {
                for ($variation = 1; $variation <= self::VARIATIONS_PER_STYLE; $variation++) {
                    try {
                        $result = $this->generateSingleLogo($openAIService, $style, $variation);

                        if ($result['success']) {
                            $successful++;
                            $totalCost += $result['cost_cents'];
                        }

                        $totalLogos++;
                        $this->updateProgress($successful);

                    } catch (Exception $e) {
                        Log::warning('Failed to generate logo', [
                            'generation_id' => $this->logoGeneration->id,
                            'style' => $style,
                            'variation' => $variation,
                            'error' => $e->getMessage(),
                        ]);

                        $totalLogos++;
                        // Continue processing other logos
                    }
                }
            }

            // Update final status
            $this->logoGeneration->update([
                'cost_cents' => $totalCost,
                'logos_completed' => $successful,
                'status' => $successful > 0 ? 'completed' : 'failed',
            ]);

            if ($successful === 0) {
                $this->cleanupFiles();
            }

            Log::info('Logo generation job completed', [
                'generation_id' => $this->logoGeneration->id,
                'successful_logos' => $successful,
                'total_cost_cents' => $totalCost,
            ]);

        } catch (Exception $e) {
            $this->handleJobFailure($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Logo generation job failed', [
            'generation_id' => $this->logoGeneration->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->updateStatus('failed');
        $this->cleanupFiles();
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'logo-generation',
            "generation-{$this->logoGeneration->id}",
        ];
    }

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return "Generate Logos for Generation #{$this->logoGeneration->id}";
    }

    /**
     * The job's backoff strategy.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1m, 2m
    }

    /**
     * Generate a single logo with specified style and variation.
     *
     * @return array<string, mixed>
     */
    private function generateSingleLogo(OpenAILogoService $service, string $style, int $variation): array
    {
        // Debug removed

        // Generate logo using OpenAI
        $apiResult = $service->generateLogo($this->logoGeneration->business_name, $style);

        if (! $apiResult['success']) {
            return $apiResult;
        }

        // Create database record
        $generatedLogo = $this->createLogoRecord($apiResult, $style, $variation);

        // Download and store the image
        $downloadResult = $this->downloadAndStoreImage($apiResult['image_url'], $generatedLogo);

        if ($downloadResult['success']) {
            $generatedLogo->update([
                'original_file_path' => $downloadResult['file_path'],
                'file_size' => $downloadResult['file_size'],
            ]);
        }

        return array_merge($apiResult, [
            'logo_id' => $generatedLogo->id,
            'download_success' => $downloadResult['success'],
        ]);
    }

    /**
     * Create a database record for the generated logo.
     *
     * @param  array<string, mixed>  $apiResult
     * @return GeneratedLogo<\Database\Factories\GeneratedLogoFactory>
     */
    private function createLogoRecord(array $apiResult, string $style, int $variation): GeneratedLogo
    {
        return GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => $style,
            'variation_number' => $variation,
            'prompt_used' => $apiResult['revised_prompt'] ?? 'Logo design prompt',
            'original_file_path' => '', // Will be updated after download
            'file_size' => 0, // Will be updated after download
            'generation_time_ms' => 0, // TODO: Track actual generation time
            'api_image_url' => $apiResult['image_url'],
        ]);
    }

    /**
     * Download image from URL and store locally.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $logo
     * @return array<string, mixed>
     */
    private function downloadAndStoreImage(string $imageUrl, GeneratedLogo $logo): array
    {
        try {
            $response = Http::timeout(60)->get($imageUrl);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => "Failed to download image: HTTP {$response->status()}",
                ];
            }

            $imageContent = $response->body();
            $fileSize = strlen($imageContent);

            // Generate filename and path
            $fileName = $this->generateFileName($logo);
            $filePath = "logos/{$this->logoGeneration->id}/originals/{$fileName}";

            // Store the file
            Storage::disk('public')->put($filePath, $imageContent);

            Log::info('Image downloaded and stored', [
                'logo_id' => $logo->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ];

        } catch (RequestException $e) {
            Log::warning('Failed to download image', [
                'logo_id' => $logo->id,
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a unique filename for the logo.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $logo
     */
    private function generateFileName(GeneratedLogo $logo): string
    {
        $businessName = Str::slug($this->logoGeneration->business_name, '-');
        $businessName = Str::limit($businessName, 30, '');

        return sprintf(
            '%s_%s_v%d_%s.png',
            $businessName,
            $logo->style,
            $logo->variation_number,
            Str::random(8)
        );
    }

    /**
     * Update the generation status.
     */
    private function updateStatus(string $status): void
    {
        $this->logoGeneration->update(['status' => $status]);
    }

    /**
     * Update the generation progress.
     */
    protected function updateProgress(int $completed): void
    {
        $this->logoGeneration->update(['logos_completed' => $completed]);
    }

    /**
     * Clean up files on failure.
     */
    private function cleanupFiles(): void
    {
        $logoDir = "logos/{$this->logoGeneration->id}";

        if (Storage::disk('public')->exists($logoDir)) {
            Storage::disk('public')->deleteDirectory($logoDir);

            Log::info('Cleaned up logo files', [
                'generation_id' => $this->logoGeneration->id,
                'directory' => $logoDir,
            ]);
        }
    }

    /**
     * Handle job failure with cleanup.
     */
    private function handleJobFailure(\Throwable $exception): void
    {
        $this->updateStatus('failed');
        $this->cleanupFiles();

        Log::error('Logo generation job failed with exception', [
            'generation_id' => $this->logoGeneration->id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
