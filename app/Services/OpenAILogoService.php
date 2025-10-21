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
     * DALL-E 2 API endpoint.
     */
    protected const DALLE_API_URL = 'https://api.openai.com/v1/images/generations';

    /**
     * Image size for DALL-E 2 (256x256).
     */
    protected const IMAGE_SIZE = '256x256';

    /**
     * Number of logos to generate.
     */
    protected const LOGOS_COUNT = 4;

    public function __construct(
        protected string $apiKey
    ) {}

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
            // Call DALL-E 2 API
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
     * Call the DALL-E 2 API to generate an image.
     */
    protected function callDalleApi(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post(self::DALLE_API_URL, [
            'model' => 'dall-e-2', // Explicitly using DALL-E 2
            'prompt' => $prompt,
            'n' => 1, // Generate 1 image per request
            'size' => self::IMAGE_SIZE, // 256x256
            'response_format' => 'url',
        ]);

        if (! $response->successful()) {
            throw new LogoGenerationException(
                'DALL-E API request failed: '.$response->body()
            );
        }

        $data = $response->json();

        if (! isset($data['data'][0]['url'])) {
            throw new LogoGenerationException('Invalid response from DALL-E API');
        }

        return $data['data'][0]['url'];
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

        // Save to storage
        Storage::put($filePath, $imageContent);

        return $filePath;
    }

    /**
     * Get available logo styles.
     */
    public static function getAvailableStyles(): array
    {
        return array_keys(self::LOGO_STYLES);
    }
}
