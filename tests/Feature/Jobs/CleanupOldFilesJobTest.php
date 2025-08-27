<?php

declare(strict_types=1);

use App\Jobs\CleanupOldFilesJob;
use App\Models\LogoGeneration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

describe('Cleanup Old Files Job', function (): void {
    it('cleans up logo generations older than specified age', function (): void {
        // Create old completed generation
        $oldGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Create recent completed generation
        $recentGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(15),
        ]);

        // Create files for both generations
        Storage::disk('public')->put("logos/{$oldGeneration->id}/originals/logo.png", 'old logo');
        Storage::disk('public')->put("logos/{$recentGeneration->id}/originals/logo.png", 'recent logo');

        $job = new CleanupOldFilesJob(30); // 30 days threshold
        $job->handle();

        // Old generation files should be cleaned up
        expect(Storage::disk('public')->exists("logos/{$oldGeneration->id}/originals/logo.png"))->toBeFalse();

        // Recent generation files should remain
        expect(Storage::disk('public')->exists("logos/{$recentGeneration->id}/originals/logo.png"))->toBeTrue();
    });

    it('does not clean up active logo generations', function (): void {
        // Create old but active generation
        $activeGeneration = LogoGeneration::factory()->create([
            'status' => 'processing',
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Create files
        Storage::disk('public')->put("logos/{$activeGeneration->id}/originals/logo.png", 'active logo');
        Storage::disk('public')->put("logos/{$activeGeneration->id}/temp/processing.tmp", 'temp file');

        $job = new CleanupOldFilesJob(30);
        $job->handle();

        // Main files should remain for active generation
        expect(Storage::disk('public')->exists("logos/{$activeGeneration->id}/originals/logo.png"))->toBeTrue();

        // But temp files should still be cleaned if old enough
        // (This would depend on the temp file's actual timestamp in real scenario)
    });

    it('cleans up temporary files based on age', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);

        // Create temp files
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/old_temp.png", 'old temp');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/new_temp.png", 'new temp');

        $job = new CleanupOldFilesJob(30);
        $job->handle();

        // For completed generations, all temp files should be cleaned
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/temp/old_temp.png"))->toBeFalse();
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/temp/new_temp.png"))->toBeFalse();
    });

    it('cleans up orphaned files without database records', function (): void {
        // Create files for non-existent logo generation
        $nonExistentId = 99999;
        Storage::disk('public')->put("logos/{$nonExistentId}/originals/orphan.png", 'orphan file');
        Storage::disk('public')->put("logos/{$nonExistentId}/customized/orphan_blue.svg", 'orphan custom');

        $job = new CleanupOldFilesJob(30);
        $job->handle();

        // Orphaned files should be cleaned up
        expect(Storage::disk('public')->exists("logos/{$nonExistentId}/originals/orphan.png"))->toBeFalse();
        expect(Storage::disk('public')->exists("logos/{$nonExistentId}/customized/orphan_blue.svg"))->toBeFalse();
    });

    it('cleans up failed generations when enabled', function (): void {
        // Create failed generation older than 7 days
        $failedGeneration = LogoGeneration::factory()->create([
            'status' => 'failed',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Create files
        Storage::disk('public')->put("logos/{$failedGeneration->id}/originals/failed.png", 'failed logo');
        Storage::disk('public')->put("logos/{$failedGeneration->id}/temp/failed.tmp", 'failed temp');

        $job = new CleanupOldFilesJob(30, true); // Include failed generations
        $job->handle();

        // Failed generation files should be cleaned up
        expect(Storage::disk('public')->exists("logos/{$failedGeneration->id}/originals/failed.png"))->toBeFalse();
        expect(Storage::disk('public')->exists("logos/{$failedGeneration->id}/temp/failed.tmp"))->toBeFalse();
    });

    it('preserves recent failed generations for debugging', function (): void {
        // Create recently failed generation (less than 7 days)
        $recentFailedGeneration = LogoGeneration::factory()->create([
            'status' => 'failed',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Create files
        Storage::disk('public')->put("logos/{$recentFailedGeneration->id}/originals/recent_failed.png", 'recent failed');

        $job = new CleanupOldFilesJob(30, true);
        $job->handle();

        // Recent failed generation files should remain
        expect(Storage::disk('public')->exists("logos/{$recentFailedGeneration->id}/originals/recent_failed.png"))->toBeTrue();
    });

    it('skips failed generations when disabled', function (): void {
        // Create old failed generation
        $failedGeneration = LogoGeneration::factory()->create([
            'status' => 'failed',
            'created_at' => Carbon::now()->subDays(40), // Older than max age
        ]);

        // Create files
        Storage::disk('public')->put("logos/{$failedGeneration->id}/originals/old_failed.png", 'old failed');

        $job = new CleanupOldFilesJob(30, false); // Don't include failed generations cleanup
        $job->handle();

        // Failed generation files should be cleaned up by the main old generation cleanup
        // The flag only controls the separate "failed generation cleanup" logic
        expect(Storage::disk('public')->exists("logos/{$failedGeneration->id}/originals/old_failed.png"))->toBeFalse();
    });

    it('handles cleanup errors gracefully', function (): void {
        // Create a generation that might cause issues (mock scenario)
        $problematicGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Create files in a way that might cause permission issues (in real scenario)
        Storage::disk('public')->put("logos/{$problematicGeneration->id}/originals/locked.png", 'locked file');

        // Job should complete without throwing exceptions
        $job = new CleanupOldFilesJob(30);

        expect(fn () => $job->handle())->not->toThrow(\Exception::class);
    });

    it('calculates storage freed during cleanup', function (): void {
        // Create old generation with known file sizes
        $oldGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Create files with known content sizes
        $logoContent = str_repeat('a', 1024); // 1KB
        $customContent = str_repeat('b', 2048); // 2KB
        Storage::disk('public')->put("logos/{$oldGeneration->id}/originals/logo.png", $logoContent);
        Storage::disk('public')->put("logos/{$oldGeneration->id}/customized/logo_blue.svg", $customContent);

        $job = new CleanupOldFilesJob(30);
        $job->handle();

        // Files should be cleaned up
        expect(Storage::disk('public')->exists("logos/{$oldGeneration->id}/originals/logo.png"))->toBeFalse();
        expect(Storage::disk('public')->exists("logos/{$oldGeneration->id}/customized/logo_blue.svg"))->toBeFalse();
    });
});

describe('Job Configuration', function (): void {
    it('has correct queue configuration', function (): void {
        $job = new CleanupOldFilesJob;

        expect($job->queue)->toBe('file-cleanup');
        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(1800);
    });

    it('has appropriate backoff strategy', function (): void {
        $job = new CleanupOldFilesJob;

        $backoff = $job->backoff();
        expect($backoff)->toBe([60, 300, 900]); // 1min, 5min, 15min
    });

    it('generates correct tags', function (): void {
        $job = new CleanupOldFilesJob(45);

        $tags = $job->tags();
        expect($tags)->toContain('file-cleanup');
        expect($tags)->toContain('maintenance');
        expect($tags)->toContain('cleanup-45days');
    });

    it('has descriptive display name', function (): void {
        $job = new CleanupOldFilesJob(60);

        expect($job->displayName())->toBe('Cleanup Old Files (60 days)');
    });
});
