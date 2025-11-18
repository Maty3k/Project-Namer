<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->exportService = app(ExportService::class);
});

describe('Export Performance Benchmarks', function (): void {
    it('creates exports efficiently in bulk', function (): void {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Create 100 exports
        for ($i = 0; $i < 100; $i++) {
            Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
            ]);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        // Performance expectations
        expect($executionTime)->toBeLessThan(5000); // Under 5 seconds
        expect($memoryUsed)->toBeLessThan(50); // Under 50MB
        expect(Export::count())->toBe(100);
    });

    it('retrieves user exports with pagination efficiently', function (): void {
        // Create 500 exports
        Export::factory()->count(500)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        // Paginate results
        $exports = Export::where('user_id', $this->user->id)
            ->with(['exportable', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(100); // Under 100ms
        expect($queryCount)->toBeLessThanOrEqual(4); // Efficient eager loading
        expect($exports->count())->toBe(15);
        expect($exports->total())->toBe(500);
    });

    it('filters exports by type efficiently', function (): void {
        // Create mixed export types
        Export::factory()->count(200)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        Export::factory()->count(150)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
        ]);

        Export::factory()->count(100)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
        ]);

        $startTime = microtime(true);

        // Filter by type
        $pdfExports = Export::where('user_id', $this->user->id)
            ->where('export_type', 'pdf')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(100); // Under 100ms
        expect($pdfExports->total())->toBe(200);
    });

    it('handles export deletion with file cleanup efficiently', function (): void {
        // Create 100 exports with files
        $exports = collect();
        for ($i = 0; $i < 100; $i++) {
            $export = Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'file_path' => "exports/test-{$i}.pdf",
            ]);
            Storage::put($export->file_path, 'PDF content');
            $exports->push($export);
        }

        $startTime = microtime(true);

        // Delete all exports
        foreach ($exports as $export) {
            $export->delete();
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(5000); // Under 5 seconds
        expect(Export::count())->toBe(0);

        // Verify files are deleted
        foreach ($exports as $export) {
            expect(Storage::exists($export->file_path))->toBeFalse();
        }
    });

    it('queries expired exports efficiently', function (): void {
        // Create mix of expired and active exports
        Export::factory()->count(300)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
        ]);

        Export::factory()->count(200)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => now()->addDays(7),
        ]);

        $startTime = microtime(true);

        // Query expired exports
        $expiredExports = Export::where('expires_at', '<', now())->count();

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(100); // Under 100ms
        expect($expiredExports)->toBe(300);
    });

    it('updates download counts efficiently under load', function (): void {
        $export = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'download_count' => 0,
        ]);

        $startTime = microtime(true);

        // Simulate 1000 downloads
        for ($i = 0; $i < 1000; $i++) {
            $export->increment('download_count');
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $export->refresh();

        // Performance expectations
        expect($executionTime)->toBeLessThan(10000); // Under 10 seconds
        expect($export->download_count)->toBe(1000);
    });

    it('handles concurrent export creation without UUID conflicts', function (): void {
        $startTime = microtime(true);

        // Simulate concurrent export creation
        $exports = collect();
        for ($i = 0; $i < 100; $i++) {
            $exportData = [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => 'pdf',
            ];

            $export = $this->exportService->createExport($this->user, $exportData);
            $exports->push($export);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Verify all UUIDs are unique
        $uniqueUuids = $exports->pluck('uuid')->unique();

        // Performance expectations
        expect($executionTime)->toBeLessThan(20000); // Under 20 seconds (accounts for export generation overhead)
        expect($exports->count())->toBe(100);
        expect($uniqueUuids->count())->toBe(100); // All UUIDs must be unique
    });

    it('batch processes export cleanup efficiently', function (): void {
        // Create 1000 expired exports
        $exports = Export::factory()->count(1000)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
        ]);

        // Create files for each export
        foreach ($exports as $export) {
            Storage::put($export->file_path, 'Test content');
        }

        $startTime = microtime(true);
        $deletedCount = 0;

        // Batch process with chunkById
        Export::where('expires_at', '<', now())
            ->chunkById(100, function ($chunk) use (&$deletedCount): void {
                foreach ($chunk as $export) {
                    if (Storage::exists($export->file_path)) {
                        Storage::delete($export->file_path);
                    }
                    $export->delete();
                    $deletedCount++;
                }
            });

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(30000); // Under 30 seconds for 1000 items
        expect($deletedCount)->toBe(1000);
        expect(Export::count())->toBe(0);
    });

    it('aggregates export analytics efficiently', function (): void {
        // Create exports with various download counts
        for ($i = 0; $i < 1000; $i++) {
            Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'download_count' => fake()->numberBetween(0, 100),
            ]);
        }

        $startTime = microtime(true);

        // Get analytics
        $analytics = [
            'total_exports' => Export::where('user_id', $this->user->id)->count(),
            'total_downloads' => Export::where('user_id', $this->user->id)->sum('download_count'),
            'avg_downloads' => Export::where('user_id', $this->user->id)->avg('download_count'),
            'pdf_count' => Export::where('user_id', $this->user->id)->where('export_type', 'pdf')->count(),
            'csv_count' => Export::where('user_id', $this->user->id)->where('export_type', 'csv')->count(),
            'json_count' => Export::where('user_id', $this->user->id)->where('export_type', 'json')->count(),
        ];

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(500); // Under 500ms
        expect($analytics['total_exports'])->toBe(1000);
        expect($analytics['total_downloads'])->toBeGreaterThan(0);
    });

    it('searches exports efficiently with large datasets', function (): void {
        // Create 1000 exports with file paths
        for ($i = 0; $i < 1000; $i++) {
            Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'file_path' => "exports/project-{$i}.pdf",
            ]);
        }

        $startTime = microtime(true);

        // Search exports
        $exports = Export::where('user_id', $this->user->id)
            ->where('file_path', 'like', '%project-5%')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(200); // Under 200ms
        expect($exports->total())->toBeGreaterThan(0);
    });

    it('handles file size calculations efficiently', function (): void {
        // Create 500 exports with file sizes
        $exports = Export::factory()->count(500)->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
        ]);

        $startTime = microtime(true);

        // Calculate total storage used
        $totalSize = Export::where('user_id', $this->user->id)->sum('file_size');
        $avgSize = Export::where('user_id', $this->user->id)->avg('file_size');
        $maxSize = Export::where('user_id', $this->user->id)->max('file_size');

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(500); // Under 500ms
        expect($totalSize)->toBeGreaterThan(0);
        expect($avgSize)->toBeGreaterThan(0);
        expect($maxSize)->toBeGreaterThan(0);
    });
});
