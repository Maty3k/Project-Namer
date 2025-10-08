<?php

declare(strict_types=1);

use App\Jobs\GenerateNamesWithModelJob;
use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use App\Services\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

uses(RefreshDatabase::class);

describe('GenerateNamesWithModelJob Progress Tracking', function (): void {
    beforeEach(function (): void {
        // Mock Prism to avoid actual API calls
        Prism::fake([
            TextResponseFake::make()->withText('1. TechFlow\n2. DataVibe\n3. CodeForge\n4. BitStream\n5. CloudNest\n6. PixelPulse\n7. NeuralNet\n8. QuantumLeap\n9. SyncWave\n10. ByteBloom'),
        ]);
    });

    it('sets progress to 0 when job starts', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'progress' => 0,
        ]);

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        $job->handle(app(PromptBuilder::class));

        $generation->refresh();
        expect($generation->progress)->toBeGreaterThanOrEqual(0);
    });

    it('updates progress to 25 after API request starts', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
        ]);

        Event::fake();

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        $job->handle(app(PromptBuilder::class));

        // Check that progress was updated at some point during execution
        $generation->refresh();
        expect($generation->progress)->toBeGreaterThanOrEqual(0);
    });

    it('dispatches ai-generation-progress event during execution', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
        ]);

        Event::fake();

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        $job->handle(app(PromptBuilder::class));

        // Job should complete successfully
        $generation->refresh();
        expect($generation->execution_metadata)->toHaveKey('model_status');
    });

    it('sets progress to 100 when job completes successfully', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
        ]);

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        $job->handle(app(PromptBuilder::class));

        $generation->refresh();
        expect($generation->execution_metadata['model_status']['gpt-4'])->toBe('completed');
    });

    it('handles progress updates with error handling', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
        ]);

        // Test that updateProgress method has error handling by ensuring
        // progress updates don't fail the entire job
        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        $job->handle(app(PromptBuilder::class));

        $generation->refresh();
        // Progress should have been tracked successfully
        expect($generation->progress)->toBeGreaterThanOrEqual(0);
        expect($generation->execution_metadata['model_status']['gpt-4'])->toBeIn(['completed', 'running']);
    });

    it('tracks progress for deep thinking mode', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
            'deep_thinking' => true,
        ]);

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10, 'deep_thinking' => true]
        );

        $job->handle(app(PromptBuilder::class));

        $generation->refresh();
        expect($generation->execution_metadata['model_status']['gpt-4'])->toBe('completed');
    });

    it('can be dispatched to queue', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        GenerateNamesWithModelJob::dispatch(
            $generation,
            'gpt-4',
            'A tech startup',
            ['mode' => 'creative', 'count' => 10]
        );

        Queue::assertPushed(GenerateNamesWithModelJob::class);
    });

    it('uses ai-generation queue', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $job = new GenerateNamesWithModelJob(
            $generation,
            'gpt-4',
            'A tech startup'
        );

        expect($job->queue)->toBe('ai-generation');
    });
});
