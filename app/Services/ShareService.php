<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * ShareService handles all business logic for creating and managing shares.
 *
 * Provides functionality for creating public and password-protected shares,
 * validating access, recording analytics, and generating social media metadata.
 */
final readonly class ShareService
{
    private const RATE_LIMIT_MAX_ATTEMPTS = 10;

    private const RATE_LIMIT_DECAY_MINUTES = 60;

    public function __construct(
        private ShareMonitoringService $monitoringService
    ) {}

    /**
     * Create a new share with validation and rate limiting.
     *
     * @param  array<string, mixed>  $shareData
     *
     * @throws ValidationException
     * @throws ThrottleRequestsException
     */
    public function createShare(User $user, array $shareData): Share
    {
        // Check rate limiting
        $rateLimitKey = "share-creation:{$user->id}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw new ThrottleRequestsException("Too many share creation attempts. Try again in {$seconds} seconds.");
        }

        // Validate share data
        $validated = $this->validateShareData($shareData);

        // Create the share
        $share = Share::create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        // Track rate limiting
        RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY_MINUTES * 60);

        return $share;
    }

    /**
     * Validate share access with password authentication if required.
     * Includes caching for frequently accessed shares.
     *
     * @return array<string, mixed>
     */
    public function validateShareAccess(string $uuid, ?string $password = null): array
    {
        $cacheKey = "share_access:{$uuid}";

        // For public shares, cache the validation result
        if ($password === null) {
            $cachedResult = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($uuid) {
                $share = Share::where('uuid', $uuid)->first();

                if (! $share) {
                    return ['success' => false, 'error' => 'Share not found'];
                }

                if (! $share->is_active) {
                    return ['success' => false, 'error' => 'Share not found or inactive'];
                }

                if ($share->isExpired()) {
                    return ['success' => false, 'error' => 'Share has expired'];
                }

                return ['success' => true, 'share' => $share];
            });

            return $cachedResult;
        }

        // For password-protected shares, don't cache but still validate normally
        $share = Share::where('uuid', $uuid)->first();

        if (! $share) {
            return ['success' => false, 'error' => 'Share not found'];
        }

        if (! $share->is_active) {
            return ['success' => false, 'error' => 'Share not found or inactive'];
        }

        if ($share->isExpired()) {
            return ['success' => false, 'error' => 'Share has expired'];
        }

        if ($share->share_type === 'password_protected') {
            // At this point, $password should not be null since we handled null case above
            if (! $share->validatePassword($password ?: '')) {
                return ['success' => false, 'error' => 'Invalid password'];
            }
        }

        return ['success' => true, 'share' => $share];
    }

    /**
     * Record access to a share with analytics data and monitoring.
     *
     * @param  array<string, mixed>  $accessData
     */
    public function recordShareAccess(Share $share, array $accessData = []): void
    {
        // Record the access in the database
        $share->recordAccess(
            $accessData['ip_address'] ?? null,
            $accessData['user_agent'] ?? null,
            $accessData['referrer'] ?? null
        );

        // Monitor the access for security patterns
        $this->monitoringService->monitorAccess($share, $accessData);
    }

    /**
     * Generate social media metadata for a share.
     * Caches metadata for frequently requested shares.
     *
     * @return array<string, string>
     */
    public function generateSocialMediaMetadata(Share $share): array
    {
        $cacheKey = "share_metadata:{$share->uuid}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($share) {
            $showTitle = $share->settings['show_title'] ?? true;
            $showDescription = $share->settings['show_description'] ?? true;

            $title = $showTitle ? ($share->title ?: 'Shared Logo Designs') : 'Shared Logo Designs';
            $description = $showDescription ? ($share->description ?: 'Check out these amazing logo designs created with our AI-powered generator.') : 'Check out these amazing logo designs created with our AI-powered generator.';
            $url = $share->getShareUrl();

            return [
                // Open Graph tags
                'og:title' => $title,
                'og:description' => $description,
                'og:url' => $url,
                'og:type' => 'website',
                'og:site_name' => config('app.name'),
                'og:locale' => 'en_US',

                // Twitter Card tags
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $title,
                'twitter:description' => $description,
                'twitter:url' => $url,

                // Additional meta tags
                'description' => $description,
                'author' => $share->user->name ?? 'Anonymous',
            ];
        });
    }

    /**
     * Update an existing share with new data.
     * Clears related cache entries.
     *
     * @param  array<string, mixed>  $updateData
     */
    public function updateShare(Share $share, array $updateData): Share
    {
        $validated = $this->validateUpdateData($updateData);

        $share->update($validated);

        // Clear cache for this share
        Cache::forget("share_access:{$share->uuid}");
        Cache::forget("share_metadata:{$share->uuid}");

        return $share->fresh();
    }

    /**
     * Deactivate a share (soft delete).
     * Clears related cache entries.
     */
    public function deactivateShare(Share $share): void
    {
        $share->update(['is_active' => false]);

        // Clear cache for this share
        Cache::forget("share_access:{$share->uuid}");
        Cache::forget("share_metadata:{$share->uuid}");
    }

    /**
     * Get user's shares with pagination and filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getUserShares(User $user, array $filters = []): array
    {
        $query = Share::where('user_id', $user->id)
            ->with(['shareable'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['share_type'])) {
            $query->where('share_type', $filters['share_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
        ];
    }

    /**
     * Get analytics data for a share.
     *
     * @return array<string, mixed>
     */
    public function getShareAnalytics(Share $share): array
    {
        $accesses = $share->accesses();

        return [
            'total_views' => $share->view_count,
            'unique_visitors' => $accesses->distinct('ip_address')->count(),
            'recent_views' => $accesses->where('accessed_at', '>=', now()->subDays(7))->count(),
            'today_views' => $accesses->whereDate('accessed_at', today())->count(),
            'peak_day' => $this->getPeakAccessDay($share),
            'referrer_stats' => $this->getReferrerStats($share),
        ];
    }

    /**
     * Validate share creation data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateShareData(array $data): array
    {
        $validator = Validator::make($data, [
            'shareable_type' => ['required', 'string'],
            'shareable_id' => ['required', 'integer', 'exists:logo_generations,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'share_type' => ['required', Rule::in(['public', 'password_protected'])],
            'password' => ['required_if:share_type,password_protected', 'string', 'min:6'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'settings' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate share update data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateUpdateData(array $data): array
    {
        $validator = Validator::make($data, [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
        ]);

        return $validator->validated();
    }

    /**
     * Get the day with the most accesses for a share.
     *
     * @return array<string, mixed>
     */
    private function getPeakAccessDay(Share $share): array
    {
        $peakDay = $share->accesses()
            ->selectRaw('DATE(accessed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('count', 'desc')
            ->first();

        /** @var object{date: string, count: int}|null $peakDay */
        return $peakDay ? [
            'date' => $peakDay->date,
            'views' => $peakDay->count,
        ] : ['date' => null, 'views' => 0];
    }

    /**
     * Get referrer statistics for a share.
     *
     * @return array<array<string, mixed>>
     */
    private function getReferrerStats(Share $share): array
    {
        $result = $share->accesses()
            ->whereNotNull('referrer')
            ->selectRaw('referrer, COUNT(*) as count')
            ->groupBy('referrer')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        /** @var \Illuminate\Support\Collection<int, array{referrer: string, views: int}> $mapped */
        $mapped = $result->map(fn ($item) => [
            'referrer' => $item->getAttribute('referrer'),
            'views' => (int) $item->getAttribute('count'),
        ]);

        return $mapped->all();
    }
}
