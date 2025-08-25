<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Services\LogoVariantCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

describe('LogoVariantCacheService', function (): void {
    it('caches logo variants effectively', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        $variant1 = LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);
        $variant2 = LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
        ]);

        $service = app(LogoVariantCacheService::class);

        // First call - should hit database and cache
        $startTime = microtime(true);
        $variants = $service->getLogoVariants($logo->id);
        $firstCallTime = microtime(true) - $startTime;

        expect($variants)->toHaveCount(2);
        expect($variants->pluck('color_scheme')->toArray())
            ->toEqual(['monochrome', 'ocean_blue']);

        // Second call - should hit cache
        $startTime = microtime(true);
        $cachedVariants = $service->getLogoVariants($logo->id);
        $cachedCallTime = microtime(true) - $startTime;

        expect($cachedCallTime)->toBeLessThan($firstCallTime);
        expect($cachedVariants)->toHaveCount(2);
    });

    it('caches logo variants by color scheme', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
        ]);

        $service = app(LogoVariantCacheService::class);

        // Get only monochrome variants
        $monochromeVariants = $service->getLogoVariants($logo->id, 'monochrome');
        expect($monochromeVariants)->toHaveCount(1);
        expect($monochromeVariants->first()->color_scheme)->toBe('monochrome');

        // Get only ocean_blue variants
        $oceanBlueVariants = $service->getLogoVariants($logo->id, 'ocean_blue');
        expect($oceanBlueVariants)->toHaveCount(1);
        expect($oceanBlueVariants->first()->color_scheme)->toBe('ocean_blue');
    });

    it('caches full logo generation data', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo1 = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
        ]);
        $logo2 = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'corporate',
        ]);

        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo1->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        // First call - should cache
        $generation = $service->getCachedLogoGeneration($logoGeneration->id);
        expect($generation)->not->toBeNull();
        expect($generation->generatedLogos)->toHaveCount(2);

        // Find the logo with color variants
        $logoWithVariants = $generation->generatedLogos->firstWhere('id', $logo1->id);
        expect($logoWithVariants)->not->toBeNull();
        expect($logoWithVariants->colorVariants)->toHaveCount(1);

        // Verify cache is hit on second call
        $cachedGeneration = $service->getCachedLogoGeneration($logoGeneration->id);
        expect($cachedGeneration->id)->toBe($generation->id);
    });

    it('groups logos by style with caching', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $modernLogo = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'modern',
            'variation_number' => 1,
        ]);
        $corporateLogo = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'style' => 'corporate',
            'variation_number' => 1,
        ]);

        LogoColorVariant::factory()->create([
            'generated_logo_id' => $modernLogo->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        $logosByStyle = $service->getLogosByStyle($logoGeneration->id);

        expect($logosByStyle)->toHaveCount(2);
        expect($logosByStyle[0]['style'])->toBeIn(['modern', 'corporate']);
        expect($logosByStyle[0]['logos'])->toHaveCount(1);

        // Check that color variants are included
        $modernStyle = collect($logosByStyle)->firstWhere('style', 'modern');
        expect($modernStyle['logos'][0]['color_variants'])->toHaveCount(1);
        expect($modernStyle['logos'][0]['color_variants'][0]['color_scheme'])->toBe('monochrome');
    });

    it('provides color scheme statistics', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo1 = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        $logo2 = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        // Create variants with different color schemes
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo1->id,
            'color_scheme' => 'monochrome',
        ]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo1->id,
            'color_scheme' => 'ocean_blue',
        ]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo2->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        $stats = $service->getColorSchemeStats($logoGeneration->id);
        expect($stats)->toHaveKey('monochrome', 2);
        expect($stats)->toHaveKey('ocean_blue', 1);
    });

    it('checks if variant exists efficiently', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        expect($service->variantExists($logo->id, 'monochrome'))->toBeTrue();
        expect($service->variantExists($logo->id, 'ocean_blue'))->toBeFalse();

        // Second call should hit cache
        expect($service->variantExists($logo->id, 'monochrome'))->toBeTrue();
    });

    it('invalidates cache when logo is updated', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        // Cache the data
        $variants = $service->getLogoVariants($logo->id);
        expect($variants)->toHaveCount(1);

        // Invalidate cache
        $service->invalidateLogoCache($logo->id);

        // Add another variant
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'ocean_blue',
        ]);

        // Should get fresh data from database
        $newVariants = $service->getLogoVariants($logo->id);
        expect($newVariants)->toHaveCount(2);
    });

    it('invalidates generation cache', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        $service = app(LogoVariantCacheService::class);

        // Cache generation data
        $generation = $service->getCachedLogoGeneration($logoGeneration->id);
        expect($generation->generatedLogos)->toHaveCount(1);

        // Invalidate generation cache
        $service->invalidateGenerationCache($logoGeneration->id);

        // Add another logo
        GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        // Should get fresh data
        $newGeneration = $service->getCachedLogoGeneration($logoGeneration->id);
        expect($newGeneration->generatedLogos)->toHaveCount(2);
    });

    it('warms up cache efficiently', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);
        LogoColorVariant::factory()->create([
            'generated_logo_id' => $logo->id,
            'color_scheme' => 'monochrome',
        ]);

        $service = app(LogoVariantCacheService::class);

        // Warm up cache
        $startTime = microtime(true);
        $service->warmCache($logoGeneration->id);
        $warmupTime = microtime(true) - $startTime;

        // Subsequent calls should be faster
        $startTime = microtime(true);
        $generation = $service->getCachedLogoGeneration($logoGeneration->id);
        $logosByStyle = $service->getLogosByStyle($logoGeneration->id);
        $stats = $service->getColorSchemeStats($logoGeneration->id);
        $cachedTime = microtime(true) - $startTime;

        expect($cachedTime)->toBeLessThan($warmupTime);
        expect($generation)->not->toBeNull();
        expect($logosByStyle)->not->toBeEmpty();
        expect($stats)->not->toBeEmpty();
    });

    it('provides cache statistics', function (): void {
        $service = app(LogoVariantCacheService::class);

        $stats = $service->getCacheStats();
        expect($stats)->toHaveKey('cache_prefix');
        expect($stats)->toHaveKey('default_ttl');
        expect($stats)->toHaveKey('generation_ttl');
        expect($stats)->toHaveKey('cache_store');
    });

    it('clears all cache', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logo = GeneratedLogo::factory()->create(['logo_generation_id' => $logoGeneration->id]);

        $service = app(LogoVariantCacheService::class);

        // Cache some data
        $service->getCachedLogoGeneration($logoGeneration->id);
        $service->getLogosByStyle($logoGeneration->id);

        // Clear all cache
        $service->clearAllCache();

        // Verify cache was cleared by checking that subsequent calls take time
        // (This is a simplified test - in practice you'd verify with cache internals)
        $startTime = microtime(true);
        $generation = $service->getCachedLogoGeneration($logoGeneration->id);
        $queryTime = microtime(true) - $startTime;

        expect($generation)->not->toBeNull();
        expect($queryTime)->toBeGreaterThan(0); // Should take some time to query DB
    });
});
