<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareRequest;
use App\Http\Resources\ShareResource;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * ShareController handles API endpoints for share management.
 *
 * Provides CRUD operations for shares with proper authorization,
 * validation, and rate limiting.
 */
final class ShareController extends Controller
{
    public function __construct(
        private readonly ShareService $shareService
    ) {
        $this->middleware('auth');
        $this->middleware('throttle.shares')->only(['store']);
    }

    /**
     * Get paginated list of user's shares with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'share_type' => ['sometimes', 'in:public,password_protected'],
            'is_active' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->shareService->getUserShares($request->user(), $filters);

        return response()->json([
            'data' => ShareResource::collection($result['data']),
            'pagination' => $result['pagination'],
        ]);
    }

    /**
     * Create a new share.
     */
    public function store(ShareRequest $request): JsonResponse
    {
        $validated = $request->getSanitizedData();

        try {
            $share = $this->shareService->createShare($request->user(), $validated);

            return response()->json([
                'data' => new ShareResource($share),
                'message' => 'Share created successfully',
            ], 201);
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
            ], 429);
        }
    }

    /**
     * Show a specific share.
     */
    public function show(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('view', $share);

        $share->load(['shareable', 'user']);

        return response()->json([
            'data' => new ShareResource($share),
        ]);
    }

    /**
     * Update a share.
     */
    public function update(ShareRequest $request, Share $share): JsonResponse
    {
        Gate::authorize('update', $share);

        $validated = $request->getSanitizedData();

        $updatedShare = $this->shareService->updateShare($share, $validated);

        return response()->json([
            'data' => new ShareResource($updatedShare),
            'message' => 'Share updated successfully',
        ]);
    }

    /**
     * Deactivate a share (soft delete).
     */
    public function destroy(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('delete', $share);

        $this->shareService->deactivateShare($share);

        return response()->json([
            'message' => 'Share deactivated successfully',
        ]);
    }

    /**
     * Get analytics for a specific share.
     */
    public function analytics(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('view', $share);

        $analytics = $this->shareService->getShareAnalytics($share);

        return response()->json([
            'data' => $analytics,
        ]);
    }

    /**
     * Get social media metadata for a share.
     */
    public function metadata(Request $request, Share $share): JsonResponse
    {
        Gate::authorize('view', $share);

        $metadata = $this->shareService->generateSocialMediaMetadata($share);

        return response()->json([
            'data' => $metadata,
        ]);
    }
}
