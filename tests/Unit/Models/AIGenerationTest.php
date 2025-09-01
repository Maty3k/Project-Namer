<?php

declare(strict_types=1);

use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;

test('AIGeneration model can be created with required attributes', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'generation_session_id' => 'session_123',
        'models_requested' => ['gpt-4o', 'claude-3.5-sonnet'],
        'generation_mode' => 'creative',
        'deep_thinking' => false,
        'status' => 'pending',
        'prompt_used' => 'Generate creative names for a tech startup',
        'results_data' => ['names' => ['TechFlow', 'DataVibe']],
        'execution_metadata' => ['total_time_ms' => 1500],
    ]);

    expect($aiGeneration)->toBeInstanceOf(AIGeneration::class)
        ->and($aiGeneration->project_id)->toBe($project->id)
        ->and($aiGeneration->user_id)->toBe($user->id)
        ->and($aiGeneration->generation_session_id)->toBe('session_123')
        ->and($aiGeneration->models_requested)->toBe(['gpt-4o', 'claude-3.5-sonnet'])
        ->and($aiGeneration->generation_mode)->toBe('creative')
        ->and($aiGeneration->deep_thinking)->toBeFalse()
        ->and($aiGeneration->status)->toBe('pending')
        ->and($aiGeneration->prompt_used)->toBe('Generate creative names for a tech startup')
        ->and($aiGeneration->results_data)->toBe(['names' => ['TechFlow', 'DataVibe']])
        ->and($aiGeneration->execution_metadata)->toBe(['total_time_ms' => 1500]);
});

test('AIGeneration belongs to project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($aiGeneration->project)->toBeInstanceOf(Project::class)
        ->and($aiGeneration->project->id)->toBe($project->id);
});

test('AIGeneration belongs to user', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    expect($aiGeneration->user)->toBeInstanceOf(User::class)
        ->and($aiGeneration->user->id)->toBe($user->id);
});

test('AIGeneration casts arrays and boolean properly', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'generation_session_id' => 'session_123',
        'models_requested' => json_encode(['gpt-4o', 'claude-3.5-sonnet']),
        'generation_mode' => 'creative',
        'deep_thinking' => 1,
        'status' => 'pending',
        'results_data' => json_encode(['names' => ['TechFlow']]),
        'execution_metadata' => json_encode(['total_time_ms' => 1500]),
    ]);

    expect($aiGeneration->models_requested)->toBeArray()
        ->and($aiGeneration->deep_thinking)->toBeBool()
        ->and($aiGeneration->results_data)->toBeArray()
        ->and($aiGeneration->execution_metadata)->toBeArray();
});

test('AIGeneration has scope for active generations', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $activeGenerations = AIGeneration::active()->get();
    
    expect($activeGenerations)->toHaveCount(2);
});

test('AIGeneration has scope for completed generations', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'failed',
    ]);
    
    AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $completedGenerations = AIGeneration::completed()->get();
    
    expect($completedGenerations)->toHaveCount(1);
});

test('AIGeneration has scope for recent generations', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    // Create old generation
    $oldGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'created_at' => now()->subDays(2),
    ]);
    
    // Create recent generation
    $recentGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'created_at' => now()->subHours(2),
    ]);

    $recentGenerations = AIGeneration::recent()->get();
    
    expect($recentGenerations)->toHaveCount(1)
        ->and($recentGenerations->first()->id)->toBe($recentGeneration->id);
});

test('AIGeneration can check if generation is in progress', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $pendingGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
    
    $runningGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);
    
    $completedGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    expect($pendingGeneration->isInProgress())->toBeTrue()
        ->and($runningGeneration->isInProgress())->toBeTrue()
        ->and($completedGeneration->isInProgress())->toBeFalse();
});

test('AIGeneration can mark as completed', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    $results = ['names' => ['TechFlow', 'DataVibe', 'CodeForge']];
    $metadata = ['total_time_ms' => 2500, 'model_used' => 'gpt-4o'];
    
    $aiGeneration->markAsCompleted($results, $metadata);

    expect($aiGeneration->fresh()->status)->toBe('completed')
        ->and($aiGeneration->fresh()->results_data)->toBe($results)
        ->and($aiGeneration->fresh()->execution_metadata)->toBe($metadata)
        ->and($aiGeneration->fresh()->completed_at)->not->toBeNull();
});

test('AIGeneration can mark as failed', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'status' => 'running',
    ]);

    $errorMessage = 'API rate limit exceeded';
    
    $aiGeneration->markAsFailed($errorMessage);

    expect($aiGeneration->fresh()->status)->toBe('failed')
        ->and($aiGeneration->fresh()->error_message)->toBe($errorMessage)
        ->and($aiGeneration->fresh()->failed_at)->not->toBeNull();
});

test('AIGeneration can get duration in seconds', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $startTime = now();
    $endTime = $startTime->copy()->addSeconds(45);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'started_at' => $startTime,
        'completed_at' => $endTime,
    ]);

    expect($aiGeneration->getDurationInSeconds())->toBe(45);
});

test('AIGeneration returns null duration when not started', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    
    $aiGeneration = AIGeneration::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'started_at' => null,
    ]);

    expect($aiGeneration->getDurationInSeconds())->toBeNull();
});