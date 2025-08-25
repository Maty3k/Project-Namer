<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ExportGenerationException;
use App\Models\Export;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ExportService handles all business logic for creating and managing exports.
 *
 * Provides functionality for generating PDF, CSV, and JSON exports with
 * memory management, file cleanup, and secure download serving.
 */
final class ExportService
{
    private const MAX_MEMORY_LIMIT = 10 * 1024 * 1024; // 10MB

    private const SUPPORTED_FORMATS = ['pdf', 'csv', 'json'];

    private const DEFAULT_EXPIRES_DAYS = 7;

    /**
     * Create a new export with validation and file generation.
     *
     * @param  array<string, mixed>  $exportData
     * @return Export<\Database\Factories\ExportFactory>
     *
     * @throws ValidationException
     * @throws ExportGenerationException
     */
    public function createExport(User $user, array $exportData): Export
    {
        $validated = $this->validateExportData($exportData);

        // Create export record first
        $export = Export::create([
            'user_id' => $user->id,
            'exportable_type' => $validated['exportable_type'],
            'exportable_id' => $validated['exportable_id'],
            'export_type' => $validated['export_type'],
            'settings' => $validated['settings'] ?? [],
            'expires_at' => $this->calculateExpirationDate($validated),
        ]);

        try {
            // Generate the actual file
            $this->generateExportFile($export, $validated);

            // Update file information
            $export->update([
                'file_size' => Storage::size($export->file_path),
                'is_ready' => true,
            ]);

            return $export;
        } catch (\Exception $e) {
            // Clean up failed export
            $export->delete();
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }

            throw new ExportGenerationException("Failed to generate {$validated['export_type']} export: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Serve export file as download with proper headers and tracking.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     */
    public function serveDownload(Export $export): StreamedResponse
    {
        if (! $export->fileExists()) {
            throw new \RuntimeException('Export file not found');
        }

        // Update download count
        $export->increment('download_count');
        $export->update(['last_downloaded_at' => now()]);

        $filename = $export->generateFilename();
        $mimeType = $this->getMimeType($export->export_type);

        return Storage::download($export->file_path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Clean up expired exports and their files.
     */
    public function cleanupExpiredExports(): int
    {
        $expiredExports = Export::where('expires_at', '<', now())->get();
        $deletedCount = 0;

        foreach ($expiredExports as $export) {
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }
            $export->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Get user's export history with statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserExports(User $user): array
    {
        $exports = Export::where('user_id', $user->id)->get();

        return [
            'total' => $exports->count(),
            'by_type' => $exports->groupBy('export_type')->map->count(),
            'total_downloads' => $exports->sum('download_count'),
            'total_size' => $exports->sum('file_size'),
        ];
    }

    /**
     * Get export analytics for a user.
     *
     * @return array<string, mixed>
     */
    public function getExportAnalytics(User $user): array
    {
        $exports = Export::where('user_id', $user->id)->get();

        return [
            'total_exports' => $exports->count(),
            'total_downloads' => $exports->sum('download_count'),
            'popular_formats' => $exports->groupBy('export_type')
                ->map->count()
                ->sortDesc()
                ->take(5),
            'recent_activity' => $exports->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Generate the actual export file based on type.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     * @param  array<string, mixed>  $validated
     */
    private function generateExportFile(Export $export, array $validated): void
    {
        $exportable = $export->exportable;

        switch ($export->export_type) {
            case 'pdf':
                $this->generatePdfExport($export, $exportable, $validated);
                break;
            case 'csv':
                $this->generateCsvExport($export, $exportable, $validated);
                break;
            case 'json':
                $this->generateJsonExport($export, $exportable, $validated);
                break;
        }
    }

    /**
     * Generate PDF export using DomPDF.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     * @param  mixed  $exportable
     * @param  array<string, mixed>  $validated
     */
    private function generatePdfExport(Export $export, $exportable, array $validated): void
    {
        $data = [
            'exportable' => $exportable,
            'export' => $export,
            'settings' => $validated['settings'] ?? [],
            'include_domains' => $validated['include_domains'] ?? false,
            'include_metadata' => $validated['include_metadata'] ?? false,
            'include_branding' => $validated['include_branding'] ?? false,
            'template' => $validated['template'] ?? 'default',
        ];

        // Load related data efficiently
        if (method_exists($exportable, 'generatedLogos') && ($validated['include_logos'] ?? false)) {
            /** @phpstan-ignore-next-line - Dynamic relationship loading based on method existence */
            $exportable->load('generatedLogos.colorVariants');
        }

        $pdf = Pdf::loadView('exports.pdf.template', $data);

        // Set paper and margins based on template
        if (($validated['template'] ?? 'default') === 'professional') {
            $pdf->setPaper('A4', 'portrait')
                ->setOptions(['defaultFont' => 'sans-serif']);
        }

        $export->file_path = "exports/{$export->id}.pdf";
        Storage::put($export->file_path, $pdf->output());
    }

    /**
     * Generate CSV export with proper formatting.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     * @param  mixed  $exportable
     * @param  array<string, mixed>  $validated
     */
    private function generateCsvExport(Export $export, $exportable, array $validated): void
    {
        $headers = ['Business Name', 'Description', 'Status', 'Created At'];

        if ($validated['include_domains'] ?? false) {
            $headers = array_merge($headers, ['Domain Available', 'Domain Checked']);
        }

        $rows = [$headers];

        // Add main record
        $row = [
            $exportable->business_name ?? 'N/A',
            $exportable->business_description ?? 'N/A',
            $exportable->status ?? 'N/A',
            $exportable->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
        ];

        if ($validated['include_domains'] ?? false) {
            $row[] = $exportable->domain_available ? 'Yes' : 'No';
            $row[] = $exportable->domain_checked_at?->format('Y-m-d H:i:s') ?? 'N/A';
        }

        $rows[] = $row;

        // Convert to CSV format
        $csvContent = '';
        foreach ($rows as $row) {
            $csvContent .= '"'.implode('","', $row).'"'."\n";
        }

        $export->file_path = "exports/{$export->id}.csv";
        Storage::put($export->file_path, $csvContent);
    }

    /**
     * Generate JSON export with complete data structure.
     *
     * @param  Export<\Database\Factories\ExportFactory>  $export
     * @param  mixed  $exportable
     * @param  array<string, mixed>  $validated
     */
    private function generateJsonExport(Export $export, $exportable, array $validated): void
    {
        $initialMemory = memory_get_usage();

        $data = [
            'logo_generation' => [
                'id' => $exportable->id,
                'business_name' => $exportable->business_name,
                'business_description' => $exportable->business_description,
                'status' => $exportable->status,
                'created_at' => $exportable->created_at?->toISOString(),
                'updated_at' => $exportable->updated_at?->toISOString(),
            ],
        ];

        if ($validated['include_metadata'] ?? false) {
            $data['export_metadata'] = [
                'exported_at' => now()->toISOString(),
                'exported_by' => $export->user->name ?? 'Unknown',
                'export_version' => '1.0',
                'total_records' => 1,
            ];
        }

        if (($validated['include_logos'] ?? false) && method_exists($exportable, 'generatedLogos')) {
            // Use chunking for memory efficiency with large datasets
            $logos = [];
            $exportable->generatedLogos()->chunk(10, function ($logoChunk) use (&$logos): void {
                foreach ($logoChunk as $logo) {
                    $logos[] = [
                        'id' => $logo->id,
                        'style' => $logo->style,
                        'prompt' => $logo->prompt,
                        'original_url' => $logo->original_url,
                        'local_path' => $logo->local_path,
                        'created_at' => $logo->created_at?->toISOString(),
                    ];
                }
            });
            $data['generated_logos'] = $logos;
        }

        // Check memory usage
        $currentMemory = memory_get_usage();
        if ($currentMemory - $initialMemory > self::MAX_MEMORY_LIMIT) {
            throw new \RuntimeException('Export exceeds memory limit');
        }

        $export->file_path = "exports/{$export->id}.json";
        Storage::put($export->file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Validate export data before creation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateExportData(array $data): array
    {
        $validator = Validator::make($data, [
            'exportable_type' => ['required', 'string'],
            'exportable_id' => ['required', 'integer', 'exists:logo_generations,id'],
            'export_type' => ['required', Rule::in(self::SUPPORTED_FORMATS)],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'include_domains' => ['sometimes', 'boolean'],
            'include_metadata' => ['sometimes', 'boolean'],
            'include_logos' => ['sometimes', 'boolean'],
            'include_branding' => ['sometimes', 'boolean'],
            'template' => ['sometimes', 'string', Rule::in(['default', 'professional'])],
            'settings' => ['sometimes', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Calculate expiration date based on settings.
     *
     * @param  array<string, mixed>  $validated
     */
    private function calculateExpirationDate(array $validated): \Illuminate\Support\Carbon
    {
        $days = $validated['expires_in_days'] ?? self::DEFAULT_EXPIRES_DAYS;

        return now()->addDays($days);
    }

    /**
     * Get MIME type for export format.
     */
    private function getMimeType(string $exportType): string
    {
        return match ($exportType) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }
}
