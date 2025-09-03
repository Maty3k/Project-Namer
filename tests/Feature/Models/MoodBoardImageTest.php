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
        'height' => 400,
        'z_index' => 1,
        'notes' => 'Test notes',
    ]);

    expect($moodBoardImage->mood_board_id)->toBe($moodBoard->id);
    expect($moodBoardImage->project_image_id)->toBe($projectImage->id);
    expect($moodBoardImage->position)->toBe(1);
    expect($moodBoardImage->x_position)->toBe(100);
    expect($moodBoardImage->y_position)->toBe(200);
    expect($moodBoardImage->width)->toBe(300);
    expect($moodBoardImage->height)->toBe(400);
    expect($moodBoardImage->z_index)->toBe(1);
    expect($moodBoardImage->notes)->toBe('Test notes');
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

test('has basic position and size attributes', function (): void {
    $moodBoardImage = MoodBoardImage::factory()->create([
        'position' => 1,
        'x_position' => 100,
        'y_position' => 150,
        'width' => 300,
        'height' => 200,
        'z_index' => 2,
    ]);

    expect($moodBoardImage->position)->toBe(1);
    expect($moodBoardImage->x_position)->toBe(100);
    expect($moodBoardImage->y_position)->toBe(150);
    expect($moodBoardImage->width)->toBe(300);
    expect($moodBoardImage->height)->toBe(200);
    expect($moodBoardImage->z_index)->toBe(2);
});

test('can have notes attached', function (): void {
    $moodBoardImage = MoodBoardImage::factory()->create([
        'notes' => 'This is a test note',
    ]);

    expect($moodBoardImage->notes)->toBe('This is a test note');
});

test('unique constraint prevents duplicate image in same mood board', function (): void {
    $moodBoard = MoodBoard::factory()->create();
    $projectImage = ProjectImage::factory()->create();

    MoodBoardImage::factory()->create([
        'mood_board_id' => $moodBoard->id,
        'project_image_id' => $projectImage->id,
    ]);

    expect(function () use ($moodBoard, $projectImage): void {
        MoodBoardImage::factory()->create([
            'mood_board_id' => $moodBoard->id,
            'project_image_id' => $projectImage->id,
        ]);
    })->toThrow(Exception::class);
});
