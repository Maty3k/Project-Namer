<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\LogoGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomizeLogosRequest;
use App\Http\Requests\LogoGenerationRequest;
use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use App\Services\ColorPaletteService;
use App\Services\LogoVariantCacheService;
use App\Services\SvgColorProcessor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Logo Generation API Controller.
 *
 * Handles logo generation requests, status checks, and customization.
 */
class LogoGenerationController extends Controller
{
    public function __construct(
        private readonly ColorPaletteService $colorPaletteService,
        private readonly SvgColorProcessor $svgColorProcessor,
        private readonly LogoVariantCacheService $cacheService
    ) {}

    /**
     * Generate logos for a business idea.
     */
    public function generate(LogoGenerationRequest $request): JsonResponse
    {
        // Check if read-only mode is enabled
        if (config('database.read_only', false)) {
            return response()->json([
                'message' => 'Service is in maintenance mode. You can still view existing logos.',
                'read_only' => true,
            ], 503);
        }

        // Check for high system load and adjust count if necessary
        $requestedCount = $request->validated('count', 4);
        $adjustedCount = $requestedCount;

        if (config('app.high_load', false) && $requestedCount > 4) {
            $adjustedCount = 4;
        }

        // Handle fallback service if primary is unavailable
        $useFallback = $request->validated('use_fallback', false);

        try {
            $logoGeneration = LogoGeneration::create([
                'session_id' => $request->validated('session_id'),
                'business_name' => $request->validated('business_name'),
                'business_description' => $request->validated('business_description'),
                'status' => 'pending',
                'total_logos_requested' => $adjustedCount * 3, // Multiple variations
                'logos_completed' => 0,
                'api_provider' => 'openai',
                'cost_cents' => 0,
                'progress' => 0,
                'started_at' => now(),
            ]);

            // Dispatch the logo generation job
            GenerateLogosJob::dispatch($logoGeneration);

            $response = [
                'data' => $this->formatLogoGenerationResponse($logoGeneration),
                'message' => 'Logo generation started successfully',
            ];

            // Add fallback or high load messaging
            if (config('app.high_load', false) && $requestedCount > 4) {
                $response['message'] = 'Due to high demand, we\'re generating '.$adjustedCount.' logos instead of '.$requestedCount.'.';
                $response['adjusted_count'] = $adjustedCount;
                $response['reason'] = 'high_load';
            }

            if ($useFallback) {
                $response['message'] = 'Using alternative generation method. This may take a bit longer.';
                $response['using_fallback'] = true;
            }

            return response()->json($response, 202);

        } catch (ConnectionException) {
            throw LogoGenerationException::connectionFailed();
        } catch (\Exception $e) {
            \Log::error('Logo generation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
            ]);

            throw LogoGenerationException::invalidResponse();
        }
    }

    /**
     * Get generation status and progress.
     */
    public function status(LogoGeneration $logoGeneration): JsonResponse
    {
        // Cache status for completed generations only (they don't change)
        $cacheKey = "logo_status:{$logoGeneration->id}";
        $cacheTime = $logoGeneration->status === 'completed' ? 3600 : 30; // 1 hour for completed, 30 seconds for processing

        $data = Cache::remember($cacheKey, $cacheTime, function () use ($logoGeneration): array {
            // Refresh from database to get latest status
            $logoGeneration->refresh();

            $progressPercentage = $logoGeneration->total_logos_requested > 0
                ? (int) round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100)
                : 0;

            // Calculate estimated time remaining
            $estimatedTimeRemaining = null;
            $estimatedCompletionTime = null;
            if ($logoGeneration->status === 'processing' && $logoGeneration->logos_completed > 0) {
                $remainingLogos = $logoGeneration->total_logos_requested - $logoGeneration->logos_completed;
                $estimatedTimeRemaining = $remainingLogos * 30; // 30 seconds per logo
                $estimatedCompletionTime = now()->addSeconds($estimatedTimeRemaining);
            }

            // Generate user-friendly status messages
            $message = match ($logoGeneration->status) {
                'pending' => 'Starting logo generation...',
                'processing' => $progressPercentage > 0
                    ? "Generating your logos... {$progressPercentage}% complete"
                    : 'Generating your logos...',
                'completed' => 'Your logos are ready!',
                'failed' => $logoGeneration->error_message ?: 'Generation failed. Please try again.',
                'partial' => 'Some logos were generated successfully. You can retry to generate the remaining ones.',
                default => 'Processing...'
            };

            $data = [
                'id' => $logoGeneration->id,
                'status' => $logoGeneration->status,
                'message' => $message,
                'progress' => $logoGeneration->progress ?? $progressPercentage,
                'progress_percentage' => $progressPercentage,
                'logos_completed' => $logoGeneration->logos_completed,
                'total_logos_requested' => $logoGeneration->total_logos_requested,
                'cost_cents' => $logoGeneration->cost_cents,
                'error_message' => $logoGeneration->error_message,
                'estimated_completion_time' => null,
                'created_at' => $logoGeneration->created_at->toISOString(),
                'updated_at' => $logoGeneration->updated_at->toISOString(),
            ];

            // Add progress-specific information
            if ($logoGeneration->status === 'processing') {
                if ($estimatedTimeRemaining) {
                    $data['estimated_time_remaining'] = $estimatedTimeRemaining;
                    if ($estimatedTimeRemaining < 120) {
                        $data['message'] = "Your logos will be ready in about {$estimatedTimeRemaining} seconds";
                    } else {
                        $minutes = ceil($estimatedTimeRemaining / 60);
                        $data['message'] = "Your logos will be ready in about {$minutes} minute".($minutes > 1 ? 's' : '');
                    }
                }
                $data['estimated_completion'] = $estimatedCompletionTime?->toISOString();
                $data['estimated_completion_time'] = $estimatedCompletionTime?->toISOString();
            }

            // Add error information for failed generations
            if ($logoGeneration->status === 'failed') {
                $data['can_retry'] = true;
                if ($logoGeneration->error_message) {
                    $data['message'] = $logoGeneration->error_message;
                }
            }

            // Add partial generation information
            if ($logoGeneration->status === 'partial') {
                $data['generated_count'] = $logoGeneration->logos_completed;
                $data['total_count'] = $logoGeneration->total_logos_requested;
                $data['can_retry'] = true;
            }

            return $data;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Get generated logos with color scheme information.
     */
    public function show(LogoGeneration $logoGeneration): JsonResponse
    {
        // Cache completed logo generations for longer periods
        $cacheKey = "logo_api_show:{$logoGeneration->id}";
        $cacheTime = $logoGeneration->status === 'completed' ? 7200 : 300; // 2 hours for completed, 5 minutes for others

        $data = Cache::remember($cacheKey, $cacheTime, function () use ($logoGeneration): array {
            // Use the cache service to get logos efficiently
            $cachedGeneration = $this->cacheService->getCachedLogoGeneration($logoGeneration->id);
            $generation = $cachedGeneration ?: $logoGeneration->load(['generatedLogos.colorVariants']);

            $logos = $generation->generatedLogos
                ->map(fn ($logo) => [
                    'id' => $logo->id,
                    'style' => $logo->style,
                    'variation_number' => $logo->variation_number,
                    'prompt_used' => $logo->prompt_used,
                    'original_file_path' => $logo->original_file_path,
                    'file_size' => $logo->file_size,
                    'image_width' => $logo->image_width,
                    'image_height' => $logo->image_height,
                    'download_url' => route('api.logos.download', [
                        'logoGeneration' => $logo->logo_generation_id,
                        'generatedLogo' => $logo->id,
                    ]),
                    'preview_url' => $logo->original_file_path ? asset('storage/'.$logo->original_file_path) : null,
                    'color_variants' => $logo->colorVariants->map(fn ($variant) => [
                        'color_scheme' => $variant->color_scheme,
                        'file_path' => $variant->file_path,
                        'download_url' => route('api.logos.download', [
                            'logoGeneration' => $variant->generatedLogo->logo_generation_id,
                            'generatedLogo' => $variant->generated_logo_id,
                            'color_scheme' => $variant->color_scheme,
                        ]),
                    ]),
                ]);

            return [
                'id' => $generation->id,
                'session_id' => $generation->session_id,
                'business_name' => $generation->business_name,
                'business_description' => $generation->business_description,
                'status' => $generation->status,
                'logos' => $logos->toArray(),
                'color_schemes' => $this->getAvailableColorSchemes(),
                'created_at' => $generation->created_at->toISOString(),
                'updated_at' => $generation->updated_at->toISOString(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Apply color customization to logos.
     */
    public function customize(CustomizeLogosRequest $request, LogoGeneration $logoGeneration): JsonResponse
    {
        $colorScheme = $request->validated('color_scheme');
        $logoIds = $request->validated('logo_ids');

        $logos = $logoGeneration->generatedLogos()
            ->whereIn('id', $logoIds)
            ->get();

        if ($logos->count() !== count($logoIds)) {
            return response()->json([
                'message' => 'Some logos not found or do not belong to this generation',
            ], 422);
        }

        $customizedLogos = [];
        $palette = $this->colorPaletteService->getColorPalette($colorScheme);

        foreach ($logos as $logo) {
            try {
                // Check if customization already exists
                $existingVariant = $logo->colorVariants()
                    ->where('color_scheme', $colorScheme)
                    ->first();

                if ($existingVariant) {
                    $customizedLogos[] = $this->formatColorVariantResponse($existingVariant);

                    continue;
                }

                // Read the original SVG/PNG and apply colors
                if (! $logo->fileExists()) {
                    continue;
                }

                $originalPath = storage_path('app/public/'.$logo->original_file_path);
                $originalContent = file_get_contents($originalPath);

                $result = $this->svgColorProcessor->processSvg($originalContent, $palette);

                if (! $result['success']) {
                    throw LogoGenerationException::colorProcessingFailed();
                }

                $customizedContent = $result['svg'];

                // Save customized version
                $customizedFileName = $logo->generateDownloadFilename($colorScheme, 'svg');
                $customizedPath = "logos/{$logoGeneration->id}/customized/{$customizedFileName}";

                \Storage::disk('public')->put($customizedPath, $customizedContent);

                // Create color variant record
                $colorVariant = $logo->colorVariants()->create([
                    'color_scheme' => $colorScheme,
                    'file_path' => $customizedPath,
                    'file_size' => strlen((string) $customizedContent),
                ]);

                // Invalidate cache after creating new variant
                $this->cacheService->invalidateLogoCache($logo->id);

                $customizedLogos[] = $this->formatColorVariantResponse($colorVariant);

            } catch (\Exception $e) {
                \Log::warning('Failed to customize logo', [
                    'logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Invalidate API cache if any logos were customized
        if (! empty($customizedLogos)) {
            Cache::forget("logo_api_show:{$logoGeneration->id}");
        }

        return response()->json([
            'data' => [
                'customized_logos' => $customizedLogos,
            ],
            'message' => count($customizedLogos).' logos customized successfully',
        ]);
    }

    /**
     * Retry failed logo generation.
     */
    public function retry(LogoGeneration $logoGeneration): JsonResponse
    {
        if ($logoGeneration->status !== 'failed') {
            return response()->json([
                'message' => 'Only failed generations can be retried',
            ], 422);
        }

        $logoGeneration->update([
            'status' => 'processing',
            'error_message' => null,
            'started_at' => now(),
        ]);

        GenerateLogosJob::dispatch($logoGeneration);

        return response()->json([
            'message' => 'Logo generation has been restarted.',
            'status' => 'processing',
        ], 202);
    }

    /**
     * Complete partial logo generation.
     */
    public function complete(LogoGeneration $logoGeneration): JsonResponse
    {
        if ($logoGeneration->status !== 'partial') {
            return response()->json([
                'message' => 'Only partial generations can be completed',
            ], 422);
        }

        $remainingCount = $logoGeneration->total_logos_requested - $logoGeneration->logos_completed;

        $logoGeneration->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        GenerateLogosJob::dispatch($logoGeneration);

        return response()->json([
            'message' => 'Generating remaining logos...',
            'remaining_count' => $remainingCount,
        ], 202);
    }

    /**
     * Format logo generation response.
     *
     * @return array<string, mixed>
     */
    private function formatLogoGenerationResponse(LogoGeneration $logoGeneration): array
    {
        return [
            'id' => $logoGeneration->id,
            'session_id' => $logoGeneration->session_id,
            'business_name' => $logoGeneration->business_name,
            'business_description' => $logoGeneration->business_description,
            'status' => $logoGeneration->status,
            'total_logos_requested' => $logoGeneration->total_logos_requested,
            'logos_completed' => $logoGeneration->logos_completed,
            'cost_cents' => $logoGeneration->cost_cents,
            'created_at' => $logoGeneration->created_at->toISOString(),
        ];
    }

    /**
     * Get available color schemes.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableColorSchemes(): array
    {
        return Cache::remember('api_color_schemes', 86400, function (): array { // 24 hours cache
            $schemes = $this->colorPaletteService->getAllColorSchemesWithMetadata();

            return array_values(array_map(fn ($scheme, $id) => [
                'name' => $id,
                'display_name' => $scheme['name'],
                'colors' => [
                    'primary' => $scheme['colors']['primary'],
                    'secondary' => $scheme['colors']['secondary'],
                    'accent' => $scheme['colors']['accent'],
                ],
            ], $schemes, array_keys($schemes)));
        });
    }

    /**
     * Format color variant response.
     *
     * @param  \App\Models\LogoColorVariant  $colorVariant
     * @return array<string, mixed>
     */
    private function formatColorVariantResponse($colorVariant): array
    {
        return [
            'id' => $colorVariant->id,
            'original_logo_id' => $colorVariant->generated_logo_id,
            'color_scheme' => $colorVariant->color_scheme,
            'file_path' => $colorVariant->file_path,
            'file_size' => $colorVariant->file_size,
            'download_url' => route('api.logos.download', [
                'logoGeneration' => $colorVariant->generatedLogo->logo_generation_id,
                'generatedLogo' => $colorVariant->generated_logo_id,
                'color_scheme' => $colorVariant->color_scheme,
            ]),
            'preview_url' => asset('storage/'.$colorVariant->file_path),
        ];
    }
}
