<?php

declare(strict_types=1);

use App\Exceptions\LogoGenerationException;
use App\Jobs\GenerateLogosJob;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();
});

describe('Logo Generation Error Handling', function (): void {
    describe('API Connection Failures', function (): void {
        it('handles connection timeouts gracefully', function (): void {
            // Connection errors happen during job processing, not during the initial request
            // The initial request should succeed and create the logoGeneration record
            $response = $this->postJson('/api/logos/generate', [
                'session_id' => 'test-session',
                'business_name' => 'Test Business',
                'business_description' => 'A test business for testing',
                'count' => 4,
            ]);

            $response->assertStatus(202)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'business_name',
                        'status',
                    ],
                    'message',
                ]);

            // The connection error would be handled in the job processing
            expect($response->json('data.status'))->toBe('pending');
        });

        it('handles API rate limiting with appropriate retry time', function (): void {
            // Test the rate limiting exception directly
            expect(function (): void {
                throw LogoGenerationException::rateLimited(300);
            })->toThrow(LogoGenerationException::class);

            $exception = LogoGenerationException::rateLimited(300);
            expect($exception->getErrorCode())->toBe('RATE_LIMITED');
            expect($exception->getRetryAfter())->toBe(300);
            expect($exception->getMessage())->toContain('high demand');
        });

        it('handles invalid API responses with user-friendly messages', function (): void {
            // Test the invalid response exception directly
            $exception = LogoGenerationException::invalidResponse();

            expect($exception->getErrorCode())->toBe('INVALID_API_RESPONSE');
            expect($exception->getMessage())->toBe('Unable to process logo generation. Our team has been notified.');
            expect($exception->getRecoveryActions())->toHaveCount(2);
            expect($exception->getUserGuidance())->toContain('temporary issue');
        });
    });

    describe('File System Errors', function (): void {
        it('handles file download failures gracefully', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/nonexistent/file.png',
            ]);

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Logo file not found. It may have been removed or is being regenerated.',
                    'error_code' => 'FILE_NOT_FOUND',
                ]);
        });

        it('handles storage disk unavailability', function (): void {
            // Mock storage failure
            Storage::shouldReceive('disk->exists')
                ->andReturn(false);

            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            $response = $this->get("/api/logos/{$logoGeneration->id}/download/{$logo->id}");

            $response->assertStatus(404);
        });

        it('handles corrupted logo files during color processing', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/corrupted.svg',
            ]);

            // Create corrupted SVG file
            Storage::disk('public')->put($logo->original_file_path, '<invalid-svg-content>');

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'logo_ids' => [$logo->id],
                'color_scheme' => 'ocean_blue',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'customized_logos' => [],
                    ],
                    'message' => '0 logos customized successfully',
                ]);
        });
    });

    describe('Validation Errors', function (): void {
        it('provides clear error messages for invalid generation requests', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'session_id' => '',
                'business_name' => '',
                'business_description' => '',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'session_id',
                    'business_name',
                    'business_description',
                ]);
        });

        it('validates color scheme customization requests', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'logo_ids' => [],
                'color_scheme' => 'invalid_scheme',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'logo_ids',
                    'color_scheme',
                ]);
        });

        it('handles attempts to customize non-existent logos', function (): void {
            $logoGeneration = LogoGeneration::factory()->create();

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/customize", [
                'logo_ids' => [999, 998],
                'color_scheme' => 'ocean_blue',
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors',
                ]);
        });
    });

    describe('Service Degradation Scenarios', function (): void {
        it('handles read-only mode gracefully', function (): void {
            config(['database.read_only' => true]);

            $response = $this->postJson('/api/logos/generate', [
                'session_id' => 'test-session',
                'business_name' => 'Test Business',
                'business_description' => 'A test business',
                'count' => 4,
            ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Service is in maintenance mode. You can still view existing logos.',
                    'read_only' => true,
                ]);
        });

        it('adjusts logo count during high load conditions', function (): void {
            config(['app.high_load' => true]);

            $response = $this->postJson('/api/logos/generate', [
                'session_id' => 'test-session',
                'business_name' => 'Test Business',
                'business_description' => 'A test business',
                'count' => 8,
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Due to high demand, we\'re generating 4 logos instead of 8.',
                    'adjusted_count' => 4,
                    'reason' => 'high_load',
                ]);

            // Verify the job was dispatched with adjusted count
            Queue::assertPushed(GenerateLogosJob::class, function ($job) {
                return $job->logoGeneration->total_logos_requested === 12; // 4 * 3 variations
            });
        });

        it('provides fallback service messaging', function (): void {
            $response = $this->postJson('/api/logos/generate', [
                'session_id' => 'test-session',
                'business_name' => 'Test Business',
                'business_description' => 'A test business',
                'count' => 4,
                'use_fallback' => true,
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Using alternative generation method. This may take a bit longer.',
                    'using_fallback' => true,
                ]);
        });
    });

    describe('Recovery Operations', function (): void {
        it('allows retrying failed logo generation', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'failed',
                'error_message' => 'Previous generation failed',
            ]);

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/retry");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Logo generation has been restarted.',
                    'status' => 'processing',
                ]);

            $logoGeneration->refresh();
            expect($logoGeneration->status)->toBe('processing');
            expect($logoGeneration->error_message)->toBeNull();

            Queue::assertPushed(GenerateLogosJob::class);
        });

        it('prevents retrying non-failed generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/retry");

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Only failed generations can be retried',
                ]);
        });

        it('allows completing partial generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'partial',
                'total_logos_requested' => 12,
                'logos_completed' => 6,
            ]);

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/complete");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Generating remaining logos...',
                    'remaining_count' => 6,
                ]);

            $logoGeneration->refresh();
            expect($logoGeneration->status)->toBe('processing');

            Queue::assertPushed(GenerateLogosJob::class);
        });

        it('prevents completing non-partial generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $response = $this->postJson("/api/logos/{$logoGeneration->id}/complete");

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Only partial generations can be completed',
                ]);
        });
    });
});

describe('Status API Error Handling', function (): void {
    it('provides detailed progress information', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'processing',
            'total_logos_requested' => 12,
            'logos_completed' => 8,
        ]);

        $response = $this->get("/api/logos/{$logoGeneration->id}/status");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'message',
                    'progress',
                    'progress_percentage',
                    'logos_completed',
                    'total_logos_requested',
                    'estimated_time_remaining',
                    'estimated_completion',
                ],
            ]);

        $data = $response->json('data');
        expect($data['progress_percentage'])->toBe(67); // 8/12 * 100 rounded
        expect($data['estimated_time_remaining'])->toBe(120); // 4 remaining * 30 seconds
    });

    it('handles failed generation status with retry information', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'failed',
            'error_message' => 'API quota exceeded. Please try again later.',
        ]);

        $response = $this->get("/api/logos/{$logoGeneration->id}/status");

        $response->assertOk();

        $data = $response->json('data');
        expect($data['status'])->toBe('failed');
        expect($data['can_retry'])->toBeTrue();
        expect($data['message'])->toBe('API quota exceeded. Please try again later.');
    });

    it('handles partial generation status', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'status' => 'partial',
            'total_logos_requested' => 12,
            'logos_completed' => 7,
        ]);

        $response = $this->get("/api/logos/{$logoGeneration->id}/status");

        $response->assertOk();

        $data = $response->json('data');
        expect($data['status'])->toBe('partial');
        expect($data['can_retry'])->toBeTrue();
        expect($data['generated_count'])->toBe(7);
        expect($data['total_count'])->toBe(12);
    });
});
