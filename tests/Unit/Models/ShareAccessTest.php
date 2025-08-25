<?php

declare(strict_types=1);

use App\Models\Share;
use App\Models\ShareAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('ShareAccess Model', function (): void {
    it('creates a share access record with required attributes', function (): void {
        $share = Share::factory()->create();

        $shareAccess = ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
            'referrer' => 'https://example.com',
        ]);

        expect($shareAccess)->toBeInstanceOf(ShareAccess::class);
        expect($shareAccess->share_id)->toBe($share->id);
        expect($shareAccess->ip_address)->toBe('192.168.1.1');
        expect($shareAccess->user_agent)->toBe('Mozilla/5.0 (compatible; TestBot/1.0)');
        expect($shareAccess->referrer)->toBe('https://example.com');
        expect($shareAccess->accessed_at)->not->toBeNull();
    });

    it('belongs to a share', function (): void {
        $share = Share::factory()->create();
        $shareAccess = ShareAccess::factory()->create(['share_id' => $share->id]);

        expect($shareAccess->share)->toBeInstanceOf(Share::class);
        expect($shareAccess->share->id)->toBe($share->id);
    });

    it('automatically sets accessed_at timestamp', function (): void {
        $share = Share::factory()->create();

        $shareAccess = ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => '192.168.1.1',
        ]);

        expect($shareAccess->accessed_at)->not->toBeNull();
        expect($shareAccess->accessed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('handles IPv6 addresses', function (): void {
        $share = Share::factory()->create();
        $ipv6Address = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $shareAccess = ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => $ipv6Address,
        ]);

        expect($shareAccess->ip_address)->toBe($ipv6Address);
    });

    it('handles long user agent strings', function (): void {
        $share = Share::factory()->create();
        $longUserAgent = str_repeat('Mozilla/5.0 ', 100); // Very long user agent

        $shareAccess = ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => $longUserAgent,
        ]);

        expect($shareAccess->user_agent)->toBe($longUserAgent);
    });

    it('handles null optional fields gracefully', function (): void {
        $share = Share::factory()->create();

        $shareAccess = ShareAccess::create([
            'share_id' => $share->id,
            'ip_address' => null,
            'user_agent' => null,
            'referrer' => null,
        ]);

        expect($shareAccess->ip_address)->toBeNull();
        expect($shareAccess->user_agent)->toBeNull();
        expect($shareAccess->referrer)->toBeNull();
    });

    it('scopes by date range', function (): void {
        $share = Share::factory()->create();

        // Create accesses at different times
        ShareAccess::factory()->create([
            'share_id' => $share->id,
            'accessed_at' => now()->subDays(5),
        ]);

        ShareAccess::factory()->create([
            'share_id' => $share->id,
            'accessed_at' => now()->subDays(2),
        ]);

        ShareAccess::factory()->create([
            'share_id' => $share->id,
            'accessed_at' => now()->subHours(1),
        ]);

        $recentAccesses = ShareAccess::where('accessed_at', '>=', now()->subDays(3))->get();

        expect($recentAccesses)->toHaveCount(2);
    });

    it('provides analytics methods', function (): void {
        $share = Share::factory()->create();

        ShareAccess::factory()->count(5)->create([
            'share_id' => $share->id,
            'ip_address' => '192.168.1.1',
        ]);

        ShareAccess::factory()->count(3)->create([
            'share_id' => $share->id,
            'ip_address' => '10.0.0.1',
        ]);

        $uniqueIps = ShareAccess::where('share_id', $share->id)
            ->distinct('ip_address')
            ->count('ip_address');

        $totalAccesses = ShareAccess::where('share_id', $share->id)->count();

        expect($uniqueIps)->toBe(2);
        expect($totalAccesses)->toBe(8);
    });

    it('has proper fillable attributes', function (): void {
        $fillable = ['share_id', 'ip_address', 'user_agent', 'referrer'];

        $shareAccess = new ShareAccess;

        expect($shareAccess->getFillable())->toBe($fillable);
    });

    it('cascades delete when share is deleted', function (): void {
        $share = Share::factory()->create();
        $shareAccess = ShareAccess::factory()->create(['share_id' => $share->id]);

        expect(ShareAccess::count())->toBe(1);

        $share->delete();

        expect(ShareAccess::count())->toBe(0);
    });
});
