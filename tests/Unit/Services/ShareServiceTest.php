<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('ShareService', function (): void {
    beforeEach(function (): void {
        $this->shareService = app(ShareService::class);
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create();
    });

    it('creates a public share with valid data', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'My Awesome Logos',
            'description' => 'Check out these amazing logo designs',
            'share_type' => 'public',
        ];

        $share = $this->shareService->createShare($this->user, $shareData);

        expect($share)->toBeInstanceOf(Share::class);
        expect($share->user_id)->toBe($this->user->id);
        expect($share->title)->toBe('My Awesome Logos');
        expect($share->share_type)->toBe('public');
        expect($share->uuid)->not->toBeNull();
        expect($share->is_active)->toBeTrue();
    });

    it('creates a password-protected share with hashed password', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Private Logo Share',
            'share_type' => 'password_protected',
            'password' => 'secret123',
        ];

        $share = $this->shareService->createShare($this->user, $shareData);

        expect($share->share_type)->toBe('password_protected');
        expect($share->password_hash)->not->toBeNull();
        expect($share->validatePassword('secret123'))->toBeTrue();
        expect($share->validatePassword('wrong'))->toBeFalse();
    });

    it('validates share data before creation', function (): void {
        $invalidData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => 99999, // Non-existent ID
            'share_type' => 'public',
        ];

        expect(fn () => $this->shareService->createShare($this->user, $invalidData))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('requires password for password-protected shares', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'password_protected',
            // Missing password
        ];

        expect(fn () => $this->shareService->createShare($this->user, $shareData))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('handles share expiration correctly', function (): void {
        $futureDate = now()->addDays(7);
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
            'expires_at' => $futureDate,
        ];

        $share = $this->shareService->createShare($this->user, $shareData);

        expect($share->expires_at->format('Y-m-d'))->toBe($futureDate->format('Y-m-d'));
        expect($share->isExpired())->toBeFalse();
        expect($share->isAccessible())->toBeTrue();
    });

    it('validates share access with proper authentication', function (): void {
        $share = Share::factory()->passwordProtected('test123')->create();

        $result = $this->shareService->validateShareAccess($share->uuid, 'test123');
        expect($result['success'])->toBeTrue();
        expect($result['share'])->toBeInstanceOf(Share::class);

        $result = $this->shareService->validateShareAccess($share->uuid, 'wrong');
        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Invalid password');
    });

    it('records share access with analytics', function (): void {
        $share = Share::factory()->public()->create([
            'view_count' => 0,
        ]);

        $this->shareService->recordShareAccess($share, [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'referrer' => 'https://example.com',
        ]);

        $share->refresh();
        expect($share->view_count)->toBe(1);
        expect($share->last_viewed_at)->not->toBeNull();
        expect($share->accesses)->toHaveCount(1);

        $access = $share->accesses->first();
        expect($access->ip_address)->toBe('192.168.1.1');
    });

    it('generates social media metadata for shares', function (): void {
        $share = Share::factory()->create([
            'title' => 'Amazing Logo Designs',
            'description' => 'Check out these creative logos for tech startups',
        ]);

        $metadata = $this->shareService->generateSocialMediaMetadata($share);

        expect($metadata)->toHaveKey('og:title');
        expect($metadata)->toHaveKey('og:description');
        expect($metadata)->toHaveKey('og:url');
        expect($metadata)->toHaveKey('og:type');
        expect($metadata)->toHaveKey('twitter:card');

        expect($metadata['og:title'])->toBe('Amazing Logo Designs');
        expect($metadata['og:description'])->toBe('Check out these creative logos for tech startups');
        expect($metadata['og:url'])->toContain($share->uuid);
    });

    it('enforces rate limiting for share creation', function (): void {
        // Mock rate limiter to simulate hitting the limit
        RateLimiter::shouldReceive('tooManyAttempts')
            ->with("share-creation:{$this->user->id}", 10)
            ->andReturn(true);

        RateLimiter::shouldReceive('availableIn')
            ->with("share-creation:{$this->user->id}")
            ->andReturn(300);

        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ];

        expect(fn () => $this->shareService->createShare($this->user, $shareData))
            ->toThrow(\Illuminate\Http\Exceptions\ThrottleRequestsException::class);
    });

    it('allows rate limiting bypass for valid requests', function (): void {
        RateLimiter::shouldReceive('tooManyAttempts')
            ->with("share-creation:{$this->user->id}", 10)
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->with("share-creation:{$this->user->id}", 3600)
            ->once();

        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ];

        $share = $this->shareService->createShare($this->user, $shareData);
        expect($share)->toBeInstanceOf(Share::class);
    });

    it('updates share settings and preferences', function (): void {
        $share = Share::factory()->create();

        $newSettings = [
            'theme' => 'dark',
            'layout' => 'grid',
            'show_domains' => true,
        ];

        $updatedShare = $this->shareService->updateShare($share, [
            'title' => 'Updated Title',
            'settings' => $newSettings,
        ]);

        expect($updatedShare->title)->toBe('Updated Title');
        expect($updatedShare->settings)->toBe($newSettings);
    });

    it('deactivates shares instead of hard delete', function (): void {
        $share = Share::factory()->create(['is_active' => true]);

        $this->shareService->deactivateShare($share);

        $share->refresh();
        expect($share->is_active)->toBeFalse();
        expect(Share::find($share->id))->not->toBeNull(); // Still exists
    });

    it('gets user shares with pagination and filtering', function (): void {
        Share::factory()->count(15)->create(['user_id' => $this->user->id, 'share_type' => 'public']);
        Share::factory()->count(5)->create(['user_id' => $this->user->id, 'share_type' => 'password_protected']);

        $result = $this->shareService->getUserShares($this->user, [
            'per_page' => 10,
            'share_type' => 'public',
        ]);

        expect($result)->toHaveKey('data');
        expect($result)->toHaveKey('pagination');
        expect($result['data'])->toHaveCount(10);
        expect($result['pagination']['total'])->toBe(15);
    });

    it('generates share analytics data', function (): void {
        $share = Share::factory()->create([
            'view_count' => 0,
        ]);

        // Record some access through the service (which updates view_count)
        $this->shareService->recordShareAccess($share, [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ]);
        $this->shareService->recordShareAccess($share, [
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Test Browser',
        ]);
        $this->shareService->recordShareAccess($share, [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
        ]);

        $analytics = $this->shareService->getShareAnalytics($share);

        expect($analytics)->toHaveKey('total_views');
        expect($analytics)->toHaveKey('unique_visitors');
        expect($analytics)->toHaveKey('recent_views');
        expect($analytics['total_views'])->toBe(3);
        expect($analytics['unique_visitors'])->toBe(2);
    });

    it('validates expired shares are not accessible', function (): void {
        $expiredShare = Share::factory()->expired()->create();

        $result = $this->shareService->validateShareAccess($expiredShare->uuid);

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Share has expired');
    });

    it('handles inactive shares properly', function (): void {
        $inactiveShare = Share::factory()->inactive()->create();

        $result = $this->shareService->validateShareAccess($inactiveShare->uuid);

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Share not found or inactive');
    });
});
