<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\PromptData;
use App\Models\ProjectImage;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Media\Image;

class VisionAnalysisService
{
    public function __construct(
        protected PromptLoaderService $promptLoader
    ) {}
    /**
     * Analyze image using OpenAI Vision API via Prism with markdown config.
     *
     * @return array<string, mixed>
     */
    public function analyzeImage(ProjectImage $image): array
    {
        $cacheKey = "vision_analysis:{$image->uuid}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Validate image file exists
        if (! Storage::disk('public')->exists($image->file_path)) {
            throw new Exception('Image file not found');
        }

        // Load prompt configuration from markdown
        $promptData = $this->promptLoader->loadWithCache('vision-analysis');

        $imagePath = Storage::disk('public')->path($image->file_path);

        try {
            $response = Prism::text()
                ->using($promptData->provider, $promptData->model)
                ->withPrompt(
                    $promptData->promptText,
                    [Image::fromLocalPath($imagePath)]
                )
                ->withClientOptions([
                    'max_tokens' => $promptData->maxTokens ?? 500,
                    'temperature' => $promptData->temperature ?? 0.3,
                ])
                ->asText();

            $content = $response->text;

            $analysis = json_decode($content, true);
            if (! $analysis) {
                throw new Exception('Failed to parse vision analysis response');
            }

            // Cache the result for 1 hour
            Cache::put($cacheKey, $analysis, 3600);

            return $analysis;

        } catch (Exception $e) {
            Log::error('Vision analysis failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Analyze image and store results in the model.
     *
     * @return array<string, mixed>
     */
    public function analyzeImageWithContext(ProjectImage $image): array
    {
        $analysis = $this->analyzeImage($image);

        // Store analysis in the image model
        $image->update(['ai_analysis' => $analysis]);

        return $analysis;
    }

    /**
     * Get formatted image context for AI name generation.
     *
     * @param  array<ProjectImage>  $images
     */
    public function getImageContextForGeneration(array $images): string
    {
        if (empty($images)) {
            return '';
        }

        $contexts = [];
        foreach ($images as $image) {
            if ($image->ai_analysis) {
                $analysis = $image->ai_analysis;

                $context = 'Image: '.($analysis['description'] ?? 'No description')."\n";
                $context .= 'Mood: '.($analysis['mood'] ?? 'Unknown')."\n";
                $context .= 'Style: '.($analysis['style'] ?? 'Unknown')."\n";
                $context .= 'Business Relevance: '.($analysis['business_relevance'] ?? 'Unknown')."\n";

                if (! empty($analysis['colors'])) {
                    $context .= 'Colors: '.implode(', ', $analysis['colors'])."\n";
                }

                if (! empty($analysis['objects'])) {
                    $context .= 'Objects: '.implode(', ', $analysis['objects'])."\n";
                }

                $contexts[] = $context;
            }
        }

        if (empty($contexts)) {
            return '';
        }

        return "\n\n--- Image Context ---\n".
               "The following images provide visual context for this business:\n\n".
               implode("\n", $contexts).
               "\nPlease consider this visual context when generating business names.\n";
    }

    /**
     * Clear analysis cache for an image.
     */
    public function clearCache(ProjectImage $image): void
    {
        $cacheKey = "vision_analysis:{$image->uuid}";
        Cache::forget($cacheKey);
    }
}
