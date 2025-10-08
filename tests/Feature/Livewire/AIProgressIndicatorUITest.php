<?php

declare(strict_types=1);

use App\Livewire\Components\AIProgressIndicator;
use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('progress bar is hidden when no generation is active', function (): void {
    Livewire::test(AIProgressIndicator::class)
        ->assertSee('hidden');
});

test('progress bar is visible when generation starts', function (): void {
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
        ->assertSee('Generating Names')
        ->assertSee('remaining');
});

test('progress bar displays normal mode styling', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 50,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->assertSee('Generating Names');
});

test('progress bar displays deep thinking mode styling', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 50,
        'deep_thinking' => true,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => true,
        ])
        ->assertSee('Deep Thinking Mode');
});

test('progress bar displays estimated time remaining', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 50,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 50,
        ])
        ->assertSee('remaining');
});

test('progress bar shows completion message when done', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 95,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-progress', [
            'progress' => 100,
        ])
        ->assertSee('Complete!');
});

test('progress bar has ARIA accessibility attributes', function (): void {
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

    $html = $component->get('progress');

    // Component should have ARIA attributes in the view
    $component->assertSee('role', false)
        ->assertSee('aria', false);
});

test('progress bar is hidden when generation completes', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $generation = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
        'progress' => 90,
    ]);

    Livewire::test(AIProgressIndicator::class)
        ->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-complete')
        ->assertSee('hidden');
});

test('progress bar width reflects current progress percentage', function (): void {
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
        ->dispatch('ai-generation-progress', [
            'progress' => 75,
        ])
        ->assertSee('width: 75%', false);
});
