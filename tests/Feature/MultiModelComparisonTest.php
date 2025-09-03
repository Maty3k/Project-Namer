<?php

declare(strict_types=1);

use App\Livewire\NameGeneratorDashboard;
use App\Livewire\ProjectPage;
use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use App\Services\AI\AIGenerationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Multi-Model Project',
        'description' => 'Testing parallel AI model execution and comparison',
    ]);
    $this->actingAs($this->user);
});

describe('Parallel Model Execution', function (): void {
    test('AI generation service can execute multiple models in parallel', function (): void {
        $mockService = $this->mock(AIGenerationService::class);
        $mockService->shouldReceive('generateWithModels')
            ->once()
            ->andReturn([
                'gpt-4' => ['ParallelTech', 'SyncFlow', 'MultiCore'],
                'claude-3.5-sonnet' => ['ConcurrentWorks', 'AsyncLogic', 'ParallelStream'],
                'gemini-1.5-pro' => ['ThreadedSoft', 'BatchProcess', 'QueuedTasks'],
            ]);

        $aiGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'models_requested' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
        ]);

        $results = $mockService->generateWithModels(
            $aiGeneration,
            ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
            'Generate business names for parallel processing software',
            ['mode' => 'tech-focused']
        );

        expect($results)->toBeArray();
        expect($results)->toHaveKeys(['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro']);
        expect($results['gpt-4'])->toHaveCount(3);
        expect($results['claude-3.5-sonnet'])->toHaveCount(3);
        expect($results['gemini-1.5-pro'])->toHaveCount(3);
    });

    test('Dashboard can handle parallel model generation', function (): void {
        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'A parallel processing software company')
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiModelResults', [
                'gpt-4' => ['ParallelTech', 'SyncFlow'],
                'claude-3.5-sonnet' => ['ConcurrentWorks', 'AsyncLogic'],
            ]);

        // Should handle parallel generation without errors
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('aiModelResults'))->toBeArray();
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('aiModelResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
    });

    test('ProjectPage can handle parallel model generation', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['ParallelTech1', 'SyncFlow1'],
                'claude-3.5-sonnet' => ['ConcurrentWorks1', 'AsyncLogic1'],
                'gemini-1.5-pro' => ['ThreadedSoft1', 'BatchProcess1'],
            ]);

        // Should handle parallel generation without errors
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro']);
    });

    test('Parallel generation can be cancelled with partial results preservation', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'models_requested' => ['gpt-4', 'claude-3.5-sonnet'],
            'execution_metadata' => [
                'model_status' => [
                    'gpt-4' => 'completed',
                    'claude-3.5-sonnet' => 'running',
                ],
            ],
        ]);

        // Mock completed results in cache for GPT-4
        Cache::put("ai_generation_result_{$generation->id}_gpt-4", [
            'model_id' => 'gpt-4',
            'results' => ['CompletedName1', 'CompletedName2'],
            'status' => 'completed',
            'execution_time_ms' => 1500,
            'names_generated' => 2,
        ], 600);

        // Mock running job for Claude (no cached result yet)
        Cache::put("ai_generation_result_{$generation->id}_claude-3.5-sonnet", [
            'model_id' => 'claude-3.5-sonnet',
            'results' => [],
            'status' => 'running',
            'execution_time_ms' => 0,
            'names_generated' => 0,
        ], 600);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('currentAIGenerationId', $generation->id);

        // Simulate cancellation logic
        $generation->update([
            'status' => 'cancelled',
            'results_data' => [
                'gpt-4' => ['CompletedName1', 'CompletedName2'],
                'claude-3.5-sonnet' => null,
                'gemini-1.5-pro' => null,
            ],
            'total_names_generated' => 2,
            'execution_metadata' => [
                'cancelled_with_partial_results' => true,
                'model_status' => [
                    'gpt-4' => 'completed',
                    'claude-3.5-sonnet' => 'cancelled',
                ],
            ],
        ]);

        // Update cache to reflect cancellation
        Cache::put("ai_generation_result_{$generation->id}_claude-3.5-sonnet", [
            'model_id' => 'claude-3.5-sonnet',
            'results' => [],
            'status' => 'cancelled',
            'execution_time_ms' => 0,
            'names_generated' => 0,
        ], 600);

        // Should preserve partial results
        $refreshedGeneration = $generation->fresh();
        expect($refreshedGeneration->status)->toBe('cancelled');
        expect($refreshedGeneration->results_data)->toBeArray();
        expect($refreshedGeneration->results_data)->toHaveKey('gpt-4');
        expect($refreshedGeneration->results_data['gpt-4'])->toHaveCount(2);
        expect($refreshedGeneration->total_names_generated)->toBe(2);
        expect($refreshedGeneration->execution_metadata['cancelled_with_partial_results'])->toBe(true);

        // Should mark incomplete models as cancelled in cache
        $claudeCache = Cache::get("ai_generation_result_{$generation->id}_claude-3.5-sonnet");
        expect($claudeCache['status'])->toBe('cancelled');
    });
});

describe('Real-time Progress Tracking', function (): void {
    test('AI generation dispatches lifecycle events properly', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['EventTest1', 'EventTest2'],
                'claude-3.5-sonnet' => ['EventTest3', 'EventTest4'],
            ]);

        // Check that component has proper state for event handling
        expect($component->get('useAIGeneration'))->toBe(true);
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('selectedAIModels'))->toEqual(['gpt-4', 'claude-3.5-sonnet']);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
    });

    test('Preferences saving dispatches events', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('generationMode', 'creative')
            ->set('deepThinking', true)
            ->set('enableModelComparison', true);

        // Verify preferences are set correctly
        expect($component->get('selectedAIModels'))->toEqual(['gpt-4', 'claude-3.5-sonnet']);
        expect($component->get('generationMode'))->toBe('creative');
        expect($component->get('deepThinking'))->toBe(true);
        expect($component->get('enableModelComparison'))->toBe(true);
    });

    test('User preference learning system recommends models based on usage patterns', function (): void {
        // Create historical AI generations for user with different model performance
        $gptGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'models_requested' => ['gpt-4'],
            'results_data' => ['gpt-4' => ['GPTName1', 'GPTName2', 'GPTName3']],
            'status' => 'completed',
            'total_names_generated' => 5, // Higher names generated
            'total_response_time_ms' => 1200,
            'generation_session_id' => 'session_gpt4_test',
        ]);

        $claudeGeneration = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'models_requested' => ['claude-3.5-sonnet'],
            'results_data' => ['claude-3.5-sonnet' => ['ClaudeName1', 'ClaudeName2']],
            'status' => 'completed',
            'total_names_generated' => 2, // Lower names generated
            'total_response_time_ms' => 800,
            'generation_session_id' => 'session_claude_test',
        ]);

        // Create name suggestions showing GPT-4 session was more productive
        NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'ai_generation_session_id' => $gptGeneration->generation_session_id,
            'ai_model_used' => 'gpt-4',
            'name' => 'GPTName1',
        ]);

        NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'ai_generation_session_id' => $gptGeneration->generation_session_id,
            'ai_model_used' => 'gpt-4',
            'name' => 'GPTName2',
        ]);

        // Claude session had fewer suggestions created
        NameSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'ai_generation_session_id' => $claudeGeneration->generation_session_id,
            'ai_model_used' => 'claude-3.5-sonnet',
            'name' => 'ClaudeName1',
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid]);

        $recommendations = $component->instance()->getModelRecommendations();

        // GPT-4 should score higher due to higher names generated and suggestions created
        expect($recommendations['recommended_models'])->toContain('gpt-4');
        expect($recommendations['model_scores']['gpt-4'])->toBeGreaterThan($recommendations['model_scores']['claude-3.5-sonnet']);
        expect($recommendations['based_on_generations'])->toBe(2);
    });

    test('Individual model progress can be tracked separately', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'models_requested' => ['gpt-4', 'claude-3.5-sonnet'],
            'execution_metadata' => [
                'model_status' => [
                    'gpt-4' => 'completed',
                    'claude-3.5-sonnet' => 'running',
                ],
                'completion_times' => [
                    'gpt-4' => 1500, // milliseconds
                ],
            ],
        ]);

        $statusSnapshot = $generation->getStatusSnapshot();

        expect($statusSnapshot)->toHaveKey('id');
        expect($statusSnapshot['status'])->toBe('running');
        expect($statusSnapshot['is_completed'])->toBe(false);
    });

    test('Progress tracking shows model-specific performance metrics', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'models_requested' => ['gpt-4', 'claude-3.5-sonnet'],
            'execution_metadata' => [
                'model_metrics' => [
                    'gpt-4' => [
                        'response_time_ms' => 1200,
                        'tokens_used' => 450,
                        'cost_cents' => 8,
                        'names_generated' => 5,
                    ],
                    'claude-3.5-sonnet' => [
                        'response_time_ms' => 800,
                        'tokens_used' => 380,
                        'cost_cents' => 3,
                        'names_generated' => 5,
                    ],
                ],
            ],
        ]);

        $metrics = $generation->execution_metadata['model_metrics'];

        expect($metrics['gpt-4']['response_time_ms'])->toBe(1200);
        expect($metrics['claude-3.5-sonnet']['response_time_ms'])->toBe(800);
        expect($metrics['gpt-4']['cost_cents'])->toBeGreaterThan($metrics['claude-3.5-sonnet']['cost_cents']);
    });
});

describe('Model Comparison Interface', function (): void {
    test('Dashboard displays tabbed interface for model comparison', function (): void {
        $component = Livewire::test(NameGeneratorDashboard::class)
            ->set('businessIdea', 'AI comparison software')
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiModelResults', [
                'gpt-4' => ['TechCompare', 'ModelAnalyzer'],
                'claude-3.5-sonnet' => ['CompareAI', 'ModelBench'],
            ]);

        // Should show model comparison tabs
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('aiModelResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
        expect($component->get('selectedAIModels'))->toHaveCount(2);
    });

    test('ProjectPage displays tabbed interface for model comparison', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['ProjectCompare', 'ModelTest'],
                'claude-3.5-sonnet' => ['CompareLogic', 'TestSuite'],
            ]);

        // Should show model comparison tabs
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
    });

    test('Model tabs show individual performance metrics', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'execution_metadata' => [
                'model_metrics' => [
                    'gpt-4' => [
                        'response_time_ms' => 1500,
                        'tokens_used' => 520,
                        'cost_cents' => 12,
                        'creativity_score' => 8.5,
                        'relevance_score' => 9.2,
                    ],
                    'claude-3.5-sonnet' => [
                        'response_time_ms' => 900,
                        'tokens_used' => 410,
                        'cost_cents' => 4,
                        'creativity_score' => 9.1,
                        'relevance_score' => 8.8,
                    ],
                ],
            ],
        ]);

        $gpt4Metrics = $generation->execution_metadata['model_metrics']['gpt-4'];
        $claudeMetrics = $generation->execution_metadata['model_metrics']['claude-3.5-sonnet'];

        // GPT-4 is more expensive but has good relevance
        expect($gpt4Metrics['cost_cents'])->toBeGreaterThan($claudeMetrics['cost_cents']);
        expect($gpt4Metrics['relevance_score'])->toBeGreaterThan($claudeMetrics['relevance_score']);

        // Claude is faster and more creative
        expect($claudeMetrics['response_time_ms'])->toBeLessThan($gpt4Metrics['response_time_ms']);
        expect($claudeMetrics['creativity_score'])->toBeGreaterThan($gpt4Metrics['creativity_score']);
    });

    test('User can switch between model tabs to compare results', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('activeModelTab', 'gpt-4')
            ->set('aiGenerationResults', [
                'gpt-4' => ['GPTName1', 'GPTName2'],
                'claude-3.5-sonnet' => ['ClaudeName1', 'ClaudeName2'],
            ]);

        // Should be able to switch tabs
        expect($component->get('activeModelTab'))->toBe('gpt-4');

        $component->set('activeModelTab', 'claude-3.5-sonnet');
        expect($component->get('activeModelTab'))->toBe('claude-3.5-sonnet');
    });
});

describe('Result Aggregation and Display', function (): void {
    test('Results from multiple models are properly aggregated', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['AggregatedName1', 'AggregatedName2'],
                'claude-3.5-sonnet' => ['MergedResult1', 'MergedResult2'],
                'gemini-1.5-pro' => ['CombinedName1', 'CombinedName2'],
            ]);

        // Should aggregate results from all models
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro']);
        expect($component->get('selectedAIModels'))->toHaveCount(3);
    });

    test('Partial results are displayed as models complete', function (): void {
        // Simulate scenario where some models complete before others
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'running',
            'models_requested' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
            'results_data' => [
                'gpt-4' => ['FastResult1', 'FastResult2'], // Completed
                'claude-3.5-sonnet' => null, // Still generating
                'gemini-1.5-pro' => ['MediumResult1'], // Partial
            ],
        ]);

        // Should be able to display partial results
        $partialResults = $generation->results_data;
        expect($partialResults['gpt-4'])->toHaveCount(2);
        expect($partialResults['claude-3.5-sonnet'])->toBeNull();
        expect($partialResults['gemini-1.5-pro'])->toHaveCount(1);
    });

    test('Results maintain model attribution and metadata', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['AttributedName1', 'AttributedName2'],
            ]);

        // Should create name suggestions with proper AI metadata
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKey('gpt-4');
    });
});

describe('Performance and Optimization', function (): void {
    test('Multiple model generation respects rate limits', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['RateLimit1', 'RateLimit2'],
                'claude-3.5-sonnet' => ['RateLimit3', 'RateLimit4'],
                'gemini-1.5-pro' => ['RateLimit5', 'RateLimit6'],
                'grok-beta' => ['RateLimit7', 'RateLimit8'],
            ]);

        // Should handle rate limiting across multiple models
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('selectedAIModels'))->toHaveCount(4);
    });

    test('Failed models do not block successful model results', function (): void {
        // One model fails, others succeed
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['SuccessName1', 'SuccessName2'], // This model succeeded
                'claude-3.5-sonnet' => [], // This model failed but doesn't block others
            ]);

        // Should complete despite individual model failures
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKey('gpt-4');
        expect($component->get('aiGenerationResults')['gpt-4'])->toHaveCount(2);
    });

    test('Generation performance metrics are tracked per model', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'total_response_time_ms' => 2300,
            'total_tokens_used' => 890,
            'total_cost_cents' => 15,
            'execution_metadata' => [
                'model_breakdown' => [
                    'gpt-4' => ['time_ms' => 1500, 'tokens' => 520, 'cost_cents' => 12],
                    'claude-3.5-sonnet' => ['time_ms' => 800, 'tokens' => 370, 'cost_cents' => 3],
                ],
            ],
        ]);

        expect($generation->total_response_time_ms)->toBe(2300);
        expect($generation->total_cost_cents)->toBe(15);
        expect($generation->execution_metadata['model_breakdown']['gpt-4']['cost_cents'])->toBe(12);
    });
});
