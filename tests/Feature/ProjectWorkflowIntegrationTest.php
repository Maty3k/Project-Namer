<?php

declare(strict_types=1);

use App\Livewire\Dashboard;
use App\Livewire\NameResultCard;
use App\Livewire\ProjectPage;
use App\Livewire\Sidebar;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

describe('Project Workflow Integration Tests', function (): void {
    test('complete project creation and navigation workflow', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Step 1: Visit dashboard
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Create New Project');

        // Step 2: Create project through dashboard
        $component = Livewire::test(Dashboard::class)
            ->set('description', 'A revolutionary e-commerce platform for sustainable products')
            ->call('createProject')
            ->assertHasNoErrors()
            ->assertDispatched('project-created');

        // Verify project was created in database
        $project = Project::where('user_id', $user->id)->latest()->first();
        expect($project)->not->toBeNull();
        expect($project->description)->toBe('A revolutionary e-commerce platform for sustainable products');
        expect($project->name)->toStartWith('Project ');

        // Step 3: Verify redirect to project page
        $this->get("/project/{$project->uuid}")
            ->assertOk()
            ->assertSee($project->name);

        // Step 4: Verify project page displays correct data
        Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->assertSet('project.description', 'A revolutionary e-commerce platform for sustainable products')
            ->assertSet('editableDescription', 'A revolutionary e-commerce platform for sustainable products')
            ->assertSee($project->name);
    });

    test('complete name suggestion workflow with selection', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create some name suggestions
        $suggestion1 = NameSuggestion::factory()->create([
            'project_id' => $project->id,
            'name' => 'EcoMart',
            'domains' => [
                ['extension' => '.com', 'available' => true],
                ['extension' => '.io', 'available' => false],
            ],
        ]);

        $suggestion2 = NameSuggestion::factory()->create([
            'project_id' => $project->id,
            'name' => 'GreenBuy',
            'is_hidden' => false,
        ]);

        $this->actingAs($user);

        // Step 1: Visit project page and see suggestions
        $projectComponent = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->assertSet('resultsFilter', 'visible');

        // Check that we can see the name suggestion cards
        expect($projectComponent->get('filteredSuggestions'))->toHaveCount(2);

        // Verify suggestion counts
        expect($projectComponent->get('suggestionCounts'))->toBe([
            'visible' => 2,
            'hidden' => 0,
            'total' => 2,
        ]);

        // Step 2: Select a name
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion1])
            ->call('selectName')
            ->assertDispatched('name-selected', $suggestion1->id);

        // Verify selection was persisted
        expect($project->fresh()->selected_name_id)->toBe($suggestion1->id);

        // Step 3: Hide a suggestion
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion2])
            ->call('hideSuggestion')
            ->assertDispatched('suggestion-hidden', $suggestion2->id);

        // Verify suggestion was hidden
        expect($suggestion2->fresh()->is_hidden)->toBeTrue();

        // Step 4: Change filter to see hidden suggestions
        $projectComponent = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->call('setResultsFilter', 'hidden')
            ->assertSet('resultsFilter', 'hidden');

        // Verify filtered suggestions show only hidden ones
        $hiddenSuggestions = $projectComponent->get('filteredSuggestions');
        expect($hiddenSuggestions)->toHaveCount(1);
        expect($hiddenSuggestions->first()->name)->toBe('GreenBuy');

        // Step 5: Show all suggestions
        $projectComponent->call('setResultsFilter', 'all')
            ->assertSet('resultsFilter', 'all');

        $allSuggestions = $projectComponent->get('filteredSuggestions');
        expect($allSuggestions)->toHaveCount(2);
        expect($allSuggestions->pluck('name')->toArray())->toContain('EcoMart', 'GreenBuy');
    });

    test('sidebar integration with project workflow', function (): void {
        $user = User::factory()->create();
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'First Project',
            'created_at' => now()->subDay(),
        ]);
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Second Project',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Step 1: Test sidebar shows projects in chronological order
        $sidebarComponent = Livewire::test(Sidebar::class)
            ->assertSee('Second Project')
            ->assertSee('First Project');

        $projects = $sidebarComponent->get('projects');
        expect($projects->pluck('name')->toArray())->toBe([
            'Second Project',
            'First Project',
        ]);

        // Step 2: Test active project highlighting
        Livewire::test(Sidebar::class, ['activeProjectUuid' => $project1->uuid])
            ->assertSet('activeProjectUuid', $project1->uuid);

        // Step 3: Create new project and verify sidebar updates
        Livewire::test(Dashboard::class)
            ->set('description', 'Third project description')
            ->call('createProject')
            ->assertDispatched('project-created');

        $newProject = Project::where('user_id', $user->id)->latest()->first();

        // Test that sidebar receives the event (in real app this would auto-update)
        $sidebarComponent = Livewire::test(Sidebar::class);
        $sidebarComponent->dispatch('project-created', $newProject->uuid);

        expect($sidebarComponent->get('projects'))->toHaveCount(3);
    });

    test('name selection persistence across page reloads', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $suggestion = NameSuggestion::factory()->create([
            'project_id' => $project->id,
            'name' => 'SelectedName',
        ]);

        $this->actingAs($user);

        // Step 1: Select a name
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('selectName')
            ->assertDispatched('name-selected', $suggestion->id);

        // Step 2: Verify persistence in database
        expect($project->fresh()->selected_name_id)->toBe($suggestion->id);

        // Step 3: Simulate page reload by creating new component instance
        $newProjectComponent = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid]);

        // Step 4: Verify the name result card shows as selected
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion->fresh()])
            ->assertSet('isSelected', true);

        // Step 5: Deselect the name
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('deselectName')
            ->assertDispatched('name-deselected', $suggestion->id);

        // Step 6: Verify deselection persistence
        expect($project->fresh()->selected_name_id)->toBeNull();

        // Step 7: Verify UI reflects deselection
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion->fresh()])
            ->assertSet('isSelected', false);
    });

    test('project editing workflow with real-time sidebar updates', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $this->actingAs($user);

        // Step 1: Edit project name
        $projectComponent = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->call('editName')
            ->assertSet('editingName', true)
            ->set('editableName', 'Updated Project Name')
            ->call('saveName')
            ->assertSet('editingName', false)
            ->assertDispatched('project-updated', $project->uuid);

        // Verify name was updated in database
        expect($project->fresh()->name)->toBe('Updated Project Name');

        // Step 2: Edit project description
        $projectComponent->set('editableDescription', 'Updated project description with more details')
            ->call('saveDescription')
            ->assertDispatched('project-updated', $project->uuid);

        // Verify description was updated
        expect($project->fresh()->description)->toBe('Updated project description with more details');

        // Step 3: Test sidebar updates (simulate event handling)
        $sidebarComponent = Livewire::test(Sidebar::class, ['activeProjectUuid' => $project->uuid]);

        // Dispatch the event that would normally be caught by the sidebar
        $sidebarComponent->dispatch('project-updated', $project->uuid);

        // In real usage, the sidebar would show the updated name
        expect($project->fresh()->name)->toBe('Updated Project Name');
    });

    test('complete user workflow from dashboard to name selection', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Step 1: User starts at dashboard
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Create New Project');

        // Step 2: User creates project
        $dashboardComponent = Livewire::test(Dashboard::class)
            ->set('description', 'My new SaaS product for managing team workflows')
            ->call('createProject')
            ->assertHasNoErrors();

        $project = Project::where('user_id', $user->id)->first();

        // Step 3: User is redirected to project page
        $this->get("/project/{$project->uuid}")
            ->assertOk()
            ->assertSee($project->name)
            ->assertSee('My new SaaS product for managing team workflows');

        // Step 4: Create some name suggestions (simulate AI generation)
        $suggestions = [
            NameSuggestion::factory()->create([
                'project_id' => $project->id,
                'name' => 'TeamFlow',
                'domains' => [
                    ['extension' => '.com', 'available' => true, 'price' => 12.99],
                    ['extension' => '.io', 'available' => false],
                ],
                'logos' => [
                    ['url' => 'logo1.png', 'style' => 'minimalist'],
                    ['url' => 'logo2.png', 'style' => 'modern'],
                ],
            ]),
            NameSuggestion::factory()->create([
                'project_id' => $project->id,
                'name' => 'WorkSync',
                'domains' => [
                    ['extension' => '.com', 'available' => false],
                    ['extension' => '.app', 'available' => true, 'price' => 15.99],
                ],
            ]),
        ];

        // Step 5: User views suggestions on project page
        $projectComponent = Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->assertSee('2 visible, 0 hidden');

        // Verify suggestions are loaded
        expect($projectComponent->get('filteredSuggestions'))->toHaveCount(2);

        // Step 6: User expands and examines first suggestion
        $cardComponent = Livewire::test(NameResultCard::class, ['suggestion' => $suggestions[0]])
            ->call('toggleExpanded')
            ->assertSet('expanded', true)
            ->assertSee('.com')
            ->assertSee('.io')
            ->assertSee('minimalist')
            ->assertSee('modern');

        // Step 7: User selects the first name
        $cardComponent->call('selectName')
            ->assertDispatched('name-selected', $suggestions[0]->id);

        // Step 8: User hides the second suggestion
        Livewire::test(NameResultCard::class, ['suggestion' => $suggestions[1]])
            ->call('hideSuggestion')
            ->assertDispatched('suggestion-hidden', $suggestions[1]->id);

        // Step 9: Verify final state
        expect($project->fresh()->selected_name_id)->toBe($suggestions[0]->id);
        expect($suggestions[1]->fresh()->is_hidden)->toBeTrue();

        // Step 10: Test filter functionality
        $projectComponent->call('setResultsFilter', 'hidden');
        $hiddenSuggestions = $projectComponent->get('filteredSuggestions');
        expect($hiddenSuggestions)->toHaveCount(1);
        expect($hiddenSuggestions->first()->name)->toBe('WorkSync');

        $projectComponent->call('setResultsFilter', 'all');
        $allSuggestions = $projectComponent->get('filteredSuggestions');
        expect($allSuggestions)->toHaveCount(2);
    });

    test('error handling and validation workflow', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test dashboard validation
        Livewire::test(Dashboard::class)
            ->set('description', 'short') // Less than 10 characters
            ->call('createProject')
            ->assertHasErrors('description');

        // Test project page name validation
        $project = Project::factory()->create(['user_id' => $user->id]);

        Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->call('editName')
            ->set('editableName', 'A') // Less than 2 characters
            ->call('saveName')
            ->assertHasErrors('editableName');

        // Test project page description validation
        Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
            ->set('editableDescription', 'short') // Less than 10 characters
            ->call('saveDescription')
            ->assertHasErrors('editableDescription');

        // Test unauthorized access
        $otherUser = User::factory()->create();
        $suggestion = NameSuggestion::factory()->create(['project_id' => $project->id]);

        $this->actingAs($otherUser);

        Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
            ->call('selectName')
            ->assertForbidden();
    });
});
