<?php

declare(strict_types=1);

use App\Models\MoodBoard;
use App\Models\MoodBoardImage;
use App\Models\ProjectImage;

test('can create mood board image with required attributes', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();

    $moodBoardImage = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
        'position' => 1,
        'x_position' => 100,
        'y_position' => 200,
        'width' => 300,
        'height' => 250,
        'z_index' => 5,
    ]);

    expect($moodBoardImage->mood_board_id)->toBe($moodBoard->id);
    expect($moodBoardImage->project_image_id)->toBe($projectImage->id);
    expect($moodBoardImage->position)->toBe(1);
    expect($moodBoardImage->x_position)->toBe(100);
    expect($moodBoardImage->y_position)->toBe(200);
    expect($moodBoardImage->width)->toBe(300);
    expect($moodBoardImage->height)->toBe(250);
    expect($moodBoardImage->z_index)->toBe(5);
});

test('belongs to mood board relationship works', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create(['mood_board_id' => $moodBoard->id]);

    expect($moodBoardImage->moodBoard)->toBeInstanceOf(MoodBoard::class);
    expect($moodBoardImage->moodBoard->id)->toBe($moodBoard->id);
});

test('belongs to project image relationship works', function (): void {
    $projectImage = ProjectImage::factory()->create();
    $moodBoardImage = MoodBoardImage::factory()->create(['project_image_id' => $projectImage->id]);

    expect($moodBoardImage->projectImage)->toBeInstanceOf(ProjectImage::class);
    expect($moodBoardImage->projectImage->id)->toBe($projectImage->id);
});

test('scope by mood board filters correctly', function (): void {
    $moodBoard1 = MoodBoard::factory()->create();
    $moodBoard2 = MoodBoard::factory()->create();
    $image1 = MoodBoardImage::factory()->create(['mood_board_id' => $moodBoard1->id]);
    $image2 = MoodBoardImage::factory()->create(['mood_board_id' => $moodBoard2->id]);

    $results = MoodBoardImage::byMoodBoard($moodBoard1->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($image1->id);
});

test('scope ordered by position works correctly', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $image1 = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'position' => 3,
    ]);
    $image2 = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'position' => 1,
    ]);
    $image3 = MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'position' => 2,
    ]);

    $results = MoodBoardImage::orderedByPosition()->get();

    expect($results->pluck('position')->toArray())->toBe([1, 2, 3]);
});

test('scope within bounds filters correctly', function (): void {
    $image1 = MoodBoardImage::factory()->create([
        'x_position' => 100,
        'y_position' => 100,
    ]);
    $image2 = MoodBoardImage::factory()->create([
        'x_position' => 500,
        'y_position' => 500,
    ]);

    $results = MoodBoardImage::withinBounds(50, 50, 200, 200)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($image1->id);
});

test('update position method works correctly', function (): void {
    $moodBoardImage = MoodBoardImage::factory()->create([
        'position' => 1,
        'x_position' => 100,
        'y_position' => 100,
    ]);

    $moodBoardImage->updatePosition(2, 200, 300);

    expect($moodBoardImage->refresh()->position)->toBe(2);
    expect($moodBoardImage->x_position)->toBe(200);
    expect($moodBoardImage->y_position)->toBe(300);
});

test('update position method with nulls works correctly', function (): void {
    $moodBoardImage = MoodBoardImage::factory()->create([
        'position' => 1,
        'x_position' => 100,
        'y_position' => 100,
    ]);

    $moodBoardImage->updatePosition(2);

    expect($moodBoardImage->refresh()->position)->toBe(2);
    expect($moodBoardImage->x_position)->toBeNull();
    expect($moodBoardImage->y_position)->toBeNull();
});

test('update dimensions method works correctly', function (): void {
    $moodBoardImage = MoodBoardImage::factory()->create([
        'width' => 100,
        'height' => 100,
    ]);

    $moodBoardImage->updateDimensions(300, 250);

    expect($moodBoardImage->refresh()->width)->toBe(300);
    expect($moodBoardImage->refresh()->height)->toBe(250);
});
