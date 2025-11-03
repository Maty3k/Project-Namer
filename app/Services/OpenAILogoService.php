<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LogoGenerationException;
use App\Jobs\GenerateSingleLogoJob;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\NameSuggestion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Prism\Prism\Prism;

class OpenAILogoService
{
    /**
     * Available logo styles.
     */
    protected const LOGO_STYLES = ['minimalist', 'modern', 'playful', 'corporate'];

    /**
     * Number of logos to generate.
     */
    protected const LOGOS_COUNT = 4;

    public function __construct(
        protected PromptLoaderService $promptLoader,
        protected ColorPaletteService $colorPaletteService
    ) {}

    /**
     * Generate all 4 logos for a logo generation request by dispatching parallel jobs.
     */
    public function generateLogos(LogoGeneration $logoGeneration): void
    {
        Log::info('Dispatching parallel logo generation jobs', [
            'logo_generation_id' => $logoGeneration->id,
            'styles' => self::LOGO_STYLES,
        ]);

        // Dispatch all 4 logo generation jobs in parallel
        foreach (self::LOGO_STYLES as $style) {
            GenerateSingleLogoJob::dispatch($logoGeneration, $style);
        }

        Log::info('All logo generation jobs dispatched', [
            'logo_generation_id' => $logoGeneration->id,
            'job_count' => count(self::LOGO_STYLES),
        ]);
    }

    /**
     * Generate a single logo with the specified style (public method for GenerateSingleLogoJob).
     */
    public function generateSingleLogoPublic(
        LogoGeneration $logoGeneration,
        string $style
    ): void {
        // Load prompt config from markdown
        $promptData = $this->promptLoader->loadWithCache("logo-generation-{$style}");

        // Create the logo record
        $generatedLogo = GeneratedLogo::create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => $style,
            'status' => 'processing',
        ]);

        // Get project ID from NameSuggestion
        $nameSuggestion = NameSuggestion::where('name', $logoGeneration->business_name)->first();
        $projectId = $nameSuggestion?->project_id;

        // Build the prompt with color palette if available
        $prompt = $this->buildPrompt(
            $logoGeneration->business_name,
            $logoGeneration->business_description ?? '',
            $promptData,
            $projectId
        );

        // Save the prompt
        $generatedLogo->update(['prompt' => $prompt]);

        try {
            // Call DALL-E 2 API via Prism
            $imageUrl = $this->callDalleApi($prompt, $promptData);

            // Download and save the image
            $filePath = $this->downloadAndSaveImage($imageUrl, $logoGeneration->id, $style);

            // Update the logo record
            $generatedLogo->update([
                'file_path' => $filePath,
                'status' => 'completed',
            ]);

            // Increment completed logos count
            $logoGeneration->incrementCompletedLogos();

            // Refresh the model to get the updated count (important for parallel processing)
            $logoGeneration->refresh();

            // Update NameSuggestion with completed logos after EACH logo
            // This enables real-time one-by-one logo display in the UI
            $this->updateNameSuggestionWithLogos($logoGeneration);

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
     * Build the DALL-E prompt for logo generation using markdown template.
     */
    protected function buildPrompt(
        string $businessName,
        string $businessDescription,
        PromptData $promptData,
        ?int $projectId = null
    ): string {
        // Build business description clause
        $businessDescriptionClause = ! empty($businessDescription)
            ? " that {$businessDescription}"
            : '';

        // Get color palette from project images if available
        $colorPalette = '';
        if ($projectId) {
            $colors = $this->colorPaletteService->getColorPaletteFromImages($projectId);
            if ($colors) {
                $colorPalette = "\n\n{$colors}";
            }
        }

        // Interpolate variables in the prompt template
        $basePrompt = $this->promptLoader->interpolate($promptData->promptText, [
            'businessName' => $businessName,
            'styleDescription' => $promptData->metadata['style_description'] ?? '',
            'businessDescriptionClause' => $businessDescriptionClause,
        ]);

        // Append color palette instruction at the end for emphasis
        return $basePrompt.$colorPalette;
    }

    /**
     * Call the image generation API via Prism (supports DALL-E 2/3 and gpt-image-1).
     */
    protected function callDalleApi(string $prompt, PromptData $promptData): string
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $clientOptions = [
                    'n' => $promptData->metadata['n'] ?? 1,
                    'size' => $promptData->metadata['size'] ?? '1024x1024',
                    'response_format' => $promptData->metadata['response_format'] ?? 'b64_json',
                    'timeout' => 60, // 60 second timeout for API call
                    'connect_timeout' => 30, // 30 second connection timeout
                ];

                // Add quality parameter if specified (for gpt-image-1 and DALL-E 3)
                if (isset($promptData->metadata['quality'])) {
                    $clientOptions['quality'] = $promptData->metadata['quality'];
                }

                $response = Prism::image()
                    ->using($promptData->provider, $promptData->model)
                    ->withPrompt($prompt)
                    ->withClientOptions($clientOptions)
                    ->generate();

                $image = $response->firstImage();

                if (! $image) {
                    throw new LogoGenerationException('Invalid response from image generation API');
                }

                // Handle both URL and base64 responses
                if ($image->url) {
                    return $image->url;
                }

                if ($image->base64) {
                    // Save base64 data directly and return file path
                    return $this->saveBase64Image($image->base64, uniqid('logo_', true));
                }

                throw new LogoGenerationException('No image URL or base64 data in API response');
            } catch (\Exception $e) {
                $attempt++;

                // If this was the last attempt, throw the exception
                if ($attempt >= $maxRetries) {
                    throw new LogoGenerationException(
                        'DALL-E API request failed: '.$e->getMessage()
                    );
                }

                // Log retry attempt
                Log::warning("DALL-E API call failed, retrying (attempt {$attempt}/{$maxRetries})", [
                    'error' => $e->getMessage(),
                ]);

                // Exponential backoff: 2s, 4s, 8s (longer delays for SSL issues)
                $sleepTime = pow(2, $attempt);
                sleep($sleepTime);
            }
        }

        // This should never be reached, but satisfies type checking
        throw new LogoGenerationException('Failed to generate logo after retries');
    }

    /**
     * Save base64 encoded image data to storage.
     */
    protected function saveBase64Image(string $base64Data, string $uniqueId): string
    {
        // Decode base64 data
        $imageContent = base64_decode($base64Data);

        // Generate temporary file path that will be used by downloadAndSaveImage
        $filename = $uniqueId.'.png';
        $tempPath = "temp/{$filename}";

        // Save to temporary location
        Storage::disk('public')->put($tempPath, $imageContent);

        // Return full path
        return Storage::disk('public')->path($tempPath);
    }

    /**
     * Download the image from URL and save it to storage.
     * Also handles local file paths from saveBase64Image.
     */
    protected function downloadAndSaveImage(
        string $imageUrlOrPath,
        int $logoGenerationId,
        string $style
    ): string {
        // Check if this is a local file path (from base64)
        if (file_exists($imageUrlOrPath)) {
            $imageContent = file_get_contents($imageUrlOrPath);
            // Clean up temp file
            unlink($imageUrlOrPath);
        } else {
            // Download the image from URL
            $imageContent = Http::timeout(30)->get($imageUrlOrPath)->body();
        }

        // Generate filename
        $filename = Str::slug("{$logoGenerationId}-{$style}").'-'.Str::random(8).'.png';
        $filePath = "logos/{$logoGenerationId}/{$filename}";

        // Save to public storage so it's accessible via web
        Storage::disk('public')->put($filePath, $imageContent);

        return $filePath;
    }

    /**
     * Update NameSuggestion with completed logos.
     */
    protected function updateNameSuggestionWithLogos(LogoGeneration $logoGeneration): void
    {
        // Find NameSuggestion by matching the business name
        $nameSuggestion = NameSuggestion::where('name', $logoGeneration->business_name)->first();

        if (! $nameSuggestion) {
            Log::warning('No NameSuggestion found for business name', [
                'business_name' => $logoGeneration->business_name,
                'logo_generation_id' => $logoGeneration->id,
            ]);

            return;
        }

        // Get all completed logos for this generation
        $completedLogos = $logoGeneration->generatedLogos()
            ->where('status', 'completed')
            ->get();

        // Build logos array for NameSuggestion
        $logosData = $completedLogos->map(fn (GeneratedLogo $logo) => [
            'style' => $logo->style,
            'url' => $logo->url,
        ])->all();

        // Update NameSuggestion with logos data
        $nameSuggestion->update(['logos' => $logosData]);

        Log::info('Updated NameSuggestion with logos', [
            'name_suggestion_id' => $nameSuggestion->id,
            'business_name' => $logoGeneration->business_name,
            'logos_count' => count($logosData),
        ]);

        // Note: UI updates are handled via Livewire polling (wire:poll)
        // in the NameResultCard component, not via events
    }

    /**
     * Get available logo styles.
     *
     * @return array<int, string>
     */
    public static function getAvailableStyles(): array
    {
        return self::LOGO_STYLES;
    }
}
