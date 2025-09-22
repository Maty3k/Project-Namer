<?php

declare(strict_types=1);

use App\Jobs\ProcessAIGenerationBatch;
use App\Models\GenerationSession;
use App\Models\NameSuggestion;
use App\Models\User;
use App\Services\AI\CachingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('ProcessAIGenerationBatch Job', function (): void {
    it('processes generation sessions successfully', function (): void {
        $response = "1. TechFlow\n2. InnovateLab\n3. DigitalCraft\n4. CodeForge\n5. DataStream\n6. TechHub\n7. InnovateCore\n8. DigitalFlow\n9. CodeCraft\n10. DataForge";

        Prism::fake([
            TextResponseFake::make()->withText($response),
        ]);

        // Create test generation session
        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-session-123',
            'business_description' => 'A tech startup platform',
            'generation_mode' => 'tech-focused',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'status' => 'pending',
        ]);

        // Mock cache to return empty responses for all cache lookups
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        // Dispatch the job
        $job = new ProcessAIGenerationBatch(['test-session-123'], 'normal');
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        // Assert session was processed
        $session->refresh();
        expect($session->status)->toBe('completed');
        expect($session->started_at)->not->toBeNull();

        // Assert name suggestions were created
        $suggestions = NameSuggestion::where('project_id', $session->project_id)->get();
        expect($suggestions)->toHaveCount(10);
        expect($suggestions->first()->name)->toBeIn(['TechFlow', 'InnovateLab', 'DigitalCraft']);
    });

    it('handles sessions with cached results', function (): void {
        $cachedResults = [
            'gpt-4' => ['CachedFlow', 'CachedLab', 'CachedCraft', 'CachedForge', 'CachedStream'],
        ];

        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-cached-session',
            'business_description' => 'A cached startup platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'status' => 'pending',
        ]);

        // Mock cache to return cached results
        $cacheKey = hash('sha256', trim(strtolower($session->business_description))).':'.
                   hash('sha256', implode(',', $session->requested_models)).':'.
                   $session->generation_mode.':0';

        Cache::shouldReceive('get')
            ->with("ai_generation_result:{$cacheKey}")
            ->andReturn([
                'results' => $cachedResults,
                'models' => ['gpt-4'],
                'generated_at' => now()->timestamp,
                'result_count' => 5,
            ]);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $job = new ProcessAIGenerationBatch(['test-cached-session'], 'normal');
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        $session->refresh();
        expect($session->status)->toBe('completed');

        $suggestions = NameSuggestion::where('project_id', $session->project_id)->get();
        expect($suggestions)->toHaveCount(5);
        expect($suggestions->first()->name)->toBeIn(['CachedFlow', 'CachedLab', 'CachedCraft']);
    });

    it('handles multiple models correctly', function (): void {
        $gptResponse = "1. GPTFlow\n2. GPTLab\n3. GPTCraft\n4. GPTForge\n5. GPTStream";
        $claudeResponse = "1. ClaudeFlow\n2. ClaudeLab\n3. ClaudeCraft\n4. ClaudeForge\n5. ClaudeStream";

        Prism::fake([
            TextResponseFake::make()->withText($gptResponse),
            TextResponseFake::make()->withText($claudeResponse),
        ]);

        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-multi-model',
            'business_description' => 'A multi-model platform',
            'generation_mode' => 'professional',
            'deep_thinking' => true,
            'requested_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'status' => 'pending',
        ]);

        // Mock cache to return empty responses for all cache lookups
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $job = new ProcessAIGenerationBatch(['test-multi-model'], 'normal');
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        $session->refresh();
        expect($session->status)->toBe('completed');

        $suggestions = NameSuggestion::where('project_id', $session->project_id)->get();
        expect($suggestions)->toHaveCount(10); // 5 from each model
    });

    it('handles failed generation gracefully', function (): void {
        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-failed-session',
            'business_description' => 'A failing platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'status' => 'pending',
        ]);

        // Mock cache to return empty responses for all cache lookups
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        // Make Prism throw an exception by not setting up any fake responses
        // When no responses are set up, Prism will throw an exception
        Prism::fake([]);

        $job = new ProcessAIGenerationBatch(['test-failed-session'], 'normal');

        // The job should handle the error gracefully and not throw
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        // Verify the session status was updated to failed
        $session->refresh();
        expect($session->status)->toBe('failed');
    });

    it('skips non-pending sessions', function (): void {
        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-processing-session',
            'business_description' => 'A processing platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'status' => 'processing', // Not pending
        ]);

        $job = new ProcessAIGenerationBatch(['test-processing-session'], 'normal');
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        $session->refresh();
        expect($session->status)->toBe('processing'); // Status unchanged
    });

    it('handles missing sessions gracefully', function (): void {
        // Mock cache to return empty responses for all cache lookups
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $job = new ProcessAIGenerationBatch(['non-existent-session'], 'normal');

        // The job should not throw an exception, but handle the error gracefully
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        // The job completes without throwing, but logs the error internally
        expect(true)->toBeTrue(); // Simple assertion to show test passes
    });

    it('can be queued with different priorities', function (): void {
        Queue::fake();

        ProcessAIGenerationBatch::dispatch(['session1'], 'high');
        ProcessAIGenerationBatch::dispatch(['session2'], 'normal');
        ProcessAIGenerationBatch::dispatch(['session3'], 'low');

        Queue::assertPushed(ProcessAIGenerationBatch::class, 3);
    });

    it('generates correct domain placeholders', function (): void {
        $response = "1. TestFlow\n2. TestLab\n3. TestCraft";

        Prism::fake([
            TextResponseFake::make()->withText($response),
        ]);

        $session = GenerationSession::factory()->create([
            'user_id' => $this->user->id,
            'session_id' => 'test-domains',
            'business_description' => 'A domain test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'status' => 'pending',
        ]);

        // Mock cache to return empty responses for all cache lookups
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $job = new ProcessAIGenerationBatch(['test-domains'], 'normal');
        $job->handle(app(\App\Services\AIGenerationService::class), app(CachingService::class));

        $suggestion = NameSuggestion::where('project_id', $session->project_id)->first();
        expect($suggestion->domains)->toBeArray();
        expect($suggestion->domains)->toHaveCount(4); // .com, .io, .co, .net

        $domains = collect($suggestion->domains);
        expect($domains->pluck('extension')->toArray())->toBe(['.com', '.io', '.co', '.net']);
        expect($domains->pluck('available')->unique()->toArray())->toBe([null]); // Not checked yet
    });
});
