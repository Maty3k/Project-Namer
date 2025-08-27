<?php

declare(strict_types=1);

use App\Jobs\GenerateLogosJob;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();

    // Set up HTTP fake for OpenAI API
    Http::fake([
        'api.openai.com/*' => Http::response([
            'data' => [
                [
                    'url' => 'https://example.com/generated-logo.png',
                    'revised_prompt' => 'A minimalist logo design for a coffee shop',
                ],
            ],
        ]),
        'example.com/*' => Http::response('fake-image-data'),
    ]);
});

describe('Logo Generation API', function (): void {
    describe('POST /api/logos/generate', function (): void {
        it('can generate logos with valid business data', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'business_name' => 'Modern Coffee Shop',
                'business_description' => 'A trendy coffee shop serving artisanal beverages',
                'session_id' => 'test-session-123',
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'session_id',
                        'business_name',
                        'business_description',
                        'status',
                        'total_logos_requested',
                        'logos_completed',
                        'cost_cents',
                        'created_at',
                    ],
                    'message',
                ]);

            expect($response->json('data.status'))->toBe('pending')
                ->and($response->json('data.business_name'))->toBe('Modern Coffee Shop')
                ->and($response->json('data.total_logos_requested'))->toBe(12);

            // Verify job was dispatched
            Queue::assertPushed(GenerateLogosJob::class);
        });

        it('validates required fields', function (): void {
            $response = $this->postJson('/api/logos/generate', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['business_name', 'session_id']);
        });

        it('validates business name length', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'business_name' => '',
                'session_id' => 'test-session',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['business_name']);
        });

        it('handles business name that is too long', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'business_name' => str_repeat('A', 256),
                'session_id' => 'test-session',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['business_name']);
        });

        it('validates session_id format', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'business_name' => 'Test Business',
                'session_id' => '',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_id']);
        });

        it('limits business description length', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'business_name' => 'Test Business',
                'business_description' => str_repeat('A', 1001),
                'session_id' => 'test-session',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['business_description']);
        });
    });

    describe('GET /api/logos/{logoGeneration}/status', function (): void {
        it('returns generation status for pending request', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'pending',
                'logos_completed' => 0,
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'status',
                        'progress_percentage',
                        'logos_completed',
                        'total_logos_requested',
                        'estimated_completion_time',
                    ],
                ]);

            expect($response->json('data.status'))->toBe('pending')
                ->and($response->json('data.progress_percentage'))->toBe(0);
        });

        it('returns generation status for processing request', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'processing',
                'logos_completed' => 6,
                'total_logos_requested' => 12,
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");

            $response->assertOk();

            expect($response->json('data.status'))->toBe('processing')
                ->and($response->json('data.progress_percentage'))->toBe(50)
                ->and($response->json('data.logos_completed'))->toBe(6);
        });

        it('returns generation status for completed request', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
                'logos_completed' => 12,
                'total_logos_requested' => 12,
                'cost_cents' => 4800,
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");

            $response->assertOk();

            expect($response->json('data.status'))->toBe('completed')
                ->and($response->json('data.progress_percentage'))->toBe(100)
                ->and($response->json('data.cost_cents'))->toBe(4800);
        });

        it('returns 404 for non-existent generation', function (): void {
            $response = $this->getJson('/api/logos/999999/status');

            $response->assertNotFound();
        });

        it('includes error information for failed generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'failed',
                'error_message' => 'API quota exceeded',
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}/status");

            $response->assertOk();

            expect($response->json('data.status'))->toBe('failed')
                ->and($response->json('data.error_message'))->toBe('API quota exceeded');
        });
    });

    describe('GET /api/logos/{logoGeneration}', function (): void {
        it('returns completed generation with all logos', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $logos = GeneratedLogo::factory()->count(12)->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'business_name',
                        'status',
                        'logos' => [
                            '*' => [
                                'id',
                                'style',
                                'variation_number',
                                'original_file_path',
                                'file_size',
                                'download_url',
                                'preview_url',
                            ],
                        ],
                        'color_schemes',
                    ],
                ]);

            expect($response->json('data.logos'))->toHaveCount(12);
        });

        it('groups logos by style', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            // Create 3 logos for each style
            foreach (['minimalist', 'modern', 'playful', 'corporate'] as $style) {
                GeneratedLogo::factory()->count(3)->create([
                    'logo_generation_id' => $logoGeneration->id,
                    'style' => $style,
                ]);
            }

            $response = $this->getJson("/api/logos/{$logoGeneration->id}");

            $response->assertOk();

            $logosByStyle = collect($response->json('data.logos'))->groupBy('style');

            expect($logosByStyle)->toHaveCount(4)
                ->and($logosByStyle['minimalist'])->toHaveCount(3)
                ->and($logosByStyle['modern'])->toHaveCount(3)
                ->and($logosByStyle['playful'])->toHaveCount(3)
                ->and($logosByStyle['corporate'])->toHaveCount(3);
        });

        it('returns 404 for non-existent generation', function (): void {
            $response = $this->getJson('/api/logos/999999');

            $response->assertNotFound();
        });

        it('returns generation even if not completed but with empty logos array', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'processing',
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}");

            $response->assertOk();

            expect($response->json('data.logos'))->toBeArray()->toBeEmpty();
        });

        it('includes available color schemes', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $response = $this->getJson("/api/logos/{$logoGeneration->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'color_schemes' => [
                            '*' => [
                                'name',
                                'display_name',
                                'colors' => [
                                    'primary',
                                    'secondary',
                                    'accent',
                                ],
                            ],
                        ],
                    ],
                ]);
        });
    });

    describe('POST /api/logos/{logoGeneration}/customize', function (): void {
        it('can customize logos with color scheme', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Create a fake SVG file for testing
            Storage::disk('public')->put($logo->original_file_path, '<svg><rect fill="#000000"/></svg>');

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'color_scheme' => 'ocean_blue',
                'logo_ids' => [$logo->id],
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'customized_logos' => [
                            '*' => [
                                'id',
                                'original_logo_id',
                                'color_scheme',
                                'file_path',
                                'download_url',
                            ],
                        ],
                    ],
                    'message',
                ]);
        });

        it('validates color scheme exists', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'color_scheme' => 'invalid_scheme',
                'logo_ids' => [1],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['color_scheme']);
        });

        it('validates logo IDs belong to the generation', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $otherLogo = GeneratedLogo::factory()->create(); // Different generation

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'color_scheme' => 'ocean_blue',
                'logo_ids' => [$otherLogo->id],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo_ids.0']);
        });

        it('requires at least one logo ID', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'color_scheme' => 'ocean_blue',
                'logo_ids' => [],
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['logo_ids']);
        });
    });
});
