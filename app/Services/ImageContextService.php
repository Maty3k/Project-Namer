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

        $context = "\n\n╔═══════════════════════════════════════════════════════════╗\n";
        $context .= "║   🎨 CRITICAL: VISUAL INSPIRATION PROVIDED                ║\n";
        $context .= "╚═══════════════════════════════════════════════════════════╝\n\n";

        $context .= "⚠️  MANDATORY REQUIREMENT: The user has uploaded {$imageCount} inspiration {$plural}.\n";
        $context .= "These images are THE PRIMARY CREATIVE DIRECTION for naming.\n";
        $context .= "ALL generated names MUST be DIRECTLY inspired by these images.\n\n";

        $context .= "📸 VISUAL INSPIRATION SOURCES:\n\n";

        foreach ($images as $image) {
            $context .= "🔹 **{$image->title}**";

            if ($image->description) {
                $context .= "\n   Context: {$image->description}";
            }

            $context .= "\n\n";
        }

        $context .= "═══════════════════════════════════════════════════════════\n\n";
        $context .= "🎯 YOUR NAMING INSTRUCTIONS:\n\n";
        $context .= "1. Read each inspiration image title and description carefully\n";
        $context .= "2. Extract the key themes, emotions, and visual concepts\n";
        $context .= "3. Generate names that DIRECTLY reference or evoke these inspirations\n";
        $context .= "4. Make the connection between the image and name OBVIOUS\n";
        $context .= "5. Prioritize image inspiration OVER the business description\n\n";

        $context .= "✅ GOOD NAME EXAMPLES (if inspiration was 'River inspiration'):\n";
        $context .= "   - RiverFlow, Rapids, StreamLine, Current, Tributary\n\n";

        $context .= "❌ BAD NAME EXAMPLES (ignoring the river inspiration):\n";
        $context .= "   - TechHub, CloudSync, DataPro (generic, no connection to river)\n\n";

        $context .= "⚡ Remember: The visual inspirations are NOT optional suggestions.\n";
        $context .= "They are MANDATORY creative constraints that every name must reflect.\n";

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
