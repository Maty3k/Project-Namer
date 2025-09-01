<?php

declare(strict_types=1);

use App\Livewire\Dashboard;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('dashboard page loads successfully for authenticated user', function (): void {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    $response->assertSee('Describe your project');
});

test('dashboard page redirects unauthenticated users', function (): void {
    $response = $this->get('/dashboard');
    
    $response->assertRedirect('/login');
});

test('dashboard component renders correctly', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    Livewire::test(Dashboard::class)
        ->assertSee('Describe your project')
        ->assertSee('Create New Project');
});

test('project creation with valid description', function (): void {
    $user = User::factory()->create();
    $description = 'This is my amazing new startup idea for revolutionizing the way people order coffee';
    
    $this->actingAs($user);
    
    Livewire::test(Dashboard::class)
        ->set('description', $description)
        ->call('createProject')
        ->assertHasNoErrors()
        ->assertRedirect();
    
    expect(Project::where('user_id', $user->id)->exists())->toBeTrue();
    
    $project = Project::where('user_id', $user->id)->first();
    expect($project->description)->toBe($description);
    expect($project->uuid)->not()->toBeNull();
});

test('validation error with empty description', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    Livewire::test(Dashboard::class)
        ->set('description', '')
        ->call('createProject')
        ->assertHasErrors(['description' => 'required']);
});

test('validation error with description less than 10 characters', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    Livewire::test(Dashboard::class)
        ->set('description', 'Too short')
        ->call('createProject')
        ->assertHasErrors(['description' => 'min:10']);
});

test('successful redirect to project page after creation', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $component = Livewire::test(Dashboard::class)
        ->set('description', 'This is a valid project description that is long enough')
        ->call('createProject');
    
    $project = Project::where('user_id', $user->id)->first();
    
    $component->assertRedirect("/project/{$project->uuid}");
});

test('default project name generation', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    Livewire::test(Dashboard::class)
        ->set('description', 'This is a test project description for name generation')
        ->call('createProject');
    
    $project = Project::where('user_id', $user->id)->first();
    
    expect($project->name)->not()->toBeNull();
    expect($project->name)->toContain('Project');
});

test('UUID uniqueness on project creation', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    // Create first project
    Livewire::test(Dashboard::class)
        ->set('description', 'First project description')
        ->call('createProject');
    
    // Create second project
    Livewire::test(Dashboard::class)
        ->set('description', 'Second project description')
        ->call('createProject');
    
    $projects = Project::where('user_id', $user->id)->get();
    
    expect($projects)->toHaveCount(2);
    expect($projects[0]->uuid)->not()->toBe($projects[1]->uuid);
});

test('description field is cleared after successful project creation', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $component = Livewire::test(Dashboard::class)
        ->set('description', 'Test project description that will be cleared')
        ->call('createProject');
    
    $component->assertSet('description', '');
});

test('character counter updates in real-time', function (): void {
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    $component = Livewire::test(Dashboard::class)
        ->set('description', 'Test')
        ->assertSeeText('4 / 2000')
        ->set('description', 'This is a longer test description!')
        ->assertSeeText('34 / 2000');
});
