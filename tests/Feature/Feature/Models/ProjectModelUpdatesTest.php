<?php

declare(strict_types=1);

use App\Models\MoodBoard;
use App\Models\Project;
use App\Models\ProjectImage;

test('project has many project images relationship works', function (): void {
    $project = Project::factory()->create();
    $projectImage = ProjectImage::factory()->create(['project_id' => $project->id]);

    expect($project->projectImages)->toHaveCount(1);
    expect($project->projectImages->first())->toBeInstanceOf(ProjectImage::class);
    expect($project->projectImages->first()->id)->toBe($projectImage->id);
});

test('project has many mood boards relationship works', function (): void {
    $project = Project::factory()->create();
    $moodBoard = MoodBoard::factory()->create(['project_id' => $project->id]);

    expect($project->moodBoards)->toHaveCount(1);
    expect($project->moodBoards->first())->toBeInstanceOf(MoodBoard::class);
    expect($project->moodBoards->first()->id)->toBe($moodBoard->id);
});

test('project belongs to default mood board relationship works', function (): void {
    $project = Project::factory()->create();
    $moodBoard = MoodBoard::factory()->create(['project_id' => $project->id]);

    $project->update(['default_mood_board_id' => $moodBoard->id]);

    expect($project->defaultMoodBoard)->toBeInstanceOf(MoodBoard::class);
    expect($project->defaultMoodBoard->id)->toBe($moodBoard->id);
});

test('project can have multiple images and mood boards', function (): void {
    $project = Project::factory()->create();

    $image1 = ProjectImage::factory()->create(['project_id' => $project->id]);
    $image2 = ProjectImage::factory()->create(['project_id' => $project->id]);

    $moodBoard1 = MoodBoard::factory()->create(['project_id' => $project->id]);
    $moodBoard2 = MoodBoard::factory()->create(['project_id' => $project->id]);

    expect($project->projectImages)->toHaveCount(2);
    expect($project->moodBoards)->toHaveCount(2);

    expect($project->projectImages->pluck('id'))->toContain($image1->id, $image2->id);
    expect($project->moodBoards->pluck('id'))->toContain($moodBoard1->id, $moodBoard2->id);
});
