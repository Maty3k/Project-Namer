<?php

declare(strict_types=1);

use App\Jobs\AnalyzeImageWithAIJob;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\VisionAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    Storage::fake('public');
});

test('job can be dispatched and queued', function (): void {
    Queue::fake();

    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    AnalyzeImageWithAIJob::dispatch($image);

    Queue::assertPushed(AnalyzeImageWithAIJob::class, fn ($job) => $job->image->id === $image->id);
});

test('job processes image analysis successfully', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'test/image.jpg',
        'ai_analysis' => null,
    ]);

    Storage::disk('public')->put($image->file_path, 'fake image content');

    $mockService = $this->mock(VisionAnalysisService::class);
    $mockService->shouldReceive('analyzeImageWithContext')
        ->once()
        ->with($image)
        ->andReturnUsing(function ($image) {
            $image->update(['ai_analysis' => [
                'description' => 'Test analysis result',
                'mood' => 'professional',
                'colors' => ['blue'],
                'objects' => ['computer'],
                'style' => 'modern',
                'business_relevance' => 'technology',
            ]]);

            return [
                'description' => 'Test analysis result',
                'mood' => 'professional',
                'colors' => ['blue'],
                'objects' => ['computer'],
                'style' => 'modern',
                'business_relevance' => 'technology',
            ];
        });

    $job = new AnalyzeImageWithAIJob($image);
    $job->handle();

    expect($image->fresh())->ai_analysis->toBeArray()
        ->and($image->fresh()->ai_analysis['description'])->toBe('Test analysis result');
});

test('job handles analysis failure gracefully', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'ai_analysis' => null,
    ]);

    $mockService = $this->mock(VisionAnalysisService::class);
    $mockService->shouldReceive('analyzeImageWithContext')
        ->once()
        ->with($image)
        ->andThrow(new \Exception('Vision analysis failed'));

    $job = new AnalyzeImageWithAIJob($image);

    $job->handle();

    // Image should remain unanalyzed
    expect($image->fresh())->ai_analysis->toBeNull();
});

test('job skips analysis if image already analyzed', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'ai_analysis' => [
            'description' => 'Previously analyzed',
            'mood' => 'existing',
        ],
    ]);

    $mockService = $this->mock(VisionAnalysisService::class);
    $mockService->shouldNotReceive('analyzeImageWithContext');

    $job = new AnalyzeImageWithAIJob($image);
    $job->handle();

    expect($image->fresh()->ai_analysis['description'])->toBe('Previously analyzed');
});

test('job handles image file not found', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'file_path' => 'nonexistent/image.jpg',
        'ai_analysis' => null,
    ]);

    $mockService = $this->mock(VisionAnalysisService::class);
    $mockService->shouldReceive('analyzeImageWithContext')
        ->once()
        ->with($image)
        ->andThrow(new \Exception('Image file not found'));

    $job = new AnalyzeImageWithAIJob($image);

    $job->handle();
});

test('job can be serialized and unserialized', function (): void {
    $image = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
    ]);

    $job = new AnalyzeImageWithAIJob($image);

    $serialized = serialize($job);
    $unserialized = unserialize($serialized);

    expect($unserialized)->toBeInstanceOf(AnalyzeImageWithAIJob::class)
        ->and($unserialized->image->id)->toBe($image->id);
});
