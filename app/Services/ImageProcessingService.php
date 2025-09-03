<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProjectImage;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ImageProcessingService
{
    /**
     * Process uploaded image to extract metadata and create thumbnails.
     */
    public function processImage(ProjectImage $image): bool
    {
        try {
            $originalPath = Storage::disk('public')->path($image->file_path);

            if (! file_exists($originalPath)) {
                Log::error('Image file not found for processing', ['image_id' => $image->id, 'path' => $originalPath]);

                return false;
            }

            // Extract image dimensions and metadata
            $imageInfo = getimagesize($originalPath);
            if ($imageInfo !== false) {
                $image->update([
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'aspect_ratio' => round($imageInfo[0] / $imageInfo[1], 2),
                ]);
            }

            // Generate thumbnails
            $this->generateThumbnails($image, $originalPath);

            // Extract dominant colors
            $this->extractDominantColors($image, $originalPath);

            // Mark as completed
            $image->update(['processing_status' => 'completed']);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to process image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);

            $image->update(['processing_status' => 'failed']);

            return false;
        }
    }

    /**
     * Generate multiple thumbnail sizes for the image.
     */
    protected function generateThumbnails(ProjectImage $image, string $originalPath): void
    {
        $thumbnailSizes = [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 400],
        ];

        foreach ($thumbnailSizes as $size => $dimensions) {
            $thumbnailFilename = "{$image->uuid}_{$size}.webp";
            $thumbnailPath = "projects/{$image->project_id}/images/thumbnails/{$size}/{$thumbnailFilename}";
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);

            // Create directory if it doesn't exist
            $thumbnailDir = dirname($fullThumbnailPath);
            if (! file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate thumbnail
            Image::load($originalPath)
                ->fit(Fit::Crop, $dimensions['width'], $dimensions['height'])
                ->format('webp')
                ->save($fullThumbnailPath);
        }

        // Set the medium thumbnail as the default thumbnail path
        $defaultThumbnailPath = "projects/{$image->project_id}/images/thumbnails/medium/{$image->uuid}_medium.webp";
        $image->update(['thumbnail_path' => $defaultThumbnailPath]);
    }

    /**
     * Extract dominant colors from the image.
     */
    protected function extractDominantColors(ProjectImage $image, string $originalPath): void
    {
        try {
            // Create a resized version for color analysis (faster processing)
            $tempPath = sys_get_temp_dir().'/color_analysis_'.$image->uuid.'.jpg';

            Image::load($originalPath)
                ->width(100)
                ->height(100)
                ->save($tempPath);

            // Simple color extraction (this is a basic implementation)
            $colors = $this->getImageColors($tempPath);

            $image->update(['dominant_colors' => $colors]);

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

        } catch (Exception $e) {
            Log::warning('Failed to extract colors from image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract color palette from image.
     *
     * @return array<string>
     */
    protected function getImageColors(string $imagePath): array
    {
        // Basic color extraction - get average colors from image quadrants
        $image = imagecreatefromjpeg($imagePath);
        if (! $image) {
            return [];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $colors = [];

        // Sample colors from different regions of the image
        $samplePoints = [
            ['x' => $width * 0.25, 'y' => $height * 0.25],
            ['x' => $width * 0.75, 'y' => $height * 0.25],
            ['x' => $width * 0.25, 'y' => $height * 0.75],
            ['x' => $width * 0.75, 'y' => $height * 0.75],
            ['x' => $width * 0.5, 'y' => $height * 0.5], // Center
        ];

        foreach ($samplePoints as $point) {
            $rgb = imagecolorat($image, (int) $point['x'], (int) $point['y']);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $colors[] = sprintf('#%02x%02x%02x', $r, $g, $b);
        }

        imagedestroy($image);

        return array_unique($colors);
    }

    /**
     * Optimize image file size while maintaining quality.
     */
    public function optimizeImage(string $imagePath): bool
    {
        try {
            Image::load($imagePath)
                ->quality(85)
                ->optimize()
                ->save();

            return true;
        } catch (Exception $e) {
            Log::warning('Failed to optimize image', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
