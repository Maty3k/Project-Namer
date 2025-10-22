<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LogoGenerationException;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class OpenAILogoService
{
    /**
     * Logo styles with their descriptions for DALL-E prompts.
     */
    protected const LOGO_STYLES = [
        'minimalist' => 'minimalist, clean, simple geometric shapes',
        'modern' => 'modern, sleek, contemporary design',
        'playful' => 'playful, fun, vibrant and colorful',
        'corporate' => 'professional, corporate, business-focused',
    ];

    /**
     * Image size for DALL-E 2 (256x256).
     */
    protected const IMAGE_SIZE = '256x256';

    /**
     * Number of logos to generate.
     */
    protected const LOGOS_COUNT = 4;

    /**
     * Generate all 4 logos for a logo generation request.
     */
    public function generateLogos(LogoGeneration $logoGeneration): void
    {
        foreach (self::LOGO_STYLES as $style => $styleDescription) {
            try {
                $this->generateSingleLogo($logoGeneration, $style, $styleDescription);
            } catch (LogoGenerationException $e) {
                Log::error("Failed to generate {$style} logo", [
                    'logo_generation_id' => $logoGeneration->id,
                    'error' => $e->getMessage(),
                ]);

                // Create a failed logo record
                GeneratedLogo::create([
                    'logo_generation_id' => $logoGeneration->id,
                    'style' => $style,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate a single logo with the specified style.
     */
    protected function generateSingleLogo(
        LogoGeneration $logoGeneration,
        string $style,
        string $styleDescription
    ): void {
        // Create the logo record
        $generatedLogo = GeneratedLogo::create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => $style,
            'status' => 'processing',
        ]);

        // Build the prompt
        $prompt = $this->buildPrompt(
            $logoGeneration->business_name,
            $logoGeneration->business_description ?? '',
            $styleDescription
        );

        // Save the prompt
        $generatedLogo->update(['prompt' => $prompt]);

        try {
            // Call DALL-E 2 API via Prism
            $imageUrl = $this->callDalleApi($prompt);

            // Download and save the image
            $filePath = $this->downloadAndSaveImage($imageUrl, $logoGeneration->id, $style);

            // Update the logo record
            $generatedLogo->update([
                'file_path' => $filePath,
                'status' => 'completed',
            ]);

            // Increment completed logos count
            $logoGeneration->incrementCompletedLogos();

            // Check if all logos are complete
            if ($logoGeneration->logos_completed >= $logoGeneration->total_logos_requested) {
                $logoGeneration->markAsCompleted();
            }
        } catch (\Exception $e) {
            $generatedLogo->markAsFailed($e->getMessage());

            throw new LogoGenerationException(
                "Failed to generate {$style} logo: {$e->getMessage()}"
            );
        }
    }

    /**
     * Build the DALL-E prompt for logo generation.
     */
    protected function buildPrompt(
        string $businessName,
        string $businessDescription,
        string $styleDescription
    ): string {
        $prompt = "Create a {$styleDescription} logo for a business called '{$businessName}'";

        if (! empty($businessDescription)) {
            $prompt .= " that {$businessDescription}";
        }

        $prompt .= '. The logo should be simple, memorable, and work well at small sizes. No text in the logo.';

        return $prompt;
    }

    /**
     * Call the DALL-E 2 API via Prism to generate an image.
     */
    protected function callDalleApi(string $prompt): string
    {
        try {
            $response = Prism::image()
                ->using(Provider::OpenAI, 'dall-e-2')
                ->withPrompt($prompt)
                ->withClientOptions([
                    'n' => 1,
                    'size' => self::IMAGE_SIZE,
                    'response_format' => 'url',
                ])
                ->generate();

            $image = $response->firstImage();

            if (! $image || ! $image->url) {
                throw new LogoGenerationException('Invalid response from DALL-E API');
            }

            return $image->url;
        } catch (\Exception $e) {
            throw new LogoGenerationException(
                'DALL-E API request failed: '.$e->getMessage()
            );
        }
    }

    /**
     * Download the image from URL and save it to storage.
     */
    protected function downloadAndSaveImage(
        string $imageUrl,
        int $logoGenerationId,
        string $style
    ): string {
        // Download the image
        $imageContent = Http::timeout(30)->get($imageUrl)->body();

        // Generate filename
        $filename = Str::slug("{$logoGenerationId}-{$style}").'-'.Str::random(8).'.png';
        $filePath = "logos/{$logoGenerationId}/{$filename}";

        // Save to public storage so it's accessible via web
        Storage::disk('public')->put($filePath, $imageContent);

        return $filePath;
    }

    /**
     * Get available logo styles.
     *
     * @return array<int, string>
     */
    public static function getAvailableStyles(): array
    {
        return array_keys(self::LOGO_STYLES);
    }
}
