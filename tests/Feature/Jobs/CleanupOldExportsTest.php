<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

describe('CleanupOldExports Job', function (): void {
    it('deletes expired exports', function (): void {
        $expiredExport = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/expired-test.pdf',
        ]);

        Storage::put($expiredExport->file_path, 'PDF content');

        $activeExport = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => now()->addDays(7),
            'file_path' => 'exports/active-test.pdf',
        ]);

        Storage::put($activeExport->file_path, 'PDF content');

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        expect(Export::find($expiredExport->id))->toBeNull();
        expect(Export::find($activeExport->id))->not->toBeNull();
        expect(Storage::exists($expiredExport->file_path))->toBeFalse();
        expect(Storage::exists($activeExport->file_path))->toBeTrue();
    });

    it('deletes old exports without expiration after 90 days', function (): void {
        $oldExport = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => null,
            'file_path' => 'exports/old-test.pdf',
            'created_at' => now()->subDays(91),
        ]);

        Storage::put($oldExport->file_path, 'PDF content');

        $recentExport = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => null,
            'file_path' => 'exports/recent-test.pdf',
            'created_at' => now()->subDays(89),
        ]);

        Storage::put($recentExport->file_path, 'PDF content');

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        expect(Export::find($oldExport->id))->toBeNull();
        expect(Export::find($recentExport->id))->not->toBeNull();
        expect(Storage::exists($oldExport->file_path))->toBeFalse();
        expect(Storage::exists($recentExport->file_path))->toBeTrue();
    });

    it('handles missing files gracefully', function (): void {
        $export = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/missing.pdf',
        ]);

        // File doesn't exist in storage

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        expect(Export::find($export->id))->toBeNull();
    });

    it('logs cleanup operations', function (): void {
        Log::spy();

        Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
        ]);

        Storage::put('exports/test.pdf', 'PDF content');

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Cleanup old exports completed'));
    });

    it('processes exports in batches', function (): void {
        $exports = Export::factory()->count(150)->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        foreach ($exports as $export) {
            Storage::put($export->file_path, 'PDF content');
        }

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        $remainingExports = Export::whereIn('id', $exports->pluck('id'))->count();
        expect($remainingExports)->toBe(0);
    });

    it('logs errors when file deletion fails', function (): void {
        Log::spy();

        $export = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
        ]);

        Storage::put($export->file_path, 'PDF content');

        // Make storage fail
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('delete')->andThrow(new \Exception('Deletion failed'));

        try {
            dispatch_sync(new \App\Jobs\CleanupOldExports);
        } catch (\Exception) {
            // Expected to catch the exception
        }

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains($message, 'Failed to delete export file'));
    });

    it('deletes database record even if file deletion fails', function (): void {
        $export = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
        ]);

        // File doesn't exist

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        expect(Export::find($export->id))->toBeNull();
    });

    it('counts deleted exports correctly', function (): void {
        Log::spy();

        Export::factory()->count(5)->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => str_contains($message, 'Cleanup old exports completed')
                && isset($context['deleted'])
                && $context['deleted'] === 5);
    });

    it('handles different export types', function (): void {
        $pdfExport = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'file_path' => 'exports/test.pdf',
        ]);

        $csvExport = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
            'file_path' => 'exports/test.csv',
        ]);

        $jsonExport = Export::factory()->expired()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
            'file_path' => 'exports/test.json',
        ]);

        Storage::put($pdfExport->file_path, 'PDF content');
        Storage::put($csvExport->file_path, 'CSV content');
        Storage::put($jsonExport->file_path, 'JSON content');

        dispatch_sync(new \App\Jobs\CleanupOldExports);

        expect(Export::find($pdfExport->id))->toBeNull();
        expect(Export::find($csvExport->id))->toBeNull();
        expect(Export::find($jsonExport->id))->toBeNull();
        expect(Storage::exists($pdfExport->file_path))->toBeFalse();
        expect(Storage::exists($csvExport->file_path))->toBeFalse();
        expect(Storage::exists($jsonExport->file_path))->toBeFalse();
    });
});
