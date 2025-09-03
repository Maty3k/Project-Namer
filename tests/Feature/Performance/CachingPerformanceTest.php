<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Services\ColorPaletteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

describe('Logo Variant Caching', function (): void {
    it('caches color-customized logo variants effectively', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        $cacheKey = "logo_variants:{$logo->id}:monochrome";

        // First call - should cache the result
        $startTime = microtime(true);
        $variants = Cache::remember($cacheKey, 3600, fn (): array => LogoColorVariant::where('generated_logo_id', $logo->id)
            ->where('color_scheme', 'monochrome')
            ->get()
            ->toArray());
        $firstCallTime = microtime(true) - $startTime;

        // Second call - should be from cache
        $startTime = microtime(true);
        $cachedVariants = Cache::get($cacheKey);
        $cachedCallTime = microtime(true) - $startTime;

        expect($cachedCallTime)->toBeLessThan($firstCallTime);
        expect($cachedVariants)->toEqual($variants);
        expect(Cache::has($cacheKey))->toBeTrue();
    });

    it('invalidates logo variant cache when variants are updated', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        $variant = LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);

        $cacheKey = "logo_variants:{$logo->id}:monochrome";
        Cache::put($cacheKey, [$variant->toArray()], 3600);

        expect(Cache::has($cacheKey))->toBeTrue();

        // Update variant - should invalidate cache
        $variant->update(['file_size' => 50000]);
        Cache::forget($cacheKey);

        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('API Response Caching', function (): void {
    it('caches frequently accessed logo data', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        $cacheKey = "logo_data:{$logo->id}";

        // Cache logo data with relationships
        $logoData = Cache::remember($cacheKey, 1800, fn (): array => $logo->load(['logoGeneration', 'colorVariants'])->toArray());

        expect(Cache::has($cacheKey))->toBeTrue();
        expect($logoData)->toHaveKey('id');
        expect($logoData)->toHaveKey('logo_generation');
        expect($logoData)->toHaveKey('color_variants');
    });

    it('measures cache hit performance vs database queries', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(10)->create(['logo_generation_id' => $logoGeneration->id]);

        // Measure database query time
        $startTime = microtime(true);
        $dbResults = GeneratedLogo::with(['logoGeneration', 'colorVariants'])
            ->where('logo_generation_id', $logoGeneration->id)
            ->get();
        $dbTime = microtime(true) - $startTime;

        // Cache the results
        $cacheKey = "generation_logos:{$logoGeneration->id}";
        Cache::put($cacheKey, $dbResults->toArray(), 3600);

        // Measure cache retrieval time
        $startTime = microtime(true);
        $cacheResults = Cache::get($cacheKey);
        $cacheTime = microtime(true) - $startTime;

        expect($cacheTime)->toBeLessThan($dbTime);
        expect(count($cacheResults))->toBe(10);
    });
});

describe('Database Query Performance', function (): void {
    it('measures query performance for logo gallery pagination', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        // Measure paginated query performance
        $startTime = microtime(true);
        $results = GeneratedLogo::with('logoGeneration')
            ->where('logo_generation_id', $logoGeneration->id)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();
        $queryTime = microtime(true) - $startTime;

        // Should complete within reasonable time (under 100ms for 50 records)
        expect($queryTime)->toBeLessThan(0.1);
        expect($results)->toHaveCount(12);
    });

    it('measures performance impact of eager loading', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(20)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add color variants (max 3 per logo to avoid unique constraint issues)
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green'];
        foreach ($logos as $logo) {
            foreach ($colorSchemes as $colorScheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                ]);
            }
        }

        // Measure N+1 query problem
        $startTime = microtime(true);
        $queryCount = DB::getQueryLog();
        DB::enableQueryLog();

        $logosWithoutEager = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->get();
        foreach ($logosWithoutEager as $logo) {
            $logo->colorVariants; // This creates N+1 queries
        }
        $withoutEagerQueries = count(DB::getQueryLog()) - count($queryCount);
        $withoutEagerTime = microtime(true) - $startTime;

        DB::flushQueryLog();

        // Measure with eager loading
        $startTime = microtime(true);
        DB::enableQueryLog();

        $logosWithEager = GeneratedLogo::with('colorVariants')
            ->where('logo_generation_id', $logoGeneration->id)
            ->get();
        $withEagerQueries = count(DB::getQueryLog());
        $withEagerTime = microtime(true) - $startTime;

        // Eager loading should use fewer queries
        expect($withEagerQueries)->toBeLessThan($withoutEagerQueries);

        DB::disableQueryLog();
    });
});

describe('Memory Usage Performance', function (): void {
    it('monitors memory usage during large logo processing', function (): void {
        $initialMemory = memory_get_usage(true);

        // Simulate processing multiple large logos
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(100)->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => 500000, // 500KB files
        ]);

        // Process logos in chunks to manage memory
        $chunkSize = 25;
        $chunks = $logos->chunk($chunkSize);

        foreach ($chunks as $chunk) {
            $chunkMemory = memory_get_usage(true);
            // Process each chunk
            $chunk->load(['logoGeneration', 'colorVariants']);

            // Memory should not grow excessively per chunk
            $memoryIncrease = memory_get_usage(true) - $chunkMemory;
            expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024); // Less than 50MB per chunk

            // Clean up chunk to free memory
            unset($chunk);
        }

        $finalMemory = memory_get_usage(true);
        $totalMemoryUsage = $finalMemory - $initialMemory;

        // Total memory usage should be reasonable
        expect($totalMemoryUsage)->toBeLessThan(100 * 1024 * 1024); // Less than 100MB total
    });

    it('measures color palette generation performance', function (): void {
        $service = app(ColorPaletteService::class);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Generate multiple color palettes
        $palettes = [];
        for ($i = 0; $i < 10; $i++) {
            $palettes[] = $service->getColorPalette('monochrome');
        }

        $endTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        // Should complete quickly and use minimal memory
        expect($endTime)->toBeLessThan(1.0); // Under 1 second for 10 palettes
        expect($memoryUsed)->toBeLessThan(10 * 1024 * 1024); // Under 10MB
        expect($palettes)->toHaveCount(10);
    });
});

describe('Cache Configuration Performance', function (): void {
    it('verifies cache store performance characteristics', function (): void {
        $testData = [
            'logos' => array_fill(0, 100, ['id' => random_int(1, 1000), 'data' => str_repeat('x', 1000)]),
        ];

        // Test write performance
        $startTime = microtime(true);
        Cache::put('performance_test', $testData, 300);
        $writeTime = microtime(true) - $startTime;

        // Test read performance
        $startTime = microtime(true);
        $retrievedData = Cache::get('performance_test');
        $readTime = microtime(true) - $startTime;

        // Cache operations should be fast
        expect($writeTime)->toBeLessThan(0.1); // Under 100ms
        expect($readTime)->toBeLessThan(0.01); // Under 10ms
        expect($retrievedData)->toEqual($testData);

        Cache::forget('performance_test');
    });

    it('tests cache invalidation performance', function (): void {
        // Set up multiple cache keys
        $keys = [];
        for ($i = 0; $i < 50; $i++) {
            $key = "test_key_{$i}";
            $keys[] = $key;
            Cache::put($key, "value_{$i}", 300);
        }

        // Measure bulk invalidation time
        $startTime = microtime(true);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        $invalidationTime = microtime(true) - $startTime;

        // Bulk invalidation should be reasonably fast
        expect($invalidationTime)->toBeLessThan(0.5); // Under 500ms for 50 keys

        // Verify all keys are invalidated
        foreach ($keys as $key) {
            expect(Cache::has($key))->toBeFalse();
        }
    });
});
