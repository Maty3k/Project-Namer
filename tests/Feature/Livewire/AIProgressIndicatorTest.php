<?php

declare(strict_types=1);

use App\Livewire\Components\AIProgressIndicator;
use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('component initializes with default state', function (): void {
    Livewire::test(AIProgressIndicator::class)
        ->assertSet('generationId', null)
        ->assertSet('progress', 0)
        ->assertSet('isDeepThinking', false)
        ->assertSet('estimatedTimeRemaining', null)
        ->assertSet('isComplete', false);
});

test('component starts progress when ai-generation-started event is dispatched', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'progress' => 0,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->assertSet('generationId', $generation->id)
        ->assertSet('isDeepThinking', false)
        ->assertSet('progress', 0)
        ->assertSet('isComplete', false);
});

test('component starts progress in deep thinking mode', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'progress' => 0,
        'deep_thinking' => true,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => true,
        ])
        ->assertSet('generationId', $generation->id)
        ->assertSet('isDeepThinking', true)
        ->assertSet('progress', 0);
});

test('component updates progress when ai-generation-progress event is dispatched', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 0,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 50,
        ])
        ->assertSet('progress', 50);
});

test('component completes progress when ai-generation-complete event is dispatched', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 75,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-complete')
        ->assertSet('progress', 100)
        ->assertSet('isComplete', true)
        ->assertSet('estimatedTimeRemaining', 0);
});

test('component fetches progress from database', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 35,
    ]);

    $component = Livewire::test(AIProgressIndicator::class);
    $component->set('generationId', $generation->id);

    $result = $component->instance()->getProgress();

    expect($result)->toBeArray()
        ->and($result['progress'])->toBe(35)
        ->and($result['status'])->toBe('running');
});

test('component calculates estimated time remaining for normal mode', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 50,
    ]);

    $component = Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 50,
        ]);

    // Normal mode: 4 seconds total, 50% done = 2 seconds remaining
    $estimatedTime = $component->get('estimatedTimeRemaining');
    expect($estimatedTime)->toBe(2);
});

test('component calculates estimated time remaining for deep thinking mode', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 50,
        'deep_thinking' => true,
    ]);

    $component = Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => true,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 50,
        ]);

    // Deep thinking mode: 10 seconds total, 50% done = 5 seconds remaining
    $estimatedTime = $component->get('estimatedTimeRemaining');
    expect($estimatedTime)->toBe(5);
});

test('component returns zero progress when generation not found', function (): void {
    $component = Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => 99999,
            'is_deep_thinking' => false,
        ]);

    $result = $component->call('getProgress');

    expect($result->get('progress'))->toBe(0);
});

test('component returns zero progress when no generation id set', function (): void {
    $component = Livewire::test(AIProgressIndicator::class);

    $result = $component->call('getProgress');

    expect($result->get('progress'))->toBe(0);
});

test('component estimated time is zero when progress is 100', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'progress' => 100,
    ]);

    $component = Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 100,
        ]);

    expect($component->get('estimatedTimeRemaining'))->toBe(0);
});
