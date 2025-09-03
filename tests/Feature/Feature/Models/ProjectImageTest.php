<?php

declare(strict_types=1);

use App\Models\ImageGenerationContext;
use App\Models\MoodBoardImage;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;

test('can create project image with required attributes', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $projectImage = ProjectImage::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'original_filename' => 'test-image.jpg',
        'stored_filename' => 'test-image.jpg',
        'file_path' => 'images/test-image.jpg',
    ]);

    expect($projectImage->project_id)->toBe($project->id);
    expect($projectImage->user_id)->toBe($user->id);
    expect($projectImage->original_filename)->toBe('test-image.jpg');
    expect($projectImage->stored_filename)->toBe('test-image.jpg');
    expect($projectImage->file_path)->toBe('images/test-image.jpg');
    expect($projectImage->uuid)->not->toBeNull();
});

test('belongs to project relationship works', function (): void {
    $project = Project::factory()->create();
    $projectImage = ProjectImage::factory()->create(['project_id' => $project->id]);

    expect($projectImage->project)->toBeInstanceOf(Project::class);
    expect($projectImage->project->id)->toBe($project->id);
});

test('belongs to user relationship works', function (): void {
    $user = User::factory()->create();
    $projectImage = ProjectImage::factory()->create(['user_id' => $user->id]);

    expect($projectImage->user)->toBeInstanceOf(User::class);
    expect($projectImage->user->id)->toBe($user->id);
});

test('has many mood board images relationship works', function (): void {
    $projectImage = ProjectImage::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create(['project_image_id' => $projectImage->id]);

    expect($projectImage->moodBoardImages)->toHaveCount(1);
    expect($projectImage->moodBoardImages->first())->toBeInstanceOf(MoodBoardImage::class);
});

test('has many image generation context relationship works', function (): void {
    $projectImage = ProjectImage::factory()->create();
    $context = ImageGenerationContext::factory()->create(['project_image_id' => $projectImage->id]);

    expect($projectImage->generationContexts()->count())->toBe(1);
    expect($projectImage->generationContexts()->first())->toBeInstanceOf(ImageGenerationContext::class);
});

test('scope for project filters correctly', function (): void {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $image1 = ProjectImage::factory()->create(['project_id' => $project1->id]);
    $image2 = ProjectImage::factory()->create(['project_id' => $project2->id]);

    $results = ProjectImage::forProject($project1->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($image1->id);
});

test('scope with tags filters correctly', function (): void {
    $image1 = ProjectImage::factory()->create(['tags' => ['logo', 'brand']]);
    $image2 = ProjectImage::factory()->create(['tags' => ['website', 'header']]);

    $results = ProjectImage::withTags(['logo'])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($image1->id);
});

test('scope processed filters correctly', function (): void {
    $processedImage = ProjectImage::factory()->create(['processing_status' => 'completed']);
    $unprocessedImage = ProjectImage::factory()->create(['processing_status' => 'pending']);

    $results = ProjectImage::processed()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($processedImage->id);
});

test('scope recent returns most recent images', function (): void {
    $oldImage = ProjectImage::factory()->create(['created_at' => now()->subDays(10)]);
    $recentImage = ProjectImage::factory()->create(['created_at' => now()->subHours(1)]);

    $results = ProjectImage::recent()->get();

    expect($results->first()->id)->toBe($recentImage->id);
});

test('get file size formatted returns correct format', function (): void {
    $image = ProjectImage::factory()->create(['file_size' => 1024000]); // 1MB

    expect($image->getFileSizeFormatted())->toBe('1000 KB');
});

test('get file size formatted handles bytes', function (): void {
    $image = ProjectImage::factory()->create(['file_size' => 512]);

    expect($image->getFileSizeFormatted())->toBe('512 B');
});

test('has tag method works correctly', function (): void {
    $image = ProjectImage::factory()->create(['tags' => ['logo', 'brand', 'icon']]);

    expect($image->hasTag('logo'))->toBeTrue();
    expect($image->hasTag('missing'))->toBeFalse();
});

test('update processing status works', function (): void {
    $image = ProjectImage::factory()->create(['processing_status' => 'pending']);

    $image->updateProcessingStatus('completed');

    expect($image->refresh()->processing_status)->toBe('completed');
});

test('generates uuid on creation', function (): void {
    $image = ProjectImage::factory()->create(['uuid' => null]);

    expect($image->uuid)->not->toBeNull();
    expect(strlen($image->uuid))->toBe(36);
});
