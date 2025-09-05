<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\LogoGenerationException;
use App\Http\Controllers\Controller;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\UploadedLogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Logo Download Controller.
 *
 * Handles individual and batch logo file downloads.
 */
class LogoDownloadController extends Controller
{
    /**
     * Download an individual logo file.
     */
    public function download(Request $request, LogoGeneration $logoGeneration, GeneratedLogo $generatedLogo): Response|JsonResponse
    {
        // Verify the logo belongs to the generation
        if ($generatedLogo->logo_generation_id !== $logoGeneration->id) {
            throw LogoGenerationException::fileNotFound();
        }

        $colorScheme = $request->query('color_scheme');

        // If color scheme specified, get customized version
        if ($colorScheme) {
            $colorVariant = $generatedLogo->colorVariants()
                ->where('color_scheme', $colorScheme)
                ->first();

            if (! $colorVariant) {
                throw LogoGenerationException::fileNotFound();
            }

            if (! Storage::disk('public')->exists($colorVariant->file_path)) {
                throw LogoGenerationException::fileNotFound();
            }

            $content = Storage::disk('public')->get($colorVariant->file_path);
            $filename = $generatedLogo->generateDownloadFilename($colorScheme);
            $mimeType = $this->getMimeType($colorVariant->file_path);

            return response($content)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        // Return original logo
        if (! $generatedLogo->original_file_path || ! Storage::disk('public')->exists($generatedLogo->original_file_path)) {
            throw LogoGenerationException::fileNotFound();
        }

        $content = Storage::disk('public')->get($generatedLogo->original_file_path);
        $filename = $generatedLogo->generateDownloadFilename();
        $mimeType = $this->getMimeType($generatedLogo->original_file_path);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download all logos as a ZIP file.
     */
    public function downloadBatch(Request $request, LogoGeneration $logoGeneration): JsonResponse|StreamedResponse
    {
        $colorScheme = $request->query('color_scheme');

        // Get logos based on whether color scheme is specified
        if ($colorScheme) {
            $logos = $logoGeneration->generatedLogos()
                ->whereHas('colorVariants', function ($query) use ($colorScheme): void {
                    $query->where('color_scheme', $colorScheme);
                })
                ->with(['colorVariants' => function ($query) use ($colorScheme): void {
                    $query->where('color_scheme', $colorScheme);
                }])
                ->get();
        } else {
            $logos = $logoGeneration->generatedLogos()->get();
        }

        if ($logos->isEmpty()) {
            return response()->json([
                'message' => 'No logos available for download',
            ], 400);
        }

        // Create temporary ZIP file
        $zipPath = storage_path('app/temp/'.uniqid('logos_', true).'.zip');
        $zip = new ZipArchive;

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw LogoGenerationException::downloadFailed();
        }

        // Add logos to ZIP
        foreach ($logos as $logo) {
            if ($colorScheme && $logo->colorVariants->isNotEmpty()) {
                // Add customized version
                $variant = $logo->colorVariants->first();
                $filePath = $variant->file_path;
                $filename = $logo->generateDownloadFilename($colorScheme);
            } else {
                // Add original version
                $filePath = $logo->original_file_path;
                $filename = $logo->generateDownloadFilename();
            }

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                $content = Storage::disk('public')->get($filePath);
                $zip->addFromString($filename, $content);
            }
        }

        $zip->close();

        // Generate ZIP filename
        $businessName = \Str::slug($logoGeneration->business_name, '-');
        $zipFilename = $colorScheme
            ? "{$businessName}-logos-{$colorScheme}.zip"
            : "{$businessName}-logos.zip";

        // Stream the ZIP file and clean up
        return response()->streamDownload(
            function () use ($zipPath): void {
                readfile($zipPath);
                unlink($zipPath); // Clean up temporary file
            },
            $zipFilename,
            [
                'Content-Type' => 'application/zip',
            ]
        );
    }

    /**
     * Download an uploaded logo file.
     */
    public function downloadUploaded(UploadedLogo $uploadedLogo): Response|JsonResponse
    {
        // Verify the uploaded logo belongs to the current session (for security)
        if ($uploadedLogo->session_id !== session()->getId()) {
            return response()->json([
                'message' => 'Unauthorized access to logo file',
            ], 403);
        }

        if (! $uploadedLogo->file_path || ! Storage::disk('public')->exists($uploadedLogo->file_path)) {
            return response()->json([
                'message' => 'Logo file not found',
            ], 404);
        }

        $content = Storage::disk('public')->get($uploadedLogo->file_path);
        $filename = $uploadedLogo->generateDownloadFilename();
        $mimeType = $this->getMimeType($uploadedLogo->file_path);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get MIME type based on file extension.
     */
    private function getMimeType(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
