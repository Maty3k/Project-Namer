<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LogoGenerationException;
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
     * Generate all 4 logos for a logo generation request.
     */
    public function generateLogos(LogoGeneration $logoGeneration): void
    {
        $styleIndex = 0;
        foreach (self::LOGO_STYLES as $style) {
            // Check if a logo with this style already exists for this generation
            $existingLogo = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
                ->where('style', $style)
                ->first();

            if ($existingLogo) {
                Log::info("Skipping {$style} logo - already exists", [
                    'logo_generation_id' => $logoGeneration->id,
                    'style' => $style,
                ]);

                continue;
            }

            // Add delay between API calls to prevent SSL connection issues
            // Skip delay for first logo
            if ($styleIndex > 0) {
                sleep(5); // 5 second delay between logos to prevent SSL issues
            }

            try {
                $this->generateSingleLogo($logoGeneration, $style);
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

            $styleIndex++;
        }
    }

    /**
     * Generate a single logo with the specified style.
     */
    protected function generateSingleLogo(
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

            // Check if all logos are complete
            if ($logoGeneration->logos_completed >= $logoGeneration->total_logos_requested) {
                $logoGeneration->markAsCompleted();

                // Update NameSuggestion with completed logos
                $this->updateNameSuggestionWithLogos($logoGeneration);
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
     * Call the DALL-E 2 API via Prism to generate an image using markdown config.
     */
    protected function callDalleApi(string $prompt, PromptData $promptData): string
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = Prism::image()
                    ->using($promptData->provider, $promptData->model)
                    ->withPrompt($prompt)
                    ->withClientOptions([
                        'n' => $promptData->metadata['n'] ?? 1,
                        'size' => $promptData->metadata['size'] ?? '256x256',
                        'response_format' => $promptData->metadata['response_format'] ?? 'url',
                    ])
                    ->generate();

                $image = $response->firstImage();

                if (! $image || ! $image->url) {
                    throw new LogoGenerationException('Invalid response from DALL-E API');
                }

                return $image->url;
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
