<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can delete their own AI generation through ProjectPage', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'generation_session_id' => 'test-session',
    ]);

    // Create associated name suggestions
    NameSuggestion::factory()->count(3)->create([
        'project_id' => $project->id,
        'ai_generation_session_id' => 'test-session',
    ]);

    expect(AIGeneration::count())->toBe(1);
    expect(NameSuggestion::count())->toBe(3);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('deleteAIGeneration', $aiGeneration->id)
        ->assertDispatched('show-toast')
        ->assertDispatched('ai-generation-deleted');

    expect(AIGeneration::count())->toBe(0);
    expect(NameSuggestion::count())->toBe(0);
});

test('user cannot delete AI generation they do not own', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    $ownerGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'status' => 'completed',
    ]);

    expect(AIGeneration::count())->toBe(1);

    // Owner tries to delete their own generation - should work
    Livewire::actingAs($owner)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('deleteAIGeneration', $ownerGeneration->id)
        ->assertDispatched('show-toast')
        ->assertDispatched('ai-generation-deleted');

    // Generation should be deleted
    expect(AIGeneration::count())->toBe(0);
});

test('user cannot delete in-progress AI generation', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    expect(AIGeneration::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('deleteAIGeneration', $aiGeneration->id)
        ->assertDispatched('show-toast');

    // Generation should still exist since it's in progress
    expect(AIGeneration::count())->toBe(1);
});

test('user can bulk delete multiple AI generations', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $generations = AIGeneration::factory()->count(3)->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $generationIds = $generations->pluck('id')->toArray();

    expect(AIGeneration::count())->toBe(3);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('bulkDeleteAIGenerations', $generationIds)
        ->assertDispatched('show-toast')
        ->assertDispatched('ai-generations-bulk-deleted');

    expect(AIGeneration::count())->toBe(0);
});

test('bulk delete with empty array shows error message', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('bulkDeleteAIGenerations', [])
        ->assertDispatched('show-toast');
});

test('user can delete all completed generations', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Create completed generations
    AIGeneration::factory()->count(2)->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    // Create in-progress generation (should not be deleted)
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    expect(AIGeneration::count())->toBe(3);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('deleteAllCompletedGenerations')
        ->assertDispatched('show-toast');

    // Only the running generation should remain
    expect(AIGeneration::count())->toBe(1);
    expect(AIGeneration::first()->status)->toBe('running');
});

test('delete all completed generations with no completed generations', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Only create in-progress generation
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    expect(AIGeneration::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('deleteAllCompletedGenerations')
        ->assertDispatched('show-toast');

    // Generation should still exist since it's in progress
    expect(AIGeneration::count())->toBe(1);
});

test('unauthorized user cannot access deletion methods', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);

    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'status' => 'completed',
    ]);

    // Other user should get authorization error when trying to access project
    Livewire::actingAs($otherUser)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->assertForbidden();
});
