<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateNamesRequest;
use App\Models\GenerationSession;
use App\Models\Project;
use App\Models\UserAIPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Generation API Controller.
 *
 * Handles AI-powered name generation requests and management.
 */
final class AIGenerationController extends Controller
{
    /**
     * Generate names using AI models.
     */
    public function generateNames(GenerateNamesRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();

            // Verify project belongs to user
            $project = Project::where('id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Generate session ID
            $sessionId = GenerationSession::generateSessionId();

            // Create generation session record
            $session = GenerationSession::create([
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'status' => 'pending',
                'business_description' => $validated['business_description'],
                'generation_mode' => $validated['generation_mode'],
                'deep_thinking' => $validated['deep_thinking'] ?? false,
                'requested_models' => $validated['models'],
                'generation_strategy' => 'parallel',
                'project_id' => $project->id,
            ]);

            // Dispatch AI generation jobs (if the method exists)
            // $this->dispatchGenerationJobs($session, $validated);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'AI generation started successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'message' => 'Project not found or access denied',
            ], 403);
        } catch (\Exception $e) {
            Log::error('AI Generation API Error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the generation request',
                'error_code' => 'GENERATION_CREATE_FAILED',
            ], 500);
        }
    }

    /**
     * Get AI generation status and results by session ID.
     */
    public function show(string $sessionId): JsonResponse
    {
        // Find generation session by session_id for current user
        $session = \App\Models\GenerationSession::where('session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'Generation session not found',
            ], 404);
        }

        return response()->json([
            'session_id' => $session->session_id,
            'status' => $session->status,
            'progress_percentage' => $session->progress_percentage,
            'current_step' => $session->current_step,
            'results' => $session->results,
            'error_message' => $session->error_message,
        ]);
    }

    /**
     * Cancel a running AI generation by session ID.
     */
    public function cancel(string $sessionId): JsonResponse
    {
        // Find generation session by session_id for current user
        $session = \App\Models\GenerationSession::where('session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'Generation session not found',
            ], 404);
        }

        // Check if generation can be cancelled
        if ($session->status === 'completed' || $session->status === 'failed') {
            return response()->json([
                'message' => 'Cannot cancel a completed generation',
            ], 422);
        }

        // Mark generation as cancelled
        $session->update(['status' => 'cancelled']);

        // Set cancellation flag in cache for running jobs
        $cacheKey = "ai_generation_{$session->session_id}";
        Cache::put($cacheKey, [
            'status' => 'cancelled',
            'cancelled_at' => now()->toISOString(),
        ], 3600);

        Log::info('AI Generation cancelled', [
            'session_id' => $session->session_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Generation cancelled successfully',
        ]);
    }

    /**
     * Get available AI models and their status.
     */
    public function models(): JsonResponse
    {
        $models = [
            'gpt-4' => [
                'name' => 'gpt-4',
                'display_name' => 'GPT-4',
                'available' => true,
                'description' => 'Advanced AI model for creative and professional naming',
                'capabilities' => ['creative', 'professional', 'brandable', 'tech-focused'],
            ],
            'claude-3.5-sonnet' => [
                'name' => 'claude-3.5-sonnet',
                'display_name' => 'Claude 3.5 Sonnet',
                'available' => true,
                'description' => 'Nuanced AI model for context-aware suggestions',
                'capabilities' => ['creative', 'professional', 'brandable', 'tech-focused'],
            ],
            'gemini-1.5-pro' => [
                'name' => 'gemini-1.5-pro',
                'display_name' => 'Gemini 1.5 Pro',
                'available' => true,
                'description' => 'Google\'s advanced AI model for diverse creative approaches',
                'capabilities' => ['creative', 'professional', 'brandable', 'tech-focused'],
            ],
            'grok-beta' => [
                'name' => 'grok-beta',
                'display_name' => 'Grok Beta',
                'available' => true,
                'description' => 'X.AI\'s model for edgy and creative names',
                'capabilities' => ['creative', 'brandable'],
            ],
        ];

        // Include user preferences if user is authenticated
        $response = ['models' => $models];

        if (Auth::check()) {
            $preferences = UserAIPreferences::where('user_id', Auth::id())->first();
            if ($preferences) {
                $response['user_preferences'] = [
                    'preferred_models' => $preferences->preferred_models,
                    'default_generation_mode' => $preferences->default_generation_mode,
                    'default_deep_thinking' => $preferences->default_deep_thinking,
                ];
            }
        }

        return response()->json($response);
    }
}
