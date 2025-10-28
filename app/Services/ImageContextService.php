<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectImage;

/**
 * Service for fetching and formatting image context for AI prompts.
 *
 * Retrieves project images with their titles and descriptions,
 * formatting them into a concise string that can be injected
 * into AI generation prompts to influence name suggestions.
 */
final class ImageContextService
{
    /**
     * Get formatted image context for a project.
     *
     * Returns null if no images exist for the project.
     * Returns a formatted string describing all images if they exist.
     */
    public function getContextForProject(int $projectId): ?string
    {
        $images = ProjectImage::query()
            ->where('project_id', $projectId)
            ->where('processing_status', 'completed')
            ->whereNotNull('title')
            ->orderBy('created_at', 'desc')
            ->get(['title', 'description']);

        if ($images->isEmpty()) {
            return null;
        }

        return $this->formatImagesAsContext($images);
    }

    /**
     * Format a collection of images into a prompt-friendly string.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, ProjectImage>  $images
     */
    protected function formatImagesAsContext($images): string
    {
        $imageCount = $images->count();
        $plural = $imageCount === 1 ? 'image' : 'images';

        $context = "\n\n=== IMPORTANT VISUAL INSPIRATION ===\n";
        $context .= "The user has carefully selected {$imageCount} inspiration {$plural} that should STRONGLY influence the name generation:\n\n";

        foreach ($images as $image) {
            $context .= "• {$image->title}";

            if ($image->description) {
                $context .= ": {$image->description}";
            }

            $context .= "\n";
        }

        $context .= "\nYou MUST incorporate themes, imagery, and concepts from these inspirations into the generated names. ";
        $context .= "The names should evoke the visual and thematic elements described above. ";
        $context .= "Make the connection between the inspiration and the names clear and meaningful.";

        return $context;
    }

    /**
     * Get formatted image context for a project by Project model.
     */
    public function getContextForProjectModel(Project $project): ?string
    {
        return $this->getContextForProject($project->id);
    }
}
