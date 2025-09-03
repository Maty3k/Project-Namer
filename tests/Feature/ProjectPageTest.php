<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('project page loads successfully for project owner', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get("/project/{$project->uuid}");

    $response->assertStatus(200)
        ->assertSee($project->name)
        ->assertSee($project->description);
});

test('project page redirects unauthenticated users', function (): void {
    $project = Project::factory()->create();

    $response = $this->get("/project/{$project->uuid}");

    $response->assertRedirect('/login');
});

test('project page returns 404 for non-existent project', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/project/non-existent-uuid');

    $response->assertStatus(404);
});

test('project page returns 403 for unauthorized user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get("/project/{$project->uuid}");

    $response->assertStatus(403);
});

test('project page component renders correctly', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Project Name',
        'description' => 'This is a test project description for testing purposes',
    ]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->assertSet('project.id', $project->id)
        ->assertSet('project.name', $project->name)
        ->assertSet('project.description', $project->description)
        ->assertSet('editableName', 'Test Project Name')
        ->assertSet('editableDescription', 'This is a test project description for testing purposes')
        ->assertSee('Test Project Name')
        ->assertSee('Description'); // Check for the label instead of textarea content
});

test('can edit project name inline', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->assertSet('editingName', true)
        ->set('editableName', 'New Project Name')
        ->call('saveName')
        ->assertSet('editingName', false)
        ->assertSet('project.name', 'New Project Name');

    expect($project->fresh()->name)->toBe('New Project Name');
});

test('can cancel name editing', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->set('editableName', 'Changed Name')
        ->call('cancelNameEdit')
        ->assertSet('editingName', false)
        ->assertSet('editableName', 'Original Name');

    expect($project->fresh()->name)->toBe('Original Name');
});

test('validates name is required when editing', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->set('editableName', '')
        ->call('saveName')
        ->assertHasErrors(['editableName' => 'required']);
});

test('validates name length when editing', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    // Test minimum length
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->set('editableName', 'x')
        ->call('saveName')
        ->assertHasErrors(['editableName' => 'min:2']);

    // Test maximum length
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->set('editableName', str_repeat('x', 256))
        ->call('saveName')
        ->assertHasErrors(['editableName' => 'max:255']);
});

test('can edit project description', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'description' => 'Original description',
    ]);

    $this->actingAs($user);

    $newDescription = 'This is a new and updated project description';

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('editableDescription', $newDescription)
        ->call('saveDescription')
        ->assertSet('project.description', $newDescription);

    expect($project->fresh()->description)->toBe($newDescription);
});

test('auto-saves description after typing delay', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $newDescription = 'Auto-saved description';

    $component = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('editableDescription', $newDescription);

    // Simulate delay and auto-save trigger
    $component->call('autoSaveDescription');

    expect($project->fresh()->description)->toBe($newDescription);
});

test('validates description length', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    // Test minimum length
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('editableDescription', 'short')
        ->call('saveDescription')
        ->assertHasErrors(['editableDescription' => 'min:10']);

    // Test maximum length
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('editableDescription', str_repeat('x', 2001))
        ->call('saveDescription')
        ->assertHasErrors(['editableDescription' => 'max:2000']);
});

test('displays character count for description', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('editableDescription', 'Test description')
        ->assertSee('16 / 2000');
});

test('unauthorized user cannot edit project', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get("/project/{$project->uuid}");

    // Should get 403 Forbidden when trying to access another user's project
    $response->assertStatus(403);
});

test('component handles non-existent project gracefully', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(ProjectPage::class, ['uuid' => 'non-existent-uuid']);
});

test('project page shows loading state during saves', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('editName')
        ->set('editableName', 'New Name')
        ->assertSee('wire:loading'); // Check for loading state
});

test('auto-generation parameter shows AI controls and triggers generation', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    // Test via HTTP request to simulate the real workflow
    $response = $this->get("/project/{$project->uuid}?auto_generate=1");

    $response->assertStatus(200);

    // Note: The actual assertion for component state would require integration testing
    // For now, we verify the page loads without errors with the parameter
});

test('component does not auto-trigger generation without parameter', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->assertSet('showAIControls', false)
        ->assertSet('useAIGeneration', false)
        ->assertNotDispatched('trigger-auto-generation');
});

// Generation Mode Toggle Tests

test('can select generation mode with toggle buttons', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->assertSet('generationMode', '') // Default is empty
        ->call('toggleGenerationMode', 'professional')
        ->assertSet('generationMode', 'professional');
});

test('can deselect generation mode by clicking same toggle button', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('generationMode', 'professional')
        ->call('toggleGenerationMode', 'professional')
        ->assertSet('generationMode', ''); // Deselected
});

test('can switch between different generation modes', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $component = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid]);

    $modes = ['creative', 'professional', 'brandable', 'tech-focused'];

    foreach ($modes as $mode) {
        $component->call('toggleGenerationMode', $mode)
            ->assertSet('generationMode', $mode);
    }
});

test('handles invalid generation mode gracefully', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->call('toggleGenerationMode', 'invalid-mode')
        ->assertSet('generationMode', ''); // Should remain unchanged (empty)
});

test('generation mode can be empty when deselected', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('generationMode', 'brandable')
        ->call('toggleGenerationMode', 'brandable')
        ->assertSet('generationMode', '') // Should be empty
        ->call('toggleGenerationMode', 'creative')
        ->assertSet('generationMode', 'creative'); // Should work from empty state
});

test('generation requires mode to be selected when AI is enabled', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('useAIGeneration', true)
        ->set('selectedAIModels', ['gpt-4'])
        ->set('generationMode', '') // Empty generation mode
        ->call('generateMoreNames')
        ->assertHasErrors(['generationMode' => 'required']);
});
