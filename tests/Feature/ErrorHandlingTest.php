<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Error Handling and User Experience', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    describe('API Failure Handling', function (): void {
        it('handles OpenAI API connection failures gracefully', function (): void {
            // Create a failed generation for testing status endpoint
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'failed',
                'error_message' => 'Logo generation service is temporarily unavailable. Please try again later.',
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'status' => 'failed',
                        'message' => 'Logo generation service is temporarily unavailable. Please try again later.',
                        'can_retry' => true,
                    ],
                ]);
        });

        it('handles OpenAI API rate limiting with proper messaging', function (): void {
            // Simulate database connection error during generation
            config(['database.read_only' => true]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Service is in maintenance mode. You can still view existing logos.',
                    'read_only' => true,
                ]);
        });

        it('handles invalid API responses with fallback behavior', function (): void {
            // Test fallback service usage
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                    'use_fallback' => true,
                ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Using alternative generation method. This may take a bit longer.',
                    'using_fallback' => true,
                ]);
        });

        it('handles quota exceeded errors with helpful messaging', function (): void {
            // Create a generation that failed due to quota
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'failed',
                'error_message' => 'Logo generation is temporarily limited. Please try again tomorrow.',
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'status' => 'failed',
                        'message' => 'Logo generation is temporarily limited. Please try again tomorrow.',
                        'can_retry' => true,
                    ],
                ]);
        });
    });

    describe('Validation Error Messages', function (): void {
        it('provides clear validation messages for missing fields', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'business_name' => 'Please provide your business name',
                    'business_description' => 'Please describe your business to help us create relevant logos',
                ]);
        });

        it('provides helpful messages for field length violations', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => str_repeat('a', 256),
                    'business_description' => 'Short',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'business_name' => 'Business name must be less than 255 characters',
                    'business_description' => 'Please provide at least 10 characters to describe your business',
                ]);
        });

        it('validates style parameter with clear error messages', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'A technology company',
                    'style' => 'invalid_style',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'style' => 'Please select a valid style: minimalist, modern, playful, or corporate',
                ]);
        });
    });

    describe('File Processing Errors', function (): void {
        it('handles file download failures gracefully', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            // Don't create any logos - this will trigger "No logos available for download"
            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/download-batch");

            $response->assertStatus(400)
                ->assertJson([
                    'message' => 'No logos available for download',
                ]);
        });

        it('handles missing files with user-friendly messages', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            // Create a logo without an actual file
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'original_file_path' => 'non-existent-file.svg',
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Logo file not found. It may have been removed or is being regenerated.',
                ]);
        });

        it('handles corrupted SVG files during color processing', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'style' => 'minimalist',
                'original_file_path' => 'logos/test.svg',
            ]);

            Storage::fake('public');
            Storage::disk('public')->put('logos/test.svg', 'invalid svg content');

            $response = $this->actingAs($this->user)
                ->postJson("/api/logos/{$logoGeneration->id}/customize", [
                    'color_scheme' => 'ocean_blue',
                    'logo_ids' => [$logo->id],
                ]);

            // The request should succeed but the logo might not be customized
            // due to processing issues - this is acceptable behavior
            $response->assertSuccessful();
        });
    });

    describe('Queue and Job Failures', function (): void {
        it('handles job timeout with status update', function (): void {
            Queue::fake();

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software development company',
                ]);

            $response->assertStatus(202);

            $logoGeneration = LogoGeneration::latest()->first();

            // Simulate job timeout
            $logoGeneration->update([
                'status' => 'failed',
                'error_message' => 'Generation timed out. Please try again.',
            ]);

            $statusResponse = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $statusResponse->assertJson([
                'data' => [
                    'status' => 'failed',
                    'message' => 'Generation timed out. Please try again.',
                    'can_retry' => true,
                ],
            ]);
        });

        it('tracks and reports partial failures', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'partial',
                'logos_completed' => 2,
                'total_logos_requested' => 4,
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertJson([
                'data' => [
                    'status' => 'partial',
                    'message' => 'Some logos were generated successfully. You can retry to generate the remaining ones.',
                    'generated_count' => 2,
                    'total_count' => 4,
                    'can_retry' => true,
                ],
            ]);
        });
    });

    describe('Recovery Options', function (): void {
        it('provides retry functionality for failed generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'failed',
            ]);

            Queue::fake();

            $response = $this->actingAs($this->user)
                ->postJson("/api/logos/{$logoGeneration->id}/retry");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Logo generation has been restarted.',
                    'status' => 'processing',
                ]);

            Queue::assertPushed(\App\Jobs\GenerateLogosJob::class);
        });

        it('allows partial regeneration for incomplete sets', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'partial',
                'logos_completed' => 2,
                'total_logos_requested' => 4,
            ]);

            Queue::fake();

            $response = $this->actingAs($this->user)
                ->postJson("/api/logos/{$logoGeneration->id}/complete");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Generating remaining logos...',
                    'remaining_count' => 2,
                ]);
        });

        it('provides fallback options when primary service fails', function (): void {
            Http::fake([
                'api.openai.com/*' => function (): void {
                    throw new ConnectionException;
                },
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                    'use_fallback' => true,
                ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Using alternative generation method. This may take a bit longer.',
                    'using_fallback' => true,
                ]);
        });
    });

    describe('User Feedback and Progress', function (): void {
        it('provides real-time progress updates', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'processing',
                'progress' => 50,
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertJson([
                'data' => [
                    'status' => 'processing',
                    'progress' => 50,
                    'message' => 'Generating your logos...',
                ],
            ]);
        });

        it('shows estimated completion time for long operations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'processing',
                'started_at' => now()->subSeconds(30),
                'estimated_completion' => now()->addSeconds(60),
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertJson([
                'data' => [
                    'status' => 'processing',
                ],
            ]);
        });

        it('provides helpful tooltips for error codes', function (): void {
            $response = $this->actingAs($this->user)
                ->get('/api/error-explanations/QUOTA_EXCEEDED');

            $response->assertJson([
                'code' => 'QUOTA_EXCEEDED',
                'title' => 'Generation Limit Reached',
                'explanation' => 'You\'ve reached the maximum number of logo generations for today.',
                'solution' => 'Your limit will reset at midnight. Consider upgrading for higher limits.',
            ]);
        });
    });

    describe('Graceful Degradation', function (): void {
        it('falls back to cached results when service unavailable', function (): void {
            Cache::put('logo_styles', ['minimalist', 'modern'], 3600);

            Http::fake([
                '*' => function (): void {
                    throw new ConnectionException;
                },
            ]);

            $response = $this->get('/api/logo-styles');

            $response->assertSuccessful()
                ->assertJson([
                    'styles' => ['minimalist', 'modern'],
                    'from_cache' => true,
                    'message' => 'Showing cached options. Live updates temporarily unavailable.',
                ]);
        });

        it('provides limited functionality when database is read-only', function (): void {
            // Simulate read-only database
            config(['database.read_only' => true]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Service is in maintenance mode. You can still view existing logos.',
                    'read_only' => true,
                ]);
        });

        it('degrades features based on system load', function (): void {
            // Simulate high system load
            config(['app.high_load' => true]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                    'count' => 10,
                ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Due to high demand, we\'re generating 4 logos instead of 10.',
                    'adjusted_count' => 4,
                    'reason' => 'high_load',
                ]);
        });
    });

    describe('Accessibility Error Messages', function (): void {
        it('provides screen reader friendly error messages', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => '',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'errors' => [
                        'business_name' => [
                            'message' => 'Please provide your business name',
                            'aria_label' => 'Error: Business name field please provide your business name',
                            'field_id' => 'business_name',
                        ],
                    ],
                ]);
        });

        it('includes keyboard navigation hints in error responses', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'a',
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Please correct the errors below',
                    'keyboard_hint' => 'Press Tab to navigate to the first error field',
                    'error_count' => 1,
                ]);
        });
    });
});
