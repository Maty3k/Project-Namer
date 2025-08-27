<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LogoGenerationController;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Services\LazyLoadingService;
use App\Services\LogoVariantCacheService;
use App\Services\MemoryManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    Storage::fake('local');
});

describe('Performance Requirements Verification', function (): void {
    it('meets API response time requirements under 200ms', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        GeneratedLogo::factory()->count(10)->create(['logo_generation_id' => $logoGeneration->id]);

        $controller = app(LogoGenerationController::class);

        // Warm up cache first
        $controller->show($logoGeneration);

        // Measure actual performance
        $startTime = microtime(true);
        $response = $controller->show($logoGeneration);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        expect($response->getStatusCode())->toBe(200);
        expect($responseTime)->toBeLessThan(200); // Must be under 200ms
    });

    it('meets database query performance requirements under 100ms', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(100)->create(['logo_generation_id' => $logoGeneration->id]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Complex query that uses multiple indexes
        $results = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->with(['colorVariants'])
            ->orderBy('style')
            ->orderBy('variation_number')
            ->paginate(12);

        $endTime = microtime(true);
        DB::disableQueryLog();

        $queryTime = ($endTime - $startTime) * 1000;

        expect($results->count())->toBeGreaterThan(0);
        expect($queryTime)->toBeLessThan(100); // Database queries under 100ms
    });

    it('meets memory usage requirements under 50MB for standard operations', function (): void {
        $memoryService = app(MemoryManagementService::class);
        $logoGeneration = LogoGeneration::factory()->create();

        $startMemory = memory_get_usage();

        // Standard logo generation operation
        GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        // Process some variants
        $logos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->take(10)->get();
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green', 'warm_sunset', 'royal_purple'];
        foreach ($logos as $index => $logo) {
            LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => $colorSchemes[$index % count($colorSchemes)],
            ]);
        }

        $endMemory = memory_get_usage();
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        expect($memoryUsed)->toBeLessThan(50); // Under 50MB for standard operations
    });

    it('meets cache hit ratio requirements above 80%', function (): void {
        $cacheService = app(LogoVariantCacheService::class);
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(20)->create(['logo_generation_id' => $logoGeneration->id]);

        // Prime the cache with first request
        $cacheService->getCachedLogoGeneration($logoGeneration->id);
        $cacheService->getLogosByStyle($logoGeneration->id);

        $totalRequests = 10;
        $cacheHits = 0;

        for ($i = 0; $i < $totalRequests; $i++) {
            $startTime = microtime(true);
            $cacheService->getCachedLogoGeneration($logoGeneration->id);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;

            // Cached responses should be very fast (under 10ms)
            if ($responseTime < 10) {
                $cacheHits++;
            }
        }

        $cacheHitRatio = ($cacheHits / $totalRequests) * 100;
        expect($cacheHitRatio)->toBeGreaterThan(80); // Cache hit ratio above 80%
    });

    it('meets lazy loading performance requirements under 150ms', function (): void {
        $lazyLoadingService = app(LazyLoadingService::class);
        $logoGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(100)->create(['logo_generation_id' => $logoGeneration->id]);

        // Test pagination performance
        $startTime = microtime(true);
        $result = $lazyLoadingService->getPaginatedLogos($logoGeneration->id, 1, 12);
        $endTime = microtime(true);

        $paginationTime = ($endTime - $startTime) * 1000;

        expect($result['data'])->toHaveCount(12);
        expect($paginationTime)->toBeLessThan(150); // Pagination under 150ms

        // Test infinite scroll performance
        $startTime = microtime(true);
        $scrollResult = $lazyLoadingService->getLogosForInfiniteScroll($logoGeneration->id, null, 12);
        $endTime = microtime(true);

        $scrollTime = ($endTime - $startTime) * 1000;

        expect($scrollResult['data'])->toHaveCount(12);
        expect($scrollTime)->toBeLessThan(150); // Infinite scroll under 150ms
    });

    it('meets concurrent request performance requirements', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        $services = [
            app(LogoVariantCacheService::class),
            app(LazyLoadingService::class),
            app(MemoryManagementService::class),
        ];

        // Simulate concurrent requests
        $operations = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 5; $i++) {
            $operationStart = microtime(true);

            // Multiple service calls
            $services[0]->getCachedLogoGeneration($logoGeneration->id);
            $services[1]->getPaginatedLogos($logoGeneration->id, 1, 12);
            $services[2]->getMemoryUsage();

            $operationEnd = microtime(true);
            $operations[] = ($operationEnd - $operationStart) * 1000;
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $averageOperationTime = array_sum($operations) / count($operations);

        expect($totalTime)->toBeLessThan(1000); // Total time under 1 second
        expect($averageOperationTime)->toBeLessThan(200); // Average operation under 200ms
    });

    it('meets scalability requirements with large datasets', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create large dataset
        $batchSize = 200;
        $batches = 5;
        $responseTimes = [];

        for ($batch = 0; $batch < $batches; $batch++) {
            GeneratedLogo::factory()->count($batchSize)->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Test query performance with growing dataset
            $startTime = microtime(true);
            $results = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
                ->orderBy('id')
                ->paginate(20);
            $endTime = microtime(true);

            $responseTime = ($endTime - $startTime) * 1000;
            $responseTimes[] = $responseTime;
        }

        // Performance should not degrade significantly with larger datasets
        $firstBatch = $responseTimes[0];
        $lastBatch = end($responseTimes);
        $performanceDegradation = ($lastBatch - $firstBatch) / $firstBatch * 100;

        expect($lastBatch)->toBeLessThan(300); // Still under 300ms with 1000 records
        expect($performanceDegradation)->toBeLessThan(200); // Less than 200% degradation
    });

    it('meets memory efficiency requirements for large operations', function (): void {
        $memoryService = app(MemoryManagementService::class);
        $logoGeneration = LogoGeneration::factory()->create();

        $startMemory = memory_get_usage();

        // Large-scale operation
        $memoryService->startMemoryTracking($logoGeneration->id);

        // Process many logos
        GeneratedLogo::factory()->count(200)->create(['logo_generation_id' => $logoGeneration->id]);

        // Generate multiple variants
        $logos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->take(50)->get();
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green'];
        foreach ($logos as $logo) {
            foreach (array_slice($colorSchemes, 0, 3) as $scheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $scheme,
                ]);
            }
        }

        $memoryReport = $memoryService->stopMemoryTracking($logoGeneration->id);
        $finalMemory = memory_get_usage();
        $totalMemoryUsed = ($finalMemory - $startMemory) / 1024 / 1024;

        expect($memoryReport['memory_efficient'])->toBeTrue();
        expect($memoryReport['peak_memory_mb'])->toBeLessThan(150); // Peak under 150MB (more realistic)
        expect($totalMemoryUsed)->toBeLessThan(100); // Total usage under 100MB
    });

    it('meets overall system performance benchmarks', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);
        GeneratedLogo::factory()->count(100)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add color variants to some logos
        $logos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)->take(30)->get();
        $colorSchemes = ['monochrome', 'ocean_blue'];
        foreach ($logos as $logo) {
            foreach ($colorSchemes as $scheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $scheme,
                ]);
            }
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Complete workflow test
        $lazyLoadingService = app(LazyLoadingService::class);
        $cacheService = app(LogoVariantCacheService::class);

        // Multiple operations
        $result1 = $lazyLoadingService->getPaginatedLogos($logoGeneration->id, 1, 20);
        $result2 = $cacheService->getLogosByStyle($logoGeneration->id);
        $result3 = $lazyLoadingService->getLogosByStyleLazy($logoGeneration->id, 10);
        $result4 = $cacheService->getCachedLogoGeneration($logoGeneration->id);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $totalTime = ($endTime - $startTime) * 1000;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        // Verify results
        expect($result1['data'])->toHaveCount(20);
        expect($result2)->not->toBeEmpty();
        expect($result3)->not->toBeEmpty();
        expect($result4)->not->toBeNull();

        // Performance benchmarks
        expect($totalTime)->toBeLessThan(500); // Complete workflow under 500ms
        expect($memoryUsed)->toBeLessThan(20); // Memory usage under 20MB

        // Log performance metrics for monitoring
        Log::info('Performance benchmark completed', [
            'total_time_ms' => $totalTime,
            'memory_used_mb' => $memoryUsed,
            'operations_completed' => 4,
            'logos_processed' => 100,
            'variants_processed' => 60,
        ]);
    });
});
