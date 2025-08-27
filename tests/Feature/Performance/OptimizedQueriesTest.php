<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Optimized Database Queries', function (): void {
    it('efficiently filters generations by status and creation time', function (): void {
        // Create generations at different times with various statuses
        $oldCompleted = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);
        $recentCompleted = LogoGeneration::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(2),
        ]);
        $processing = LogoGeneration::factory()->create([
            'status' => 'processing',
            'created_at' => now()->subMinutes(30),
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the status + created_at composite index
        $recentCompleted = LogoGeneration::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(1))
            ->orderBy('created_at', 'desc')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($recentCompleted)->toHaveCount(1);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with composite index
    });

    it('efficiently handles session-based status filtering', function (): void {
        $sessionId = 'test-session-123';

        LogoGeneration::factory()->create([
            'session_id' => $sessionId,
            'status' => 'completed',
        ]);
        LogoGeneration::factory()->create([
            'session_id' => $sessionId,
            'status' => 'processing',
        ]);
        LogoGeneration::factory()->create([
            'session_id' => 'other-session',
            'status' => 'completed',
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the session_id + status composite index
        $sessionCompleted = LogoGeneration::where('session_id', $sessionId)
            ->where('status', 'completed')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($sessionCompleted)->toHaveCount(1);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with composite index
    });

    it('efficiently queries logos by generation, style, and variation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create logos with specific ordering requirements
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
            'variation_number' => 1,
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
            'variation_number' => 2,
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'corporate',
            'variation_number' => 1,
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the generation_id + style + variation_number composite index
        $orderedLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->orderBy('style')
            ->orderBy('variation_number')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($orderedLogos)->toHaveCount(3);
        expect($orderedLogos[0]->style)->toBe('corporate'); // Should be ordered correctly
        expect($orderedLogos[1]->style)->toBe('modern');
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with ordered index
    });

    it('efficiently filters logos by file size range', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create logos with different file sizes
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => 50000, // 50KB
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => 150000, // 150KB
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'file_size' => 500000, // 500KB
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the file_size index
        $mediumSizeLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->whereBetween('file_size', [100000, 200000])
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($mediumSizeLogos)->toHaveCount(1);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with file_size index
    });

    it('efficiently queries logos by image dimensions', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create logos with different dimensions
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'image_width' => 512,
            'image_height' => 512,
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'image_width' => 1024,
            'image_height' => 1024,
        ]);
        GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'image_width' => 2048,
            'image_height' => 2048,
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the image_width + image_height composite index
        $hdLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->where('image_width', 1024)
            ->where('image_height', 1024)
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($hdLogos)->toHaveCount(1);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with composite index
    });

    it('efficiently analyzes generation time performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create logos with different generation times, ensuring some are slow
        GeneratedLogo::factory()->count(25)->create([
            'logo_generation_id' => $logoGeneration->id,
            'generation_time_ms' => fake()->numberBetween(10000, 25000), // Fast ones
        ]);
        GeneratedLogo::factory()->count(25)->create([
            'logo_generation_id' => $logoGeneration->id,
            'generation_time_ms' => fake()->numberBetween(35000, 60000), // Slow ones
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query for performance analysis using generation_time_ms index
        $slowLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->where('generation_time_ms', '>', 30000) // Slower than 30 seconds
            ->orderBy('generation_time_ms', 'desc')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($slowLogos->count())->toBeGreaterThan(0);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with generation_time_ms index
    });

    it('efficiently tracks cost aggregations', function (): void {
        // Create generations with various costs, all completed
        LogoGeneration::factory()->count(20)->create([
            'status' => 'completed',
            'cost_cents' => fake()->numberBetween(1500, 5000), // Ensure > 1000
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using cost_cents index for aggregation
        $totalCost = LogoGeneration::where('status', 'completed')
            ->where('cost_cents', '>', 1000)
            ->sum('cost_cents');

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($totalCost)->toBeGreaterThan(0);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with cost_cents index
    });

    it('efficiently handles color scheme statistics with temporal filtering', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        // Create variants at different times
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
            'created_at' => now()->subDays(2),
        ]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
            'created_at' => now()->subHours(1),
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the color_scheme + created_at composite index
        $recentVariants = LogoColorVariant::where('color_scheme', 'ocean_blue')
            ->where('created_at', '>=', now()->subHours(2))
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($recentVariants)->toHaveCount(1);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with composite index
    });

    it('efficiently orders logo variants by creation time within a logo', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        // Create variants at different times
        $firstVariant = LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
            'created_at' => now()->subHours(2),
        ]);
        $secondVariant = LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
            'created_at' => now()->subHours(1),
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query using the generated_logo_id + created_at composite index
        $orderedVariants = LogoColorVariant::where('generated_logo_id', $logo->id)
            ->orderBy('created_at')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($orderedVariants)->toHaveCount(2);
        expect($orderedVariants[0]->color_scheme)->toBe('monochrome'); // Older one first
        expect($orderedVariants[1]->color_scheme)->toBe('ocean_blue'); // Newer one second
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with ordered composite index
    });

    it('measures overall query performance improvement with all indexes', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'completed',
            'session_id' => 'perf-test-session',
        ]);

        // Create complex dataset
        $logos = GeneratedLogo::factory()->count(100)->create([
            'logo_generation_id' => $logoGeneration->id,
        ]);

        $colorSchemes = ['monochrome', 'ocean_blue'];
        foreach ($logos->take(50) as $logo) {
            foreach ($colorSchemes as $colorScheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $colorScheme,
                ]);
            }
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Complex query that uses multiple indexes
        $results = LogoGeneration::where('session_id', 'perf-test-session')
            ->where('status', 'completed')
            ->with([
                'generatedLogos' => function ($query): void {
                    $query->where('file_size', '>', 50000)
                        ->orderBy('style', 'asc')
                        ->orderBy('variation_number', 'asc');
                },
                'generatedLogos.colorVariants' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                },
            ])
            ->first();

        $queryTime = microtime(true) - $startTime;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($results)->not->toBeNull();
        expect($results->generatedLogos)->not->toBeEmpty();
        expect($queryTime)->toBeLessThan(0.3); // Complex query should still be fast
        expect(count($queries))->toBeLessThanOrEqual(3); // Should use efficient eager loading
    });
});
