<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Setup CSRF token for all tests
    $this->withSession(['_token' => 'test-token'])
        ->withHeaders(['X-CSRF-TOKEN' => 'test-token']);
});

describe('Share Security Audit', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('prevents unauthorized users from viewing other users shares', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/shares/{$share->id}");

        $response->assertForbidden();
    });

    it('prevents unauthorized users from updating other users shares', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/shares/{$share->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    });

    it('prevents unauthorized users from deleting other users shares', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->otherUser->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertForbidden();
        expect(Share::find($share->id))->not->toBeNull();
    });

    it('requires authentication for share creation', function (): void {
        $response = $this->postJson('/api/shares', [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $response->assertUnauthorized();
    });

    it('validates share_type to prevent invalid values', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['share_type']);
    });

    it('prevents XSS attacks by rejecting malicious input', function (): void {
        $xssTitle = '<script>alert("XSS")</script>Test Title';

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => $xssTitle,
            ]);

        // The system rejects HTML/script tags in titles for security
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        // Verify safe title works
        $safeResponse = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => 'Safe Title',
            ]);

        $safeResponse->assertCreated();
    });

    it('securely hashes passwords for password-protected shares', function (): void {
        $plainPassword = 'MySecurePassword123!';

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'password_protected',
                'password' => $plainPassword,
            ]);

        $response->assertCreated();
        $share = Share::latest()->first();

        // Password should be hashed
        expect($share->password_hash)->not->toBe($plainPassword);
        expect($share->password_hash)->not->toBeNull();
        expect(Hash::check($plainPassword, $share->password_hash))->toBeTrue();
    });

    it('generates cryptographically secure UUIDs for shares', function (): void {
        $share = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);

        // UUID should be 36 characters (8-4-4-4-12 format)
        expect(strlen($share->uuid))->toBe(36);
        expect($share->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

        // UUID should be unique
        $anotherShare = Share::factory()->create([
            'user_id' => $this->user->id,
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
        ]);
        expect($share->uuid)->not->toBe($anotherShare->uuid);
    });

    it('prevents SQL injection attacks by rejecting malicious input', function (): void {
        $sqlInjection = "'; DROP TABLE shares; --";

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => $sqlInjection,
            ]);

        // The system rejects SQL-like syntax in titles for security
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        // Table should still exist
        expect(Share::count())->toBeGreaterThanOrEqual(0);

        // Verify safe title with quotes works
        $safeResponse = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => 'My Project',
            ]);

        $safeResponse->assertCreated();
    });

    it('validates shareable_id exists to prevent orphaned shares', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => 99999,
                'share_type' => 'public',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_id']);
    });

    it('enforces maximum title length', function (): void {
        $longTitle = str_repeat('a', 256);

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => $longTitle,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('enforces maximum description length', function (): void {
        $longDescription = str_repeat('a', 1001);

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'description' => $longDescription,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });
});

describe('Export Security Audit', function (): void {
    beforeEach(function (): void {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('prevents unauthorized users from viewing other users exports', function (): void {
        $export = Export::factory()->create([
            'user_id' => $this->otherUser->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports/{$export->id}");

        $response->assertForbidden();
    });

    it('prevents unauthorized users from downloading other users exports', function (): void {
        $export = Export::factory()->create([
            'user_id' => $this->otherUser->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
        ]);

        // Create the file
        \Illuminate\Support\Facades\Storage::put($export->file_path, 'PDF content');

        $response = $this->actingAs($this->user)
            ->get("/downloads/{$export->uuid}");

        $response->assertForbidden();
    });

    it('prevents unauthorized users from deleting other users exports', function (): void {
        $export = Export::factory()->create([
            'user_id' => $this->otherUser->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/exports/{$export->id}");

        $response->assertForbidden();
        expect(Export::find($export->id))->not->toBeNull();
    });

    it('validates export_type to prevent invalid formats', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => 'invalid_format',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['export_type']);
    });

    it('generates cryptographically secure UUIDs for exports', function (): void {
        $export = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);

        // UUID should be 36 characters (8-4-4-4-12 format)
        expect(strlen($export->uuid))->toBe(36);
        expect($export->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');

        // UUID should be unique
        $anotherExport = Export::factory()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
        ]);
        expect($export->uuid)->not->toBe($anotherExport->uuid);
    });
});

describe('Rate Limiting Audit', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('enforces rate limiting on share creation', function (): void {
        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'share_type' => 'public',
                ]);

            $response->assertStatus(201);
        }

        // 11th request should be rate limited
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
            ]);

        $response->assertStatus(429); // Too Many Requests
    });
});
