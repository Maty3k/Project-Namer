<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProjectImage;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VisionAnalysisService
{
    /**
     * Analyze image using OpenAI Vision API.
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

        $imagePath = Storage::disk('public')->path($image->file_path);
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = $image->mime_type ?? 'image/jpeg';

        $prompt = $this->buildAnalysisPrompt();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('ai.openai_api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}",
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            if (! $response->successful()) {
                throw new Exception('Vision API request failed');
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

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
     * Build the vision analysis prompt for consistent results.
     */
    protected function buildAnalysisPrompt(): string
    {
        return 'Analyze this image and provide a JSON response with the following structure:
{
  "description": "A clear, detailed description of what you see in the image",
  "mood": "The emotional tone and atmosphere (e.g., professional, playful, elegant, rustic)",
  "colors": ["Array of dominant colors in the image"],
  "objects": ["Key objects, elements, or subjects visible"],
  "style": "The visual style or aesthetic (e.g., modern, vintage, minimalist, artistic)",
  "business_relevance": "What types of businesses or industries this image might represent or appeal to"
}

Focus on elements that would be relevant for business naming and branding. Be specific and descriptive but concise.';
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
