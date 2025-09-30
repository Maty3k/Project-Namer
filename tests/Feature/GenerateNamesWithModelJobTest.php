<?php

declare(strict_types=1);

use App\Jobs\GenerateNamesWithModelJob;
use App\Models\AIGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('GenerateNamesWithModelJob', function (): void {
    it('generates names successfully for a model', function (): void {
        $response = "1. ModelFlow\n2. ModelLab\n3. ModelCraft\n4. ModelForge\n5. ModelStream";

        Prism::fake([
            TextResponseFake::make()->withText($response),
        ]);

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'generation_session_id' => 'test-session-123',
            'models_requested' => ['gpt-4'],
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'status' => 'pending',
            'prompt_used' => 'A creative platform for testing',
        ]);

        $job = new GenerateNamesWithModelJob(
            $aiGeneration,
            'gpt-4',
            'A creative platform for testing',
            ['mode' => 'creative', 'deep_thinking' => false]
        );

        $job->handle(app(\App\Services\AIGenerationService::class));

        // Check that results were cached
        $cacheKey = "ai_generation_result_{$aiGeneration->id}_gpt-4";
        $cachedResult = Cache::get($cacheKey);

        expect($cachedResult)->not->toBeNull();
        expect($cachedResult['status'])->toBe('completed');
        expect($cachedResult['model_id'])->toBe('gpt-4');
        expect($cachedResult['names_generated'])->toBe(5);
        expect($cachedResult['execution_time_ms'])->toBeNumeric();

        // Check that model status was updated
        $aiGeneration->refresh();
        $metadata = $aiGeneration->execution_metadata;
        expect($metadata['model_status']['gpt-4'])->toBe('completed');
        expect($metadata['model_metrics']['gpt-4']['execution_time_ms'])->toBeNumeric();
        expect($metadata['model_metrics']['gpt-4']['names_generated'])->toBe(5);
    });

    it('handles API failures gracefully', function (): void {
        // Set up Prism to fail by not providing any responses
        Prism::fake([]);

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'generation_session_id' => 'test-session-fail',
            'models_requested' => ['gpt-4'],
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'status' => 'pending',
            'prompt_used' => 'A failing platform',
        ]);

        $job = new GenerateNamesWithModelJob(
            $aiGeneration,
            'gpt-4',
            'A failing platform',
            ['mode' => 'creative', 'deep_thinking' => false]
        );

        // The job should throw an exception for retryable errors
        expect(fn () => $job->handle(app(\App\Services\AIGenerationService::class)))
            ->toThrow(Exception::class, 'AI generation failed: Could not find a response for the request');

        // Check that error was cached even though exception was thrown
        $cacheKey = "ai_generation_result_{$aiGeneration->id}_gpt-4";
        $cachedResult = Cache::get($cacheKey);

        expect($cachedResult)->not->toBeNull();
        expect($cachedResult['status'])->toBe('failed');
        expect($cachedResult['error'])->toBeString();
        expect($cachedResult['names_generated'])->toBe(0);

        // Check that model status was updated to failed
        $aiGeneration->refresh();
        $metadata = $aiGeneration->execution_metadata;
        expect($metadata['model_status']['gpt-4'])->toBe('failed');
        expect($metadata['model_metrics']['gpt-4']['error'])->toBeString();
    });

    it('can be queued with correct settings', function (): void {
        Queue::fake();

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);

        GenerateNamesWithModelJob::dispatch(
            $aiGeneration,
            'gpt-4',
            'Test prompt',
            ['mode' => 'creative']
        );

        Queue::assertPushed(GenerateNamesWithModelJob::class, fn ($job) => $job->queue === 'ai-generation' &&
               $job->tries === 3 &&
               $job->timeout === 120);
    });

    it('determines retry behavior correctly for different errors', function (): void {
        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $job = new GenerateNamesWithModelJob(
            $aiGeneration,
            'gpt-4',
            'Test prompt',
            []
        );

        // Test permanent errors (should not retry)
        $permanentErrors = [
            'Invalid API key',
            'Insufficient quota',
            'Model not found',
            'Unauthorized access',
            'Forbidden request',
        ];

        foreach ($permanentErrors as $errorMessage) {
            $exception = new Exception($errorMessage);

            // Use reflection to access protected method
            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod('shouldRetry');

            expect($method->invoke($job, $exception))->toBe(false);
        }

        // Test transient errors (should retry)
        $transientErrors = [
            'Network timeout',
            'Connection refused',
            'Server error 500',
            'Rate limit exceeded',
        ];

        foreach ($transientErrors as $errorMessage) {
            $exception = new Exception($errorMessage);

            $reflection = new ReflectionClass($job);
            $method = $reflection->getMethod('shouldRetry');

            expect($method->invoke($job, $exception))->toBe(true);
        }
    });

    it('handles job failure correctly', function (): void {
        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'execution_metadata' => [],
        ]);

        $job = new GenerateNamesWithModelJob(
            $aiGeneration,
            'gpt-4',
            'Test prompt',
            []
        );

        $exception = new Exception('Permanent failure');
        $job->failed($exception);

        // Check that failure was cached
        $cacheKey = "ai_generation_result_{$aiGeneration->id}_gpt-4";
        $cachedResult = Cache::get($cacheKey);

        expect($cachedResult)->not->toBeNull();
        expect($cachedResult['status'])->toBe('failed');
        expect($cachedResult['error'])->toBe('Permanent failure');

        // Check that model status was updated
        $aiGeneration->refresh();
        $metadata = $aiGeneration->execution_metadata;
        expect($metadata['model_status']['gpt-4'])->toBe('failed');
        expect($metadata['model_metrics']['gpt-4']['error'])->toBe('Permanent failure');
    });
});
