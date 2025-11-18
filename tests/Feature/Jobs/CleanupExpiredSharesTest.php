<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

describe('CleanupExpiredShares Job', function (): void {
    it('deactivates expired shares', function (): void {
        $expiredShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $activeShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        $expiredShare->refresh();
        $activeShare->refresh();

        expect($expiredShare->is_active)->toBeFalse();
        expect($activeShare->is_active)->toBeTrue();
    });

    it('does not affect shares without expiration', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => null,
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        $share->refresh();
        expect($share->is_active)->toBeTrue();
    });

    it('deletes expired shares older than 30 days', function (): void {
        // Create old inactive share (updated_at > 30 days ago)
        $oldExpiredShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDays(35),
            'is_active' => false,
            'updated_at' => now()->subDays(31),
        ]);

        // Create recently inactive share (updated_at < 30 days ago)
        $recentExpiredShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDays(5),
            'is_active' => false,
            'updated_at' => now()->subDays(5),
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        expect(Share::find($oldExpiredShare->id))->toBeNull();
        expect(Share::find($recentExpiredShare->id))->not->toBeNull();
    });

    it('logs cleanup operations', function (): void {
        Log::spy();

        Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Expired shares cleanup completed'));
    });

    it('handles multiple expired shares', function (): void {
        Share::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        $activeExpiredShares = Share::where('expires_at', '<', now())
            ->where('is_active', true)
            ->count();

        expect($activeExpiredShares)->toBe(0);
    });

    it('does not delete recently expired shares', function (): void {
        $recentShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subHours(2),
            'is_active' => false,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        expect(Share::find($recentShare->id))->not->toBeNull();
    });

    it('processes shares in batches', function (): void {
        Share::factory()->count(150)->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        $activeExpiredShares = Share::where('expires_at', '<', now())
            ->where('is_active', true)
            ->count();

        expect($activeExpiredShares)->toBe(0);
    });

    it('updates deactivated_at timestamp', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        dispatch_sync(new \App\Jobs\CleanupExpiredShares);

        $share->refresh();
        expect($share->deactivated_at)->not->toBeNull();
        expect($share->deactivated_at->isToday())->toBeTrue();
    });
});
