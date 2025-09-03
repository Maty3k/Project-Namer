<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadProjectImagesRequest;
use App\Jobs\ProcessUploadedImageJob;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload images to a project.
     */
    public function store(UploadProjectImagesRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();
        $uploadedImages = [];

        foreach ($validated['images'] as $file) {
            // Store the original file
            $uuid = Str::uuid()->toString();
            $extension = $file->getClientOriginalExtension();
            $storedFilename = "{$uuid}.{$extension}";
            $filePath = $file->storeAs(
                "projects/{$project->id}/images/originals",
                $storedFilename,
                'public'
            );

            // Create the database record
            $image = ProjectImage::create([
                'uuid' => $uuid,
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'width' => null, // Will be populated by processing job
                'height' => null, // Will be populated by processing job
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'tags' => $validated['tags'] ?? null,
                'processing_status' => 'pending',
                'is_public' => $validated['is_public'] ?? false,
            ]);

            // Dispatch job for background processing
            ProcessUploadedImageJob::dispatch($image);

            $uploadedImages[] = [
                'id' => $image->id,
                'uuid' => $image->uuid,
                'original_filename' => $image->original_filename,
                'processing_status' => $image->processing_status,
            ];
        }

        // Update project counters
        $project->increment('total_images', count($uploadedImages));
        $files = new \Illuminate\Support\Collection($validated['images']);
        $project->increment('storage_used_bytes', $files->sum(fn ($file) => $file->getSize()));

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'images' => $uploadedImages,
        ]);
    }

    /**
     * Delete an uploaded image.
     */
    public function destroy(Project $project, ProjectImage $image): JsonResponse
    {
        $this->authorize('update', $project);

        if ($image->project_id !== $project->id) {
            return response()->json(['error' => 'Image does not belong to this project'], 404);
        }

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

        // Delete database record
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }
}
