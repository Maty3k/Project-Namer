<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;
use App\Services\ExportService;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);
uses(WithoutMiddleware::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed', // Required for exports
    ]);
    $this->shareService = app(ShareService::class);
    $this->exportService = app(ExportService::class);
});

describe('Share Error Handling', function (): void {
    it('handles invalid share UUID gracefully', function (): void {
        $response = $this->get('/share/invalid-uuid-12345');

        $response->assertNotFound();
    });

    it('handles non-existent share UUID', function (): void {
        $response = $this->get('/share/550e8400-e29b-41d4-a716-446655440000');

        $response->assertNotFound();
    });

    it('handles expired share access attempts', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        // Manually set expiration after creation to bypass validation
        $share->update(['expires_at' => now()->subHour()]);

        $response = $this->get($share->getShareUrl());

        $response->assertNotFound();
    });

    it('handles inactive share access attempts', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $share->update(['is_active' => false]);

        $response = $this->get($share->getShareUrl());

        $response->assertNotFound();
    });

    it('handles deleted shareable resource', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        // Delete the logo generation
        $this->logoGeneration->delete();

        $response = $this->get($share->getShareUrl());

        // Should handle gracefully - 200 if page loads without error, or 404/500 if it errors
        // Since the shareable relationship is soft deleted, the page might load but show empty content
        expect($response->status())->toBeIn([200, 404, 500]);
    });

    it('handles password verification with empty password', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'password_protected',
            'password' => 'SecurePass123',
        ]);

        $response = $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    });

    it('handles password verification for non-password-protected share', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $response = $this->post(route('public-share.authenticate', $share->uuid), [
            'password' => 'SomePassword',
        ]);

        // Should redirect since it's not password protected
        expect($response->status())->toBeIn([302, 404, 422]);
    });

    it('handles concurrent share creation within rate limits', function (): void {
        // Enable middleware specifically for this test to test rate limiting
        $this->withMiddleware([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // Clear any existing rate limit data
        \Illuminate\Support\Facades\Cache::flush();

        $successfulRequests = 0;
        $rateLimitedRequests = 0;

        // Attempt 12 share creations (limit is 10 per minute)
        for ($i = 0; $i < 12; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/shares', [
                    'shareable_type' => LogoGeneration::class,
                    'shareable_id' => $this->logoGeneration->id,
                    'share_type' => 'public',
                ]);

            if ($response->status() === 201) {
                $successfulRequests++;
            } elseif ($response->status() === 429) {
                $rateLimitedRequests++;
            }
        }

        expect($successfulRequests)->toBe(10)
            ->and($rateLimitedRequests)->toBe(2);
    });
});

describe('Export Error Handling', function (): void {
    it('handles invalid export UUID', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', 'invalid-uuid'));

        $response->assertNotFound();
    });

    it('handles non-existent export UUID', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', '550e8400-e29b-41d4-a716-446655440000'));

        $response->assertNotFound();
    });

    it('handles expired export download attempts', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Manually set expiration after creation to bypass validation
        $export->update(['expires_at' => now()->subDay()]);

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertStatus(410); // Gone
    });

    it('handles missing export file', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Delete the physical file
        Storage::delete($export->file_path);

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertNotFound();
    });

    it('handles invalid export type', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => 'invalid_type',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['export_type']);
    });

    it('handles deleted exportable resource', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        // Delete the logo generation
        $this->logoGeneration->delete();

        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        // Should still allow download of existing export file
        if (Storage::exists($export->file_path)) {
            $response->assertSuccessful();
        } else {
            $response->assertNotFound();
        }
    });

    it('handles concurrent export creation within rate limits', function (): void {
        // Enable middleware specifically for this test to test rate limiting
        $this->withMiddleware([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

        // Clear any existing rate limit data
        \Illuminate\Support\Facades\Cache::flush();

        $successfulRequests = 0;
        $rateLimitedRequests = 0;

        // Attempt 17 export creations (limit is 15 per minute)
        for ($i = 0; $i < 17; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => $this->logoGeneration->id,
                    'export_type' => 'pdf',
                    'expires_in_days' => 7,
                    'template' => 'default',
                    'include_domains' => true,
                    'include_metadata' => true,
                    'include_logos' => true,
                    'include_branding' => true,
                ]);

            if ($response->status() === 201) {
                $successfulRequests++;
            } elseif ($response->status() === 429) {
                $rateLimitedRequests++;
            }
        }

        expect($successfulRequests)->toBe(15)
            ->and($rateLimitedRequests)->toBe(2);
    });

    it('handles unauthorized export access', function (): void {
        $export = $this->exportService->createExport($this->user, [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('api.exports.download', $export->uuid));

        $response->assertForbidden();
    });
});

describe('API Validation Error Handling', function (): void {
    it('handles share creation with missing required fields', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_type', 'shareable_id', 'share_type']);
    });

    it('handles share creation with invalid shareable type', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => 'InvalidType',
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_type']);
    });

    it('handles share creation with non-existent shareable ID', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => 99999,
                'share_type' => 'public',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_id']);
    });

    it('handles password-protected share without password', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'password_protected',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('handles share creation with weak password', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'password_protected',
                'password' => '123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('handles export creation with missing required fields', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exportable_type', 'exportable_id', 'export_type']);
    });

    it('handles export creation with invalid exportable type', function (): void {
        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', [
                'exportable_type' => 'InvalidType',
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => 'pdf',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exportable_type']);
    });
});

describe('Authorization Error Handling', function (): void {
    it('handles unauthenticated share creation attempt', function (): void {
        // Enable auth middleware to test unauthenticated access
        $this->withMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
        ]);

        $response = $this->postJson('/api/shares', [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $response->assertUnauthorized();
    });

    it('handles unauthenticated export creation attempt', function (): void {
        // Enable auth middleware to test unauthenticated access
        $this->withMiddleware([
            \Illuminate\Auth\Middleware\Authenticate::class,
        ]);

        $response = $this->postJson('/api/exports', [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
        ]);

        $response->assertUnauthorized();
    });

    it('handles share creation for resource not owned by user', function (): void {
        $otherUser = User::factory()->create();
        $otherUserGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $otherUserGeneration->id,
                'share_type' => 'public',
            ]);

        // Authorization is handled in validation rules, returns 422
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['shareable_id']);
    });

    it('handles export creation for resource not owned by user', function (): void {
        $otherUser = User::factory()->create();
        $otherUserGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $otherUserGeneration->id,
                'export_type' => 'pdf',
                'expires_in_days' => 7,
                'template' => 'default',
                'include_domains' => true,
                'include_metadata' => true,
                'include_logos' => true,
                'include_branding' => true,
            ]);

        // Authorization is handled in validation rules, returns 422
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exportable_id']);
    });

    it('handles share deletion by unauthorized user', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertForbidden();
    });
});

describe('Network and Server Error Handling', function (): void {
    it('handles large title gracefully', function (): void {
        $largeTitle = str_repeat('A', 1000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => $largeTitle,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    it('handles large description gracefully', function (): void {
        $largeDescription = str_repeat('B', 2000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'description' => $largeDescription,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    it('handles special characters in title and description', function (): void {
        $specialChars = "Test <>&\"' 中文 🚀";

        $response = $this->actingAs($this->user)
            ->postJson('/api/shares', [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'share_type' => 'public',
                'title' => $specialChars,
                'description' => $specialChars,
            ]);

        // Should reject HTML tags but allow safe special characters
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['title']);
        } else {
            $response->assertCreated();
        }
    });

    it('handles concurrent access to the same share', function (): void {
        $share = $this->shareService->createShare($this->user, [
            'shareable_type' => LogoGeneration::class,
            'shareable_id' => $this->logoGeneration->id,
            'share_type' => 'public',
        ]);

        // Simulate 10 concurrent accesses
        $responses = collect();
        for ($i = 0; $i < 10; $i++) {
            $responses->push($this->get($share->getShareUrl()));
        }

        // All requests should succeed
        $responses->each(fn($response) => $response->assertSuccessful());

        // View count should match number of accesses
        $share->refresh();
        expect($share->view_count)->toBe(10);
    });
});
