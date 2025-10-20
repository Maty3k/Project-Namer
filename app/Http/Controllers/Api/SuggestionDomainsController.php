<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NameSuggestion;
use Illuminate\Http\JsonResponse;

class SuggestionDomainsController extends Controller
{
    /**
     * Get domains and logos for a specific suggestion.
     */
    public function show(NameSuggestion $suggestion): JsonResponse
    {
        // Authorize that the user can view this suggestion's project
        $this->authorize('view', $suggestion->project);

        // Refresh from database to get latest data
        $suggestion->refresh();

        // Find the most recent completed LogoGeneration for this name
        $logoGeneration = \App\Models\LogoGeneration::where('business_name', $suggestion->name)
            ->where('status', 'completed')
            ->latest()
            ->first();

        return response()->json([
            'domains' => $suggestion->domains ?? [],
            'logos' => $suggestion->logos ?? [],
            'logoGenerationId' => $logoGeneration?->id,
        ]);
    }
}
