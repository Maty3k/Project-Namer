<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePreferencesRequest;
use App\Models\GenerationSession;
use App\Models\UserAIPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * User AI Preferences API Controller.
 *
 * Handles user AI preferences and generation history.
 */
final class UserPreferencesController extends Controller
{
    /**
     * Get current user's AI preferences.
     */
    public function show(): JsonResponse
    {
        $preferences = UserAIPreferences::where('user_id', Auth::id())->first();

        if (! $preferences) {
            // Return default preferences
            return response()->json([
                'preferred_models' => ['gpt-4', 'claude-3.5-sonnet'],
                'default_generation_mode' => 'creative',
                'default_deep_thinking' => false,
                'auto_select_best_model' => true,
                'enable_model_comparison' => true,
                'max_concurrent_generations' => 3,
            ]);
        }

        return response()->json([
            'preferred_models' => $preferences->preferred_models,
            'default_generation_mode' => $preferences->default_generation_mode,
            'default_deep_thinking' => $preferences->default_deep_thinking,
            'auto_select_best_model' => $preferences->auto_select_best_model,
            'enable_model_comparison' => $preferences->enable_model_comparison,
            'max_concurrent_generations' => $preferences->max_concurrent_generations,
        ]);
    }

    /**
     * Update user's AI preferences.
     */
    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $preferences = UserAIPreferences::updateOrCreate([
            'user_id' => Auth::id(),
        ], $validated);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Get user's AI generation history.
     */
    public function history(): JsonResponse
    {
        $perPage = request()->integer('per_page', 10);
        $projectId = request()->integer('project_id');
        $status = request()->string('status');

        $query = GenerationSession::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($status->isNotEmpty()) {
            $query->where('status', $status);
        }

        $generations = $query->paginate($perPage);

        return response()->json([
            'data' => $generations->map(fn ($session) => [
                'session_id' => $session->session_id,
                'status' => $session->status,
                'business_description' => $session->business_description,
                'generation_mode' => $session->generation_mode,
                'requested_models' => $session->requested_models,
                'created_at' => $session->created_at->toISOString(),
            ]),
            'links' => [
                'first' => $generations->url(1),
                'last' => $generations->url($generations->lastPage()),
                'prev' => $generations->previousPageUrl(),
                'next' => $generations->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $generations->currentPage(),
                'from' => $generations->firstItem(),
                'last_page' => $generations->lastPage(),
                'per_page' => $generations->perPage(),
                'to' => $generations->lastItem(),
                'total' => $generations->total(),
            ],
        ]);
    }
}
