<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Error Handling and User Experience', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        Queue::fake();
        Storage::fake('public');
    });

    describe('API Failure Handling', function (): void {
        it('handles various failure states with appropriate messaging', function (string $status, string $errorMessage): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => $status,
                'error_message' => $errorMessage,
            ]);

            $response = $this->actingAs($this->user)
                ->get("/api/logos/{$logoGeneration->id}/status");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'status' => $status,
                        'message' => $errorMessage,
                        'can_retry' => true,
                    ],
                ]);
        })->with([
            'API connection failure' => ['failed', 'Logo generation service is temporarily unavailable. Please try again later.'],
            'quota exceeded' => ['failed', 'Logo generation is temporarily limited. Please try again tomorrow.'],
        ]);

        it('handles database read-only mode', function (): void {
            config(['database.read_only' => true]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                    'session_id' => 'test-session',
                ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Service is in maintenance mode. You can still view existing logos.',
                    'read_only' => true,
                ]);
        });
    });

    describe('Validation Error Messages', function (): void {
        it('provides clear validation messages', function (array $payload, array $expectedErrors): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', $payload);

            $response->assertStatus(422)
                ->assertJsonValidationErrors($expectedErrors);
        })->with([
            'missing fields' => [
                [],
                [
                    'business_name' => 'Please provide your business name',
                    'business_description' => 'Please describe your business to help us create relevant logos',
                ],
            ],
            'field length violations' => [
                [
                    'business_name' => str_repeat('a', 256),
                    'business_description' => 'Short',
                    'session_id' => 'test-session',
                ],
                [
                    'business_name' => 'Business name must be less than 255 characters',
                    'business_description' => 'Please provide at least 10 characters to describe your business',
                ],
            ],
            'invalid style' => [
                [
                    'business_name' => 'TechCorp',
                    'business_description' => 'A technology company',
                    'session_id' => 'test-session',
                    'style' => 'invalid_style',
                ],
                [
                    'style' => 'Please select a valid style: minimalist, modern, playful, or corporate',
                ],
            ],
        ]);
    });

    describe('File Processing Errors', function (): void {
        it('handles file-related errors with user-friendly messages', function (string $scenario, callable $setup, int $expectedStatus, array $expectedJson): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $setup($logoGeneration);

            $response = $this->actingAs($this->user)
                ->get($scenario === 'no_logos'
                    ? "/api/logos/{$logoGeneration->id}/download-batch"
                    : "/api/logos/{$logoGeneration->id}/download/{$this->testLogo->id}");

            $response->assertStatus($expectedStatus)
                ->assertJson($expectedJson);
        })->with([
            'no_logos' => [
                'no_logos',
                fn () => null,
                400,
                ['message' => 'No logos available for download'],
            ],
            'missing_file' => [
                'missing_file',
                function ($logoGeneration): void {
                    $this->testLogo = GeneratedLogo::factory()->create([
                        'logo_generation_id' => $logoGeneration->id,
                        'style' => 'minimalist',
                        'original_file_path' => 'non-existent-file.svg',
                    ]);
                },
                404,
                ['message' => 'Logo file not found. It may have been removed or is being regenerated.'],
            ],
        ]);
    });

    describe('Queue and Job Failures', function (): void {
        it('handles job timeout with status update', function (): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software development company',
                    'session_id' => 'test-session',
                ]);

            $response->assertStatus(202);

            $logoGeneration = LogoGeneration::latest()->first();

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

            $response = $this->actingAs($this->user)
                ->postJson("/api/logos/{$logoGeneration->id}/complete");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Generating remaining logos...',
                    'remaining_count' => 2,
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
        it('provides limited functionality when database is read-only', function (): void {
            config(['database.read_only' => true]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', [
                    'business_name' => 'TechCorp',
                    'business_description' => 'Software company',
                    'session_id' => 'test-session',
                ]);

            $response->assertStatus(503)
                ->assertJson([
                    'message' => 'Service is in maintenance mode. You can still view existing logos.',
                    'read_only' => true,
                ]);
        });
    });

    describe('Accessibility Error Messages', function (): void {
        it('provides accessible error messages with screen reader and keyboard support', function (array $payload, array $expectedJson): void {
            $response = $this->actingAs($this->user)
                ->postJson('/api/logos/generate', $payload);

            $response->assertStatus(422)
                ->assertJson($expectedJson);
        })->with([
            'screen reader friendly' => [
                ['business_name' => '', 'session_id' => 'test-session'],
                [
                    'errors' => [
                        'business_name' => [
                            'message' => 'Please provide your business name',
                            'aria_label' => 'Error: Business name field please provide your business name',
                            'field_id' => 'business_name',
                        ],
                    ],
                ],
            ],
            'keyboard navigation hints' => [
                ['business_name' => 'a', 'session_id' => 'test-session'],
                [
                    'message' => 'Please correct the errors below',
                    'keyboard_hint' => 'Press Tab to navigate to the first error field',
                    'error_count' => 1,
                ],
            ],
        ]);
    });
})->skip('API endpoints not yet implemented - these are placeholder tests for future error handling features');
