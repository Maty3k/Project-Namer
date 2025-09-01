# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-09-01-project-workflow-ui/spec.md

> Created: 2025-09-01
> Version: 1.0.0

## Test Coverage

### Unit Tests

**Project Model**
- Test UUID generation on creation
- Test relationship with User model
- Test relationship with NameSuggestion model
- Test selectedName relationship
- Test visibleNameSuggestions scope
- Test fillable attributes and mass assignment protection

**NameSuggestion Model**
- Test relationship with Project model
- Test visible/hidden scopes
- Test JSON casting for domains and logos
- Test boolean casting for is_hidden
- Test generation_metadata array casting

### Feature Tests

**Dashboard Page Tests**
- Test dashboard page loads successfully for authenticated user
- Test dashboard page redirects unauthenticated users
- Test project creation with valid description
- Test validation error with empty description
- Test validation error with description less than 10 characters
- Test successful redirect to project page after creation
- Test default project name generation
- Test UUID uniqueness on project creation

**Project Page Tests**
- Test project page loads with valid UUID
- Test 404 error with invalid UUID
- Test user can only access their own projects
- Test real-time description update saves to database
- Test project name inline editing and saving
- Test name suggestions display correctly
- Test filtering between visible and hidden suggestions
- Test name selection updates project and UI
- Test name deselection reverts UI but keeps database value
- Test unauthorized access returns 403

**Sidebar Navigation Tests**
- Test sidebar displays all user projects
- Test projects are ordered by created_at DESC
- Test clicking project navigates to correct page
- Test new project button navigates to dashboard
- Test active project is highlighted
- Test project name updates reflect in sidebar
- Test sidebar updates when new project is created

### Integration Tests

**Project Creation Workflow**
- Test complete flow from dashboard to project page
- Test project persists in database with correct attributes
- Test user association is properly set
- Test sidebar updates after project creation

**Name Selection Workflow**
- Test selecting a name updates the project's selected_name_id
- Test UI updates to show only selected name
- Test deselecting keeps the database value
- Test sidebar reflects selected name as project name

**Name Result Card Interactions**
- Test hiding a name suggestion sets is_hidden to true
- Test showing hidden suggestions with filter toggle
- Test logo generation button triggers appropriate action
- Test domain display for each suggestion
- Test expandable/collapsible card sections

### Livewire Component Tests

**Dashboard Component**
```php
test('dashboard component renders correctly', function () {
    $user = User::factory()->create();
    
    Livewire::test(Dashboard::class)
        ->actingAs($user)
        ->assertSee('Describe your project')
        ->assertSee('Save & Generate Names');
});

test('project creation with valid data', function () {
    $user = User::factory()->create();
    
    Livewire::test(Dashboard::class)
        ->actingAs($user)
        ->set('description', 'This is my amazing new startup idea for revolutionizing the way people order coffee')
        ->call('createProject')
        ->assertRedirect('/project/');
    
    expect(Project::where('user_id', $user->id)->exists())->toBeTrue();
});
```

**ProjectPage Component**
```php
test('project page loads with correct data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->actingAs($user)
        ->assertSet('project.name', $project->name)
        ->assertSet('project.description', $project->description);
});

test('updating project description', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    
    Livewire::test(ProjectPage::class, ['uuid' => $project->uuid])
        ->actingAs($user)
        ->set('project.description', 'Updated description')
        ->call('updateDescription');
    
    expect($project->fresh()->description)->toBe('Updated description');
});
```

**Sidebar Component**
```php
test('sidebar displays user projects', function () {
    $user = User::factory()->create();
    $projects = Project::factory()->count(3)->for($user)->create();
    
    Livewire::test(Sidebar::class)
        ->actingAs($user)
        ->assertSee($projects[0]->name)
        ->assertSee($projects[1]->name)
        ->assertSee($projects[2]->name);
});

test('projects are ordered by most recent first', function () {
    $user = User::factory()->create();
    $oldProject = Project::factory()->for($user)->create(['created_at' => now()->subDays(2)]);
    $newProject = Project::factory()->for($user)->create(['created_at' => now()]);
    
    Livewire::test(Sidebar::class)
        ->actingAs($user)
        ->assertSeeInOrder([$newProject->name, $oldProject->name]);
});
```

**NameResultCard Component**
```php
test('name result card displays suggestion data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $suggestion = NameSuggestion::factory()->for($project)->create([
        'name' => 'BrandName',
        'domains' => [
            ['extension' => '.com', 'available' => true],
            ['extension' => '.io', 'available' => false]
        ]
    ]);
    
    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->actingAs($user)
        ->assertSee('BrandName')
        ->assertSee('.com')
        ->assertSee('.io');
});

test('hiding a suggestion updates database', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $suggestion = NameSuggestion::factory()->for($project)->create();
    
    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->actingAs($user)
        ->call('hide');
    
    expect($suggestion->fresh()->is_hidden)->toBeTrue();
});
```

## Mocking Requirements

**External API Mocking**
- Mock AI name generation API responses (when integrated)
- Mock domain availability checking API (when integrated)
- Mock logo generation API (when integrated)

**Database Transaction Testing**
- Use database transactions for all tests to ensure clean state
- Use RefreshDatabase trait for proper test isolation

**Event Mocking**
- Mock Livewire events for testing component communication
- Test that appropriate events are dispatched on actions

## Test Data Factories

**ProjectFactory**
```php
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => 'Project ' . $this->faker->numberBetween(1, 100),
            'description' => $this->faker->paragraph(3),
            'user_id' => User::factory(),
        ];
    }
}
```

**NameSuggestionFactory**
```php
class NameSuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->company(),
            'domains' => [
                ['extension' => '.com', 'available' => $this->faker->boolean()],
                ['extension' => '.io', 'available' => $this->faker->boolean()],
            ],
            'logos' => null,
            'is_hidden' => false,
            'generation_metadata' => [
                'ai_model' => 'gpt-4',
                'temperature' => 0.7,
            ],
        ];
    }
    
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
    
    public function withLogos(): static
    {
        return $this->state(fn (array $attributes) => [
            'logos' => [
                ['url' => $this->faker->imageUrl(), 'style' => 'modern'],
                ['url' => $this->faker->imageUrl(), 'style' => 'minimalist'],
            ],
        ]);
    }
}
```

## Test Execution Strategy

1. Run unit tests first to ensure models work correctly
2. Run feature tests to verify complete workflows
3. Run Livewire component tests for UI interactions
4. Use parallel testing for faster execution
5. Maintain minimum 80% code coverage
6. All tests must pass before deployment