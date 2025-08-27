<?php

declare(strict_types=1);

use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('PublicShareController', function (): void {
    it('displays a public share', function (): void {
        $share = Share::factory()->public()->create([
            'title' => 'Amazing Tech Logos',
            'description' => 'Creative logo designs for technology companies',
        ]);

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertSee('Amazing Tech Logos')
            ->assertSee('Creative logo designs for technology companies');
    });

    it('shows password form for protected shares', function (): void {
        $share = Share::factory()->passwordProtected('secret123')->create();

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertSee('Password Required')
            ->assertSee('This share is password protected');
    });

    it('authenticates password-protected shares', function (): void {
        $share = Share::factory()->passwordProtected('secret123')->create([
            'title' => 'Private Logos',
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post("/share/{$share->uuid}/authenticate", [
                'password' => 'secret123',
            ]);

        $response->assertRedirect("/share/{$share->uuid}");

        // Follow the redirect and check the content
        $followUpResponse = $this->get("/share/{$share->uuid}");
        $followUpResponse->assertSuccessful()
            ->assertSee('Private Logos');
    });

    it('rejects invalid passwords', function (): void {
        $share = Share::factory()->passwordProtected('secret123')->create();

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post("/share/{$share->uuid}/authenticate", [
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors(['password'])
            ->assertRedirect("/share/{$share->uuid}");
    });

    it('returns 404 for non-existent shares', function (): void {
        $response = $this->get('/share/non-existent-uuid');

        $response->assertNotFound();
    });

    it('returns 404 for inactive shares', function (): void {
        $share = Share::factory()->inactive()->create();

        $response = $this->get("/share/{$share->uuid}");

        $response->assertNotFound();
    });

    it('returns 404 for expired shares', function (): void {
        $share = Share::factory()->expired()->create();

        $response = $this->get("/share/{$share->uuid}");

        $response->assertNotFound();
    });

    it('records access analytics', function (): void {
        $share = Share::factory()->public()->create();

        $initialViewCount = $share->view_count;
        $initialAccessCount = $share->accesses()->count();

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful();

        $share->refresh();
        expect($share->view_count)->toBe($initialViewCount + 1);
        expect($share->accesses()->count())->toBe($initialAccessCount + 1);

        $access = $share->accesses()->latest()->first();
        expect($access->ip_address)->not->toBeNull();
        expect($access->user_agent)->not->toBeNull();
    });

    it('generates social media metadata', function (): void {
        $share = Share::factory()->create([
            'title' => 'Startup Logo Collection',
            'description' => 'Modern and creative logos for tech startups',
        ]);

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertSee('<meta property="og:title" content="Startup Logo Collection"', false)
            ->assertSee('<meta property="og:description" content="Modern and creative logos for tech startups"', false)
            ->assertSee('<meta property="og:url"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image"', false);
    });

    it('handles shares with missing shareable models gracefully', function (): void {
        $share = Share::factory()->public()->create();

        // Delete the associated shareable model
        $share->shareable->delete();

        // Refresh the share to clear any cached relationships
        $share->refresh();

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertSee('Share content is no longer available');
    });

    it('displays share in different formats based on accept header', function (): void {
        $share = Share::factory()->public()->create([
            'title' => 'API Test Share',
        ]);

        // Request JSON format
        $response = $this->get("/share/{$share->uuid}", [
            'Accept' => 'application/json',
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'uuid' => $share->uuid,
                'title' => 'API Test Share',
                'share_type' => 'public',
            ]);
    });

    it('prevents access to shares requiring authentication via JSON', function (): void {
        $share = Share::factory()->passwordProtected('secret123')->create();

        $response = $this->get("/share/{$share->uuid}", [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(423) // Locked
            ->assertJson([
                'message' => 'Password required',
                'requires_password' => true,
            ]);
    });

    it('tracks referrer information when available', function (): void {
        $share = Share::factory()->public()->create();

        $response = $this->get("/share/{$share->uuid}", [
            'HTTP_REFERER' => 'https://example.com/page',
        ]);

        $response->assertSuccessful();

        $access = $share->accesses()->latest()->first();
        expect($access->referrer)->toBe('https://example.com/page');
    });

    it('handles concurrent access requests safely', function (): void {
        $share = Share::factory()->public()->create();
        $initialViewCount = $share->view_count;

        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $this->get("/share/{$share->uuid}");
        }

        $share->refresh();
        expect($share->view_count)->toBe($initialViewCount + 5);
        expect($share->accesses()->count())->toBe(5);
    });

    it('respects share settings for public display', function (): void {
        $share = Share::factory()->create([
            'settings' => [
                'show_title' => false,
                'show_description' => true,
                'theme' => 'dark',
            ],
        ]);

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertDontSee($share->title)
            ->assertSee($share->description);
    });

    it('provides download links when available', function (): void {
        Storage::fake('local');

        $logoGeneration = \App\Models\LogoGeneration::factory()->create();
        $share = Share::factory()->public()->create([
            'shareable_type' => \App\Models\LogoGeneration::class,
            'shareable_id' => $logoGeneration->id,
        ]);

        // Create a generated logo with file
        $logo = \App\Models\GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
            'original_file_path' => 'logos/test-logo.svg',
            'style' => 'modern',
        ]);

        Storage::put('logos/test-logo.svg', '<svg>test</svg>');

        $response = $this->get("/share/{$share->uuid}");

        $response->assertSuccessful()
            ->assertSee('Download');
    });
});
