<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use Livewire\Livewire;

test('user can save AI preferences', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
        ->set('generationMode', 'creative')
        ->set('deepThinking', true)
        ->call('saveAIPreferences')
        ->assertDispatched('show-toast');

    // Verify preferences were saved to database
    $preferences = UserAIPreferences::where('user_id', $user->id)->first();
    expect($preferences)->not->toBeNull();
    expect($preferences->preferred_models)->toBe(['gpt-4', 'claude-3.5-sonnet']);
    expect($preferences->default_generation_mode)->toBe('creative');
    expect($preferences->default_deep_thinking)->toBeTrue();
});

test('saved preferences are loaded when user visits new project', function (): void {
    $user = User::factory()->create();

    // Save preferences first
    UserAIPreferences::create([
        'user_id' => $user->id,
        'preferred_models' => ['gpt-4'],
        'default_generation_mode' => 'professional',
        'default_deep_thinking' => false,
        'enable_model_comparison' => true,
    ]);

    // Create a new project and visit it
    $project = Project::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid]);

    // Verify preferences were loaded
    expect($component->selectedAIModels)->toBe(['gpt-4']);
    expect($component->generationMode)->toBe('professional');
    expect($component->deepThinking)->toBeFalse();
    expect($component->enableModelComparison)->toBeTrue();
});

test('preferences persist across multiple projects', function (): void {
    $user = User::factory()->create();

    // Save preferences on first project
    $project1 = Project::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project1->uuid])
        ->set('selectedAIModels', ['claude-3.5-sonnet', 'gemini-1.5-pro'])
        ->set('generationMode', 'brandable')
        ->call('saveAIPreferences');

    // Visit second project - preferences should be loaded
    $project2 = Project::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project2->uuid]);

    expect($component->selectedAIModels)->toBe(['claude-3.5-sonnet', 'gemini-1.5-pro']);
    expect($component->generationMode)->toBe('brandable');
});

test('updating preferences overwrites previous preferences', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    // Save initial preferences
    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('selectedAIModels', ['gpt-4'])
        ->set('generationMode', 'creative')
        ->call('saveAIPreferences');

    // Update preferences
    Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid])
        ->set('selectedAIModels', ['claude-3.5-sonnet', 'grok-beta'])
        ->set('generationMode', 'tech-focused')
        ->set('deepThinking', true)
        ->call('saveAIPreferences');

    // Verify updated preferences
    $preferences = UserAIPreferences::where('user_id', $user->id)->first();
    expect($preferences->preferred_models)->toBe(['claude-3.5-sonnet', 'grok-beta']);
    expect($preferences->default_generation_mode)->toBe('tech-focused');
    expect($preferences->default_deep_thinking)->toBeTrue();
});

test('user without saved preferences starts with empty selections', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(ProjectPage::class, ['uuid' => $project->uuid]);

    // Verify no preferences loaded (empty defaults)
    expect($component->selectedAIModels)->toBe([]);
    expect($component->generationMode)->toBe('');
    expect($component->deepThinking)->toBeFalse();
});
