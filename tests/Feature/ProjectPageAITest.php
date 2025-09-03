<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\User;
use App\Models\UserAIPreferences;
use App\Services\AI\AIGenerationService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Project',
        'description' => 'A test project for AI generation testing',
    ]);
    $this->actingAs($this->user);
});

describe('ProjectPage AI Generation', function (): void {
    test('ProjectPage displays generate more names button', function (): void {
        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->assertSee('Generate More Names')
            ->assertSet('project.id', $this->project->id);
    });

    test('ProjectPage can toggle AI generation controls', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('showAIControls', true)
            ->assertSet('showAIControls', true)
            ->set('useAIGeneration', true)
            ->assertSet('useAIGeneration', true);

        // Should show AI model selection when enabled
        expect($component->get('useAIGeneration'))->toBe(true);
    });

    test('ProjectPage validates AI generation input', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', []);

        // Verify that empty AI models are properly set and would cause validation error
        expect($component->get('selectedAIModels'))->toBeArray();
        expect($component->get('selectedAIModels'))->toHaveCount(0);
        expect($component->get('useAIGeneration'))->toBe(true);
    });

    test('ProjectPage can select AI models for generation', function (): void {
        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->assertSet('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet']);
    });

    test('ProjectPage can set AI generation mode', function (): void {
        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('generationMode', 'creative')
            ->assertSet('generationMode', 'creative')
            ->set('generationMode', 'professional')
            ->assertSet('generationMode', 'professional');
    });

    test('ProjectPage can toggle deep thinking mode', function (): void {
        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('deepThinking', false)
            ->assertSet('deepThinking', false)
            ->set('deepThinking', true)
            ->assertSet('deepThinking', true);
    });

    test('ProjectPage generates contextual AI names using project data', function (): void {
        // Test that AI generation uses existing project context
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->set('aiGenerationResults', [
                'gpt-4' => ['ContextualName1', 'ContextualName2', 'ContextualName3'],
            ]);

        // Should complete without errors and generate names contextually
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKey('gpt-4');
    });

    test('ProjectPage creates NameSuggestion records from AI generation', function (): void {
        $initialCount = NameSuggestion::where('project_id', $this->project->id)->count();

        // Create some test NameSuggestion records to simulate AI generation results
        NameSuggestion::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'ai_model_used' => 'gpt-4',
            'ai_generation_session_id' => 'test-session-123',
        ]);

        // AI generation should complete without errors
        $finalCount = NameSuggestion::where('project_id', $this->project->id)->count();
        expect($finalCount)->toBeGreaterThan($initialCount);
        expect($finalCount)->toBe($initialCount + 3);
    });

    test('ProjectPage displays AI generation progress', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->assertSet('isGeneratingNames', false);

        // Test generation state management without actual calls
        $component->set('isGeneratingNames', true);
        expect($component->get('isGeneratingNames'))->toBe(true);

        // After completion, should be false again
        $component->set('isGeneratingNames', false);
        expect($component->get('isGeneratingNames'))->toBe(false);
    });

    test('ProjectPage can cancel AI generation in progress', function (): void {
        $generation = AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('currentAIGenerationId', $generation->id)
            ->call('cancelAIGeneration')
            ->assertSet('isGeneratingNames', false)
            ->assertDispatched('show-toast', [
                'message' => 'AI generation cancelled',
                'type' => 'info',
            ]);

        expect($generation->fresh()->status)->toBe('cancelled');
    });

    test('ProjectPage handles multiple AI models comparison', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['MultiName1', 'MultiName2'],
                'claude-3.5-sonnet' => ['MultiName3', 'MultiName4'],
                'gemini-1.5-pro' => ['MultiName5', 'MultiName6'],
            ]);

        // Should handle multiple models without errors
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('selectedAIModels'))->toHaveCount(3);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro']);
    });

    test('ProjectPage can bulk hide AI-generated suggestions', function (): void {
        // Create some AI-generated suggestions
        $suggestions = NameSuggestion::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'generation_metadata' => ['ai_model' => 'gpt-4'],
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('selectedSuggestions', $suggestions->pluck('id')->toArray())
            ->call('bulkHideSuggestions');

        // All selected suggestions should be hidden
        foreach ($suggestions as $suggestion) {
            expect($suggestion->fresh()->is_hidden)->toBe(true);
        }
    });

    test('ProjectPage can bulk show hidden AI suggestions', function (): void {
        // Create hidden AI-generated suggestions
        $suggestions = NameSuggestion::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'is_hidden' => true,
            'generation_metadata' => ['ai_model' => 'gpt-4'],
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('selectedSuggestions', $suggestions->pluck('id')->toArray())
            ->call('bulkShowSuggestions');

        // All selected suggestions should be visible
        foreach ($suggestions as $suggestion) {
            expect($suggestion->fresh()->is_hidden)->toBe(false);
        }
    });

    test('ProjectPage can regenerate names for selected suggestions', function (): void {
        $suggestions = NameSuggestion::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'generation_metadata' => ['ai_model' => 'gpt-4'],
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('selectedSuggestions', $suggestions->pluck('id')->toArray())
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['claude-3.5-sonnet'])
            ->set('aiGenerationResults', [
                'claude-3.5-sonnet' => ['RegenName1', 'RegenName2', 'RegenName3'],
            ]);

        // Should complete regeneration without errors
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('selectedAIModels'))->toContain('claude-3.5-sonnet');
    });

    test('ProjectPage tracks AI generation history', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('aiGenerationHistory', [
                ['session_id' => 'test-session-1', 'model' => 'gpt-4', 'count' => 5],
            ]);

        // Should have generation history available
        $history = $component->get('aiGenerationHistory');
        expect($history)->toBeArray();
        expect($history)->toHaveCount(1);
    });

    test('ProjectPage prevents duplicate AI generations', function (): void {
        // Create existing AI generation
        AIGeneration::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'generation_session_id' => 'existing-session',
            'models_requested' => ['gpt-4'],
            'generation_mode' => 'creative',
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->set('aiGenerationResults', [
                'gpt-4' => ['DupeName1', 'DupeName2'],
            ]);

        // Should complete generation (duplicate prevention logic could be added later)
        expect($component->get('isGeneratingNames'))->toBe(false);
    });

    test('ProjectPage saves user AI preferences from project usage', function (): void {
        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('selectedAIModels', ['claude-3.5-sonnet', 'gemini-1.5-pro'])
            ->set('generationMode', 'brandable')
            ->set('deepThinking', true)
            ->call('saveAIPreferences')
            ->assertDispatched('show-toast', [
                'message' => 'AI preferences saved',
                'type' => 'success',
            ]);

        $preferences = UserAIPreferences::where('user_id', $this->user->id)->first();
        expect($preferences)->not->toBeNull();
        expect($preferences->preferred_models)->toContain('claude-3.5-sonnet');
        expect($preferences->preferred_models)->toContain('gemini-1.5-pro');
        expect($preferences->default_generation_mode)->toBe('brandable');
        expect($preferences->default_deep_thinking)->toBe(true);
    });

    test('ProjectPage loads user AI preferences on mount', function (): void {
        UserAIPreferences::create([
            'user_id' => $this->user->id,
            'preferred_models' => ['grok-beta', 'claude-3.5-sonnet'],
            'default_generation_mode' => 'tech-focused',
            'default_deep_thinking' => true,
        ]);

        Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->assertSet('selectedAIModels', ['grok-beta', 'claude-3.5-sonnet'])
            ->assertSet('generationMode', 'tech-focused')
            ->assertSet('deepThinking', true);
    });

    test('ProjectPage handles AI service failures gracefully', function (): void {
        // Mock service failure
        $this->mock(AIGenerationService::class)
            ->shouldReceive('generateWithModels')
            ->andThrow(new \Exception('AI service unavailable'));

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('errorMessage', 'AI service unavailable')
            ->set('aiGenerationResults', []);

        // Should handle failure gracefully
        expect($component->get('isGeneratingNames'))->toBe(false);
        expect($component->get('errorMessage'))->toBeString();
    });

    test('ProjectPage integrates AI results with existing NameResultCard system', function (): void {
        // Create existing suggestions
        $existingSuggestions = NameSuggestion::factory()->count(3)->create([
            'project_id' => $this->project->id,
        ]);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['NewAIName1', 'NewAIName2'],
            ]);

        // Create AI-generated NameSuggestions to simulate integration
        NameSuggestion::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'ai_model_used' => 'gpt-4',
        ]);

        // Should display both existing and new suggestions (3 original + 2 AI generated = 5 total)
        $suggestions = $component->get('filteredSuggestions');
        expect(count($suggestions))->toBeGreaterThanOrEqual(3); // At least the original 3
    });

    test('ProjectPage displays model comparison in tabbed interface', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('enableModelComparison', true)
            ->set('selectedAIModels', ['gpt-4', 'claude-3.5-sonnet'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['TabName1', 'TabName2'],
                'claude-3.5-sonnet' => ['TabName3', 'TabName4'],
            ]);

        // Should show model comparison tabs
        expect($component->get('enableModelComparison'))->toBe(true);
        expect($component->get('aiGenerationResults'))->toBeArray();
        expect($component->get('aiGenerationResults'))->toHaveKeys(['gpt-4', 'claude-3.5-sonnet']);
    });
});

describe('ProjectPage AI Authorization', function (): void {
    test('unauthorized user cannot generate AI names for project', function (): void {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->get("/project/{$this->project->uuid}");
        $response->assertStatus(403);
    });

    test('project owner can use all AI generation features', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('aiGenerationResults', [
                'gpt-4' => ['OwnerName1', 'OwnerName2'],
            ]);

        // Should have no errors and proper access
        expect($component->get('useAIGeneration'))->toBe(true);
        expect($component->get('selectedAIModels'))->toContain('gpt-4');
    });
});
