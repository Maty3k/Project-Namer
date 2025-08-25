<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

describe('ShareController', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create();
    });

    it('creates a public share via API', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'My Amazing Logos',
            'description' => 'Check out these creative logo designs',
            'share_type' => 'public',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', $shareData);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'description',
                    'share_type',
                    'share_url',
                    'is_active',
                    'expires_at',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('shares', [
            'user_id' => $this->user->id,
            'title' => 'My Amazing Logos',
            'share_type' => 'public',
        ]);
    });

    it('creates a password-protected share via API', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'title' => 'Private Logos',
            'share_type' => 'password_protected',
            'password' => 'secret123',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', $shareData);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'share_type',
                    'share_url',
                ],
            ]);

        $share = Share::where('title', 'Private Logos')->first();
        expect($share->validatePassword('secret123'))->toBeTrue();
        expect($share->validatePassword('wrong'))->toBeFalse();
    });

    it('validates share creation data', function (): void {
        $invalidData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => 99999, // Non-existent ID
            'share_type' => 'public',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', $invalidData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_id']);
    });

    it('requires password for password-protected shares', function (): void {
        $shareData = [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'password_protected',
            // Missing password
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', $shareData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('lists user shares with pagination', function (): void {
        Share::factory()->count(15)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/shares?per_page=10');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'share_type',
                        'view_count',
                        'created_at',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'has_more_pages',
                ],
            ]);

        expect($response->json('data'))->toHaveCount(10);
        expect($response->json('pagination.total'))->toBe(15);
    });

    it('filters shares by type', function (): void {
        Share::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'share_type' => 'public',
        ]);
        Share::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'share_type' => 'password_protected',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/shares?share_type=public');

        $response->assertSuccessful();
        expect($response->json('pagination.total'))->toBe(5);
    });

    it('searches shares by title and description', function (): void {
        Share::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Tech Startup Logos',
            'description' => 'Modern designs for technology companies',
        ]);
        Share::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Restaurant Branding',
            'description' => 'Food and beverage logo concepts',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/shares?search=tech');

        $response->assertSuccessful();
        expect($response->json('pagination.total'))->toBe(1);
        expect($response->json('data.0.title'))->toBe('Tech Startup Logos');
    });

    it('shows a single share', function (): void {
        $share = Share::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/shares/{$share->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'description',
                    'share_type',
                    'share_url',
                    'view_count',
                    'analytics',
                    'shareable',
                    'created_at',
                ],
            ]);
    });

    it('updates a share', function (): void {
        $share = Share::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/shares/{$share->id}", $updateData);

        $response->assertSuccessful()
            ->assertJsonFragment([
                'title' => 'Updated Title',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('shares', [
            'id' => $share->id,
            'title' => 'Updated Title',
        ]);
    });

    it('deactivates a share', function (): void {
        $share = Share::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertSuccessful();

        $share->refresh();
        expect($share->is_active)->toBeFalse();
    });

    it('prevents unauthorized access to other users shares', function (): void {
        $otherUser = User::factory()->create();
        $share = Share::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/shares/{$share->id}");

        $response->assertForbidden();
    });

    it('enforces rate limiting on share creation', function (): void {
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

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', $shareData);

        $response->assertStatus(429); // Too Many Requests
    });

    it('returns share analytics', function (): void {
        $share = Share::factory()->recentlyAccessed()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/shares/{$share->id}/analytics");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'total_views',
                    'unique_visitors',
                    'recent_views',
                    'today_views',
                    'peak_day',
                    'referrer_stats',
                ],
            ]);
    });

    it('requires authentication for all endpoints', function (): void {
        $response = $this->getJson('/api/shares');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/shares', []);
        $response->assertUnauthorized();
    });

    it('validates share ownership on updates and deletes', function (): void {
        $otherUser = User::factory()->create();
        $share = Share::factory()->create(['user_id' => $otherUser->id]);

        // Try to update other user's share
        $response = $this->actingAs($this->user)
            ->putJson("/api/shares/{$share->id}", ['title' => 'Hacked']);

        $response->assertForbidden();

        // Try to delete other user's share
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertForbidden();
    });
});
