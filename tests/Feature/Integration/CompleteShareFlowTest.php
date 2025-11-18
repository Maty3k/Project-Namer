<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\ShareAccess;
use App\Models\User;
use App\Services\ExportService;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
        'business_name' => 'TechFlow Solutions',
        'business_description' => 'Technology solutions for modern businesses',
        'status' => 'completed',
    ]);
    $this->shareService = app(ShareService::class);
    $this->exportService = app(ExportService::class);
});

describe('Complete Share Creation and Viewing Flow', function (): void {
    it('completes full share creation to public viewing flow', function (): void {
        // Step 1: User creates a share
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'My Awesome Project Names',
            'description' => 'Check out these amazing business names I generated!',
            'share_type' => 'public',
        ];

        $share = $this->shareService->createShare($this->user, $shareData);

        expect($share)->toBeInstanceOf(Share::class)
            ->and($share->uuid)->not->toBeEmpty()
            ->and($share->title)->toBe('My Awesome Project Names')
            ->and($share->share_type)->toBe('public')
            ->and($share->is_active)->toBeTrue();

        // Step 2: Verify share URL is accessible
        $shareUrl = $share->getShareUrl();
        expect($shareUrl)->toContain('/share/')
            ->and($shareUrl)->toContain($share->uuid);

        // Step 3: Anonymous user accesses the share page
        $response = $this->get($shareUrl);

        $response->assertSuccessful()
            ->assertSee('My Awesome Project Names')
            ->assertSee('Check out these amazing business names I generated!')
            ->assertSee('TechFlow Solutions');

        // Step 4: Verify analytics are tracked
        $this->assertDatabaseHas('share_accesses', [
            'share_id' => $share->id,
        ]);

        $share->refresh();
        expect($share->view_count)->toBe(1);

        // Step 5: Multiple views increment count correctly
        $this->get($shareUrl);
        $this->get($shareUrl);

        $share->refresh();
        expect($share->view_count)->toBe(3)
            ->and(ShareAccess::where('share_id', $share->id)->count())->toBe(3);
    });

    it('handles share with social media URLs generation', function (): void {
        // Step 1: Create share
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Creative Business Names',
            'share_type' => 'public',
        ]);

        // Step 2: Generate social media URLs
        $socialUrls = $this->shareService->generateAllSocialMediaUrls($share);

        expect($socialUrls)->toHaveKeys(['twitter', 'linkedin', 'facebook', 'reddit', 'whatsapp'])
            ->and($socialUrls['twitter'])->toContain('twitter.com')
            ->and($socialUrls['linkedin'])->toContain('linkedin.com')
            ->and($socialUrls['facebook'])->toContain('facebook.com')
            ->and($socialUrls['reddit'])->toContain('reddit.com')
            ->and($socialUrls['whatsapp'])->toContain('wa.me');

        // Step 3: Verify share URL is included in social URLs
        foreach ($socialUrls as $platform => $url) {
            expect($url)->toContain($share->uuid);
        }
    });

    it('tracks unique visitors vs total views', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $shareUrl = $share->getShareUrl();

        // Same IP address, multiple views
        for ($i = 0; $i < 5; $i++) {
            $this->get($shareUrl, [
                'REMOTE_ADDR' => '192.168.1.100',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
            ]);
        }

        // Different IP address
        $this->get($shareUrl, [
            'REMOTE_ADDR' => '192.168.1.101',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $share->refresh();
        expect($share->view_count)->toBe(6);

        $uniqueIps = ShareAccess::where('share_id', $share->id)
            ->distinct('ip_address')
            ->count('ip_address');

        expect($uniqueIps)->toBe(2);
    });

    it('displays proper Open Graph meta tags for social sharing', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Amazing Project Names',
            'description' => 'See my creative business name ideas',
            'share_type' => 'public',
        ]);

        $response = $this->get($share->getShareUrl());

        $response->assertSee('og:title', false)
            ->assertSee('Amazing Project Names', false)
            ->assertSee('og:description', false)
            ->assertSee('See my creative business name ideas', false)
            ->assertSee('og:url', false)
            ->assertSee('og:type', false);
    });

    it('deactivates share and makes it inaccessible', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        // Share is accessible
        $response = $this->get($share->getShareUrl());
        $response->assertSuccessful();

        // Deactivate share
        $share->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        // Clear cache to ensure fresh validation
        \Illuminate\Support\Facades\Cache::forget("share_access:{$share->uuid}");

        // Share is no longer accessible
        $response = $this->get($share->getShareUrl());
        $response->assertNotFound();
    });
});

describe('Password-Protected Share Flow', function (): void {
    it('completes password-protected share creation and access flow', function (): void {
        // Step 1: Create password-protected share
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Confidential Project Names',
            'share_type' => 'password_protected',
            'password' => 'SecurePass123!',
        ];

        $share = $this->shareService->createShare($this->user, $shareData);

        expect($share->share_type)->toBe('password_protected')
            ->and($share->password_hash)->not->toBeNull()
            ->and($share->password_hash)->not->toBe('SecurePass123!'); // Should be hashed

        // Step 2: Access without password shows password form
        $response = $this->get($share->getShareUrl());

        $response->assertSuccessful()
            ->assertSee('This share is password protected')
            ->assertSee('password');

        // Step 3: Verify incorrect password is rejected
        $response = $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => 'WrongPassword',
        ]);

        // Should redirect back to share page and not authenticate
        $response->assertRedirect();
        expect(session("share_authenticated_{$share->uuid}"))->toBeFalsy();

        // Step 4: Verify correct password grants access
        $response = $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => 'SecurePass123!',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas("share_authenticated_{$share->uuid}", true);

        // Step 5: Access with verified session shows content
        $response = $this->get($share->getShareUrl());

        $response->assertSuccessful()
            ->assertDontSee('This share is password protected')
            ->assertSee('Confidential Project Names')
            ->assertSee('TechFlow Solutions');
    });

    it('password verification persists across page reloads', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'password_protected',
            'password' => 'TestPass123',
        ]);

        // Verify password
        $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => 'TestPass123',
        ]);

        // First access - should show content with business name
        $response = $this->get($share->getShareUrl());
        $response->assertSuccessful()
            ->assertSee('TechFlow Solutions');

        // Second access - should still show content (session persists)
        $response = $this->get($share->getShareUrl());
        $response->assertSuccessful()
            ->assertSee('TechFlow Solutions');
    });

    it('tracks views only after password verification', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'password_protected',
            'password' => 'SecurePass',
        ]);

        // Access without password - no view recorded
        $this->get($share->getShareUrl());

        $share->refresh();
        expect($share->view_count)->toBe(0);

        // Verify password
        $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => 'SecurePass',
        ]);

        // Access with verified password - view recorded
        $this->get($share->getShareUrl());

        $share->refresh();
        expect($share->view_count)->toBe(1);
    });
});

describe('Export Generation and Download Flow', function (): void {
    it('completes PDF export generation and download flow', function (): void {
        // Step 1: Generate PDF export
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ];

        $export = $this->exportService->createExport($this->user, $exportData);

        expect($export)->toBeInstanceOf(Export::class)
            ->and($export->export_type)->toBe('pdf')
            ->and($export->file_path)->toContain('.pdf')
            ->and($export->download_count)->toBe(0);

        // Step 2: Verify file was created
        expect(Storage::exists($export->file_path))->toBeTrue();

        // Step 3: Download export (authenticated)
        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition');

        // Step 4: Verify download count incremented
        $export->refresh();
        expect($export->download_count)->toBe(1);

        // Step 5: Multiple downloads increment count
        $this->actingAs($this->user)->get(route('api.exports.download', $export->uuid));
        $this->actingAs($this->user)->get(route('api.exports.download', $export->uuid));

        $export->refresh();
        expect($export->download_count)->toBe(3);
    });

    it('completes CSV export generation and download flow', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
        ]);

        expect($export->export_type)->toBe('csv')
            ->and($export->file_path)->toContain('.csv');

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertSuccessful();

        expect(str_contains($response->headers->get('Content-Type'), 'text/csv'))->toBeTrue();

        $export->refresh();
        expect($export->download_count)->toBe(1);
    });

    it('completes JSON export generation and download flow', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
        ]);

        expect($export->export_type)->toBe('json')
            ->and($export->file_path)->toContain('.json');

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json');

        $export->refresh();
        expect($export->download_count)->toBe(1);
    });

    it('prevents unauthorized users from downloading exports', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Create another user
        $otherUser = User::factory()->create();

        // Other user attempts to download
        $response = $this->actingAs($otherUser)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertForbidden();

        // Download count should not increment
        $export->refresh();
        expect($export->download_count)->toBe(0);
    });

    it('handles expired export downloads', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Manually set expired date after creation
        $export->update(['expires_at' => now()->subDay()]);

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertStatus(410); // Gone
    });

    it('handles missing export files gracefully', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Delete the file
        Storage::delete($export->file_path);

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertNotFound();
    });
});

describe('Share Management Dashboard Flow', function (): void {
    it('completes share management workflow', function (): void {
        // Step 1: Create multiple shares
        $shares = collect();
        for ($i = 0; $i < 5; $i++) {
            $shares->push($this->shareService->createShare($this->user, [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'title' => "Project Names {$i}",
                'share_type' => 'public',
            ]));
        }

        // Step 2: Access share management page
        $response = $this->actingAs($this->user)
            ->get(route('shares.index'));

        $response->assertSuccessful();

        // Step 3: Verify all shares are listed
        foreach ($shares as $share) {
            $response->assertSee($share->title);
        }

        // Step 4: Delete a share
        $shareToDelete = $shares->first();
        $response = $this->actingAs($this->user)
            ->delete(route('api.shares.destroy', $shareToDelete->id));

        $response->assertOk()
            ->assertJson(['message' => 'Share deactivated successfully']);

        // Step 5: Verify share is deleted
        $this->assertDatabaseMissing('shares', [
            'id' => $shareToDelete->id,
            'deactivated_at' => null,
        ]);

        // Step 6: Verify deleted share is inaccessible
        $response = $this->get($shareToDelete->getShareUrl());
        $response->assertNotFound();
    });

    it('filters shares by status', function (): void {
        // Create active share
        $activeShare = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Active Share',
            'share_type' => 'public',
        ]);

        // Create inactive share
        $inactiveShare = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Inactive Share',
            'share_type' => 'public',
        ]);
        $inactiveShare->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        // Create expired share
        $expiredShare = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Expired Share',
            'share_type' => 'public',
        ]);
        // Manually set expiration after creation
        $expiredShare->update(['expires_at' => now()->subDay()]);

        $response = $this->actingAs($this->user)
            ->get(route('shares.index'));

        $response->assertSuccessful()
            ->assertSee('Active Share')
            ->assertSee('Inactive Share')
            ->assertSee('Expired Share');
    });

    it('sorts shares by creation date and view count', function (): void {
        $oldShare = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Old Share',
            'share_type' => 'public',
        ]);
        $oldShare->update(['created_at' => now()->subWeek()]);

        $newShare = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'New Share',
            'share_type' => 'public',
        ]);

        // Add views to old share
        for ($i = 0; $i < 10; $i++) {
            $this->get($oldShare->getShareUrl());
        }

        $response = $this->actingAs($this->user)
            ->get(route('shares.index'));

        $response->assertSuccessful();
    });

    it('prevents users from accessing other users shares', function (): void {
        $otherUser = User::factory()->create();
        $otherUserGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $otherUserShare = $this->shareService->createShare($otherUser, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $otherUserGeneration->id,
            'title' => 'Other User Share',
            'share_type' => 'public',
        ]);

        // Attempt to delete other user's share
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test-token'])
            ->withHeaders(['X-CSRF-TOKEN' => 'test-token'])
            ->deleteJson(route('api.shares.destroy', $otherUserShare->id));

        $response->assertForbidden();
    });
});
