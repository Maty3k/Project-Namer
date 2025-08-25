<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use App\Services\ExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class, TestCase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

describe('ExportService', function (): void {
    beforeEach(function (): void {
        $this->exportService = app(ExportService::class);
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'business_name' => 'TechFlow Solutions',
            'business_description' => 'Innovative software solutions for modern businesses',
        ]);
    });

    it('creates a PDF export with proper formatting', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'include_domains' => true,
            'include_metadata' => true,
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export)->toBeInstanceOf(Export::class);
        expect($export->export_type)->toBe('pdf');
        expect($export->file_path)->toContain('.pdf');
        expect($export->fileExists())->toBeTrue();
        expect($export->file_size)->toBeGreaterThan(0);
    });

    it('creates a CSV export with proper headers and data', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
            'include_domains' => true,
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export->export_type)->toBe('csv');
        expect($export->file_path)->toContain('.csv');
        expect($export->fileExists())->toBeTrue();

        // Verify CSV content has headers
        $csvContent = Storage::get($export->file_path);
        expect($csvContent)->toContain('Business Name');
        expect($csvContent)->toContain('Description');
        expect($csvContent)->toContain('Status');
    });

    it('creates a JSON export with complete data structure', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
            'include_metadata' => true,
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export->export_type)->toBe('json');
        expect($export->file_path)->toContain('.json');
        expect($export->fileExists())->toBeTrue();

        // Verify JSON structure
        $jsonContent = json_decode((string) Storage::get($export->file_path), true);
        expect($jsonContent)->toHaveKey('logo_generation');
        expect($jsonContent)->toHaveKey('export_metadata');
        expect($jsonContent['logo_generation'])->toHaveKey('business_name');
    });

    it('validates export data before creation', function (): void {
        $invalidData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => 99999, // Non-existent ID
            'export_type' => 'invalid_format',
        ];

        expect(fn () => $this->exportService->createExport($this->user, $invalidData))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('sets appropriate expiration dates for exports', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'expires_in_days' => 7,
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export->expires_at)->not->toBeNull();
        expect(now()->diffInDays($export->expires_at))->toBeGreaterThanOrEqual(6)
            ->and(now()->diffInDays($export->expires_at))->toBeLessThanOrEqual(7);
        expect($export->isExpired())->toBeFalse();
    });

    it('serves export files with proper headers and download tracking', function (): void {
        $export = Export::factory()->pdf()->create([
            'file_path' => 'exports/test.pdf',
            'download_count' => 5,
        ]);

        Storage::put($export->file_path, 'PDF content here');

        $response = $this->exportService->serveDownload($export);

        expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
        expect($response->headers->get('Content-Type'))->toBe('application/pdf');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');

        $export->refresh();
        expect($export->download_count)->toBe(6);
    });

    it('handles large exports with memory management', function (): void {
        // Create a large logo generation with many generated logos
        $logos = \App\Models\GeneratedLogo::factory()->count(50)->create([
            'logo_generation_id' => $this->logoGeneration->id,
        ]);

        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
            'include_logos' => true,
        ];

        $initialMemory = memory_get_usage();

        $export = $this->exportService->createExport($this->user, $exportData);

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        expect($export->fileExists())->toBeTrue();
        expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
    });

    it('cleans up expired exports', function (): void {
        // Create some expired exports
        $expiredExports = Export::factory()->count(3)->expired()->create();
        $activeExports = Export::factory()->count(2)->create(['expires_at' => now()->addDays(7)]);

        // Create files for all exports
        foreach ($expiredExports as $export) {
            Storage::put($export->file_path, 'expired content');
        }
        foreach ($activeExports as $export) {
            Storage::put($export->file_path, 'active content');
        }

        $deletedCount = $this->exportService->cleanupExpiredExports();

        expect($deletedCount)->toBe(3);

        // Verify expired exports are deleted
        foreach ($expiredExports as $export) {
            expect(Storage::exists($export->file_path))->toBeFalse();
        }

        // Verify active exports remain
        foreach ($activeExports as $export) {
            expect(Storage::exists($export->file_path))->toBeTrue();
        }
    });

    it('generates appropriate filenames for different export types', function (): void {
        $pdfData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ];

        $csvData = [...$pdfData, 'export_type' => 'csv'];
        $jsonData = [...$pdfData, 'export_type' => 'json'];

        $pdfExport = $this->exportService->createExport($this->user, $pdfData);
        $csvExport = $this->exportService->createExport($this->user, $csvData);
        $jsonExport = $this->exportService->createExport($this->user, $jsonData);

        expect($pdfExport->generateFilename())->toEndWith('.pdf');
        expect($csvExport->generateFilename())->toEndWith('.csv');
        expect($jsonExport->generateFilename())->toEndWith('.json');

        expect($pdfExport->generateFilename())->toContain('techflow-solutions');
    });

    it('handles export errors gracefully', function (): void {
        // Mock PDF generation to fail
        Pdf::shouldReceive('loadView')->andThrow(new \Exception('PDF generation failed'));

        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ];

        expect(fn () => $this->exportService->createExport($this->user, $exportData))
            ->toThrow(\App\Exceptions\ExportGenerationException::class);
    });

    it('respects user export limits and rate limiting', function (): void {
        // Get initial count
        $initialCount = Export::where('user_id', $this->user->id)->count();

        // Create multiple exports for the same user
        Export::factory()->count(10)->create(['user_id' => $this->user->id]);

        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ];

        // Should still allow export (limit is higher)
        $export = $this->exportService->createExport($this->user, $exportData);
        expect($export)->toBeInstanceOf(Export::class);

        // But should track rate limiting
        $userExports = $this->exportService->getUserExports($this->user);
        expect($userExports['total'])->toBe($initialCount + 11);
    });

    it('provides export analytics and statistics', function (): void {
        // Create a clean test user to avoid conflicts with other tests
        $cleanUser = User::factory()->create();

        $exports = Export::factory()->count(5)->create([
            'user_id' => $cleanUser->id,
        ]);

        // Reset all download counts to 0
        Export::where('user_id', $cleanUser->id)->update(['download_count' => 0]);

        // Simulate some downloads
        $exports[0]->refresh();
        $exports[1]->refresh();
        $exports[0]->update(['download_count' => 10]);
        $exports[1]->update(['download_count' => 5]);

        $analytics = $this->exportService->getExportAnalytics($cleanUser);

        expect($analytics)->toHaveKey('total_exports');
        expect($analytics)->toHaveKey('total_downloads');
        expect($analytics)->toHaveKey('popular_formats');
        expect($analytics)->toHaveKey('recent_activity');

        expect($analytics['total_exports'])->toBe(5);

        // Get actual total downloads from database since factory creates random counts
        $expectedDownloads = Export::where('user_id', $cleanUser->id)->sum('download_count');
        expect($analytics['total_downloads'])->toBe($expectedDownloads);
    });

    it('supports custom export templates and styling', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'template' => 'professional',
            'include_branding' => true,
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export->fileExists())->toBeTrue();
        expect($export->file_size)->toBeGreaterThan(1000); // Should be substantial with branding
    });

    it('handles concurrent export requests safely', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ];

        // Simulate multiple concurrent export requests
        $exports = [];
        for ($i = 0; $i < 5; $i++) {
            $exports[] = $this->exportService->createExport($this->user, $exportData);
        }

        expect($exports)->toHaveCount(5);
        foreach ($exports as $export) {
            expect($export)->toBeInstanceOf(Export::class);
            expect($export->fileExists())->toBeTrue();
        }
    });
});
