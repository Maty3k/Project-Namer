<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\VisionAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->service = app(VisionAnalysisService::class);

    Storage::fake('public');
});

test('analyzes image and extracts descriptive content', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
        'processing_status' => 'completed',
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'description' => 'A modern office workspace with natural lighting',
                            'mood' => 'professional, clean, minimalist',
                            'colors' => ['blue', 'white', 'gray'],
                            'objects' => ['desk', 'computer', 'plants'],
                            'style' => 'contemporary',
                            'business_relevance' => 'technology, consulting, productivity',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->analyzeImage($image);

    expect($result)->toBeArray()
        ->and($result['description'])->toBe('A modern office workspace with natural lighting')
        ->and($result['mood'])->toBe('professional, clean, minimalist')
        ->and($result['colors'])->toContain('blue', 'white', 'gray')
        ->and($result['objects'])->toContain('desk', 'computer', 'plants')
        ->and($result['style'])->toBe('contemporary')
        ->and($result['business_relevance'])->toBe('technology, consulting, productivity');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/chat/completions' &&
           $request['model'] === 'gpt-4o' &&
           isset($request['messages'][0]['content'][1]['image_url']));
});

test('handles api failure gracefully', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    Http::fake([
        'api.openai.com/*' => Http::response([], 500),
    ]);

    expect(fn () => $this->service->analyzeImage($image))
        ->toThrow(\Exception::class, 'Vision API request failed');
});

test('handles invalid api response format', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'invalid json']],
            ],
        ], 200),
    ]);

    expect(fn () => $this->service->analyzeImage($image))
        ->toThrow(\Exception::class, 'Failed to parse vision analysis response');
});

test('caches analysis results to avoid duplicate api calls', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    $mockResponse = [
        'choices' => [
            [
                'message' => [
                    'content' => json_encode([
                        'description' => 'A beautiful sunset landscape',
                        'mood' => 'peaceful, serene',
                        'colors' => ['orange', 'purple', 'pink'],
                        'objects' => ['mountains', 'sky'],
                        'style' => 'natural',
                        'business_relevance' => 'wellness, tourism, outdoor',
                    ]),
                ],
            ],
        ],
    ];

    Http::fake([
        'api.openai.com/*' => Http::response($mockResponse, 200),
    ]);

    // First call should hit the API
    $result1 = $this->service->analyzeImage($image);

    // Second call should use cache
    $result2 = $this->service->analyzeImage($image);

    expect($result1)->toBe($result2);

    Http::assertSentCount(1);
});

test('validates image file exists before analysis', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'nonexistent/image.jpg',
    ]);

    expect(fn () => $this->service->analyzeImage($image))
        ->toThrow(\Exception::class, 'Image file not found');
});

test('handles different image formats', function (): void {
    $formats = ['jpg', 'png', 'webp'];

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'description' => 'Test image analysis',
                            'mood' => 'neutral',
                            'colors' => ['gray'],
                            'objects' => ['test'],
                            'style' => 'test',
                            'business_relevance' => 'testing',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    foreach ($formats as $format) {
        $image = ProjectImage::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'file_path' => "test/image.{$format}",
            'mime_type' => "image/{$format}",
        ]);

        Storage::disk('public')->put($image->file_path, 'fake image content');

        $result = $this->service->analyzeImage($image);

        expect($result['description'])->toBe('Test image analysis');
        expect($result)->toHaveKeys(['description', 'mood', 'colors', 'objects', 'style', 'business_relevance']);
    }
});

test('integrates analysis results with project image model', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'description' => 'Professional headshot photo',
                            'mood' => 'confident, professional',
                            'colors' => ['navy', 'white'],
                            'objects' => ['person', 'suit', 'background'],
                            'style' => 'portrait',
                            'business_relevance' => 'professional services, consulting, leadership',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->analyzeImageWithContext($image);

    expect($result)->toBeArray()
        ->and($image->fresh())->ai_analysis->toBeArray()
        ->and($image->fresh()->ai_analysis['description'])->toBe('Professional headshot photo');
});
