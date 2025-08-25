<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * File Management Service for Logo Generation System.
 *
 * Handles file storage, organization, cleanup, optimization, and security
 * for generated logos and their variants.
 */
final class FileManagementService
{
    /**
     * Maximum file size in bytes (10MB).
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed file types for uploads.
     *
     * @var array<string>
     */
    private const ALLOWED_TYPES = [
        'image/png',
        'image/jpeg',
        'image/svg+xml',
    ];

    /**
     * Image dimension limits.
     */
    private const MIN_WIDTH = 50;

    private const MIN_HEIGHT = 50;

    private const MAX_WIDTH = 2048;

    private const MAX_HEIGHT = 2048;

    /**
     * Create directory structure for logo generation.
     *
     * @return array<string, string>
     */
    public function createDirectoryStructure(int $logoGenerationId): array
    {
        $basePath = "logos/{$logoGenerationId}";
        $directories = [
            'originals' => "{$basePath}/originals",
            'customized' => "{$basePath}/customized",
            'temp' => "{$basePath}/temp",
        ];

        foreach ($directories as $path) {
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
        }

        Log::info('Created directory structure for logo generation', [
            'logo_generation_id' => $logoGenerationId,
            'directories' => array_values($directories),
        ]);

        return $directories;
    }

    /**
     * Store original logo file.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $generatedLogo
     * @return array<string, mixed>
     */
    public function storeOriginalLogo(GeneratedLogo $generatedLogo, UploadedFile $file): array
    {
        if (! $this->validateFile($file)) {
            return [
                'success' => false,
                'error' => 'File validation failed',
            ];
        }

        try {
            $filename = $this->generateOriginalFilename($generatedLogo, $file->getClientOriginalExtension());
            $filePath = "logos/{$generatedLogo->logo_generation_id}/originals/{$filename}";

            // Optimize file before storing
            $optimizedContent = $this->optimizeFile($file);
            Storage::disk('public')->put($filePath, $optimizedContent);

            $fileSize = Storage::disk('public')->size($filePath);

            Log::info('Stored original logo file', [
                'logo_id' => $generatedLogo->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to store original logo file', [
                'logo_id' => $generatedLogo->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store customized logo file with color scheme.
     *
     * @param  LogoColorVariant<\Database\Factories\LogoColorVariantFactory>  $colorVariant
     * @return array<string, mixed>
     */
    public function storeCustomizedLogo(LogoColorVariant $colorVariant, UploadedFile $file): array
    {
        if (! $this->validateFile($file)) {
            return [
                'success' => false,
                'error' => 'File validation failed',
            ];
        }

        try {
            $filename = $this->generateCustomizedFilename($colorVariant, $file->getClientOriginalExtension());
            $filePath = "logos/{$colorVariant->generatedLogo->logo_generation_id}/customized/{$filename}";

            // Optimize file before storing
            $optimizedContent = $this->optimizeFile($file);
            Storage::disk('public')->put($filePath, $optimizedContent);

            $fileSize = Storage::disk('public')->size($filePath);

            Log::info('Stored customized logo file', [
                'color_variant_id' => $colorVariant->id,
                'color_scheme' => $colorVariant->color_scheme,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to store customized logo file', [
                'color_variant_id' => $colorVariant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store temporary file for processing.
     *
     * @return array<string, mixed>
     */
    public function storeTempFile(int $logoGenerationId, UploadedFile $file, string $purpose): array
    {
        try {
            $tempId = strtolower(Str::random(16));
            $extension = $file->getClientOriginalExtension();
            $filename = "{$purpose}_{$tempId}.{$extension}";
            $filePath = "logos/{$logoGenerationId}/temp/{$filename}";

            Storage::disk('public')->put($filePath, $file->getContent());

            Log::info('Stored temporary file', [
                'logo_generation_id' => $logoGenerationId,
                'purpose' => $purpose,
                'temp_id' => $tempId,
                'file_path' => $filePath,
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'temp_id' => $tempId,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to store temporary file', [
                'logo_generation_id' => $logoGenerationId,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate filename for original logo.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $generatedLogo
     */
    public function generateOriginalFilename(GeneratedLogo $generatedLogo, string $extension): string
    {
        $businessName = $this->sanitizeBusinessName($generatedLogo->logoGeneration->business_name);
        $randomId = strtolower(Str::random(8));

        return sprintf(
            '%s_%s_v%d_%s.%s',
            $businessName,
            $generatedLogo->style,
            $generatedLogo->variation_number,
            $randomId,
            $extension
        );
    }

    /**
     * Generate filename for customized logo with color scheme.
     *
     * @param  LogoColorVariant<\Database\Factories\LogoColorVariantFactory>  $colorVariant
     */
    public function generateCustomizedFilename(LogoColorVariant $colorVariant, string $extension): string
    {
        $businessName = $this->sanitizeBusinessName($colorVariant->generatedLogo->logoGeneration->business_name);
        $randomId = strtolower(Str::random(8));

        return sprintf(
            '%s_%s_v%d_%s_%s.%s',
            $businessName,
            $colorVariant->generatedLogo->style,
            $colorVariant->generatedLogo->variation_number,
            $colorVariant->color_scheme,
            $randomId,
            $extension
        );
    }

    /**
     * Get file organization structure for a logo generation.
     *
     * @return array<string, string>
     */
    public function getFileOrganization(int $logoGenerationId): array
    {
        $logoGeneration = LogoGeneration::find($logoGenerationId);
        $businessSlug = $logoGeneration ? $this->sanitizeBusinessName($logoGeneration->business_name) : 'unknown';

        return [
            'base_path' => "logos/{$logoGenerationId}",
            'date_prefix' => now()->format('Y-m-d'),
            'business_slug' => $businessSlug,
        ];
    }

    /**
     * List all files for a logo generation.
     *
     * @return array<string, array<string>>
     */
    public function listAllFiles(int $logoGenerationId): array
    {
        $basePath = "logos/{$logoGenerationId}";

        return [
            'originals' => Storage::disk('public')->files("{$basePath}/originals") ?: [],
            'customized' => Storage::disk('public')->files("{$basePath}/customized") ?: [],
            'temp' => Storage::disk('public')->files("{$basePath}/temp") ?: [],
        ];
    }

    /**
     * Calculate storage usage for a logo generation.
     *
     * @return array<string, mixed>
     */
    public function calculateStorageUsage(int $logoGenerationId): array
    {
        $files = $this->listAllFiles($logoGenerationId);
        $usage = [
            'originals_bytes' => 0,
            'customized_bytes' => 0,
            'temp_bytes' => 0,
        ];

        foreach ($files as $type => $fileList) {
            foreach ($fileList as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    $usage["{$type}_bytes"] += Storage::disk('public')->size($filePath);
                }
            }
        }

        $totalBytes = array_sum($usage);

        return array_merge($usage, [
            'total_bytes' => $totalBytes,
            'formatted_size' => $this->formatBytes($totalBytes),
        ]);
    }

    /**
     * Clean up temporary files older than specified hours.
     */
    public function cleanupTempFiles(int $logoGenerationId, int $maxAgeHours = 24): int
    {
        $tempPath = "logos/{$logoGenerationId}/temp";
        $files = Storage::disk('public')->files($tempPath);
        $cleanedCount = 0;

        foreach ($files as $filePath) {
            $shouldDelete = false;

            if ($maxAgeHours === 0) {
                // Clean all temp files regardless of age
                $shouldDelete = true;
            } else {
                // Clean temp files older than specified age
                $cutoffTime = time() - ($maxAgeHours * 3600);
                $fullPath = Storage::disk('public')->path($filePath);
                $shouldDelete = file_exists($fullPath) && filemtime($fullPath) < $cutoffTime;
            }

            if ($shouldDelete) {
                Storage::disk('public')->delete($filePath);
                $cleanedCount++;
            }
        }

        if ($cleanedCount > 0) {
            Log::info('Cleaned up temporary files', [
                'logo_generation_id' => $logoGenerationId,
                'cleaned_count' => $cleanedCount,
                'max_age_hours' => $maxAgeHours,
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Delete entire logo generation directory.
     *
     * @return array<string, mixed>
     */
    public function deleteLogoGeneration(int $logoGenerationId): array
    {
        try {
            $basePath = "logos/{$logoGenerationId}";
            $files = $this->listAllFiles($logoGenerationId);
            $deletedFiles = 0;

            // Count files before deletion
            foreach ($files as $fileList) {
                $deletedFiles += count($fileList);
            }

            // Delete the entire directory
            if (Storage::disk('public')->exists($basePath)) {
                Storage::disk('public')->deleteDirectory($basePath);
            }

            Log::info('Deleted logo generation directory', [
                'logo_generation_id' => $logoGenerationId,
                'deleted_files' => $deletedFiles,
            ]);

            return [
                'success' => true,
                'deleted_files' => $deletedFiles,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete logo generation directory', [
                'logo_generation_id' => $logoGenerationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up failed generation files.
     *
     * @param  LogoGeneration<\Database\Factories\LogoGenerationFactory>  $logoGeneration
     * @return array<string, mixed>
     */
    public function cleanupFailedGeneration(LogoGeneration $logoGeneration): array
    {
        if ($logoGeneration->status !== 'failed') {
            return [
                'success' => false,
                'error' => 'Logo generation is not marked as failed',
            ];
        }

        return $this->deleteLogoGeneration($logoGeneration->id);
    }

    /**
     * Find orphaned files (files without corresponding database records).
     *
     * @return array<string>
     */
    public function findOrphanedFiles(): array
    {
        $orphanedFiles = [];
        $logoDirectories = Storage::disk('public')->directories('logos');

        foreach ($logoDirectories as $directory) {
            // Extract logo generation ID from path
            $logoGenerationId = (int) basename($directory);

            // Check if logo generation exists in database
            if (! LogoGeneration::where('id', $logoGenerationId)->exists()) {
                $files = Storage::disk('public')->allFiles($directory);
                $orphanedFiles = array_merge($orphanedFiles, $files);
            }
        }

        return $orphanedFiles;
    }

    /**
     * Clean up orphaned files.
     */
    public function cleanupOrphanedFiles(): int
    {
        $orphanedFiles = $this->findOrphanedFiles();
        $cleanedCount = 0;

        foreach ($orphanedFiles as $filePath) {
            try {
                Storage::disk('public')->delete($filePath);
                $cleanedCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to delete orphaned file', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up empty directories
        $logoDirectories = Storage::disk('public')->directories('logos');
        foreach ($logoDirectories as $directory) {
            $logoGenerationId = (int) basename($directory);
            if (! LogoGeneration::where('id', $logoGenerationId)->exists()) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        }

        if ($cleanedCount > 0) {
            Log::info('Cleaned up orphaned files', [
                'cleaned_count' => $cleanedCount,
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Validate uploaded file.
     */
    public function validateFile(UploadedFile $file): bool
    {
        return $this->validateFileType($file, self::ALLOWED_TYPES) &&
               $this->validateFileSize($file, self::MAX_FILE_SIZE) &&
               $this->validateImageDimensions($file, self::MIN_WIDTH, self::MIN_HEIGHT, self::MAX_WIDTH, self::MAX_HEIGHT);
    }

    /**
     * Validate file type.
     *
     * @param  array<string>  $allowedTypes
     */
    public function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        return in_array($file->getMimeType(), $allowedTypes, true);
    }

    /**
     * Validate file size.
     */
    public function validateFileSize(UploadedFile $file, int $maxSizeBytes): bool
    {
        return $file->getSize() <= $maxSizeBytes;
    }

    /**
     * Validate image dimensions.
     */
    public function validateImageDimensions(UploadedFile $file, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight): bool
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return false;
        }

        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height] = $imageInfo;

        return $width >= $minWidth && $width <= $maxWidth &&
               $height >= $minHeight && $height <= $maxHeight;
    }

    /**
     * Sanitize SVG content by removing potentially harmful elements.
     */
    public function sanitizeSvgContent(UploadedFile $file): string
    {
        $content = $file->getContent();

        // Remove script tags and javascript
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        $content = preg_replace('/javascript:/i', '', (string) $content);
        $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/', '', (string) $content);

        // Remove comments and metadata
        $content = preg_replace('/<!--.*?-->/s', '', (string) $content);
        $content = preg_replace('/<metadata\b[^>]*>.*?<\/metadata>/si', '', (string) $content);

        return $content;
    }

    /**
     * Sanitize filename to prevent path traversal.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path separators and dangerous characters
        $filename = preg_replace('/[\/\\\\:*?"<>|]/', '-', $filename);
        $filename = preg_replace('/\.\.+/', '.', (string) $filename);
        $filename = trim((string) $filename, '.-');

        return $filename ?: 'sanitized-file';
    }

    /**
     * Optimize PNG image.
     *
     * @return array<string, mixed>
     */
    public function optimizePngImage(UploadedFile $file): array
    {
        $originalContent = $file->getContent();
        $originalSize = strlen($originalContent);

        // For now, return original content
        // In production, you might use libraries like pngquant or imagemin
        $optimizedContent = $originalContent;

        return [
            'success' => true,
            'optimized_content' => $optimizedContent,
            'original_size' => $originalSize,
            'optimized_size' => strlen($optimizedContent),
            'compression_ratio' => round((1 - strlen($optimizedContent) / $originalSize) * 100, 2),
        ];
    }

    /**
     * Optimize SVG image by removing unnecessary elements.
     *
     * @return array<string, mixed>
     */
    public function optimizeSvgImage(UploadedFile $file): array
    {
        $originalContent = $file->getContent();
        $originalSize = strlen($originalContent);

        $optimizedContent = $this->sanitizeSvgContent($file);

        return [
            'success' => true,
            'optimized_content' => $optimizedContent,
            'original_size' => $originalSize,
            'optimized_size' => strlen($optimizedContent),
            'compression_ratio' => round((1 - strlen($optimizedContent) / $originalSize) * 100, 2),
        ];
    }

    /**
     * Create web-optimized version of image.
     *
     * @return array<string, mixed>
     */
    public function createWebOptimizedVersion(UploadedFile $file, string $format = 'jpeg'): array
    {
        $originalContent = $file->getContent();
        $originalSize = strlen($originalContent);

        // For now, return original content
        // In production, you would use image processing libraries
        $optimizedContent = $originalContent;

        return [
            'success' => true,
            'optimized_content' => $optimizedContent,
            'format' => $format,
            'size_reduction' => round((1 - strlen($optimizedContent) / $originalSize) * 100, 2),
        ];
    }

    /**
     * Generate size variants for responsive display.
     *
     * @param  array<int>  $sizes
     * @return array<int, array<string, mixed>>
     */
    public function generateSizeVariants(UploadedFile $file, array $sizes): array
    {
        $variants = [];

        foreach ($sizes as $size) {
            // For now, return mock data
            // In production, you would use image processing libraries
            $variants[$size] = [
                'success' => true,
                'width' => $size,
                'height' => $size,
                'content' => $file->getContent(),
                'size' => $file->getSize(),
            ];
        }

        return $variants;
    }

    /**
     * Generate secure download URL with expiration.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $generatedLogo
     */
    public function generateSecureDownloadUrl(GeneratedLogo $generatedLogo, int $expiresInSeconds = 3600): string
    {
        return URL::temporarySignedRoute(
            'logos.download',
            now()->addSeconds($expiresInSeconds),
            [
                'logoGeneration' => $generatedLogo->logo_generation_id,
                'generatedLogo' => $generatedLogo->id,
            ]
        );
    }

    /**
     * Create ZIP archive for batch downloads.
     *
     * @return array<string, mixed>
     */
    public function createBatchDownloadZip(int $logoGenerationId): array
    {
        try {
            $files = $this->listAllFiles($logoGenerationId);
            $zipFileName = "logos_generation_{$logoGenerationId}_".now()->format('Y-m-d_H-i-s').'.zip';
            $zipPath = "temp/{$zipFileName}";

            // Ensure temp directory exists
            if (! Storage::disk('public')->exists('temp')) {
                Storage::disk('public')->makeDirectory('temp');
            }

            $zipFullPath = Storage::disk('public')->path($zipPath);

            $zip = new ZipArchive;
            if ($zip->open($zipFullPath, ZipArchive::CREATE) !== true) {
                throw new \Exception('Cannot create ZIP file');
            }

            $fileCount = 0;
            foreach ($files['originals'] as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    $zip->addFile(
                        Storage::disk('public')->path($filePath),
                        'originals/'.basename($filePath)
                    );
                    $fileCount++;
                }
            }

            foreach ($files['customized'] as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    $zip->addFile(
                        Storage::disk('public')->path($filePath),
                        'customized/'.basename($filePath)
                    );
                    $fileCount++;
                }
            }

            $zip->close();

            $zipSize = filesize($zipFullPath);

            return [
                'success' => true,
                'zip_path' => $zipPath,
                'file_count' => $fileCount,
                'zip_size' => $zipSize,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create batch download ZIP', [
                'logo_generation_id' => $logoGenerationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track download statistics.
     *
     * @param  GeneratedLogo<\Database\Factories\GeneratedLogoFactory>  $generatedLogo
     */
    public function trackDownload(GeneratedLogo $generatedLogo, string $format, string $ipAddress): void
    {
        $key = "downloads:logo:{$generatedLogo->id}";
        $stats = Cache::get($key, [
            'total' => 0,
            'formats' => [],
            'last_download' => null,
        ]);

        $stats['total']++;
        $stats['formats'][$format] = ($stats['formats'][$format] ?? 0) + 1;
        $stats['last_download'] = now()->toISOString();

        Cache::put($key, $stats, now()->addDays(30));

        Log::info('Download tracked', [
            'logo_id' => $generatedLogo->id,
            'format' => $format,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Get download statistics.
     *
     * @return array<string, mixed>
     */
    public function getDownloadStats(int $logoGenerationId): array
    {
        $generatedLogos = GeneratedLogo::where('logo_generation_id', $logoGenerationId)->get();
        $totalDownloads = 0;
        $formatBreakdown = [];
        $popularLogos = [];

        foreach ($generatedLogos as $logo) {
            $key = "downloads:logo:{$logo->id}";
            $stats = Cache::get($key, ['total' => 0, 'formats' => []]);

            $totalDownloads += $stats['total'];

            foreach ($stats['formats'] as $format => $count) {
                $formatBreakdown[$format] = ($formatBreakdown[$format] ?? 0) + $count;
            }

            if ($stats['total'] > 0) {
                $popularLogos[] = [
                    'logo_id' => $logo->id,
                    'downloads' => $stats['total'],
                    'style' => $logo->style,
                ];
            }
        }

        // Sort popular logos by download count
        usort($popularLogos, fn ($a, $b) => $b['downloads'] - $a['downloads']);

        return [
            'total_downloads' => $totalDownloads,
            'format_breakdown' => $formatBreakdown,
            'popular_logos' => array_slice($popularLogos, 0, 10),
        ];
    }

    /**
     * Optimize file content before storage.
     */
    private function optimizeFile(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        return match ($mimeType) {
            'image/svg+xml' => $this->optimizeSvgImage($file)['optimized_content'],
            'image/png' => $this->optimizePngImage($file)['optimized_content'],
            default => $file->getContent(),
        };
    }

    /**
     * Sanitize business name for use in filenames.
     */
    private function sanitizeBusinessName(string $businessName): string
    {
        $sanitized = Str::slug($businessName, '-');
        $sanitized = Str::limit($sanitized, 30, '');

        return $sanitized ?: 'business';
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1).' '.$units[$pow];
    }
}
