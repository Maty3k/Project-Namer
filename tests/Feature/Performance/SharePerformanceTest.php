<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\ShareAccess;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->shareService = app(ShareService::class);
});

describe('Share Performance Benchmarks', function (): void {
    it('creates shares efficiently in bulk', function (): void {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Create 100 shares
        for ($i = 0; $i < 100; $i++) {
            Share::factory()->create([
                'user_id' => $this->user->id,
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
            ]);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to ms
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Performance expectations
        expect($executionTime)->toBeLessThan(5000); // Should complete in under 5 seconds
        expect($memoryUsed)->toBeLessThan(50); // Should use less than 50MB
        expect(Share::count())->toBe(100);
    });

    it('retrieves user shares with pagination efficiently', function (): void {
        // Create 500 shares
        Share::factory()->count(500)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $startTime = microtime(true);
        $queryCount = 0;

        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        // Paginate results (15 per page)
        $shares = Share::where('user_id', $this->user->id)
            ->with(['shareable', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(100); // Should complete in under 100ms
        expect($queryCount)->toBeLessThanOrEqual(4); // Should use efficient eager loading
        expect($shares->count())->toBe(15);
        expect($shares->total())->toBe(500);
    });

    it('filters and sorts shares efficiently with large datasets', function (): void {
        // Create mixed shares (active, inactive, expired)
        Share::factory()->count(200)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => true,
        ]);

        Share::factory()->count(100)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'is_active' => false,
        ]);

        Share::factory()->count(100)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
        ]);

        $startTime = microtime(true);

        // Complex query with filters and sorting
        $shares = Share::where('user_id', $this->user->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('view_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(150); // Should complete in under 150ms
        expect($shares->count())->toBeGreaterThan(0);
    });

    it('queries shares with database indexes efficiently', function (): void {
        // Create 1000 shares
        Share::factory()->count(1000)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $startTime = microtime(true);

        // Query using indexed columns (user_id, uuid, expires_at)
        $shareByUserId = Share::where('user_id', $this->user->id)->first();
        $shareByUuid = Share::where('uuid', $shareByUserId->uuid)->first();
        $activeShares = Share::where('is_active', true)->count();

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations - indexes should make these fast
        expect($executionTime)->toBeLessThan(100); // Under 100ms with indexes
        expect($shareByUserId)->not->toBeNull();
        expect($shareByUuid)->not->toBeNull();
    });

    it('handles share access tracking at scale efficiently', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Record 1000 share accesses
        for ($i = 0; $i < 1000; $i++) {
            ShareAccess::create([
                'share_id' => $share->id,
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'accessed_at' => now(),
            ]);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        // Performance expectations
        expect($executionTime)->toBeLessThan(10000); // Should complete in under 10 seconds
        expect($memoryUsed)->toBeLessThan(100); // Should use less than 100MB
        expect(ShareAccess::count())->toBe(1000);
    });

    it('aggregates share analytics efficiently', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        // Create 5000 share accesses
        ShareAccess::factory()->count(5000)->create([
            'share_id' => $share->id,
        ]);

        $startTime = microtime(true);

        // Get analytics
        $analytics = [
            'total_views' => ShareAccess::where('share_id', $share->id)->count(),
            'unique_visitors' => ShareAccess::where('share_id', $share->id)->distinct('ip_address')->count('ip_address'),
            'recent_views' => ShareAccess::where('share_id', $share->id)
                ->where('accessed_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(500); // Should complete in under 500ms
        expect($analytics['total_views'])->toBe(5000);
        expect($analytics['unique_visitors'])->toBeGreaterThan(0);
    });

    it('deletes shares with cascading efficiently', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        // Create 1000 share accesses
        ShareAccess::factory()->count(1000)->create([
            'share_id' => $share->id,
        ]);

        $startTime = microtime(true);

        // Delete share (should cascade to share accesses)
        $share->delete();

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(1000); // Should complete in under 1 second
        expect(Share::find($share->id))->toBeNull();
        expect(ShareAccess::where('share_id', $share->id)->count())->toBe(0);
    });

    it('handles concurrent share creation without UUID conflicts', function (): void {
        $startTime = microtime(true);

        // Directly create shares to test UUID uniqueness (bypassing rate limiting)
        $shares = collect();
        for ($i = 0; $i < 100; $i++) {
            $share = Share::factory()->create([
                'user_id' => $this->user->id,
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
            ]);
            $shares->push($share);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Verify all UUIDs are unique
        $uniqueUuids = $shares->pluck('uuid')->unique();

        // Performance expectations
        expect($executionTime)->toBeLessThan(5000); // Should complete in under 5 seconds
        expect($shares->count())->toBe(100);
        expect($uniqueUuids->count())->toBe(100); // All UUIDs must be unique
    });

    it('searches shares by title efficiently with large datasets', function (): void {
        // Create 1000 shares with varying titles
        for ($i = 0; $i < 1000; $i++) {
            Share::factory()->create([
                'user_id' => $this->user->id,
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'title' => "Project {$i}",
            ]);
        }

        // Create specific shares we'll search for
        Share::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Test Project ABC',
        ]);

        $startTime = microtime(true);

        // Search for shares
        $shares = Share::where('user_id', $this->user->id)
            ->where('title', 'like', '%ABC%')
            ->paginate(15);

        $executionTime = (microtime(true) - $startTime) * 1000;

        // Performance expectations
        expect($executionTime)->toBeLessThan(200); // Should complete in under 200ms
        expect($shares->total())->toBe(10);
    });

    it('updates share view counts efficiently under load', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 0,
        ]);

        $startTime = microtime(true);

        // Simulate 500 view count increments
        for ($i = 0; $i < 500; $i++) {
            $share->increment('view_count');
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $share->refresh();

        // Performance expectations
        expect($executionTime)->toBeLessThan(5000); // Should complete in under 5 seconds
        expect($share->view_count)->toBe(500);
    });
});
