<?php

declare(strict_types=1);

use App\Livewire\Sidebar;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('sidebar renders successfully for authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->assertStatus(200)
        ->assertSee('New Project');
});

test('sidebar displays user projects in chronological order', function (): void {
    $user = User::factory()->create();

    // Create projects with different timestamps
    $project1 = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'First Project',
        'created_at' => now()->subDays(2),
    ]);

    $project2 = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Second Project',
        'created_at' => now()->subDay(),
    ]);

    $project3 = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Latest Project',
        'created_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    $projects = $component->get('projects');

    // Should be in reverse chronological order (newest first)
    expect($projects->pluck('name')->toArray())->toBe([
        'Latest Project',
        'Second Project',
        'First Project',
    ]);
});

test('sidebar only shows projects for authenticated user', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1Project = Project::factory()->create([
        'user_id' => $user1->id,
        'name' => 'User 1 Project',
    ]);

    $user2Project = Project::factory()->create([
        'user_id' => $user2->id,
        'name' => 'User 2 Project',
    ]);

    $this->actingAs($user1);

    Livewire::test(Sidebar::class)
        ->assertSee('User 1 Project')
        ->assertDontSee('User 2 Project');
});

test('sidebar highlights active project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Sidebar::class, ['activeProjectUuid' => $project->uuid])
        ->assertSet('activeProjectUuid', $project->uuid)
        ->assertSeeHtml('bg-blue-50'); // Active project highlight class
});

test('new project button navigates to dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->call('createNewProject')
        ->assertRedirect('/dashboard');
});

test('clicking project navigates to project page', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->call('selectProject', $project->uuid)
        ->assertRedirect("/project/{$project->uuid}");
});

test('sidebar updates when project is created', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    // Initially no projects
    expect($component->get('projects'))->toHaveCount(0);

    // Create a project
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Dispatch project created event
    $component->dispatch('project-created', $project->uuid);

    // Should refresh and show the new project
    expect($component->get('projects'))->toHaveCount(1);
});

test('sidebar updates when project name is changed', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    // Update project name
    $project->update(['name' => 'Updated Name']);

    // Dispatch project updated event
    $component->dispatch('project-updated', $project->uuid);

    $component->assertSee('Updated Name');
});

test('sidebar shows empty state when no projects', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->assertSee('No projects yet')
        ->assertSee('Create your first project');
});

test('sidebar shows project count', function (): void {
    $user = User::factory()->create();

    Project::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->assertSee('3 projects');
});

test('sidebar handles project deletion', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    // Initially has the project
    expect($component->get('projects'))->toHaveCount(1);

    // Delete project
    $project->delete();

    // Dispatch project deleted event
    $component->dispatch('project-deleted', $project->uuid);

    // Should refresh and project should be gone
    expect($component->get('projects'))->toHaveCount(0);
});

test('sidebar is responsive and collapsible', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->assertSet('collapsed', false)
        ->call('toggleCollapse')
        ->assertSet('collapsed', true)
        ->call('toggleCollapse')
        ->assertSet('collapsed', false);
});

test('sidebar hides project names when collapsed and shows icons only', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Project Name',
        'description' => 'Test description',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    // When expanded, should see project name and description in content
    $component->assertSee('Test Project Name')
        ->assertSee('Test description');

    // When collapsed, should NOT see project name or description in visible content
    $component->call('toggleCollapse')
        ->assertSet('collapsed', true);

    // Check that the expanded view content is not present
    $html = $component->html();
    expect($html)->not->toContain('<h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">');

    // Should have tooltip with project name when collapsed (in title attribute)
    $component->assertSeeHtml('title="Test Project Name"');

    // Should see project icon when collapsed
    $component->assertSeeHtml('<svg class="w-5 h-5');
});

test('sidebar responsive behavior works across different states', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Responsive Test Project',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);

    // Initial state - expanded (64 width)
    $component->assertSet('collapsed', false)
        ->assertSeeHtml('class="w-64 transition-all duration-300');

    // Collapse - should switch to 16 width
    $component->call('toggleCollapse')
        ->assertSet('collapsed', true)
        ->assertSeeHtml('class="w-16 transition-all duration-300');

    // Project items should have different padding when collapsed
    $component->assertSeeHtml('class="cursor-pointer rounded-lg transition-all duration-200 
                               p-2');

    // Expand again - should go back to full width
    $component->call('toggleCollapse')
        ->assertSet('collapsed', false)
        ->assertSeeHtml('class="w-64 transition-all duration-300');
});

test('sidebar shows selected project name in project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Selected Project',
    ]);

    $this->actingAs($user);

    Livewire::test(Sidebar::class, ['activeProjectUuid' => $project->uuid])
        ->assertSee('My Selected Project')
        ->assertSet('selectedProject.name', 'My Selected Project');
});

test('sidebar handles long project names gracefully', function (): void {
    $user = User::factory()->create();
    $longName = 'This is a very long project name that should be truncated or handled gracefully in the sidebar';

    Project::factory()->create([
        'user_id' => $user->id,
        'name' => $longName,
    ]);

    $this->actingAs($user);

    Livewire::test(Sidebar::class)
        ->assertSee(substr($longName, 0, 22)); // Should see first 22 chars as set in truncateName
});

test('sidebar shows recent projects first', function (): void {
    $user = User::factory()->create();

    // Create projects with different updated_at timestamps
    $oldProject = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Project',
        'updated_at' => now()->subDays(5),
    ]);

    $recentProject = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Recent Project',
        'updated_at' => now()->subHour(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Sidebar::class);
    $projects = $component->get('projects');

    // Recent project should be first
    expect($projects->first()->name)->toBe('Recent Project');
});
