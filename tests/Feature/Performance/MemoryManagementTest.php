<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Services\MemoryManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

describe('Memory Management for Large File Processing', function (): void {
    it('monitors memory usage during logo generation processing', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $service = app(MemoryManagementService::class);

        // Record initial memory usage
        $initialMemory = memory_get_usage();

        // Simulate processing multiple large logos
        $logos = GeneratedLogo::factory()->count(50)->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => fake()->numberBetween(500000, 2000000), // 500KB - 2MB files
        ]);

        $memoryUsage = $service->getMemoryUsage();

        expect($memoryUsage)->toHaveKey('current');
        expect($memoryUsage)->toHaveKey('peak');
        expect($memoryUsage)->toHaveKey('limit');
        expect($memoryUsage['current'])->toBeGreaterThan(0);
        expect($memoryUsage['peak'])->toBeGreaterThanOrEqual($memoryUsage['current']);
    });

    it('cleans up memory after processing large file batches', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $service = app(MemoryManagementService::class);

        // Create large file content to simulate memory usage
        $largeContent = str_repeat('x', 1024 * 1024); // 1MB string

        $initialMemory = memory_get_usage();

        // Process large data batch
        $service->processLargeFileBatch($logoGeneration->id, [
            ['content' => $largeContent, 'filename' => 'logo1.svg'],
            ['content' => $largeContent, 'filename' => 'logo2.svg'],
            ['content' => $largeContent, 'filename' => 'logo3.svg'],
        ]);

        // Memory should be cleaned up after processing
        $service->cleanupMemory();
        $finalMemory = memory_get_usage();

        // Memory usage should be controlled (allow some overhead)
        $memoryIncrease = $finalMemory - $initialMemory;
        expect($memoryIncrease)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB increase
    });

    it('implements memory limits and warnings', function (): void {
        $service = app(MemoryManagementService::class);

        // Test memory limit checking
        $isNearLimit = $service->isNearMemoryLimit();
        expect($isNearLimit)->toBeIn([true, false]);

        $memoryInfo = $service->getMemoryInfo();
        expect($memoryInfo)->toHaveKey('usage_percentage');
        expect($memoryInfo)->toHaveKey('available_mb');
        expect($memoryInfo)->toHaveKey('warning_threshold_reached');
        expect($memoryInfo['usage_percentage'])->toBeGreaterThanOrEqual(0);
        expect($memoryInfo['usage_percentage'])->toBeLessThanOrEqual(100);
    });

    it('handles memory-intensive logo variant generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        $service = app(MemoryManagementService::class);

        $initialMemory = memory_get_usage();

        // Simulate generating multiple color variants (memory-intensive operation)
        $variants = $service->generateColorVariantsWithMemoryManagement(
            $logo->id,
            ['monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 'electric_purple']
        );

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        expect($variants)->toHaveCount(5);
        expect($memoryUsed)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB used

        // Each variant should have memory info recorded
        foreach ($variants as $variant) {
            expect($variant)->toHaveKey('memory_used');
            expect($variant)->toHaveKey('processing_time');
            expect($variant['memory_used'])->toBeGreaterThan(0);
        }
    });

    it('implements streaming for large file operations', function (): void {
        $service = app(MemoryManagementService::class);

        // Create a large file content
        $largeContent = str_repeat('SVG content here: ', 50000); // ~1MB
        Storage::put('test-large-file.svg', $largeContent);

        $initialMemory = memory_get_usage();

        // Process file using streaming (should use minimal memory)
        $result = $service->streamProcessLargeFile(Storage::path('test-large-file.svg'));

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        expect($result)->toHaveKey('processed');
        expect($result)->toHaveKey('file_size');
        expect($result)->toHaveKey('memory_efficient');
        expect($result['processed'])->toBeTrue();
        expect($result['memory_efficient'])->toBeTrue();
        expect($memoryIncrease)->toBeLessThan(2 * 1024 * 1024); // Less than 2MB increase
    });

    it('implements garbage collection optimization', function (): void {
        $service = app(MemoryManagementService::class);

        $initialMemory = memory_get_usage();

        // Create some data to be garbage collected
        $largeArrays = [];
        for ($i = 0; $i < 10; $i++) {
            $largeArrays[] = array_fill(0, 10000, "memory test data {$i}");
        }

        $beforeGC = memory_get_usage();

        // Force garbage collection through service
        unset($largeArrays);
        $gcResult = $service->forceGarbageCollection();

        $afterGC = memory_get_usage();

        expect($gcResult)->toHaveKey('memory_freed');
        expect($gcResult)->toHaveKey('cycles_collected');
        expect($gcResult['memory_freed'])->toBeGreaterThanOrEqual(0);
        expect($afterGC)->toBeLessThan($beforeGC);
    });

    it('tracks memory usage per logo generation session', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $service = app(MemoryManagementService::class);

        // Start tracking for this generation
        $service->startMemoryTracking($logoGeneration->id);

        // Simulate various operations
        GeneratedLogo::factory()->count(20)->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => fake()->numberBetween(100000, 500000),
        ]);

        // Simulate some processing
        $dummyData = str_repeat('x', 512 * 1024); // 512KB
        unset($dummyData);

        // Stop tracking and get results
        $memoryReport = $service->stopMemoryTracking($logoGeneration->id);

        expect($memoryReport)->toHaveKey('generation_id');
        expect($memoryReport)->toHaveKey('peak_memory_mb');
        expect($memoryReport)->toHaveKey('average_memory_mb');
        expect($memoryReport)->toHaveKey('total_duration_ms');
        expect($memoryReport)->toHaveKey('memory_efficient');
        expect($memoryReport['generation_id'])->toBe($logoGeneration->id);
        expect($memoryReport['peak_memory_mb'])->toBeGreaterThan(0);
    });

    it('prevents memory leaks in long-running processes', function (): void {
        $service = app(MemoryManagementService::class);

        $memorySnapshots = [];

        // Simulate long-running process with multiple iterations
        for ($iteration = 0; $iteration < 10; $iteration++) {
            $logoGeneration = LogoGeneration::factory()->create();

            // Process some logos
            GeneratedLogo::factory()->count(5)->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Clean up after each iteration
            $service->cleanupMemory();

            $memorySnapshots[] = memory_get_usage();
        }

        // Memory usage should not grow significantly over iterations
        $firstSnapshot = $memorySnapshots[0];
        $lastSnapshot = end($memorySnapshots);
        $memoryGrowth = $lastSnapshot - $firstSnapshot;

        // Allow for some growth but ensure it's controlled (less than 5MB growth)
        expect($memoryGrowth)->toBeLessThan(5 * 1024 * 1024);

        // Memory should be relatively stable across iterations
        $maxVariation = max($memorySnapshots) - min($memorySnapshots);
        expect($maxVariation)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB variation
    });

    it('optimizes memory for different logo file sizes', function (): void {
        $service = app(MemoryManagementService::class);

        // Test with small files
        $smallFileMemory = $service->processLogosWithSizeOptimization([
            ['size' => 50000, 'content' => str_repeat('x', 1000)],   // 50KB
            ['size' => 75000, 'content' => str_repeat('x', 1500)],   // 75KB
        ]);

        // Test with large files
        $largeFileMemory = $service->processLogosWithSizeOptimization([
            ['size' => 2000000, 'content' => str_repeat('x', 100000)], // 2MB
            ['size' => 3000000, 'content' => str_repeat('x', 150000)], // 3MB
        ]);

        expect($smallFileMemory)->toHaveKey('strategy');
        expect($largeFileMemory)->toHaveKey('strategy');
        expect($smallFileMemory['strategy'])->toBe('in_memory');
        expect($largeFileMemory['strategy'])->toBe('streaming');

        // Both should have processed successfully
        expect($smallFileMemory['logos_processed'])->toBe(2);
        expect($largeFileMemory['logos_processed'])->toBe(2);

        // Memory usage should be non-negative
        expect($smallFileMemory['memory_used'])->toBeGreaterThanOrEqual(0);
        expect($largeFileMemory['memory_used'])->toBeGreaterThanOrEqual(0);
    });
});
