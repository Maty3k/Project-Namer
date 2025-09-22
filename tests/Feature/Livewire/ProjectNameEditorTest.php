<?php

declare(strict_types=1);

use App\Livewire\ProjectNameEditor;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('can edit project name', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Original Name']);

    $this->actingAs($user);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->assertSet('editing', false)
        ->assertSet('name', 'Original Name')
        ->call('startEdit')
        ->assertSet('editing', true)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertSet('editing', false)
        ->assertHasNoErrors();

    expect($project->fresh()->name)->toBe('Updated Name');
});

test('can cancel editing', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Original Name']);

    $this->actingAs($user);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->call('startEdit')
        ->set('name', 'Changed Name')
        ->call('cancel')
        ->assertSet('editing', false)
        ->assertSet('name', 'Original Name');

    expect($project->fresh()->name)->toBe('Original Name');
});

test('validates required name', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->call('startEdit')
        ->set('name', '')
        ->call('save')
        ->assertSet('editing', true)
        ->assertHasErrors(['name' => 'required']);
});

test('validates minimum name length', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->call('startEdit')
        ->set('name', 'x')
        ->call('save')
        ->assertSet('editing', true)
        ->assertHasErrors(['name' => 'min']);
});

test('validates maximum name length', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $longName = str_repeat('a', 256);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->call('startEdit')
        ->set('name', $longName)
        ->call('save')
        ->assertSet('editing', true)
        ->assertHasErrors(['name' => 'max']);
});

test('dispatches events on successful save', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectNameEditor::class, ['project' => $project])
        ->call('startEdit')
        ->set('name', 'New Name')
        ->call('save')
        ->assertDispatched('project-updated')
        ->assertDispatched('show-toast');
});
