<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeneratedLogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Lazy loading service for logo gallery display.
 *
 * Provides efficient lazy loading mechanisms for logo galleries,
 * supporting pagination, infinite scroll, and progressive loading.
 */
final readonly class LazyLoadingService
{
    private const CACHE_PREFIX = 'lazy_loading';

    private const DEFAULT_PAGE_SIZE = 12;

    private const CACHE_TTL = 1800; // 30 minutes

    public function __construct(
        private LogoVariantCacheService $cacheService
    ) {}

    /**
     * Get paginated logos for a generation with lazy loading optimization.
     *
     * @return array<string, mixed>
     */
    public function getPaginatedLogos(
        int $generationId,
        int $page = 1,
        int $perPage = self::DEFAULT_PAGE_SIZE,
        ?string $style = null
    ): array {
        $cacheKey = $this->buildCacheKey('paginated', $generationId, $page, $perPage, $style);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generationId, $page, $perPage, $style): array {
            $query = GeneratedLogo::where('logo_generation_id', $generationId)
                ->with(['logoGeneration', 'colorVariants'])
                ->orderBy('style')
                ->orderBy('variation_number');

            if ($style) {
                $query->where('style', $style);
            }

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $this->formatLogosForDisplay($paginated->items()),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                    'has_more_pages' => $paginated->hasMorePages(),
                ],
                'meta' => [
                    'total_logos' => $paginated->total(),
                    'showing_from' => ($page - 1) * $perPage + 1,
                    'showing_to' => min($page * $perPage, $paginated->total()),
                ],
            ];
        });
    }

    /**
     * Get logos for infinite scroll implementation.
     *
     * @return array<string, mixed>
     */
    public function getLogosForInfiniteScroll(
        int $generationId,
        ?int $lastId = null,
        int $limit = self::DEFAULT_PAGE_SIZE,
        ?string $style = null
    ): array {
        $cacheKey = $this->buildCacheKey('infinite', $generationId, $lastId ?? 0, $limit, $style);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generationId, $lastId, $limit, $style): array {
            $query = GeneratedLogo::where('logo_generation_id', $generationId)
                ->with(['logoGeneration', 'colorVariants'])
                ->orderBy('style')
                ->orderBy('variation_number')
                ->orderBy('id');

            if ($lastId) {
                $query->where('id', '>', $lastId);
            }

            if ($style) {
                $query->where('style', $style);
            }

            $logos = $query->take($limit + 1)->get(); // Get one extra to check if there are more

            $hasMore = $logos->count() > $limit;
            if ($hasMore) {
                $logos = $logos->take($limit);
            }

            return [
                'data' => $this->formatLogosForDisplay($logos),
                'pagination' => [
                    'has_more' => $hasMore,
                    'last_id' => $logos->last()?->id,
                    'count' => $logos->count(),
                ],
                'meta' => [
                    'style_filter' => $style,
                    'generation_id' => $generationId,
                ],
            ];
        });
    }

    /**
     * Get logos grouped by style with lazy loading for each style.
     *
     * @return array<string, mixed>
     */
    public function getLogosByStyleLazy(
        int $generationId,
        int $logosPerStyle = 6,
        bool $loadAll = false
    ): array {
        $cacheKey = $this->buildCacheKey('by_style_lazy', $generationId, $logosPerStyle, $loadAll);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generationId, $logosPerStyle, $loadAll): array {
            // First, get all available styles
            $styles = GeneratedLogo::where('logo_generation_id', $generationId)
                ->select('style')
                ->distinct()
                ->orderBy('style')
                ->pluck('style')
                ->toArray();

            $result = [];

            foreach ($styles as $style) {
                $query = GeneratedLogo::where('logo_generation_id', $generationId)
                    ->where('style', $style)
                    ->with(['logoGeneration', 'colorVariants'])
                    ->orderBy('variation_number');

                if (! $loadAll) {
                    $logos = $query->take($logosPerStyle)->get();
                    $totalCount = GeneratedLogo::where('logo_generation_id', $generationId)
                        ->where('style', $style)
                        ->count();
                } else {
                    $logos = $query->get();
                    $totalCount = $logos->count();
                }

                $result[] = [
                    'style' => $style,
                    'display_name' => ucwords((string) $style),
                    'logos' => $this->formatLogosForDisplay($logos),
                    'meta' => [
                        'total_count' => $totalCount,
                        'loaded_count' => $logos->count(),
                        'has_more' => ! $loadAll && $logos->count() < $totalCount,
                        'can_load_more' => ! $loadAll && $totalCount > $logosPerStyle,
                    ],
                ];
            }

            return $result;
        });
    }

    /**
     * Load more logos for a specific style.
     *
     * @return array<string, mixed>
     */
    public function loadMoreLogosForStyle(
        int $generationId,
        string $style,
        int $offset = 0,
        int $limit = 6
    ): array {
        $cacheKey = $this->buildCacheKey('more_style', $generationId, $style, $offset, $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generationId, $style, $offset, $limit): array {
            $logos = GeneratedLogo::where('logo_generation_id', $generationId)
                ->where('style', $style)
                ->with(['logoGeneration', 'colorVariants'])
                ->orderBy('variation_number')
                ->skip($offset)
                ->take($limit)
                ->get();

            $totalCount = GeneratedLogo::where('logo_generation_id', $generationId)
                ->where('style', $style)
                ->count();

            return [
                'data' => $this->formatLogosForDisplay($logos),
                'meta' => [
                    'style' => $style,
                    'offset' => $offset,
                    'limit' => $limit,
                    'loaded_count' => $logos->count(),
                    'total_count' => $totalCount,
                    'has_more' => ($offset + $logos->count()) < $totalCount,
                ],
            ];
        });
    }

    /**
     * Pre-load logos for performance optimization.
     *
     * @param  array<int, string>  $styles
     */
    public function preloadLogos(int $generationId, array $styles = []): void
    {
        // Warm up cache for common queries
        $this->getPaginatedLogos($generationId, 1); // First page
        $this->getLogosByStyleLazy($generationId, 6, false); // Style overview

        // If specific styles are provided, preload them
        foreach ($styles as $style) {
            $this->getPaginatedLogos($generationId, 1, self::DEFAULT_PAGE_SIZE, $style);
        }
    }

    /**
     * Get thumbnail/preview data optimized for lazy loading.
     *
     * @param  array<int, int>  $logoIds
     * @return array<string, mixed>
     */
    public function getThumbnailData(int $generationId, array $logoIds = []): array
    {
        $cacheKey = $this->buildCacheKey('thumbnails', $generationId, implode(',', $logoIds));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generationId, $logoIds): array {
            $query = GeneratedLogo::where('logo_generation_id', $generationId)
                ->select(['id', 'style', 'variation_number', 'original_file_path', 'file_size', 'image_width', 'image_height']);

            if (! empty($logoIds)) {
                $query->whereIn('id', $logoIds);
            }

            $logos = $query->get();

            return $logos->map(fn ($logo): array => [
                'id' => $logo->id,
                'style' => $logo->style,
                'variation_number' => $logo->variation_number,
                'preview_url' => $logo->original_file_path ? asset('storage/'.$logo->original_file_path) : null,
                'file_size' => $logo->file_size,
                'dimensions' => [
                    'width' => $logo->image_width,
                    'height' => $logo->image_height,
                ],
                // Generate placeholder data for progressive loading
                'placeholder' => $this->generatePlaceholderData($logo->image_width, $logo->image_height),
            ])->toArray();
        });
    }

    /**
     * Invalidate lazy loading cache for a generation.
     */
    public function invalidateCache(int $generationId): void
    {
        $patterns = [
            self::CACHE_PREFIX.":*:{$generationId}:*",
            self::CACHE_PREFIX.":*:{$generationId}",
        ];

        // In a real-world scenario with Redis, we would use SCAN to find and delete keys
        // For now, we'll clear specific known patterns
        $commonKeys = [
            'paginated', 'infinite', 'by_style_lazy', 'thumbnails',
        ];

        foreach ($commonKeys as $type) {
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget($this->buildCacheKey($type, $generationId, $page));
                Cache::forget($this->buildCacheKey($type, $generationId, $page, self::DEFAULT_PAGE_SIZE));
            }
        }

        // Clear the cache service as well
        $this->cacheService->invalidateGenerationCache($generationId);
    }

    /**
     * Format logos for display with lazy loading optimizations.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, GeneratedLogo>|\Illuminate\Support\Collection<int, GeneratedLogo>|array<int, GeneratedLogo>  $logos
     * @return array<int, array<string, mixed>>
     */
    private function formatLogosForDisplay($logos): array
    {
        $logosCollection = $logos instanceof Collection ? $logos : collect($logos);

        return $logosCollection->map(fn ($logo): array => [
            'id' => $logo->id,
            'style' => $logo->style,
            'variation_number' => $logo->variation_number,
            'original_file_path' => $logo->original_file_path,
            'preview_url' => $logo->original_file_path ? asset('storage/'.$logo->original_file_path) : null,
            'file_size' => $logo->file_size,
            'formatted_file_size' => $logo->getFormattedFileSize(),
            'dimensions' => [
                'width' => $logo->image_width,
                'height' => $logo->image_height,
            ],
            'generation_time_ms' => $logo->generation_time_ms,
            'color_variants' => $logo->colorVariants->map(fn ($variant) => [
                'id' => $variant->id,
                'color_scheme' => $variant->color_scheme,
                'display_name' => $variant->getColorSchemeDisplayName(),
                'file_path' => $variant->file_path,
                'preview_url' => asset('storage/'.$variant->file_path),
                'file_size' => $variant->file_size,
            ])->toArray(),
            'placeholder' => $this->generatePlaceholderData($logo->image_width, $logo->image_height),
        ])->toArray();
    }

    /**
     * Generate placeholder data for progressive image loading.
     *
     * @return array<string, mixed>
     */
    private function generatePlaceholderData(int $width, int $height): array
    {
        // Generate a simple base64-encoded placeholder SVG
        $aspectRatio = $height > 0 ? $width / $height : 1;
        $placeholderSvg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="#f3f4f6"/><text x="50%%" y="50%%" text-anchor="middle" dy="0.35em" font-family="sans-serif" font-size="14" fill="#9ca3af">Loading...</text></svg>',
            $width,
            $height,
            $width,
            $height
        );

        return [
            'data_url' => 'data:image/svg+xml;base64,'.base64_encode($placeholderSvg),
            'aspect_ratio' => $aspectRatio,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Build cache key for lazy loading operations.
     *
     * @param  mixed  ...$params
     */
    private function buildCacheKey(string $type, ...$params): string
    {
        $filteredParams = array_filter($params, fn ($param) => $param !== null);

        return self::CACHE_PREFIX.':'.$type.':'.implode(':', $filteredParams);
    }
}
