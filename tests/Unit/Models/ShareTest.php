<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\ShareAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('Share Model', function (): void {
    it('creates a share with required attributes', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create();

        $share = Share::create([
            'uuid' => Str::uuid()->toString(),
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
            'title' => 'Test Share',
            'share_type' => 'public',
        ]);

        expect($share)->toBeInstanceOf(Share::class);
        expect($share->uuid)->not->toBeNull();
        expect($share->shareable_type)->toBe(LogoGeneration::class);
        expect($share->shareable_id)->toBe($logoGeneration->id);
        expect($share->user_id)->toBe($user->id);
        expect($share->share_type)->toBe('public');
        expect($share->is_active)->toBeTrue();
        expect($share->view_count)->toBe(0);
    });

    it('generates UUID automatically if not provided', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create();

        $share = Share::create([
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
            'share_type' => 'public',
        ]);

        expect($share->uuid)->not->toBeNull();
        expect(Str::isUuid($share->uuid))->toBeTrue();
    });

    it('handles password-protected shares with proper hashing', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);
        $password = 'secret123';

        $share = Share::create([
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
            'share_type' => 'password_protected',
            'password' => $password,
        ]);

        expect($share->share_type)->toBe('password_protected');
        expect($share->password_hash)->not->toBeNull();
        expect($share->password_hash)->not->toBe($password);
        expect(Hash::check($password, $share->password_hash))->toBeTrue();
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $share = Share::factory()->create([
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
        ]);

        expect($share->user)->toBeInstanceOf(User::class);
        expect($share->user->id)->toBe($user->id);
    });

    it('has polymorphic relationship to shareable models', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $share = Share::factory()->create([
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
        ]);

        expect($share->shareable)->toBeInstanceOf(LogoGeneration::class);
        expect($share->shareable->id)->toBe($logoGeneration->id);
    });

    it('has many share accesses for analytics', function (): void {
        $user = User::factory()->create();
        $logoGeneration = LogoGeneration::factory()->create(['user_id' => $user->id]);

        $share = Share::factory()->create([
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
            'user_id' => $user->id,
        ]);

        ShareAccess::factory()->count(3)->create(['share_id' => $share->id]);

        $share->refresh();

        expect($share->accesses)->toHaveCount(3);
        expect($share->accesses->first())->toBeInstanceOf(ShareAccess::class);
    });

    it('provides share URL generation', function (): void {
        $share = Share::factory()->create();

        $url = $share->getShareUrl();

        expect($url)->toContain('/share/');
        expect($url)->toContain($share->uuid);
        expect($url)->toStartWith('http');
    });

    it('checks if share is expired', function (): void {
        $activeShare = Share::factory()->create(['expires_at' => null]);
        $expiredShare = Share::factory()->create(['expires_at' => now()->subDay()]);
        $futureShare = Share::factory()->create(['expires_at' => now()->addDay()]);

        expect($activeShare->isExpired())->toBeFalse();
        expect($expiredShare->isExpired())->toBeTrue();
        expect($futureShare->isExpired())->toBeFalse();
    });

    it('checks if share is accessible', function (): void {
        $activeShare = Share::factory()->create([
            'is_active' => true,
            'expires_at' => null,
        ]);

        $inactiveShare = Share::factory()->create([
            'is_active' => false,
            'expires_at' => null,
        ]);

        $expiredShare = Share::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        expect($activeShare->isAccessible())->toBeTrue();
        expect($inactiveShare->isAccessible())->toBeFalse();
        expect($expiredShare->isAccessible())->toBeFalse();
    });

    it('records share access and updates view count', function (): void {
        $share = Share::factory()->create([
            'view_count' => 5,
            'last_viewed_at' => null,
        ]);

        $share->recordAccess('192.168.1.1', 'Mozilla/5.0', 'https://example.com');

        $share->refresh();

        expect($share->view_count)->toBe(6);
        expect($share->last_viewed_at)->not->toBeNull();
        expect($share->accesses)->toHaveCount(1);

        $access = $share->accesses->first();
        expect($access->ip_address)->toBe('192.168.1.1');
        expect($access->user_agent)->toBe('Mozilla/5.0');
        expect($access->referrer)->toBe('https://example.com');
    });

    it('validates password for protected shares', function (): void {
        $password = 'secret123';
        $share = Share::factory()->create([
            'share_type' => 'password_protected',
            'password' => $password,
        ]);

        expect($share->validatePassword($password))->toBeTrue();
        expect($share->validatePassword('wrong'))->toBeFalse();

        $publicShare = Share::factory()->create(['share_type' => 'public']);
        expect($publicShare->validatePassword('any'))->toBeTrue();
    });

    it('scopes to active shares', function (): void {
        Share::factory()->create(['is_active' => true]);
        Share::factory()->create(['is_active' => false]);
        Share::factory()->create(['is_active' => true, 'expires_at' => now()->subDay()]);

        $activeShares = Share::active()->get();

        expect($activeShares)->toHaveCount(1);
    });

    it('scopes to accessible shares', function (): void {
        Share::factory()->create(['is_active' => true, 'expires_at' => null]);
        Share::factory()->create(['is_active' => false, 'expires_at' => null]);
        Share::factory()->create(['is_active' => true, 'expires_at' => now()->subDay()]);
        Share::factory()->create(['is_active' => true, 'expires_at' => now()->addDay()]);

        $accessibleShares = Share::accessible()->get();

        expect($accessibleShares)->toHaveCount(2);
    });

    it('casts settings as array', function (): void {
        $settings = ['theme' => 'dark', 'layout' => 'grid'];

        $share = Share::factory()->create(['settings' => $settings]);

        expect($share->settings)->toBe($settings);
        expect($share->settings)->toBeArray();
    });

    it('has proper fillable attributes', function (): void {
        $fillable = [
            'uuid', 'shareable_type', 'shareable_id', 'user_id', 'title',
            'description', 'share_type', 'password', 'expires_at', 'is_active', 'settings', 'last_viewed_at',
        ];

        $share = new Share;

        expect($share->getFillable())->toBe($fillable);
    });
});
