<?php

declare(strict_types=1);

use App\Livewire\ProjectPage;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Project',
        'description' => 'A test project for generation confirmation testing',
    ]);
    $this->actingAs($this->user);
});

describe('Generation Confirmation', function (): void {
    test('clicking generation mode shows confirmation instead of auto-generating', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->call('toggleGenerationMode', 'creative');

        // Should set the generation mode
        $component->assertSet('generationMode', 'creative');

        // Should dispatch confirmation event instead of starting generation
        $component->assertDispatched('show-generation-confirmation', [
            'mode' => 'creative',
            'message' => 'Generate names using creative style?',
        ]);

        // Should NOT be generating yet
        $component->assertSet('isGeneratingNames', false);
    });

    test('confirming generation calls the method without errors', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->call('confirmGeneration');

        // Should call the method without errors
        $component->assertStatus(200);
    });

    test('canceling generation resets mode', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->call('cancelGeneration');

        // Should reset generation mode
        $component->assertSet('generationMode', '');
    });

    test('auto-generation trigger shows confirmation instead of generating', function (): void {
        // Set up session for auto-generation
        session()->put('auto_generated_'.$this->project->id, true);

        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->call('handleAutoGeneration');

        // Should dispatch confirmation event instead of starting generation
        $component->assertDispatched('show-generation-confirmation', [
            'mode' => 'creative',
            'message' => 'Generate names using creative style?',
        ]);

        // Should NOT be generating yet
        $component->assertSet('isGeneratingNames', false);
    });

    test('deselecting mode does not show confirmation', function (): void {
        $component = Livewire::test(ProjectPage::class, ['uuid' => $this->project->uuid])
            ->set('useAIGeneration', true)
            ->set('selectedAIModels', ['gpt-4'])
            ->set('generationMode', 'creative')
            ->call('toggleGenerationMode', 'creative'); // Click same mode to deselect

        // Should deselect the mode
        $component->assertSet('generationMode', '');

        // Should NOT dispatch confirmation event
        $component->assertNotDispatched('show-generation-confirmation');
    });
});
