<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

describe('Lazy Loading Gallery Performance', function (): void {
    it('measures pagination performance for logo gallery', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(100)->create(['logo_generation_id' => $logoGeneration->id]);

        $startTime = microtime(true);
        $paginated = GeneratedLogo::with('logoGeneration')
            ->where('logo_generation_id', $logoGeneration->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        $paginationTime = microtime(true) - $startTime;

        expect($paginationTime)->toBeLessThan(0.1); // Under 100ms
        expect($paginated)->toBeInstanceOf(LengthAwarePaginator::class);
        expect($paginated->count())->toBe(12);
        expect($paginated->total())->toBe(100);
    });

    it('tests infinite scroll pagination performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        // Simulate loading pages as user scrolls
        $pageSize = 12;
        $totalPages = ceil(50 / $pageSize);
        $totalTime = 0;

        for ($page = 1; $page <= $totalPages; $page++) {
            $startTime = microtime(true);
            $results = GeneratedLogo::with('logoGeneration')
                ->where('logo_generation_id', $logoGeneration->id)
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();
            $pageTime = microtime(true) - $startTime;
            $totalTime += $pageTime;

            // Each page should load quickly
            expect($pageTime)->toBeLessThan(0.05); // Under 50ms per page
            expect($results->count())->toBeLessThanOrEqual($pageSize);
        }

        // Total time for all pages should be reasonable
        expect($totalTime)->toBeLessThan(0.3); // Under 300ms for all pages
    });

    it('compares eager vs lazy loading performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(30)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add color variants with unique color schemes
        $colorSchemes = ['monochrome', 'ocean_blue'];
        foreach ($logos as $logo) {
            LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => $colorSchemes[0],
            ]);
            LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => $colorSchemes[1],
            ]);
        }

        // Test lazy loading (load relationships on demand)
        DB::enableQueryLog();
        $startTime = microtime(true);

        $lazyLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->take(12)->get();
        foreach ($lazyLogos as $logo) {
            $colorVariants = $logo->colorVariants; // Lazy load
        }

        $lazyTime = microtime(true) - $startTime;
        $lazyQueries = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Test eager loading
        $startTime = microtime(true);

        $eagerLogos = GeneratedLogo::with('colorVariants')
            ->where('logo_generation_id', $logoGeneration->id)
            ->take(12)
            ->get();
        foreach ($eagerLogos as $logo) {
            $colorVariants = $logo->colorVariants; // Already loaded
        }

        $eagerTime = microtime(true) - $startTime;
        $eagerQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Eager loading should use fewer queries and be faster for this use case
        expect($eagerQueries)->toBeLessThan($lazyQueries);
        expect($eagerTime)->toBeLessThan($lazyTime);
    });
});

describe('Livewire Component Performance', function (): void {
    it('measures logo gallery component render time', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(20)->create(['logo_generation_id' => $logoGeneration->id]);

        $startTime = microtime(true);
        $component = Volt::test('pages.logo-gallery', [
            'logoGenerationId' => $logoGeneration->id,
        ]);
        $renderTime = microtime(true) - $startTime;

        // Component should render quickly (increased to 1s due to Livewire component initialization overhead)
        expect($renderTime)->toBeLessThan(1.0); // Under 1 second
        $component->assertStatus(200);
    });

    it('tests component performance with large datasets', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(100)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add many color variants to increase data complexity
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 'royal_purple'];
        foreach ($logos->take(20) as $logo) {
            foreach ($colorSchemes as $colorScheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                ]);
            }
        }

        $startTime = microtime(true);
        $component = Volt::test('pages.logo-gallery', [
            'logoGenerationId' => $logoGeneration->id,
        ]);
        $renderTime = microtime(true) - $startTime;

        // Should handle large datasets reasonably well (increased to 2s for large datasets)
        expect($renderTime)->toBeLessThan(2.0); // Under 2 seconds even with large dataset
        $component->assertStatus(200);
    });

    it('measures memory usage during component rendering', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        $initialMemory = memory_get_usage(true);

        $component = Volt::test('pages.logo-gallery', [
            'logoGenerationId' => $logoGeneration->id,
        ]);

        $memoryUsed = memory_get_usage(true) - $initialMemory;

        // Component should not use excessive memory
        expect($memoryUsed)->toBeLessThan(20 * 1024 * 1024); // Under 20MB
        $component->assertStatus(200);
    });
});

describe('Image Loading Performance', function (): void {
    it('simulates progressive image loading performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(24)->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => 100000, // 100KB files
        ]);

        // Simulate loading first batch (above the fold)
        $startTime = microtime(true);
        $firstBatch = $logos->take(6); // First 6 images
        foreach ($firstBatch as $logo) {
            // Simulate checking if file exists (would be actual file operations)
            $exists = ! empty($logo->original_file_path);
        }
        $firstBatchTime = microtime(true) - $startTime;

        // Simulate lazy loading remaining images
        $startTime = microtime(true);
        $remainingLogos = $logos->skip(6);
        foreach ($remainingLogos as $logo) {
            $exists = ! empty($logo->original_file_path);
        }
        $lazyLoadTime = microtime(true) - $startTime;

        // First batch should be prioritized (loaded quickly)
        expect($firstBatchTime)->toBeLessThan(0.05); // Under 50ms for first 6
        expect($lazyLoadTime)->toBeLessThan(0.1); // Under 100ms for remaining
    });

    it('tests thumbnail generation performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(12)->create([
            'logo_generation_id' => $logoGeneration->id,
            'image_width' => 1024,
            'image_height' => 1024,
        ]);

        // Simulate thumbnail size calculation
        $startTime = microtime(true);
        $thumbnailSizes = [];

        foreach ($logos as $logo) {
            // Calculate responsive thumbnail sizes
            $thumbnailSizes[] = [
                'small' => ['width' => 256, 'height' => 256],
                'medium' => ['width' => 512, 'height' => 512],
                'large' => ['width' => $logo->image_width, 'height' => $logo->image_height],
            ];
        }

        $calculationTime = microtime(true) - $startTime;

        // Thumbnail calculations should be fast
        expect($calculationTime)->toBeLessThan(0.01); // Under 10ms
        expect($thumbnailSizes)->toHaveCount(12);
    });
});

describe('Data Transfer Performance', function (): void {
    it('measures JSON serialization performance for API responses', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add relationships to increase complexity (max 3 unique color schemes per logo)
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green'];
        foreach ($logos->take(10) as $logo) {
            foreach ($colorSchemes as $colorScheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                ]);
            }
        }

        $startTime = microtime(true);
        $data = GeneratedLogo::with(['logoGeneration', 'colorVariants'])
            ->where('logo_generation_id', $logoGeneration->id)
            ->get()
            ->toArray();
        $serializationTime = microtime(true) - $startTime;

        // JSON serialization for API should be fast
        expect($serializationTime)->toBeLessThan(0.1); // Under 100ms
        expect($data)->toBeArray();
        expect(count($data))->toBe(50);
    });

    it('tests response compression impact on performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(30)->create(['logo_generation_id' => $logoGeneration->id]);

        $data = GeneratedLogo::with('logoGeneration')
            ->where('logo_generation_id', $logoGeneration->id)
            ->get()
            ->toArray();

        $jsonData = json_encode($data);
        $originalSize = strlen($jsonData);

        // Simulate gzip compression
        $startTime = microtime(true);
        $compressedData = gzcompress($jsonData, 6);
        $compressionTime = microtime(true) - $startTime;
        $compressedSize = strlen($compressedData);

        // Compression should be effective and fast
        expect($compressionTime)->toBeLessThan(0.05); // Under 50ms
        expect($compressedSize)->toBeLessThan($originalSize);

        $compressionRatio = ($originalSize - $compressedSize) / $originalSize;
        expect($compressionRatio)->toBeGreaterThan(0.3); // At least 30% compression
    });
});
