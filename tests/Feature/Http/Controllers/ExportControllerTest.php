<?php

declare(strict_types=1);

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('ExportController', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->logoGeneration = LogoGeneration::factory()->create();
        Storage::fake('local');
    });

    it('creates a PDF export via API', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'include_domains' => true,
            'include_metadata' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $exportData);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'export_type',
                    'file_size',
                    'download_url',
                    'expires_at',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('exports', [
            'user_id' => $this->user->id,
            'export_type' => 'pdf',
            'exportable_id' => $this->logoGeneration->id,
        ]);
    });

    it('creates a CSV export via API', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
            'include_domains' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $exportData);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'export_type' => 'csv',
                ],
            ]);

        $export = Export::where('export_type', 'csv')->first();
        expect($export->fileExists())->toBeTrue();
        expect($export->file_path)->toContain('.csv');
    });

    it('creates a JSON export via API', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
            'include_metadata' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $exportData);

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'export_type' => 'json',
                ],
            ]);

        $export = Export::where('export_type', 'json')->first();
        expect($export->fileExists())->toBeTrue();
    });

    it('validates export creation data', function (): void {
        $invalidData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => 99999, // Non-existent ID
            'export_type' => 'invalid_format',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $invalidData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['exportable_id', 'export_type']);
    });

    it('lists user exports with pagination', function (): void {
        Export::factory()->count(12)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/exports?per_page=5');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'export_type',
                        'file_size',
                        'download_count',
                        'expires_at',
                        'created_at',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('pagination.total'))->toBe(12);
    });

    it('filters exports by type', function (): void {
        Export::factory()->count(3)->pdf()->create(['user_id' => $this->user->id]);
        Export::factory()->count(2)->csv()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/exports?export_type=pdf');

        $response->assertSuccessful();
        expect($response->json('pagination.total'))->toBe(3);

        foreach ($response->json('data') as $export) {
            expect($export['export_type'])->toBe('pdf');
        }
    });

    it('shows a single export', function (): void {
        $export = Export::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports/{$export->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'export_type',
                    'file_size',
                    'download_count',
                    'download_url',
                    'formatted_file_size',
                    'is_expired',
                    'exportable',
                ],
            ]);
    });

    it('downloads export files with proper headers', function (): void {
        $export = Export::factory()->create(['user_id' => $this->user->id]);
        Storage::put($export->file_path, 'test export content');

        $response = $this->actingAs($this->user)
            ->get("/api/exports/{$export->uuid}/download");

        $response->assertSuccessful()
            ->assertHeader('content-type', $export->getContentType())
            ->assertHeader('content-disposition');

        // Verify download count incremented
        $export->refresh();
        expect($export->download_count)->toBe(1);
    });

    it('prevents downloading non-existent files', function (): void {
        $export = Export::factory()->create(['user_id' => $this->user->id]);
        // Don't create the actual file

        $response = $this->actingAs($this->user)
            ->get("/api/exports/{$export->uuid}/download");

        $response->assertNotFound();
    });

    it('prevents downloading expired exports', function (): void {
        $export = Export::factory()->expired()->create(['user_id' => $this->user->id]);
        Storage::put($export->file_path, 'test content');

        $response = $this->actingAs($this->user)
            ->get("/api/exports/{$export->uuid}/download");

        $response->assertGone(); // 410 Gone
    });

    it('deletes exports and associated files', function (): void {
        $export = Export::factory()->create(['user_id' => $this->user->id]);
        Storage::put($export->file_path, 'test content');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/exports/{$export->id}");

        $response->assertSuccessful();

        $this->assertDatabaseMissing('exports', ['id' => $export->id]);
        expect(Storage::exists($export->file_path))->toBeFalse();
    });

    it('prevents unauthorized access to other users exports', function (): void {
        $otherUser = User::factory()->create();
        $export = Export::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/exports/{$export->id}");

        $response->assertForbidden();
    });

    it('provides export analytics', function (): void {
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

    it('supports custom export templates', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'template' => 'professional',
            'include_branding' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $exportData);

        $response->assertSuccessful();

        $export = Export::latest()->first();
        expect($export->fileExists())->toBeTrue();
        expect($export->file_size)->toBeGreaterThan(1000); // Professional template should be larger
    });

    it('handles concurrent export requests', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'csv',
        ];

        // Create multiple exports concurrently
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/exports', $exportData);
        }

        foreach ($responses as $response) {
            $response->assertSuccessful();
        }

        $exportCount = Export::where('user_id', $this->user->id)->count();
        expect($exportCount)->toBe(3);
    });

    it('sets custom expiration dates', function (): void {
        $exportData = [
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'pdf',
            'expires_in_days' => 14,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/exports', $exportData);

        $response->assertSuccessful();

        $export = Export::latest()->first();
        $expectedDate = now()->addDays(14);
        expect($export->expires_at->format('Y-m-d'))->toBe($expectedDate->format('Y-m-d'));
    });

    it('requires authentication for all endpoints', function (): void {
        $response = $this->getJson('/api/exports');
        $response->assertUnauthorized();

        $response = $this->postJson('/api/exports', []);
        $response->assertUnauthorized();
    });

    it('provides public download for exports via UUID', function (): void {
        $export = Export::factory()->create();
        Storage::put($export->file_path, 'test content');

        $response = $this->get("/downloads/{$export->uuid}");

        $response->assertSuccessful()
            ->assertHeader('content-disposition');

        $export->refresh();
        expect($export->download_count)->toBe(1);
    });

    it('tracks download timestamps', function (): void {
        $export = Export::factory()->create(['user_id' => $this->user->id]);
        Storage::put($export->file_path, 'test content');

        expect($export->last_downloaded_at)->toBeNull();

        $this->actingAs($this->user)
            ->get("/api/exports/{$export->uuid}/download");

        $export->refresh();
        expect($export->last_downloaded_at)->not->toBeNull();
    });
});
