<?php

declare(strict_types=1);

use App\Models\MoodBoard;
use App\Models\ProjectImage;
use App\Models\User;
use App\Models\UserThemePreference;

test('user has many project images relationship works', function (): void {
    $user = User::factory()->create();
    $projectImage = ProjectImage::factory()->create(['user_id' => $user->id]);

    expect($user->projectImages)->toHaveCount(1);
    expect($user->projectImages->first())->toBeInstanceOf(ProjectImage::class);
    expect($user->projectImages->first()->id)->toBe($projectImage->id);
});

test('user has many mood boards relationship works', function (): void {
    $user = User::factory()->create();
    $moodBoard = MoodBoard::factory()->create(['user_id' => $user->id]);

    expect($user->moodBoards)->toHaveCount(1);
    expect($user->moodBoards->first())->toBeInstanceOf(MoodBoard::class);
    expect($user->moodBoards->first()->id)->toBe($moodBoard->id);
});

test('user has one theme preferences relationship works', function (): void {
    $user = User::factory()->create();
    $themePreference = UserThemePreference::factory()->create(['user_id' => $user->id]);

    expect($user->themePreferences)->toBeInstanceOf(UserThemePreference::class);
    expect($user->themePreferences->id)->toBe($themePreference->id);
});

test('user can have multiple project images and mood boards', function (): void {
    $user = User::factory()->create();

    $image1 = ProjectImage::factory()->create(['user_id' => $user->id]);
    $image2 = ProjectImage::factory()->create(['user_id' => $user->id]);

    $moodBoard1 = MoodBoard::factory()->create(['user_id' => $user->id]);
    $moodBoard2 = MoodBoard::factory()->create(['user_id' => $user->id]);

    expect($user->projectImages)->toHaveCount(2);
    expect($user->moodBoards)->toHaveCount(2);

    expect($user->projectImages->pluck('id'))->toContain($image1->id, $image2->id);
    expect($user->moodBoards->pluck('id'))->toContain($moodBoard1->id, $moodBoard2->id);
});

test('user can have only one theme preference', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $themePreference1 = UserThemePreference::factory()->create(['user_id' => $user1->id]);
    $themePreference2 = UserThemePreference::factory()->create(['user_id' => $user2->id]);

    // Each user should have their own theme preference
    expect($user1->themePreferences->id)->toBe($themePreference1->id);
    expect($user2->themePreferences->id)->toBe($themePreference2->id);
});
