<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateMoodBoardRequest;
use App\Http\Requests\Api\ExportMoodBoardRequest;
use App\Http\Requests\Api\MoodBoardImageRequest;
use App\Http\Requests\Api\UpdateMoodBoardRequest;
use App\Models\MoodBoard;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Services\MoodBoardExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MoodBoardController extends Controller
{
    /**
     * Display mood boards for a project.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $moodBoards = MoodBoard::where('project_id', $project->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'mood_boards' => $moodBoards,
        ]);
    }

    /**
     * Store a newly created mood board.
     */
    public function store(CreateMoodBoardRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $moodBoard = MoodBoard::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'uuid' => Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'layout_type' => $validated['layout_type'],
            'layout_config' => $validated['layout_config'] ?? [
                'background_color' => '#ffffff',
                'grid_size' => 20,
                'snap_to_grid' => true,
                'images' => [],
            ],
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return response()->json([
            'mood_board' => $moodBoard,
        ], 201);
    }

    /**
     * Display the specified mood board.
     */
    public function show(Project $project, string $uuid): JsonResponse
    {
        $this->authorize('view', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->with('projectImages')
            ->firstOrFail();

        return response()->json([
            'mood_board' => $moodBoard,
        ]);
    }

    /**
     * Update the specified mood board.
     */
    public function update(UpdateMoodBoardRequest $request, Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $validated = $request->validated();
        $moodBoard->update($validated);

        return response()->json([
            'mood_board' => $moodBoard->fresh(),
        ]);
    }

    /**
     * Remove the specified mood board.
     */
    public function destroy(Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $moodBoard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mood board deleted successfully',
        ]);
    }

    /**
     * Add images to mood board.
     */
    public function addImages(MoodBoardImageRequest $request, Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $validated = $request->validated();
        $imageUuids = $validated['image_uuids'];
        $positions = $validated['positions'] ?? [];

        // Get images that belong to this project
        $images = ProjectImage::where('project_id', $project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        if ($images->count() !== count($imageUuids)) {
            return response()->json([
                'error' => 'Some images were not found or do not belong to this project',
            ], 404);
        }

        // Update layout config with image positions
        $layoutConfig = $moodBoard->layout_config ?? ['images' => []];
        $existingImages = new \Illuminate\Support\Collection($layoutConfig['images'] ?? []);

        foreach ($positions as $position) {
            $imageUuid = $position['image_uuid'];

            // Remove existing position if it exists
            $existingImages = $existingImages->reject(fn ($img) => $img['image_uuid'] === $imageUuid);

            // Add new position
            $existingImages->push([
                'image_uuid' => $imageUuid,
                'x' => $position['x'] ?? 0,
                'y' => $position['y'] ?? 0,
                'width' => $position['width'] ?? 200,
                'height' => $position['height'] ?? 200,
                'rotation' => $position['rotation'] ?? 0,
                'z_index' => $position['z_index'] ?? 1,
            ]);
        }

        $layoutConfig['images'] = $existingImages->values()->toArray();
        $moodBoard->update(['layout_config' => $layoutConfig]);

        // Attach images to mood board via relationship
        $moodBoard->projectImages()->syncWithoutDetaching($images->pluck('id'));

        return response()->json([
            'success' => true,
            'message' => 'Images added to mood board',
            'mood_board' => $moodBoard->fresh(),
        ]);
    }

    /**
     * Remove images from mood board.
     */
    public function removeImages(Request $request, Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'image_uuids' => ['required', 'array', 'min:1'],
            'image_uuids.*' => ['string', 'uuid'],
        ]);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $imageUuids = $request->input('image_uuids');

        // Get images to remove
        $images = ProjectImage::where('project_id', $project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        // Remove from pivot relationship
        $moodBoard->projectImages()->detach($images->pluck('id'));

        // Remove from layout config
        $layoutConfig = $moodBoard->layout_config ?? ['images' => []];
        $imageCollection = new \Illuminate\Support\Collection($layoutConfig['images'] ?? []);
        $layoutConfig['images'] = $imageCollection
            ->reject(fn ($img) => in_array($img['image_uuid'], $imageUuids))
            ->values()
            ->toArray();

        $moodBoard->update(['layout_config' => $layoutConfig]);

        return response()->json([
            'success' => true,
            'message' => 'Images removed from mood board',
            'mood_board' => $moodBoard->fresh(),
        ]);
    }

    /**
     * Generate or revoke public sharing for mood board.
     */
    public function share(Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Generate public sharing token
        $shareToken = Str::random(32);
        $moodBoard->update([
            'is_public' => true,
            'share_token' => $shareToken,
        ]);

        $sharingUrl = url("/share/mood-board/{$shareToken}");

        return response()->json([
            'sharing_url' => $sharingUrl,
            'public_token' => $shareToken,
        ]);
    }

    /**
     * Revoke public sharing for mood board.
     */
    public function unshare(Project $project, string $uuid): JsonResponse
    {
        $this->authorize('update', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $moodBoard->update([
            'is_public' => false,
            'share_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Public sharing revoked',
        ]);
    }

    /**
     * Export mood board in specified format.
     */
    public function export(ExportMoodBoardRequest $request, Project $project, string $uuid): JsonResponse
    {
        $this->authorize('view', $project);

        $moodBoard = MoodBoard::where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->with('projectImages')
            ->firstOrFail();

        $validated = $request->validated();
        $format = $validated['format'];

        $exportService = app(MoodBoardExportService::class);
        $result = $exportService->export($moodBoard, $format, $validated);

        return response()->json([
            'download_url' => $result['download_url'],
            'file_path' => $result['file_path'],
            'expires_at' => $result['expires_at'],
        ]);
    }
}
