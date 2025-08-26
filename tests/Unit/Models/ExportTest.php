<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('Export Model', function (): void {
    it('creates an export with required attributes', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create();

        $export = Export::create([
            'uuid' => Str::uuid()->toString(),
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $logoGeneration->id,
            'user_id' => $user->id,
            'export_type' => 'pdf',
            'file_path' => 'exports/test.pdf',
            'file_size' => 1024,
        ]);

        expect($export)->toBeInstanceOf(Export::class);
        expect($export->uuid)->not->toBeNull();
        expect($export->exportable_type)->toBe(LogoGeneration::class);
        expect($export->exportable_id)->toBe($logoGeneration->id);
        expect($export->user_id)->toBe($user->id);
        expect($export->export_type)->toBe('pdf');
        expect($export->file_path)->toBe('exports/test.pdf');
        expect($export->file_size)->toBe(1024);
        expect($export->download_count)->toBe(0);
    });

    it('generates UUID automatically if not provided', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $export = Export::create([
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $logoGeneration->id,
            'user_id' => $user->id,
            'export_type' => 'csv',
        ]);

        expect($export->uuid)->not->toBeNull();
        expect(Str::isUuid($export->uuid))->toBeTrue();
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $export = Export::factory()->create([
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $logoGeneration->id,
            'user_id' => $user->id,
        ]);

        expect($export->user)->toBeInstanceOf(User::class);
        expect($export->user->id)->toBe($user->id);
    });

    it('has polymorphic relationship to exportable models', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $export = Export::factory()->create([
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $logoGeneration->id,
            'user_id' => $user->id,
        ]);

        expect($export->exportable)->toBeInstanceOf(LogoGeneration::class);
        expect($export->exportable->id)->toBe($logoGeneration->id);
    });

    it('provides download URL generation', function (): void {
        $export = Export::factory()->create();

        $url = $export->getDownloadUrl();

        expect($url)->toContain('/api/exports/');
        expect($url)->toContain($export->uuid);
        expect($url)->toContain('/download');
        expect($url)->toStartWith('http');
    });

    it('checks if export is expired', function (): void {
        $activeExport = Export::factory()->create(['expires_at' => null]);
        $expiredExport = Export::factory()->create(['expires_at' => now()->subDay()]);
        $futureExport = Export::factory()->create(['expires_at' => now()->addDay()]);

        expect($activeExport->isExpired())->toBeFalse();
        expect($expiredExport->isExpired())->toBeTrue();
        expect($futureExport->isExpired())->toBeFalse();
    });

    it('increments download count', function (): void {
        $export = Export::factory()->create(['download_count' => 3]);

        $export->incrementDownloadCount();

        expect($export->download_count)->toBe(4);
    });

    it('formats file size for display', function (): void {
        $smallExport = Export::factory()->create(['file_size' => 1024]); // 1KB
        $mediumExport = Export::factory()->create(['file_size' => 1048576]); // 1MB
        $largeExport = Export::factory()->create(['file_size' => 1073741824]); // 1GB

        expect($smallExport->getFormattedFileSize())->toBe('1024 B');
        expect($mediumExport->getFormattedFileSize())->toBe('1024 KB');
        expect($largeExport->getFormattedFileSize())->toBe('1024 MB');
    });

    it('handles null file size gracefully', function (): void {
        $export = Export::factory()->create(['file_size' => null]);

        expect($export->getFormattedFileSize())->toBe('Unknown');
    });

    it('checks file existence', function (): void {
        Storage::fake('local');

        $existingExport = Export::factory()->create(['file_path' => 'exports/existing.pdf']);
        $missingExport = Export::factory()->create(['file_path' => 'exports/missing.pdf']);

        Storage::put('exports/existing.pdf', 'test content');

        expect($existingExport->fileExists())->toBeTrue();
        expect($missingExport->fileExists())->toBeFalse();
    });

    it('deletes associated file when export is deleted', function (): void {
        Storage::fake('local');

        $export = Export::factory()->create(['file_path' => 'exports/test.pdf']);
        Storage::put('exports/test.pdf', 'test content');

        expect(Storage::exists('exports/test.pdf'))->toBeTrue();

        $export->delete();

        expect(Storage::exists('exports/test.pdf'))->toBeFalse();
    });

    it('scopes to non-expired exports', function (): void {
        Export::factory()->create(['expires_at' => null]);
        Export::factory()->create(['expires_at' => now()->addDay()]);
        Export::factory()->create(['expires_at' => now()->subDay()]);

        $activeExports = Export::active()->get();

        expect($activeExports)->toHaveCount(2);
    });

    it('scopes by export type', function (): void {
        Export::factory()->create(['export_type' => 'pdf']);
        Export::factory()->create(['export_type' => 'csv']);
        Export::factory()->create(['export_type' => 'json']);
        Export::factory()->create(['export_type' => 'pdf']);

        $pdfExports = Export::ofType('pdf')->get();
        $csvExports = Export::ofType('csv')->get();

        expect($pdfExports)->toHaveCount(2);
        expect($csvExports)->toHaveCount(1);
    });

    it('validates export type enum values', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        // Valid export types
        $validTypes = ['pdf', 'csv', 'json'];

        foreach ($validTypes as $type) {
            $export = Export::create([
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $logoGeneration->id,
                'user_id' => $user->id,
                'export_type' => $type,
            ]);

            expect($export->export_type)->toBe($type);
        }
    });

    it('provides content type based on export type', function (): void {
        $pdfExport = Export::factory()->create(['export_type' => 'pdf']);
        $csvExport = Export::factory()->create(['export_type' => 'csv']);
        $jsonExport = Export::factory()->create(['export_type' => 'json']);

        expect($pdfExport->getContentType())->toBe('application/pdf');
        expect($csvExport->getContentType())->toBe('text/csv');
        expect($jsonExport->getContentType())->toBe('application/json');
    });

    it('generates appropriate filename based on export type', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $user->id,
            'business_name' => 'minimalist-company',
        ]);

        $pdfExport = Export::factory()->create([
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $logoGeneration->id,
            'export_type' => 'pdf',
            'user_id' => $user->id,
        ]);

        $filename = $pdfExport->generateFilename();

        expect($filename)->toContain('minimalist-company');
        expect($filename)->toEndWith('.pdf');
        expect($filename)->toMatch('/^\d{4}-\d{2}-\d{2}_/'); // Date prefix
    });

    it('has proper fillable attributes', function (): void {
        $fillable = [
            'uuid', 'exportable_type', 'exportable_id', 'user_id', 'export_type',
            'file_path', 'file_size', 'expires_at', 'last_downloaded_at',
        ];

        $export = new Export;

        expect($export->getFillable())->toBe($fillable);
    });
});
