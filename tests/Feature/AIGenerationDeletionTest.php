<?php

declare(strict_types=1);

use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('AI generation can be deleted with proper cleanup', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'generation_session_id' => 'test-session-123',
        'status' => 'completed',
    ]);

    // Create associated name suggestions
    NameSuggestion::factory()->count(3)->create([
        'project_id' => $project->id,
        'ai_generation_session_id' => 'test-session-123',
        'ai_model_used' => 'gpt-4',
    ]);

    // Verify initial state
    expect(AIGeneration::count())->toBe(1);
    expect(NameSuggestion::where('ai_generation_session_id', 'test-session-123')->count())->toBe(3);

    // Delete the AI generation
    $result = $aiGeneration->deleteWithCleanup();

    expect($result)->toBeTrue();
    expect(AIGeneration::count())->toBe(0);
    expect(NameSuggestion::where('ai_generation_session_id', 'test-session-123')->count())->toBe(0);
});

test('only authorized users can delete AI generations', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    $aiGeneration = AIGeneration::factory()->completed()->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);

    // Owner can delete
    expect($aiGeneration->canBeDeletedBy($owner))->toBeTrue();

    // Other user cannot delete
    expect($aiGeneration->canBeDeletedBy($otherUser))->toBeFalse();
});

test('in-progress AI generations cannot be deleted', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    expect($aiGeneration->canBeDeleted())->toBeFalse();

    $result = $aiGeneration->deleteWithCleanup();
    expect($result)->toBeFalse();
    expect(AIGeneration::count())->toBe(1);
});

test('bulk delete removes multiple AI generations', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $generations = AIGeneration::factory()->count(3)->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    // Create associated name suggestions for each generation
    foreach ($generations as $generation) {
        NameSuggestion::factory()->count(2)->create([
            'project_id' => $project->id,
            'ai_generation_session_id' => $generation->generation_session_id,
            'ai_model_used' => 'gpt-4',
        ]);
    }

    $generationIds = $generations->pluck('id')->toArray();

    // Verify initial state
    expect(AIGeneration::count())->toBe(3);
    expect(NameSuggestion::count())->toBe(6);

    // Bulk delete
    $result = AIGeneration::bulkDeleteWithCleanup($generationIds, $user);

    expect($result)->toBe(3); // Number of deleted generations
    expect(AIGeneration::count())->toBe(0);
    expect(NameSuggestion::count())->toBe(0);
});

test('bulk delete only removes authorized generations', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    $ownedGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'status' => 'completed',
    ]);

    $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);
    $othersGeneration = AIGeneration::factory()->create([
        'project_id' => $otherProject->id,
        'user_id' => $otherUser->id,
        'status' => 'completed',
    ]);

    $generationIds = [$ownedGeneration->id, $othersGeneration->id];

    // Try to bulk delete both (should only delete the owned one)
    $result = AIGeneration::bulkDeleteWithCleanup($generationIds, $owner);

    expect($result)->toBe(1); // Only 1 deleted (the owned one)
    expect(AIGeneration::count())->toBe(1); // Other user's generation remains
    expect(AIGeneration::first()->id)->toBe($othersGeneration->id);
});

test('deletion clears cache entries', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'generation_session_id' => 'test-session-456',
        'status' => 'completed',
    ]);

    // Set some cache entries that should be cleared
    $cacheKeys = [
        "ai_generation_{$aiGeneration->id}",
        "ai_generation_results_{$aiGeneration->generation_session_id}",
        "project_generations_{$project->id}",
    ];

    foreach ($cacheKeys as $key) {
        cache()->put($key, 'test-data', 60);
        expect(cache()->has($key))->toBeTrue();
    }

    // Delete the generation
    $aiGeneration->deleteWithCleanup();

    // Verify cache is cleared
    foreach ($cacheKeys as $key) {
        expect(cache()->has($key))->toBeFalse();
    }
});
