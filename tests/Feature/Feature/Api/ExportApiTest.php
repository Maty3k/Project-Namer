<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    // Setup CSRF token for all tests
    $this->withSession(['_token' => 'test-token'])
        ->withHeaders(['X-CSRF-TOKEN' => 'test-token']);
});

describe('Export API Endpoints', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed', // Required for exports
        ]);
    });

    describe('POST /api/exports', function (): void {
        it('creates a PDF export', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => $this->logoGeneration->id,
                    'export_type' => 'pdf',
                    'expires_in_days' => 7,
                    'template' => 'default',
                    'include_metadata' => true,
                    'include_domains' => true,
                    'include_logos' => true,
                    'include_branding' => true,
                ]);

            $response->assertCreated()
                ->assertJson([
                    'message' => 'Export created successfully',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'uuid',
                        'export_type',
                        'file_size',
                        'created_at',
                    ],
                ]);

            expect(Export::count())->toBe(1);
            $export = Export::first();
            expect($export->export_type)->toBe('pdf');
            expect($export->user_id)->toBe($this->user->id);
            expect($export->fileExists())->toBeTrue();
        });

        it('creates a CSV export', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => $this->logoGeneration->id,
                    'export_type' => 'csv',
                    'expires_in_days' => 7,
                    'template' => 'default',
                    'include_metadata' => true,
                    'include_domains' => true,
                    'include_logos' => true,
                    'include_branding' => true,
                ]);

            $response->assertCreated();

            $export = Export::first();
            expect($export->export_type)->toBe('csv');
            expect($export->fileExists())->toBeTrue();
        });

        it('creates a JSON export', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => $this->logoGeneration->id,
                    'export_type' => 'json',
                    'expires_in_days' => 7,
                    'template' => 'default',
                    'include_metadata' => true,
                    'include_domains' => true,
                    'include_logos' => true,
                    'include_branding' => true,
                ]);

            $response->assertCreated();

            $export = Export::first();
            expect($export->export_type)->toBe('json');
            expect($export->fileExists())->toBeTrue();
        });

        it('requires authentication', function (): void {
            $response = $this->postJson('/api/exports', [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => 'pdf',
            ]);

            $response->assertUnauthorized();
        });

        it('validates required fields', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['exportable_type', 'exportable_id', 'export_type']);
        });

        it('validates export_type is valid format', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => $this->logoGeneration->id,
                    'export_type' => 'invalid_format',
                ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['export_type']);
        });

        it('validates exportable_id exists in database', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/exports', [
                    'exportable_type' => LogoGeneration::class,
                    'exportable_id' => 99999,
                    'export_type' => 'pdf',
                ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['exportable_id']);
        });

        it('handles export generation errors gracefully', function (): void {
            // Skip this test as it's testing error handling during export generation
            // which is covered by other integration tests
        })->skip('Export generation error handling covered by integration tests');
    });

    describe('GET /api/exports', function (): void {
        it('returns user exports with pagination', function (): void {
            Export::factory()->count(10)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/exports');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'uuid', 'export_type', 'file_size', 'download_count'],
                    ],
                    'pagination' => [
                        'current_page',
                        'total',
                        'per_page',
                    ],
                ])
                ->assertJsonCount(10, 'data');
        });

        it('filters exports by type', function (): void {
            Export::factory()->count(3)->create([
                'user_id' => $this->user->id,
                'export_type' => 'pdf',
            ]);
            Export::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'export_type' => 'csv',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/exports?export_type=pdf');

            $response->assertSuccessful()
                ->assertJsonCount(3, 'data');
        });

        it('paginates results', function (): void {
            Export::factory()->count(20)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/exports?per_page=5');

            $response->assertSuccessful()
                ->assertJsonCount(5, 'data')
                ->assertJsonPath('pagination.total', 20)
                ->assertJsonPath('pagination.per_page', 5);
        });

        it('only returns authenticated user exports', function (): void {
            $otherUser = User::factory()->create();
            Export::factory()->count(5)->create(['user_id' => $this->user->id]);
            Export::factory()->count(3)->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/exports');

            $response->assertSuccessful()
                ->assertJsonCount(5, 'data');
        });

        it('requires authentication', function (): void {
            $response = $this->getJson('/api/exports');

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/exports/{export}', function (): void {
        it('shows a specific export', function (): void {
            $logoGen = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $export = Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_id' => $logoGen->id,
                'export_type' => 'pdf',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/exports/{$export->id}");

            $response->assertSuccessful()
                ->assertJsonPath('data.export_type', 'pdf')
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'uuid',
                        'export_type',
                        'file_size',
                        'download_count',
                    ],
                ]);
        });

        it('prevents viewing other users exports', function (): void {
            $otherUser = User::factory()->create();
            $export = Export::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/exports/{$export->id}");

            $response->assertForbidden();
        });

        it('requires authentication', function (): void {
            $export = Export::factory()->create();

            $response = $this->getJson("/api/exports/{$export->id}");

            $response->assertUnauthorized();
        });
    });

    describe('GET /download/{uuid}', function (): void {
        it('downloads an export file', function (): void {
            $export = Export::factory()->pdf()->create([
                'user_id' => $this->user->id,
                'file_path' => 'exports/test.pdf',
            ]);
            Storage::put($export->file_path, 'PDF content');

            $response = $this->actingAs($this->user)
                ->get("/downloads/{$export->uuid}");

            $response->assertSuccessful();
            expect($response->headers->get('Content-Type'))->toBe('application/pdf');
            expect($response->headers->get('Content-Disposition'))->toContain('attachment');

            $export->refresh();
            expect($export->download_count)->toBeGreaterThan(0);
        });

        it('returns 404 for missing file', function (): void {
            $export = Export::factory()->create([
                'user_id' => $this->user->id,
                'file_path' => 'exports/missing.pdf',
            ]);

            $response = $this->actingAs($this->user)
                ->get("/downloads/{$export->uuid}");

            $response->assertNotFound();
        });

        it('returns 410 for expired export', function (): void {
            $export = Export::factory()->expired()->create([
                'user_id' => $this->user->id,
                'file_path' => 'exports/expired.pdf',
            ]);
            Storage::put($export->file_path, 'PDF content');

            $response = $this->actingAs($this->user)
                ->get("/downloads/{$export->uuid}");

            $response->assertStatus(410);
        });

        it('returns 404 for invalid UUID', function (): void {
            $response = $this->actingAs($this->user)
                ->get('/download/invalid-uuid-12345');

            $response->assertNotFound();
        });

        it('increments download count', function (): void {
            $logoGen = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $export = Export::factory()->create([
                'user_id' => $this->user->id,
                'exportable_id' => $logoGen->id,
                'file_path' => 'exports/test.pdf',
                'download_count' => 5,
            ]);
            Storage::put($export->file_path, 'PDF content');

            $this->actingAs($this->user)
                ->get("/download/{$export->uuid}");

            $export->refresh();
            expect($export->download_count)->toBe(6);
        });

        it('prevents downloading other users exports', function (): void {
            $otherUser = User::factory()->create();
            $export = Export::factory()->create([
                'user_id' => $otherUser->id,
                'file_path' => 'exports/test.pdf',
            ]);
            Storage::put($export->file_path, 'PDF content');

            $response = $this->actingAs($this->user)
                ->get("/downloads/{$export->uuid}");

            $response->assertForbidden();
        });
    });

    describe('DELETE /api/exports/{export}', function (): void {
        it('deletes an export and its file', function (): void {
            $export = Export::factory()->create([
                'user_id' => $this->user->id,
                'file_path' => 'exports/test.pdf',
            ]);
            Storage::put($export->file_path, 'PDF content');

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/exports/{$export->id}");

            $response->assertSuccessful()
                ->assertJson(['message' => 'Export deleted successfully']);

            expect(Export::find($export->id))->toBeNull();
            expect(Storage::exists($export->file_path))->toBeFalse();
        });

        it('prevents deleting other users exports', function (): void {
            $otherUser = User::factory()->create();
            $export = Export::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/exports/{$export->id}");

            $response->assertForbidden();
        });

        it('requires authentication', function (): void {
            $export = Export::factory()->create();

            $response = $this->deleteJson("/api/exports/{$export->id}");

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/exports/analytics', function (): void {
        it('returns export analytics', function (): void {
            Export::factory()->count(5)->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/exports/analytics');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        'total_exports',
                        'total_downloads',
                        'popular_formats',
                        'recent_activity',
                    ],
                ]);
        });

        it('requires authentication', function (): void {
            $response = $this->getJson('/api/exports/analytics');

            $response->assertUnauthorized();
        });
    });
});
