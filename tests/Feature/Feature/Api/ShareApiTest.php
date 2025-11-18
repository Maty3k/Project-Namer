<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Setup CSRF token for all tests
    $this->withSession(['_token' => 'test-token'])
        ->withHeaders(['X-CSRF-TOKEN' => 'test-token']);
});

describe('Share API Endpoints', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    describe('POST /api/shares', function (): void {
        it('creates a public share', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'title' => 'My Amazing Project',
                    'description' => 'Check out these cool names',
                    'share_type' => 'public',
                ]);

            $response->assertCreated()
                ->assertJson([
                    'message' => 'Share created successfully',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'uuid',
                        'title',
                        'description',
                        'share_type',
                        'is_active',
                        'created_at',
                    ],
                ]);

            expect(Share::count())->toBe(1);
            $share = Share::first();
            expect($share->title)->toBe('My Amazing Project');
            expect($share->share_type)->toBe('public');
            expect($share->user_id)->toBe($this->user->id);
        });

        it('creates a password-protected share', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'title' => 'Private Project',
                    'share_type' => 'password_protected',
                    'password' => 'secret123',
                ]);

            $response->assertCreated();

            $share = Share::first();
            expect($share->share_type)->toBe('password_protected');
            expect($share->validatePassword('secret123'))->toBeTrue();
            expect($share->validatePassword('wrong'))->toBeFalse();
        });

        it('requires authentication', function (): void {
            $response = $this->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
            ]);

            $response->assertUnauthorized();
        });

        it('validates required fields', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['shareable_type', 'shareable_id', 'share_type']);
        });

        it('validates shareable_id exists in database', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => 99999,
                    'share_type' => 'public',
                ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['shareable_id']);
        });

        it('requires password for password-protected shares', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'share_type' => 'password_protected',
                ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        });

        it('respects rate limiting', function (): void {
            RateLimiter::shouldReceive('tooManyAttempts')
                ->with("share-creation:{$this->user->id}", 10)
                ->andReturn(true);

            RateLimiter::shouldReceive('availableIn')
                ->with("share-creation:{$this->user->id}")
                ->andReturn(300);

            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'share_type' => 'public',
                ]);

            $response->assertStatus(429)
                ->assertJson([
                    'retry_after' => 300,
                ]);
        });
    });

    describe('GET /api/shares', function (): void {
        it('returns user shares with pagination', function (): void {
            Share::factory()->count(15)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/shares');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'uuid', 'title', 'share_type', 'view_count', 'is_active'],
                    ],
                    'pagination' => [
                        'current_page',
                        'total',
                        'per_page',
                    ],
                ])
                ->assertJsonCount(15, 'data');
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

            $response->assertSuccessful()
                ->assertJsonCount(5, 'data');
        });

        it('filters shares by active status', function (): void {
            Share::factory()->count(3)->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);
            Share::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'is_active' => false,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/shares?is_active=true');

            $response->assertSuccessful()
                ->assertJsonCount(3, 'data');
        });

        it('searches shares by title and description', function (): void {
            Share::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Unique Project Name',
            ]);
            Share::factory()->count(5)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/shares?search=Unique');

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.title', 'Unique Project Name');
        });

        it('paginates results', function (): void {
            Share::factory()->count(25)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/shares?per_page=10');

            $response->assertSuccessful()
                ->assertJsonCount(10, 'data')
                ->assertJsonPath('pagination.total', 25)
                ->assertJsonPath('pagination.per_page', 10);
        });

        it('only returns authenticated user shares', function (): void {
            $otherUser = User::factory()->create();
            Share::factory()->count(5)->create(['user_id' => $this->user->id]);
            Share::factory()->count(3)->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/shares');

            $response->assertSuccessful()
                ->assertJsonCount(5, 'data');
        });

        it('requires authentication', function (): void {
            $response = $this->getJson('/api/shares');

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/shares/{share}', function (): void {
        it('shows a specific share', function (): void {
            $share = Share::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'My Share',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/shares/{$share->id}");

            $response->assertSuccessful()
                ->assertJsonPath('data.title', 'My Share')
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'uuid',
                        'title',
                        'description',
                        'share_type',
                        'view_count',
                        'is_active',
                    ],
                ]);
        });

        it('prevents viewing other users shares', function (): void {
            $otherUser = User::factory()->create();
            $share = Share::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/shares/{$share->id}");

            $response->assertForbidden();
        });

        it('requires authentication', function (): void {
            $share = Share::factory()->create();

            $response = $this->getJson("/api/shares/{$share->id}");

            $response->assertUnauthorized();
        });
    });

    describe('PUT /api/shares/{share}', function (): void {
        it('updates a share', function (): void {
            $share = Share::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Old Title',
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/shares/{$share->id}", [
                    'title' => 'New Title',
                    'description' => 'Updated description',
                ]);

            $response->assertSuccessful()
                ->assertJsonPath('data.title', 'New Title')
                ->assertJson(['message' => 'Share updated successfully']);

            expect($share->fresh()->title)->toBe('New Title');
        });

        it('prevents updating other users shares', function (): void {
            $otherUser = User::factory()->create();
            $share = Share::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/shares/{$share->id}", [
                    'title' => 'Hacked Title',
                ]);

            $response->assertForbidden();
        });

        it('requires authentication', function (): void {
            $share = Share::factory()->create();

            $response = $this->putJson("/api/shares/{$share->id}", [
                'title' => 'New Title',
            ]);

            $response->assertUnauthorized();
        });
    });

    describe('DELETE /api/shares/{share}', function (): void {
        it('deactivates a share', function (): void {
            $share = Share::factory()->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/shares/{$share->id}");

            $response->assertSuccessful()
                ->assertJson(['message' => 'Share deactivated successfully']);

            expect($share->fresh()->is_active)->toBeFalse();
        });

        it('prevents deleting other users shares', function (): void {
            $otherUser = User::factory()->create();
            $share = Share::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/shares/{$share->id}");

            $response->assertForbidden();
        });

        it('requires authentication', function (): void {
            $share = Share::factory()->create();

            $response = $this->deleteJson("/api/shares/{$share->id}");

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/shares/{share}/analytics', function (): void {
        it('returns share analytics', function (): void {
            $share = Share::factory()->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/shares/{$share->id}/analytics");

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        'total_views',
                        'unique_visitors',
                        'recent_views',
                        'today_views',
                    ],
                ]);
        });

        it('prevents viewing other users share analytics', function (): void {
            $otherUser = User::factory()->create();
            $share = Share::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/shares/{$share->id}/analytics");

            $response->assertForbidden();
        });
    });

    describe('GET /api/shares/{share}/metadata', function (): void {
        it('returns social media metadata', function (): void {
            $share = Share::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Amazing Project',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/shares/{$share->id}/metadata");

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        'og:title',
                        'og:description',
                        'og:url',
                        'twitter:card',
                    ],
                ]);
        });
    });
});
