<?php

declare(strict_types=1);

use App\Models\MoodBoard;
use App\Models\MoodBoardImage;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;

test('can create mood board with required attributes', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $moodBoard = MoodBoard::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'name' => 'Brand Inspiration',
        'layout_type' => 'grid',
    ]);

    expect($moodBoard->project_id)->toBe($project->id);
    expect($moodBoard->user_id)->toBe($user->id);
    expect($moodBoard->name)->toBe('Brand Inspiration');
    expect($moodBoard->layout_type)->toBe('grid');
    expect($moodBoard->uuid)->not->toBeNull();
});

test('belongs to project relationship works', function (): void {
    $project = Project::factory()->create();
    $moodBoard = MoodBoard::factory()->create(['project_id' => $project->id]);

    expect($moodBoard->project)->toBeInstanceOf(Project::class);
    expect($moodBoard->project->id)->toBe($project->id);
});

test('belongs to user relationship works', function (): void {
    $user = User::factory()->create();
    $moodBoard = MoodBoard::factory()->create(['user_id' => $user->id]);

    expect($moodBoard->user)->toBeInstanceOf(User::class);
    expect($moodBoard->user->id)->toBe($user->id);
});

test('has many mood board images relationship works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create(['mood_board_id' => $moodBoard->id]);

    expect($moodBoard->moodBoardImages)->toHaveCount(1);
    expect($moodBoard->moodBoardImages->first())->toBeInstanceOf(MoodBoardImage::class);
});

test('has many project images through mood board images works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();
    MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
    ]);

    expect($moodBoard->projectImages)->toHaveCount(1);
    expect($moodBoard->projectImages->first())->toBeInstanceOf(ProjectImage::class);
});

test('scope for project filters correctly', function (): void {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $moodBoard1 = MoodBoard::factory()->create(['project_id' => $project1->id]);
    $moodBoard2 = MoodBoard::factory()->create(['project_id' => $project2->id]);

    $results = MoodBoard::forProject($project1->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($moodBoard1->id);
});

test('scope by layout type filters correctly', function (): void {
    $gridBoard = MoodBoard::factory()->create(['layout_type' => 'grid']);
    $freeformBoard = MoodBoard::factory()->create(['layout_type' => 'freeform']);

    $results = MoodBoard::byLayoutType('grid')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($gridBoard->id);
});

test('scope publicly shared filters correctly', function (): void {
    $publicBoard = MoodBoard::factory()->create(['is_public' => true]);
    $privateBoard = MoodBoard::factory()->create(['is_public' => false]);

    $results = MoodBoard::publiclyShared()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($publicBoard->id);
});

test('add image to mood board works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();

    $moodBoard->addImage($projectImage, 1, 100, 200);

    expect($moodBoard->moodBoardImages)->toHaveCount(1);

    $moodBoardImage = $moodBoard->moodBoardImages->first();
    expect($moodBoardImage->position)->toBe(1);
    expect($moodBoardImage->x_position)->toBe(100);
    expect($moodBoardImage->y_position)->toBe(200);
});

test('remove image from mood board works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
    ]);

    $moodBoard->removeImage($projectImage);

    expect($moodBoard->moodBoardImages)->toHaveCount(0);
});

test('reorder images works correctly', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $image1 = ProjectImage::factory()->create();
    $image2 = ProjectImage::factory()->create();

    $moodBoardImage1 = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $image1->id,
        'position' => 1,
    ]);
    $moodBoardImage2 = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $image2->id,
        'position' => 2,
    ]);

    $moodBoard->reorderImages([$image2->id, $image1->id]);

    expect($moodBoardImage1->refresh()->position)->toBe(2);
    expect($moodBoardImage2->refresh()->position)->toBe(1);
});

test('generates uuid on creation', function (): void {
    $moodBoard = MoodBoard::factory()->create(['uuid' => null]);

    expect($moodBoard->uuid)->not->toBeNull();
    expect(strlen($moodBoard->uuid))->toBe(36);
});
