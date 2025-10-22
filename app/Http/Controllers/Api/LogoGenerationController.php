<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoGenerationController extends Controller
{
    /**
     * Generate logos for a business name.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Create logo generation record
        $logoGeneration = LogoGeneration::create([
            'user_id' => auth()->id(),
            'business_name' => $validated['business_name'],
            'business_description' => $validated['business_description'] ?? null,
            'status' => 'pending',
            'total_logos_requested' => 4, // Always 4 logos
            'logos_completed' => 0,
        ]);

        // Dispatch job to generate logos
        dispatch(new GenerateLogosJob($logoGeneration));

        return response()->json([
            'success' => true,
            'message' => 'Logo generation started',
            'logo_generation_id' => $logoGeneration->id,
        ], 201);
    }

    /**
     * Get logo generation status and results.
     */
    public function show(LogoGeneration $logoGeneration): JsonResponse
    {
        $this->authorize('view', $logoGeneration);

        $logoGeneration->load('generatedLogos');

        return response()->json([
            'id' => $logoGeneration->id,
            'business_name' => $logoGeneration->business_name,
            'status' => $logoGeneration->status,
            'progress_percentage' => $logoGeneration->getProgressPercentage(),
            'total_logos_requested' => $logoGeneration->total_logos_requested,
            'logos_completed' => $logoGeneration->logos_completed,
            'logos' => $logoGeneration->generatedLogos->map(fn ($logo) => [
                'id' => $logo->id,
                'style' => $logo->style,
                'url' => $logo->url,
                'status' => $logo->status,
            ]),
        ]);
    }
}
