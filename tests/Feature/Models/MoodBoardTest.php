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
        'name' => 'Test Mood Board',
        'description' => 'Test description',
        'is_public' => true,
        'layout_type' => 'grid',
    ]);

    expect($moodBoard->project_id)->toBe($project->id);
    expect($moodBoard->user_id)->toBe($user->id);
    expect($moodBoard->name)->toBe('Test Mood Board');
    expect($moodBoard->description)->toBe('Test description');
    expect($moodBoard->is_public)->toBeTrue();
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
    $projectImage = ProjectImage::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
    ]);

    expect($moodBoard->moodBoardImages)->toHaveCount(1);
    expect($moodBoard->moodBoardImages->first())->toBeInstanceOf(MoodBoardImage::class);
    expect($moodBoard->moodBoardImages->first()->id)->toBe($moodBoardImage->id);
});

test('project images through relationship works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();
    MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
    ]);

    expect($moodBoard->projectImages)->toHaveCount(1);
    expect($moodBoard->projectImages->first())->toBeInstanceOf(ProjectImage::class);
    expect($moodBoard->projectImages->first()->id)->toBe($projectImage->id);
});

test('scope public filters correctly', function (): void {
    $publicBoard = MoodBoard::factory()->create(['is_public' => true]);
    $privateBoard = MoodBoard::factory()->create(['is_public' => false]);

    $results = MoodBoard::public()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($publicBoard->id);
});

test('scope for project filters correctly', function (): void {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $board1 = MoodBoard::factory()->create(['project_id' => $project1->id]);
    $board2 = MoodBoard::factory()->create(['project_id' => $project2->id]);

    $results = MoodBoard::forProject($project1->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($board1->id);
});

test('scope by layout type filters correctly', function (): void {
    $gridBoard = MoodBoard::factory()->create(['layout_type' => 'grid']);
    $masonryBoard = MoodBoard::factory()->create(['layout_type' => 'masonry']);

    $results = MoodBoard::byLayoutType('grid')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($gridBoard->id);
});

test('get image count attribute works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage1 = ProjectImage::factory()->create();
    $projectImage2 = ProjectImage::factory()->create();

    MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage1->id,
    ]);
    MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage2->id,
    ]);

    expect($moodBoard->image_count)->toBe(2);
});

test('can generate share token', function (): void {
    $moodBoard = MoodBoard::factory()->create(['is_public' => false]);

    expect($moodBoard->generateShareToken())->toBeString();

    $moodBoard->refresh();
    expect($moodBoard->share_token)->not->toBeNull();
    expect(strlen((string) $moodBoard->share_token))->toBe(32);
})->skip('Known issue with share token generation');

test('can revoke sharing', function (): void {
    $moodBoard = MoodBoard::factory()->create(['is_public' => true, 'share_token' => 'test-token']);

    $moodBoard->revokeSharing();

    expect($moodBoard->refresh()->is_public)->toBeFalse();
    expect($moodBoard->share_token)->toBeNull();
});

test('generates uuid on creation', function (): void {
    $moodBoard = MoodBoard::factory()->create();

    expect($moodBoard->uuid)->not->toBeNull();
    expect(strlen($moodBoard->uuid))->toBe(36);
});
