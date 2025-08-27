<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Logo variant caching service.
 *
 * Provides caching mechanisms for logo color variants to improve
 * performance when displaying logo galleries and variant information.
 */
final class LogoVariantCacheService
{
    private const CACHE_PREFIX = 'logo_variants';

    private const DEFAULT_TTL = 3600; // 1 hour

    private const GENERATION_TTL = 7200; // 2 hours

    /**
     * Get cached color variants for a specific logo and color scheme.
     *
     * @return Collection<int, LogoColorVariant>
     */
    public function getLogoVariants(int $logoId, ?string $colorScheme = null): Collection
    {
        $cacheKey = $this->buildCacheKey('logo', $logoId, $colorScheme);

        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($logoId, $colorScheme): Collection {
            $query = LogoColorVariant::where('generated_logo_id', $logoId);

            if ($colorScheme) {
                $query->where('color_scheme', $colorScheme);
            }

            return $query->get();
        });
    }

    /**
     * Get cached color variants for an entire logo generation.
     *
     * @return Collection<int, LogoColorVariant>
     */
    public function getGenerationVariants(int $generationId): Collection
    {
        $cacheKey = $this->buildCacheKey('generation', $generationId);

        return Cache::remember($cacheKey, self::GENERATION_TTL, fn (): Collection => LogoColorVariant::whereHas('generatedLogo', function ($query) use ($generationId): void {
            $query->where('logo_generation_id', $generationId);
        })
            ->with(['generatedLogo'])
            ->get());
    }

    /**
     * Get cached logo generation with all its logos and variants efficiently.
     */
    public function getCachedLogoGeneration(int $generationId): ?LogoGeneration
    {
        $cacheKey = $this->buildCacheKey('full_generation', $generationId);

        return Cache::remember($cacheKey, self::GENERATION_TTL, fn (): ?LogoGeneration => LogoGeneration::with([
            'generatedLogos' => function ($query): void {
                $query->orderBy('style')->orderBy('variation_number');
            },
            'generatedLogos.colorVariants' => function ($query): void {
                $query->orderBy('color_scheme');
            },
        ])->find($generationId));
    }

    /**
     * Get cached logos grouped by style for a specific generation.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getLogosByStyle(int $generationId): array
    {
        $cacheKey = $this->buildCacheKey('by_style', $generationId);

        return Cache::remember($cacheKey, self::GENERATION_TTL, function () use ($generationId): array {
            $logoGeneration = $this->getCachedLogoGeneration($generationId);

            if (! $logoGeneration) {
                return [];
            }

            return $logoGeneration->generatedLogos
                ->groupBy('style')
                ->map(fn ($logos, $style) => [
                    'style' => $style,
                    'display_name' => ucwords((string) $style),
                    'logos' => $logos->map(fn ($logo) => [
                        'id' => $logo->id,
                        'style' => $logo->style,
                        'variation_number' => $logo->variation_number,
                        'original_file_path' => $logo->original_file_path,
                        'preview_url' => $logo->original_file_path ? asset('storage/'.$logo->original_file_path) : null,
                        'file_size' => $logo->file_size,
                        'color_variants' => $logo->colorVariants->map(fn ($variant) => [
                            'id' => $variant->id,
                            'color_scheme' => $variant->color_scheme,
                            'display_name' => $variant->getColorSchemeDisplayName(),
                            'file_path' => $variant->file_path,
                            'preview_url' => asset('storage/'.$variant->file_path),
                            'file_size' => $variant->file_size,
                        ])->toArray(),
                    ])->toArray(),
                ])
                ->values()
                ->toArray();
        });
    }

    /**
     * Get color scheme statistics for a logo generation.
     *
     * @return array<string, int>
     */
    public function getColorSchemeStats(int $generationId): array
    {
        $cacheKey = $this->buildCacheKey('color_stats', $generationId);

        return Cache::remember($cacheKey, self::GENERATION_TTL, function () use ($generationId): array {
            $variants = $this->getGenerationVariants($generationId);

            return $variants
                ->groupBy('color_scheme')
                ->map(fn ($variants) => $variants->count())
                ->toArray();
        });
    }

    /**
     * Check if a color variant exists for a specific logo and color scheme.
     */
    public function variantExists(int $logoId, string $colorScheme): bool
    {
        $cacheKey = $this->buildCacheKey('exists', $logoId, $colorScheme);

        return Cache::remember($cacheKey, self::DEFAULT_TTL, fn (): bool => LogoColorVariant::where('generated_logo_id', $logoId)
            ->where('color_scheme', $colorScheme)
            ->exists());
    }

    /**
     * Invalidate cache for a specific logo's variants.
     */
    public function invalidateLogoCache(int $logoId): void
    {
        // Get the logo to find its generation
        $logo = GeneratedLogo::find($logoId);
        if (! $logo) {
            return;
        }

        // Clear all logo-specific caches
        $patterns = [
            $this->buildCacheKey('logo', $logoId),
            $this->buildCacheKey('logo', $logoId, '*'),
        ];

        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }

        // Clear generation-level caches
        $this->invalidateGenerationCache($logo->logo_generation_id);
    }

    /**
     * Invalidate cache for an entire logo generation.
     */
    public function invalidateGenerationCache(int $generationId): void
    {
        $keys = [
            $this->buildCacheKey('generation', $generationId),
            $this->buildCacheKey('full_generation', $generationId),
            $this->buildCacheKey('by_style', $generationId),
            $this->buildCacheKey('color_stats', $generationId),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Warm up cache for a logo generation.
     */
    public function warmCache(int $generationId): void
    {
        // Pre-load main generation data
        $this->getCachedLogoGeneration($generationId);

        // Pre-load aggregated data
        $this->getLogosByStyle($generationId);
        $this->getColorSchemeStats($generationId);
        $this->getGenerationVariants($generationId);
    }

    /**
     * Clear all logo variant caches.
     */
    public function clearAllCache(): void
    {
        $this->clearCachePattern(self::CACHE_PREFIX.':*');
    }

    /**
     * Get cache statistics for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        // Note: This is a simple implementation
        // In production, you might want to use Redis commands to get actual stats
        return [
            'cache_prefix' => self::CACHE_PREFIX,
            'default_ttl' => self::DEFAULT_TTL,
            'generation_ttl' => self::GENERATION_TTL,
            'cache_store' => config('cache.default'),
        ];
    }

    /**
     * Build a cache key for logo variants.
     */
    private function buildCacheKey(string $type, int $id, ?string $suffix = null): string
    {
        $key = self::CACHE_PREFIX.":{$type}:{$id}";

        if ($suffix) {
            $key .= ":{$suffix}";
        }

        return $key;
    }

    /**
     * Clear cache entries matching a pattern.
     */
    private function clearCachePattern(string $pattern): void
    {
        // For file/database cache stores, we'll need to manually forget specific keys
        // This is a simplified implementation - in production you might use Redis SCAN

        $keysToCheck = [
            // Logo-specific keys
            str_replace('*', 'monochrome', $pattern),
            str_replace('*', 'ocean_blue', $pattern),
            str_replace('*', 'forest_green', $pattern),
            str_replace('*', 'warm_sunset', $pattern),
            str_replace('*', 'royal_purple', $pattern),
            str_replace('*', 'corporate_navy', $pattern),
            str_replace('*', 'earthy_tones', $pattern),
            str_replace('*', 'tech_blue', $pattern),
            str_replace('*', 'vibrant_pink', $pattern),
            str_replace('*', 'charcoal_gold', $pattern),
        ];

        foreach ($keysToCheck as $key) {
            if ($key !== $pattern) { // Only if pattern was actually replaced
                Cache::forget($key);
            }
        }

        // Also try to forget the pattern itself if it's a direct key
        if (! str_contains($pattern, '*')) {
            Cache::forget($pattern);
        }
    }
}
