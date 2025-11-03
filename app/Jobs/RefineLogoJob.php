<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\LogoGenerationException;
use App\Models\GeneratedLogo;
use App\Services\PromptMarkdownService;
use EchoLabs\Prism\Facades\Prism;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RefineLogoJob implements ShouldQueue
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
        public GeneratedLogo $generatedLogo
    ) {
        $this->onQueue('image-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(PromptMarkdownService $promptMarkdownService): void
    {
        Log::info('Starting logo refinement', [
            'logo_id' => $this->generatedLogo->id,
            'style' => $this->generatedLogo->style,
        ]);

        try {
            // Get the prompt data for this style
            $promptData = $promptMarkdownService->getPromptData(
                "logo-generation-{$this->generatedLogo->style}"
            );

            // Update quality to high for refinement
            $promptData = $promptData->withMetadata(
                array_merge($promptData->metadata ?? [], ['quality' => 'high'])
            );

            // Build refinement prompt that references the original
            $refinementPrompt = $this->buildRefinementPrompt();

            // Call gpt-image-1 with high quality
            $response = Prism::image()
                ->using($promptData->provider, $promptData->model)
                ->withPrompt($refinementPrompt)
                ->withClientOptions([
                    'n' => 1,
                    'size' => '1024x1024',
                    'quality' => 'high',
                    'response_format' => 'b64_json',
                    'timeout' => 120,
                    'connect_timeout' => 60,
                ])
                ->generate();

            $image = $response->firstImage();

            if (! $image || ! $image->data) {
                throw new LogoGenerationException('No image data in refinement response');
            }

            // Save the refined logo
            $refinedFilePath = $this->saveRefinedLogo($image->data);

            // Update the logo record
            $this->generatedLogo->markAsRefined($refinedFilePath);

            Log::info('Logo refinement completed', [
                'logo_id' => $this->generatedLogo->id,
                'refined_path' => $refinedFilePath,
            ]);
        } catch (\Exception $e) {
            Log::error('Logo refinement failed', [
                'logo_id' => $this->generatedLogo->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build refinement prompt that enhances the original.
     */
    protected function buildRefinementPrompt(): string
    {
        $businessName = $this->generatedLogo->logoGeneration->business_name;
        $businessDescription = $this->generatedLogo->logoGeneration->business_description;
        $businessDescriptionClause = ! empty($businessDescription)
            ? " that {$businessDescription}"
            : '';

        // Get the base prompt from the original
        $basePrompt = str_replace(
            ['{$businessName}', '{$businessDescriptionClause}'],
            [$businessName, $businessDescriptionClause],
            $this->generatedLogo->prompt ?? ''
        );

        // Add refinement instructions
        $refinement = "\n\nREFINEMENT REQUIREMENTS:\n";
        $refinement .= "- This is a HIGH QUALITY refinement of a draft logo\n";
        $refinement .= "- Text must be EXCEPTIONALLY clear, sharp, and perfectly readable\n";
        $refinement .= "- Perfect proportions and professional layout\n";
        $refinement .= "- Maximum attention to detail and polish\n";
        $refinement .= "- Typography must be crisp with proper kerning\n";
        $refinement .= "- Colors should be vibrant and well-balanced\n";
        $refinement .= "- This must look like a professional designer created it\n";

        return $basePrompt.$refinement;
    }

    /**
     * Save the refined logo to storage.
     */
    protected function saveRefinedLogo(string $base64Data): string
    {
        // Decode base64 data
        $imageContent = base64_decode($base64Data);

        // Generate filename
        $filename = Str::slug(
            "{$this->generatedLogo->logoGeneration->id}-{$this->generatedLogo->style}-refined"
        ).'-'.Str::random(8).'.png';

        $filePath = "logos/{$this->generatedLogo->logoGeneration->id}/{$filename}";

        // Save to public storage
        Storage::disk('public')->put($filePath, $imageContent);

        return $filePath;
    }
}
