<?php

declare(strict_types=1);

use App\Livewire\NameResultCard;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('name result card renders successfully', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->assertStatus(200)
        ->assertSee('TestName');
});

test('name result card displays domain information', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'domains' => [
            ['extension' => '.com', 'available' => true],
            ['extension' => '.io', 'available' => false],
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('toggleExpanded') // Expand to show domains
        ->assertSee('.com')
        ->assertSee('.io');
});

test('name result card can be expanded and collapsed', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->assertSet('expanded', false)
        ->call('toggleExpanded')
        ->assertSet('expanded', true)
        ->call('toggleExpanded')
        ->assertSet('expanded', false);
});

test('name result card can be hidden', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'is_hidden' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('hideSuggestion')
        ->assertDispatched('suggestion-hidden', $suggestion->id);

    // Verify the suggestion was marked as hidden in the database
    expect($suggestion->fresh()->is_hidden)->toBeTrue();
});

test('name result card can be unhidden', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'is_hidden' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('showSuggestion')
        ->assertDispatched('suggestion-shown', $suggestion->id);

    // Verify the suggestion was marked as visible in the database
    expect($suggestion->fresh()->is_hidden)->toBeFalse();
});

test('name result card can be selected', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('selectName')
        ->assertDispatched('name-selected', $suggestion->id);

    // Verify the project's selected name was updated
    expect($project->fresh()->selected_name_id)->toBe($suggestion->id);
});

test('name result card shows selected state when name is selected', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    // Mark this suggestion as selected
    $project->update(['selected_name_id' => $suggestion->id]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->assertSet('isSelected', true);
});

test('name result card can deselect a selected name', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    // Mark this suggestion as selected
    $project->update(['selected_name_id' => $suggestion->id]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->assertSet('isSelected', true)
        ->call('deselectName')
        ->assertDispatched('name-deselected', $suggestion->id);

    // Verify the project's selected name was cleared
    expect($project->fresh()->selected_name_id)->toBeNull();
});

test('name result card displays domain availability status', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'domains' => [
            ['extension' => '.com', 'available' => true, 'price' => 12.99],
            ['extension' => '.io', 'available' => false],
            ['extension' => '.net', 'available' => true, 'price' => 15.99],
        ],
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

    // Test available domains count
    expect($component->get('availableDomainsCount'))->toBe(2);

    // Test total domains count
    expect($component->get('totalDomainsCount'))->toBe(3);
});

test('name result card handles missing domain data', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'domains' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

    expect($component->get('availableDomainsCount'))->toBe(0);
    expect($component->get('totalDomainsCount'))->toBe(0);
});

test('name result card displays logo count when available', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'logos' => [
            ['url' => 'logo1.png', 'style' => 'minimalist'],
            ['url' => 'logo2.png', 'style' => 'modern'],
        ],
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

    expect($component->get('logoCount'))->toBe(2);
});

test('name result card handles missing logo data', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'logos' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(NameResultCard::class, ['suggestion' => $suggestion]);

    expect($component->get('logoCount'))->toBe(0);
});

test('name result card triggers logo generation', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'logos' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('generateLogos')
        ->assertDispatched('logos-requested', $suggestion->id);
});

test('name result card only allows project owner to modify', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
    ]);

    $this->actingAs($otherUser);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->call('hideSuggestion')
        ->assertForbidden();
});

test('name result card displays generation metadata', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $suggestion = NameSuggestion::factory()->create([
        'project_id' => $project->id,
        'name' => 'TestName',
        'generation_metadata' => [
            'ai_model' => 'gpt-4',
            'temperature' => 0.7,
            'generated_at' => '2025-09-01T10:00:00Z',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(NameResultCard::class, ['suggestion' => $suggestion])
        ->assertSee('gpt-4');
});
