<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomizeLogosRequest;
use App\Http\Requests\Api\GenerateLogosRequest;
use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;
use Illuminate\Http\JsonResponse;

/**
 * Logo Generation API Controller.
 *
 * Handles logo generation requests, status checks, and customization.
 */
class LogoGenerationController extends Controller
{
    public function __construct(
        private readonly ColorPaletteService $colorPaletteService,
        private readonly SvgColorProcessor $svgColorProcessor
    ) {}

    /**
     * Generate logos for a business idea.
     */
    public function generate(GenerateLogosRequest $request): JsonResponse
    {
        $logoGeneration = LogoGeneration::create([
            'session_id' => $request->validated('session_id'),
            'business_name' => $request->validated('business_name'),
            'business_description' => $request->validated('business_description'),
            'status' => 'pending',
            'total_logos_requested' => 12, // 4 styles × 3 variations
            'logos_completed' => 0,
            'api_provider' => 'openai',
            'cost_cents' => 0,
        ]);

        // Dispatch the logo generation job
        GenerateLogosJob::dispatch($logoGeneration);

        return response()->json([
            'data' => $this->formatLogoGenerationResponse($logoGeneration),
            'message' => 'Logo generation started successfully',
        ], 201);
    }

    /**
     * Get generation status and progress.
     */
    public function status(LogoGeneration $logoGeneration): JsonResponse
    {
        $progressPercentage = $logoGeneration->total_logos_requested > 0
            ? (int) round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100)
            : 0;

        $estimatedCompletionTime = null;
        if ($logoGeneration->status === 'processing' && $logoGeneration->logos_completed > 0) {
            // Estimate based on current progress (roughly 30 seconds per logo)
            $remainingLogos = $logoGeneration->total_logos_requested - $logoGeneration->logos_completed;
            $estimatedCompletionTime = now()->addSeconds($remainingLogos * 30);
        }

        $data = [
            'id' => $logoGeneration->id,
            'status' => $logoGeneration->status,
            'progress_percentage' => $progressPercentage,
            'logos_completed' => $logoGeneration->logos_completed,
            'total_logos_requested' => $logoGeneration->total_logos_requested,
            'estimated_completion_time' => $estimatedCompletionTime?->toISOString(),
            'cost_cents' => $logoGeneration->cost_cents,
            'created_at' => $logoGeneration->created_at->toISOString(),
            'updated_at' => $logoGeneration->updated_at->toISOString(),
        ];

        // Add error information for failed generations
        if ($logoGeneration->status === 'failed' && $logoGeneration->error_message) {
            $data['error_message'] = $logoGeneration->error_message;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Get generated logos with color scheme information.
     */
    public function show(LogoGeneration $logoGeneration): JsonResponse
    {
        $logos = $logoGeneration->generatedLogos()
            ->with('colorVariants')
            ->get()
            ->map(function ($logo) {
                return [
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
                    'color_variants' => $logo->colorVariants->map(function ($variant) {
                        return [
                            'color_scheme' => $variant->color_scheme,
                            'file_path' => $variant->file_path,
                            'download_url' => route('api.logos.download', [
                                'logoGeneration' => $variant->generatedLogo->logo_generation_id,
                                'generatedLogo' => $variant->generated_logo_id,
                                'color_scheme' => $variant->color_scheme,
                            ]),
                        ];
                    }),
                ];
            });

        $data = [
            'id' => $logoGeneration->id,
            'session_id' => $logoGeneration->session_id,
            'business_name' => $logoGeneration->business_name,
            'business_description' => $logoGeneration->business_description,
            'status' => $logoGeneration->status,
            'logos' => $logos->toArray(),
            'color_schemes' => $this->getAvailableColorSchemes(),
            'created_at' => $logoGeneration->created_at->toISOString(),
            'updated_at' => $logoGeneration->updated_at->toISOString(),
        ];

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
                    continue; // Skip if processing failed
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
                    'file_size' => strlen($customizedContent),
                ]);

                $customizedLogos[] = $this->formatColorVariantResponse($colorVariant);

            } catch (\Exception $e) {
                \Log::warning('Failed to customize logo', [
                    'logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'data' => [
                'customized_logos' => $customizedLogos,
            ],
            'message' => count($customizedLogos).' logos customized successfully',
        ]);
    }

    /**
     * Format logo generation response.
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
     */
    private function getAvailableColorSchemes(): array
    {
        $schemes = $this->colorPaletteService->getAllColorSchemesWithMetadata();

        return array_values(array_map(function ($scheme, $id) {
            return [
                'name' => $id,
                'display_name' => $scheme['name'],
                'colors' => [
                    'primary' => $scheme['colors']['primary'],
                    'secondary' => $scheme['colors']['secondary'],
                    'accent' => $scheme['colors']['accent'],
                ],
            ];
        }, $schemes, array_keys($schemes)));
    }

    /**
     * Format color variant response.
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
