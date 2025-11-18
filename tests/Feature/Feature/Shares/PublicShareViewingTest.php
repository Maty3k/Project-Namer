<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\ShareAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Setup CSRF token for all tests
    $this->withSession(['_token' => 'test-token'])
        ->withHeaders(['X-CSRF-TOKEN' => 'test-token']);
});

describe('Public Share Viewing', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('displays public share content correctly', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'My Amazing Project',
            'description' => 'Check out these cool names',
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewIs('shares.show')
            ->assertViewHas('share')
            ->assertSee('My Amazing Project')
            ->assertSee('Check out these cool names');

        $viewShare = $response->viewData('share');
        expect($viewShare->uuid)->toBe($share->uuid);
    });

    it('shows password form for password-protected share', function (): void {
        $share = Share::factory()->passwordProtected('test123')->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Private Project',
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewIs('shares.password')
            ->assertSee('password')
            ->assertSee('Private Project');
    });

    it('allows password verification for protected shares', function (): void {
        $share = Share::factory()->passwordProtected('correct-password')->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->from(route('public-share.show', $share->uuid))
            ->post(route('public-share.authenticate', $share->uuid), [
                'password' => 'correct-password',
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('public-share.show', $share->uuid));
        expect(session("share_authenticated_{$share->uuid}"))->toBeTrue();
    });

    it('rejects incorrect password for protected shares', function (): void {
        $share = Share::factory()->passwordProtected('correct-password')->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->from(route('public-share.show', $share->uuid))
            ->post(route('public-share.authenticate', $share->uuid), [
                'password' => 'wrong-password',
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('public-share.show', $share->uuid))
            ->assertSessionHasErrors('password');
        expect(session("share_authenticated_{$share->uuid}"))->toBeFalsy();
    });

    it('shows content after successful password authentication', function (): void {
        $share = Share::factory()->passwordProtected('test123')->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Protected Share',
        ]);

        // Authenticate
        $this->from(route('public-share.show', $share->uuid))
            ->post(route('public-share.authenticate', $share->uuid), [
                'password' => 'test123',
                '_token' => 'test-token',
            ]);

        // Now view should show content
        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewIs('shares.show')
            ->assertSee('Protected Share');
    });

    it('returns 404 for expired shares', function (): void {
        $share = Share::factory()->expired()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertNotFound();
    });

    it('returns 404 for inactive shares', function (): void {
        $share = Share::factory()->inactive()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertNotFound();
    });

    it('returns 404 for non-existent share', function (): void {
        $response = $this->get(route('public-share.show', 'invalid-uuid-12345'));

        $response->assertNotFound();
    });

    it('records share access with analytics data', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 0,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 Test Browser',
            'Referer' => 'https://example.com',
        ])->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful();

        $share->refresh();
        expect($share->view_count)->toBe(1);
        expect($share->last_viewed_at)->not->toBeNull();

        $access = ShareAccess::where('share_id', $share->id)->first();
        expect($access)->not->toBeNull();
        expect($access->user_agent)->toBe('Mozilla/5.0 Test Browser');
        expect($access->referrer)->toBe('https://example.com');
        expect($access->ip_address)->not->toBeNull();
    });

    it('increments view count on each access', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 5,
        ]);

        $this->get(route('public-share.show', $share->uuid));
        $this->get(route('public-share.show', $share->uuid));
        $this->get(route('public-share.show', $share->uuid));

        $share->refresh();
        expect($share->view_count)->toBe(8);
        expect(ShareAccess::where('share_id', $share->id)->count())->toBe(3);
    });

    it('includes Open Graph meta tags in response', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Amazing Logo Designs',
            'description' => 'Check out these creative logos',
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewHas('metadata');

        $metadata = $response->viewData('metadata');
        expect($metadata)->toHaveKey('og:title');
        expect($metadata)->toHaveKey('og:description');
        expect($metadata)->toHaveKey('og:url');
        expect($metadata)->toHaveKey('twitter:card');
        expect($metadata['og:title'])->toBe('Amazing Logo Designs');
    });

    it('handles missing shareable content gracefully', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => 99999, // Non-existent
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewIs('shares.not-found')
            ->assertSee('content is no longer available');
    });

    it('returns JSON response for API requests', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'API Test Share',
        ]);

        $response = $this->getJson(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertJsonStructure([
                'uuid',
                'title',
                'description',
                'share_type',
                'view_count',
                'created_at',
                'settings',
                'shareable',
            ])
            ->assertJson([
                'title' => 'API Test Share',
                'uuid' => $share->uuid,
            ]);
    });

    it('requires password for API requests on protected shares', function (): void {
        $share = Share::factory()->passwordProtected('test123')->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->getJson(route('public-share.show', $share->uuid));

        $response->assertStatus(423) // Locked
            ->assertJson([
                'message' => 'Password required',
                'requires_password' => true,
            ]);
    });

    it('respects share settings for display', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'settings' => [
                'show_title' => true,
                'show_description' => false,
                'theme' => 'dark',
            ],
        ]);

        $response = $this->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertViewHas('share');

        $viewShare = $response->viewData('share');
        expect($viewShare->settings)->toHaveKey('theme');
        expect($viewShare->settings['theme'])->toBe('dark');
    });

    it('is mobile responsive', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
        ])->get(route('public-share.show', $share->uuid));

        $response->assertSuccessful()
            ->assertSee('viewport'); // Check for responsive viewport meta tag in layout
    });

    it('tracks unique visitors correctly', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 0,
        ]);

        // Same IP, multiple visits
        $this->get(route('public-share.show', $share->uuid));
        $this->get(route('public-share.show', $share->uuid));
        $this->get(route('public-share.show', $share->uuid));

        // Refresh to get updated view count
        $share->refresh();
        $analytics = app(\App\Services\ShareService::class)->getShareAnalytics($share);

        expect($analytics['total_views'])->toBe(3);
        expect($analytics['unique_visitors'])->toBe(1);
    });

    it('handles concurrent access correctly', function (): void {
        $share = Share::factory()->public()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'view_count' => 0,
        ]);

        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $this->get(route('public-share.show', $share->uuid));
        }

        $share->refresh();
        expect($share->view_count)->toBe(5);
    });
});
