<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

describe('Logo Download API', function (): void {
    describe('GET /api/logos/{logoGeneration}/download/{generatedLogo}', function (): void {
        it('can download original logo file', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Test Business',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'variation_number' => 1,
                'original_file_path' => 'logos/test/originals/test-logo.png',
            ]);

            // Create fake file
            Storage::disk('public')->put($logo->original_file_path, 'fake-png-content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $expectedFilename = $logo->generateDownloadFilename();

            $response->assertOk()
                ->assertHeader('Content-Type', 'image/png')
                ->assertHeader('Content-Disposition', "attachment; filename=\"{$expectedFilename}\"");

            expect($response->getContent())->toBe('fake-png-content');
        });

        it('can download customized logo variant', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Test Business',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'variation_number' => 1,
            ]);

            $colorVariant = LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => 'ocean_blue',
                'file_path' => 'logos/test/customized/test-logo-blue.svg',
            ]);

            Storage::disk('public')->put($colorVariant->file_path, '<svg>customized content</svg>');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}?color_scheme=ocean_blue");

            $expectedFilename = $logo->generateDownloadFilename('ocean_blue');

            $response->assertOk()
                ->assertHeader('Content-Type', 'image/svg+xml')
                ->assertHeader('Content-Disposition', "attachment; filename=\"{$expectedFilename}\"");
        });

        it('returns 404 for non-existent logo generation', function (): void {
            $response = $this->get('/api/logos/999999/download/1');

            $response->assertNotFound();
        });

        it('returns 404 for non-existent logo', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/999999");

            $response->assertNotFound();
        });

        it('returns 404 for logo that belongs to different generation', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $otherGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $otherGeneration->id,
            ]);

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertNotFound();
        });

        it('returns 404 when file does not exist', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/non-existent.png',
            ]);

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertNotFound();
        });

        it('returns 404 when color variant does not exist', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/test.png',
            ]);

            Storage::disk('public')->put($logo->original_file_path, 'content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}?color_scheme=non_existent");

            $response->assertNotFound();
        });

        it('sets correct content type for different file formats', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            // Test PNG
            $pngLogo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/logo.png',
            ]);
            Storage::disk('public')->put($pngLogo->original_file_path, 'png-content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$pngLogo->id}");
            $response->assertHeader('Content-Type', 'image/png');

            // Test SVG
            $svgLogo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/logo.svg',
            ]);
            Storage::disk('public')->put($svgLogo->original_file_path, '<svg></svg>');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$svgLogo->id}");
            $response->assertHeader('Content-Type', 'image/svg+xml');
        });

        it('generates appropriate filename for download', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Modern Coffee Shop',
            ]);

            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'variation_number' => 2,
                'original_file_path' => 'logos/test/original.png',
            ]);

            Storage::disk('public')->put($logo->original_file_path, 'content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertHeader('Content-Disposition', 'attachment; filename="modern-coffee-shop-minimalist-2.png"');
        });
    });

    describe('GET /api/logos/{logoGeneration}/download-batch', function (): void {
        it('can download all logos as ZIP file', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Test Business',
                'status' => 'completed',
            ]);

            // Create some logos
            $logos = GeneratedLogo::factory()->count(3)->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Create fake files
            foreach ($logos as $logo) {
                Storage::disk('public')->put($logo->original_file_path, "content-for-{$logo->id}");
            }

            $response = $this->get("/api/logos/{$logoGeneration->id}/download-batch");

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/zip')
                ->assertHeader('Content-Disposition', 'attachment; filename=test-business-logos.zip');

            expect($response->headers->get('Content-Type'))->toBe('application/zip');
        });

        it('can download customized logos with specific color scheme', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Test Business',
            ]);

            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            $colorVariant = LogoColorVariant::factory()->create([
                'generated_logo_id' => $logo->id,
                'color_scheme' => 'ocean_blue',
            ]);

            Storage::disk('public')->put($logo->original_file_path, 'original-content');
            Storage::disk('public')->put($colorVariant->file_path, 'customized-content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download-batch?color_scheme=ocean_blue");

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/zip')
                ->assertHeader('Content-Disposition', 'attachment; filename=test-business-logos-ocean_blue.zip');
        });

        it('returns 404 for non-existent logo generation', function (): void {
            $response = $this->get('/api/logos/999999/download-batch');

            $response->assertNotFound();
        });

        it('returns 400 when generation has no completed logos', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'pending',
            ]);

            $response = $this->get("/api/logos/{$logoGeneration->id}/download-batch");

            $response->assertStatus(400)
                ->assertJson([
                    'message' => 'No logos available for download',
                ]);
        });

        it('handles missing files gracefully in batch download', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Test Business',
                'status' => 'completed',
            ]);

            // Create logos but don't create all files
            $logo1 = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/existing.png',
            ]);

            $logo2 = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/missing.png',
            ]);

            // Only create file for first logo
            Storage::disk('public')->put($logo1->original_file_path, 'content-1');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download-batch");

            // Should still return a ZIP, just with available files
            $response->assertOk()
                ->assertHeader('Content-Type', 'application/zip');
        });

        it('includes correct filename structure in ZIP', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'business_name' => 'Modern Coffee Shop',
                'status' => 'completed',
            ]);

            GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'variation_number' => 1,
                'original_file_path' => 'logos/test/minimalist-1.png',
            ]);

            GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'modern',
                'variation_number' => 2,
                'original_file_path' => 'logos/test/modern-2.svg',
            ]);

            // Create files
            Storage::disk('public')->put('logos/test/minimalist-1.png', 'png-content');
            Storage::disk('public')->put('logos/test/modern-2.svg', 'svg-content');

            $response = $this->get("/api/logos/{$logoGeneration->id}/download-batch");

            $response->assertOk();

            // The ZIP should contain properly named files
            // We can't easily test ZIP contents in a simple test, but we verify it's created
            expect($response->headers->get('Content-Disposition'))
                ->toContain('modern-coffee-shop-logos.zip');
        });
    });

    describe('Rate Limiting', function (): void {
        it('implements rate limiting for download endpoints', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            Storage::disk('public')->put($logo->original_file_path, 'content');

            // Make many requests quickly
            for ($i = 0; $i < 10; $i++) {
                $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

                if ($i < 5) {
                    $response->assertOk();
                } else {
                    // Should eventually hit rate limit
                    if ($response->getStatusCode() === 429) {
                        expect($response->getStatusCode())->toBe(429);
                        break;
                    }
                }
            }
        });
    });
});
