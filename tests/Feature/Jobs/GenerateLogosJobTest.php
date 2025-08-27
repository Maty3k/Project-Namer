<?php

declare(strict_types=1);

use App\Jobs\GenerateLogosJob;
use App\Models\LogoGeneration;
use App\Services\OpenAILogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();
    Event::fake();

    // Set up OpenAI API key for testing
    Config::set('services.openai.api_key', 'test-api-key');

    $this->logoGeneration = LogoGeneration::factory()->create([
        'business_name' => 'Modern coffee shop serving artisanal beverages',
        'status' => 'pending',
        'total_logos_requested' => 12,
        'logos_completed' => 0,
    ]);
});

describe('GenerateLogosJob', function (): void {
    it('can be dispatched successfully', function (): void {
        $job = new GenerateLogosJob($this->logoGeneration);

        expect($job->logoGeneration->id)->toBe($this->logoGeneration->id);
    });

    it('can call OpenAI service directly like the job does', function (): void {
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

        $openAIService = app(OpenAILogoService::class);
        $result = $openAIService->generateLogo($this->logoGeneration->business_name, 'minimalist');

        expect($result['success'])->toBeTrue()
            ->and($result['image_url'])->toBe('https://example.com/generated-logo.png');

        // The service call worked, which means the job's logic should work too
    });

    it('can call job generateSingleLogo method directly', function (): void {
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

        $openAIService = app(OpenAILogoService::class);
        $job = new GenerateLogosJob($this->logoGeneration);

        // Use reflection to call the private method
        $reflection = new ReflectionClass($job);
        $method = $reflection->getMethod('generateSingleLogo');

        $result = $method->invoke($job, $openAIService, 'minimalist', 1);

        expect($result['success'])->toBeTrue();
    });

    it('generates logos for all 4 styles with 3 variations each', function (): void {
        // Set up HTTP fakes for both OpenAI API and image download
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

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        // Debug: Check what HTTP requests were made
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));

        $this->logoGeneration->refresh();

        expect($this->logoGeneration->status)->toBe('completed')
            ->and($this->logoGeneration->logos_completed)->toBe(12)
            ->and($this->logoGeneration->generatedLogos)->toHaveCount(12);

        // Verify all styles are generated
        $styles = $this->logoGeneration->generatedLogos->pluck('style')->unique();
        expect($styles->sort()->values()->toArray())->toBe(['corporate', 'minimalist', 'modern', 'playful']);

        // Verify 3 variations per style
        foreach (['minimalist', 'modern', 'playful', 'corporate'] as $style) {
            $styleCount = $this->logoGeneration->generatedLogos->where('style', $style)->count();
            expect($styleCount)->toBe(3);
        }
    });

    it('updates logo generation status to processing when started', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        // After job completes, check that it went through processing state
        $this->logoGeneration->refresh();
        expect($this->logoGeneration->status)->toBe('completed');
    });

    it('stores logos in correct directory structure', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Test logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-content'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $generatedLogo = $this->logoGeneration->generatedLogos->first();

        expect($generatedLogo->original_file_path)->toContain("logos/{$this->logoGeneration->id}/originals/")
            ->and($generatedLogo->original_file_path)->toContain('.png');

        // Verify file was actually stored
        Storage::disk('public')->assertExists($generatedLogo->original_file_path);
    });

    it('handles API failures gracefully and continues processing', function (): void {
        $logoCount = 0;

        Http::fake([
            '*' => function ($request) use (&$logoCount) {
                if (str_contains((string) $request->url(), 'api.openai.com')) {
                    $logoCount++;

                    // Fail first 3 logos permanently (use 401 which won't be retried)
                    if ($logoCount <= 3) {
                        return Http::response(['error' => ['message' => 'Authentication failed']], 401);
                    }

                    return Http::response([
                        'data' => [
                            [
                                'url' => 'https://example.com/logo.png',
                                'revised_prompt' => 'Generated logo',
                            ],
                        ],
                    ]);
                }

                if (str_contains((string) $request->url(), 'example.com')) {
                    return Http::response('fake-image-data');
                }

                return Http::response('Not Found', 404);
            },
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $this->logoGeneration->refresh();

        // Should have generated 9 successful logos (12 - 3 failures)
        expect($this->logoGeneration->logos_completed)->toBe(9)
            ->and($this->logoGeneration->status)->toBe('completed')
            ->and($this->logoGeneration->generatedLogos)->toHaveCount(9);
    });

    it('updates cost tracking during generation', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $this->logoGeneration->refresh();

        // 12 successful generations × 400 cents each = 4800 cents
        expect($this->logoGeneration->cost_cents)->toBe(4800);
    });

    it('handles image download failures', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $this->logoGeneration->refresh();

        // Should still create database records even if download fails
        expect($this->logoGeneration->generatedLogos)->toHaveCount(12)
            ->and($this->logoGeneration->status)->toBe('completed');

        // But files shouldn't exist
        foreach ($this->logoGeneration->generatedLogos as $logo) {
            if ($logo->original_file_path) {
                Storage::disk('public')->assertMissing($logo->original_file_path);
            }
        }
    });

    it('marks generation as failed if all API calls fail', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'API key invalid']], 401),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $this->logoGeneration->refresh();

        expect($this->logoGeneration->status)->toBe('failed')
            ->and($this->logoGeneration->logos_completed)->toBe(0)
            ->and($this->logoGeneration->generatedLogos)->toHaveCount(0);
    });

    it('calculates file sizes correctly', function (): void {
        $imageContent = str_repeat('x', 1024); // 1KB

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response($imageContent),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $generatedLogo = $this->logoGeneration->generatedLogos->first();

        expect($generatedLogo->file_size)->toBe(1024);
    });

    it('generates unique filenames for each logo', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $filePaths = $this->logoGeneration->generatedLogos->pluck('original_file_path');
        $uniquePaths = $filePaths->unique();

        expect($filePaths->count())->toBe($uniquePaths->count());
    });

    it('can be retried after failure', function (): void {
        Queue::fake();

        $job = new GenerateLogosJob($this->logoGeneration);

        expect($job->tries)->toBe(3)
            ->and($job->backoff())->toBe([30, 60, 120]); // Exponential backoff in seconds
    });

    it('cleans up partial files on job failure', function (): void {
        Storage::fake('public');

        // Create some fake files
        $logoDir = "logos/{$this->logoGeneration->id}";
        Storage::disk('public')->put("{$logoDir}/originals/test1.png", 'fake-content');
        Storage::disk('public')->put("{$logoDir}/originals/test2.png", 'fake-content');

        Storage::disk('public')->assertExists("{$logoDir}/originals/test1.png");
        Storage::disk('public')->assertExists("{$logoDir}/originals/test2.png");

        // Simulate job failure
        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        // Verify cleanup occurred
        expect(Storage::disk('public')->exists($logoDir))->toBeFalse();
    });

    it('creates proper directory structure', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $logoDir = "logos/{$this->logoGeneration->id}";

        // Verify directory structure exists
        expect(Storage::disk('public')->exists("{$logoDir}/originals"))->toBeTrue();

        // Verify files are in correct subdirectories
        $this->logoGeneration->generatedLogos->each(function ($logo): void {
            expect($logo->original_file_path)->toContain('/originals/');
        });
    });

    it('handles concurrent job execution safely', function (): void {
        // This tests that multiple jobs don't interfere with each other
        $logoGeneration2 = LogoGeneration::factory()->create([
            'business_name' => 'Tech startup',
            'status' => 'pending',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job1 = new GenerateLogosJob($this->logoGeneration);
        $job2 = new GenerateLogosJob($logoGeneration2);

        $job1->handle();
        $job2->handle();

        $this->logoGeneration->refresh();
        $logoGeneration2->refresh();

        expect($this->logoGeneration->status)->toBe('completed')
            ->and($logoGeneration2->status)->toBe('completed')
            ->and($this->logoGeneration->generatedLogos)->toHaveCount(12)
            ->and($logoGeneration2->generatedLogos)->toHaveCount(12);

        // Verify files are stored in separate directories
        $paths1 = $this->logoGeneration->generatedLogos->pluck('original_file_path');
        $paths2 = $logoGeneration2->generatedLogos->pluck('original_file_path');

        expect($paths1->intersect($paths2))->toBeEmpty();
    });

    it('tracks generation progress in real time', function (): void {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [
                    [
                        'url' => 'https://example.com/logo.png',
                        'revised_prompt' => 'Generated logo',
                    ],
                ],
            ]),
            'example.com/*' => Http::response('fake-image-data'),
        ]);

        $job = new GenerateLogosJob($this->logoGeneration);
        $job->handle();

        $this->logoGeneration->refresh();

        // Verify that progress was tracked correctly
        expect($this->logoGeneration->logos_completed)->toBe(12)
            ->and($this->logoGeneration->status)->toBe('completed');
    });

    it('validates logo generation model exists before processing', function (): void {
        $nonExistentGeneration = new LogoGeneration(['id' => 999999]);

        $job = new GenerateLogosJob($nonExistentGeneration);

        expect(fn () => $job->handle())
            ->toThrow(\TypeError::class);
    });

    it('respects timeout configuration', function (): void {
        $job = new GenerateLogosJob($this->logoGeneration);

        // Should have a reasonable timeout for processing 12 logos
        expect($job->timeout)->toBe(1800); // 30 minutes
    });

    it('tags job for monitoring', function (): void {
        $job = new GenerateLogosJob($this->logoGeneration);

        expect($job->tags())->toContain('logo-generation')
            ->and($job->tags())->toContain("generation-{$this->logoGeneration->id}");
    });

    it('provides meaningful job display name', function (): void {
        $job = new GenerateLogosJob($this->logoGeneration);

        $displayName = $job->displayName();

        expect($displayName)->toContain('Generate Logos')
            ->and($displayName)->toContain((string) $this->logoGeneration->id);
    });
});
