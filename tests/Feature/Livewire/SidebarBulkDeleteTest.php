<?php

declare(strict_types=1);

use App\Livewire\Sidebar;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can toggle bulk delete mode', function (): void {
    Livewire::test(Sidebar::class)
        ->assertSet('bulkDeleteMode', false)
        ->call('toggleBulkDeleteMode')
        ->assertSet('bulkDeleteMode', true)
        ->call('toggleBulkDeleteMode')
        ->assertSet('bulkDeleteMode', false);
});

test('clears selected projects when exiting bulk delete mode', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleBulkDeleteMode')
        ->call('toggleProjectSelection', $projects[0]->uuid)
        ->call('toggleProjectSelection', $projects[1]->uuid)
        ->assertSet('selectedProjects', [$projects[0]->uuid, $projects[1]->uuid])
        ->call('toggleBulkDeleteMode')
        ->assertSet('selectedProjects', []);
});

test('can toggle project selection', function (): void {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleProjectSelection', $project->uuid)
        ->assertSet('selectedProjects', [$project->uuid])
        ->call('toggleProjectSelection', $project->uuid)
        ->assertSet('selectedProjects', []);
});

test('can select all projects', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('selectAllProjects')
        ->assertSet('selectedProjects', $projects->pluck('uuid')->toArray());
});

test('can deselect all projects', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('selectAllProjects')
        ->assertSet('selectedProjects', $projects->pluck('uuid')->toArray())
        ->call('deselectAllProjects')
        ->assertSet('selectedProjects', []);
});

test('shows confirmation modal when bulk deleting', function (): void {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleProjectSelection', $project->uuid)
        ->assertSet('showBulkDeleteConfirmation', false)
        ->call('confirmBulkDelete')
        ->assertSet('showBulkDeleteConfirmation', true);
});

test('shows error if trying to bulk delete with no projects selected', function (): void {
    Livewire::test(Sidebar::class)
        ->call('confirmBulkDelete')
        ->assertDispatched('toast', message: 'No projects selected', type: 'error');
});

test('can cancel bulk delete', function (): void {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleProjectSelection', $project->uuid)
        ->call('confirmBulkDelete')
        ->assertSet('showBulkDeleteConfirmation', true)
        ->call('cancelBulkDelete')
        ->assertSet('showBulkDeleteConfirmation', false);
});

test('can bulk delete projects', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('selectAllProjects')
        ->call('bulkDeleteProjects')
        ->assertDispatched('toast', message: '3 projects deleted successfully', type: 'success')
        ->assertDispatched('project-deleted')
        ->assertSet('selectedProjects', [])
        ->assertSet('bulkDeleteMode', false)
        ->assertSet('showBulkDeleteConfirmation', false);

    expect(Project::count())->toBe(0);
});

test('bulk delete shows singular message for one project', function (): void {
    $project = Project::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleProjectSelection', $project->uuid)
        ->call('bulkDeleteProjects')
        ->assertDispatched('toast', message: '1 project deleted successfully', type: 'success');

    expect(Project::count())->toBe(0);
});

test('bulk delete only deletes user\'s own projects', function (): void {
    $myProjects = Project::factory()->count(2)->create(['user_id' => $this->user->id]);
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleProjectSelection', $myProjects[0]->uuid)
        ->call('toggleProjectSelection', $otherProject->uuid) // Attempt to select other user's project
        ->call('bulkDeleteProjects');

    // Only my projects should be deleted
    expect(Project::where('user_id', $this->user->id)->count())->toBe(1);
    expect(Project::where('user_id', $otherUser->id)->count())->toBe(1);
});

test('redirects to dashboard if active project is deleted in bulk', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class, ['activeProjectUuid' => $projects[0]->uuid])
        ->call('selectAllProjects')
        ->call('bulkDeleteProjects')
        ->assertRedirect(route('dashboard'));
});

test('does not redirect if active project is not deleted', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class, ['activeProjectUuid' => $projects[0]->uuid])
        ->call('toggleProjectSelection', $projects[1]->uuid)
        ->call('toggleProjectSelection', $projects[2]->uuid)
        ->call('bulkDeleteProjects')
        ->assertNoRedirect();

    expect(Project::count())->toBe(1);
    expect(Project::first()->uuid)->toBe($projects[0]->uuid);
});

test('bulk delete mode displays checkboxes in UI', function (): void {
    $projects = Project::factory()->count(2)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->assertSee('Bulk Delete')
        ->assertDontSee('Select All')
        ->call('toggleBulkDeleteMode')
        ->assertSee('Select All')
        ->assertSee('Deselect All')
        ->assertSee('Cancel');
});

test('bulk delete button shows count of selected projects', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::test(Sidebar::class)
        ->call('toggleBulkDeleteMode')
        ->call('toggleProjectSelection', $projects[0]->uuid)
        ->call('toggleProjectSelection', $projects[1]->uuid)
        ->assertSee('Delete (2)');
});

test('selected projects show arrow indicators in bulk delete mode', function (): void {
    $projects = Project::factory()->count(3)->create(['user_id' => $this->user->id]);

    $component = Livewire::test(Sidebar::class)
        ->call('toggleBulkDeleteMode')
        ->assertSet('bulkDeleteMode', true);

    // Select first project
    $component->call('toggleProjectSelection', $projects[0]->uuid)
        ->assertSet('selectedProjects', [$projects[0]->uuid]);

    // Verify the component renders with selected project
    expect($component->get('selectedProjects'))->toContain($projects[0]->uuid);

    // Select all projects
    $component->call('selectAllProjects')
        ->assertSet('selectedProjects', $projects->pluck('uuid')->toArray());

    // Deselect all projects
    $component->call('deselectAllProjects')
        ->assertSet('selectedProjects', []);
});
