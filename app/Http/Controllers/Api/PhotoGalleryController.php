<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkImageActionsRequest;
use App\Http\Requests\Api\UpdateImageMetadataRequest;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoGalleryController extends Controller
{
    /**
     * Display project gallery images with filtering and pagination.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $query = ProjectImage::where('project_id', $project->id)
            ->where('processing_status', '!=', 'failed');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply tag filter
        if ($request->filled('tags')) {
            $tags = explode(',', (string) $request->input('tags'));
            $query->whereJsonContains('tags', $tags);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('processing_status', $request->input('status'));
        }

        // Apply sorting
        $sort = $request->input('sort', 'date_desc');
        match ($sort) {
            'date_asc' => $query->orderBy('created_at', 'asc'),
            'date_desc' => $query->orderBy('created_at', 'desc'),
            'name_asc' => $query->orderBy('original_filename', 'asc'),
            'name_desc' => $query->orderBy('original_filename', 'desc'),
            'size_asc' => $query->orderBy('file_size', 'asc'),
            'size_desc' => $query->orderBy('file_size', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = min((int) $request->input('per_page', 20), 100);
        $images = $query->paginate($perPage);

        return response()->json([
            'images' => $images->items(),
            'meta' => [
                'total' => $images->total(),
                'per_page' => $images->perPage(),
                'current_page' => $images->currentPage(),
                'last_page' => $images->lastPage(),
                'from' => $images->firstItem(),
                'to' => $images->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified image details.
     */
    public function show(Project $project, string $uuid): JsonResponse
    {
        $this->authorize('view', $project);

        $image = ProjectImage::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'image' => $image,
        ]);
    }

    /**
     * Update the specified image metadata.
     */
    public function update(UpdateImageMetadataRequest $request, Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $image = ProjectImage::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $validated = $request->validated();
        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Image metadata updated successfully',
            'image' => $image->fresh(),
        ]);
    }

    /**
     * Perform bulk actions on multiple images.
     */
    public function bulkAction(BulkImageActionsRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();
        $imageUuids = $validated['image_uuids'];

        $images = ProjectImage::where('project_id', $project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        if ($images->count() !== count($imageUuids)) {
            return response()->json([
                'error' => 'Some images were not found or do not belong to this project',
            ], 404);
        }

        $action = $validated['action'] ?? null;

        // Handle delete action specifically for DELETE requests
        if ($action === 'delete') {
            foreach ($images as $image) {
                // Delete files from storage
                if (Storage::disk('public')->exists($image->file_path)) {
                    Storage::disk('public')->delete($image->file_path);
                }
                if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                    Storage::disk('public')->delete($image->thumbnail_path);
                }

                // Update project counters
                $project->decrement('total_images');
                $project->decrement('storage_used_bytes', $image->file_size);
            }

            $totalDeleted = $images->count();
            ProjectImage::whereIn('uuid', $imageUuids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$totalDeleted} images",
                'deleted_count' => $totalDeleted,
            ]);
        }

        switch ($action) {
            case 'add_tags':
                $newTags = $validated['tags'] ?? [];
                foreach ($images as $image) {
                    $existingTags = $image->tags ?? [];
                    $mergedTags = array_unique(array_merge($existingTags, $newTags));
                    $image->update(['tags' => $mergedTags]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Tags added to selected images',
                    'updated_count' => $images->count(),
                ]);

            case 'remove_tags':
                $tagsToRemove = $validated['tags'] ?? [];
                foreach ($images as $image) {
                    $existingTags = $image->tags ?? [];
                    $filteredTags = array_values(array_diff($existingTags, $tagsToRemove));
                    $image->update(['tags' => $filteredTags]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Tags removed from selected images',
                    'updated_count' => $images->count(),
                ]);

            case 'toggle_public':
                $isPublic = $validated['is_public'] ?? false;
                $images->each(fn ($image) => $image->update(['is_public' => $isPublic]));

                $status = $isPublic ? 'public' : 'private';

                return response()->json([
                    'success' => true,
                    'message' => "Images marked as {$status}",
                    'updated_count' => $images->count(),
                ]);

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
    }
}
