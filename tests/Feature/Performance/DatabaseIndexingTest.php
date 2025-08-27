<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Database Query Optimization', function (): void {
    it('tests logo generation by session query performance', function (): void {
        // Create multiple generations for different sessions
        $sessionId = 'test-session-123';
        $otherSessionId = 'other-session-456';

        LogoGeneration::factory()->count(3)->create(['session_id' => $sessionId]);
        LogoGeneration::factory()->count(5)->create(['session_id' => $otherSessionId]);
        LogoGeneration::factory()->count(2)->create(); // Random sessions

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query that should use session_id index
        $generations = LogoGeneration::where('session_id', $sessionId)->get();

        $queryTime = microtime(true) - $startTime;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($generations)->toHaveCount(3);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with index
        expect(count($queries))->toBe(1); // Should be a single query
    });

    it('tests logo filtering by generation and style performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create logos with different styles
        GeneratedLogo::factory()->count(20)->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
        ]);
        GeneratedLogo::factory()->count(15)->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'corporate',
        ]);
        GeneratedLogo::factory()->count(10)->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'minimalist',
        ]);

        // Create logos for other generations
        $otherGeneration = LogoGeneration::factory()->create();
        GeneratedLogo::factory()->count(30)->create(['logo_generation_id' => $otherGeneration->id]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query that should use composite index (logo_generation_id, style)
        $modernLogos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->where('style', 'modern')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($modernLogos)->toHaveCount(20);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with composite index
    });

    it('tests color variant lookup by logo and scheme performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        // Create color variants
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
        ]);

        // Create variants for other logos
        $otherLogos = GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);
        foreach ($otherLogos as $otherLogo) {
            LogoColorVariant::factory()->create([
                'generated_logo_id' => $otherLogo->id,
                'color_scheme' => 'forest_green',
            ]);
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query that should use unique composite index
        $variant = LogoColorVariant::where('generated_logo_id', $logo->id)
            ->where('color_scheme', 'monochrome')
            ->first();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($variant)->not->toBeNull();
        expect($variant->color_scheme)->toBe('monochrome');
        expect($queryTime)->toBeLessThan(0.1); // Should be very fast with unique index
    });

    it('tests logo generation status filtering performance', function (): void {
        // Create generations with different statuses
        LogoGeneration::factory()->count(20)->create(['status' => 'completed']);
        LogoGeneration::factory()->count(5)->create(['status' => 'processing']);
        LogoGeneration::factory()->count(3)->create(['status' => 'failed']);
        LogoGeneration::factory()->count(2)->create(['status' => 'pending']);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query that should use status index
        $completedGenerations = LogoGeneration::where('status', 'completed')->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($completedGenerations)->toHaveCount(20);
        expect($queryTime)->toBeLessThan(0.1); // Should be fast with status index
    });

    it('tests pagination performance on large datasets', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create a large number of logos
        GeneratedLogo::factory()->count(1000)->create(['logo_generation_id' => $logoGeneration->id]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Test pagination query with ordering
        $logos = GeneratedLogo::where('logo_generation_id', $logoGeneration->id)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->offset(100)
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($logos)->toHaveCount(12);
        expect($queryTime)->toBeLessThan(0.2); // Should be reasonably fast even with large dataset
    });

    it('tests join query performance between logos and variants', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(50)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add color variants to some logos
        foreach ($logos->take(25) as $logo) {
            LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => 'monochrome',
            ]);
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Query with eager loading (should use proper joins)
        $logosWithVariants = GeneratedLogo::with('colorVariants')
            ->where('logo_generation_id', $logoGeneration->id)
            ->get();

        $queryTime = microtime(true) - $startTime;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($logosWithVariants)->toHaveCount(50);
        expect($queryTime)->toBeLessThan(0.2);
        expect(count($queries))->toBeLessThanOrEqual(2); // Should be 2 queries with eager loading
    });

    it('analyzes query plan for complex logo search', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'completed']);

        // Create logos with various styles
        $styles = ['modern', 'corporate', 'minimalist', 'playful'];
        foreach ($styles as $style) {
            GeneratedLogo::factory()->count(25)->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => $style,
            ]);
        }

        // Test complex query that should use multiple indexes
        $startTime = microtime(true);

        $logos = GeneratedLogo::whereHas('logoGeneration', function ($query): void {
            $query->where('status', 'completed');
        })
            ->where('logo_generation_id', $logoGeneration->id)
            ->whereIn('style', ['modern', 'corporate'])
            ->orderBy('style')
            ->orderBy('variation_number')
            ->get();

        $queryTime = microtime(true) - $startTime;

        expect($logos)->toHaveCount(50); // 25 modern + 25 corporate
        expect($queryTime)->toBeLessThan(0.3); // Complex query should still be reasonably fast
    });

    it('tests logo variant aggregation performance', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory()->count(20)->create(['logo_generation_id' => $logoGeneration->id]);

        // Add multiple variants per logo
        $colorSchemes = ['monochrome', 'ocean_blue', 'forest_green', 'warm_sunset'];
        foreach ($logos as $logo) {
            foreach (array_slice($colorSchemes, 0, random_int(1, 3)) as $scheme) {
                LogoColorVariant::factory()->create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $scheme,
                ]);
            }
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Aggregation query to count variants by color scheme
        $variantCounts = LogoColorVariant::whereHas('generatedLogo', function ($query) use ($logoGeneration): void {
            $query->where('logo_generation_id', $logoGeneration->id);
        })
            ->select('color_scheme', DB::raw('COUNT(*) as count'))
            ->groupBy('color_scheme')
            ->get();

        $queryTime = microtime(true) - $startTime;
        DB::disableQueryLog();

        expect($variantCounts->count())->toBeGreaterThan(0);
        expect($queryTime)->toBeLessThan(0.2); // Aggregation should be reasonably fast
    });
});
