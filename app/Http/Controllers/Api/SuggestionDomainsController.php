<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NameSuggestion;
use Illuminate\Http\JsonResponse;

class SuggestionDomainsController extends Controller
{
    /**
     * Get domains for a specific suggestion.
     */
    public function show(NameSuggestion $suggestion): JsonResponse
    {
        // Authorize that the user can view this suggestion's project
        $this->authorize('view', $suggestion->project);

        return response()->json([
            'domains' => $suggestion->domains ?? [],
        ]);
    }
}
