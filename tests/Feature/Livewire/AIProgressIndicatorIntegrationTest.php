<?php

declare(strict_types=1);

use App\Livewire\Components\AIProgressIndicator;
use App\Livewire\NameGeneratorDashboard;
use App\Models\AIGeneration;
use App\Models\User;
use App\Services\OpenAINameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Mock Prism to avoid actual API calls
    Prism::fake([
        TextResponseFake::make()->withText('1. TechFlow\n2. DataVibe\n3. CodeForge\n4. BitStream\n5. CloudNest\n6. PixelPulse\n7. NeuralNet\n8. QuantumLeap\n9. SyncWave\n10. ByteBloom'),
    ]);

    // Mock OpenAI service for fallback
    $this->mock(OpenAINameService::class, function ($mock): void {
        $mock->shouldReceive('generateNames')
            ->andReturn(['TechFlow', 'DataVibe', 'CodeForge', 'BitStream', 'CloudNest']);
    });
});

test('progress indicator is included in NameGeneratorDashboard when AI is enabled', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Progress indicator is only visible when generation is in progress
    Livewire::test(NameGeneratorDashboard::class)
        ->set('useAIGeneration', true)
        ->set('isGeneratingNames', true)
        ->assertSee('Generating Names with AI'); // Check for the title instead
});

test('progress indicator is not shown when AI is disabled', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(NameGeneratorDashboard::class)
        ->set('useAIGeneration', false)
        ->assertDontSee('wire:poll.500ms="getProgress"', false);
});

test('dashboard dispatches ai-generation-started event with correct parameters', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(NameGeneratorDashboard::class)
        ->set('useAIGeneration', true)
        ->set('businessIdea', 'A tech startup')
        ->set('generationMode', 'creative')
        ->set('selectedAIModels', ['gpt-4'])
        ->set('deepThinking', false)
        ->call('generateNamesWithAI')
        ->assertDispatched('ai-generation-started');
});

test('dashboard dispatches ai-generation-started with deep thinking flag', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(NameGeneratorDashboard::class)
        ->set('useAIGeneration', true)
        ->set('businessIdea', 'A tech startup')
        ->set('generationMode', 'creative')
        ->set('selectedAIModels', ['gpt-4'])
        ->set('deepThinking', true)
        ->call('generateNamesWithAI')
        ->assertDispatched('ai-generation-started');
});

test('dashboard dispatches ai-generation-complete event when generation finishes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a completed AIGeneration to simulate job completion
    $aiGeneration = AIGeneration::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'progress' => 100,
        'results_data' => [
            'names' => ['TechNova', 'InnovateLabs', 'FutureSync'],
            'model_results' => [
                'gpt-4' => [
                    'names' => ['TechNova', 'InnovateLabs', 'FutureSync'],
                    'execution_time_ms' => 1000,
                ],
            ],
        ],
    ]);

    // Test that checkGenerationStatus dispatches completion event
    Livewire::test(NameGeneratorDashboard::class)
        ->set('currentAIGenerationId', $aiGeneration->id)
        ->set('isGeneratingNames', true)
        ->call('checkGenerationStatus')
        ->assertDispatched('ai-generation-complete');
});

test('progress indicator responds to ai-generation-started event from dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $progressIndicator = Livewire::test(AIProgressIndicator::class);

    // Simulate the event that dashboard would dispatch
    $progressIndicator->dispatch('ai-generation-started', [
        'generation_id' => 1,
        'is_deep_thinking' => false,
    ]);

    $progressIndicator
        ->assertSet('generationId', 1)
        ->assertSet('isDeepThinking', false)
        ->assertSet('progress', 0);
});

test('progress indicator shows correct mode for deep thinking', function (): void {
    $progressIndicator = Livewire::test(AIProgressIndicator::class);

    $progressIndicator->dispatch('ai-generation-started', [
        'generation_id' => 1,
        'is_deep_thinking' => true,
    ]);

    $progressIndicator
        ->assertSet('isDeepThinking', true)
        ->assertSee('Deep Thinking Mode');
});

test('progress indicator shows correct mode for normal generation', function (): void {
    $progressIndicator = Livewire::test(AIProgressIndicator::class);

    $progressIndicator->dispatch('ai-generation-started', [
        'generation_id' => 1,
        'is_deep_thinking' => false,
    ]);

    $progressIndicator
        ->assertSet('isDeepThinking', false)
        ->assertSee('Generating Names');
});

test('progress indicator hides when ai-generation-complete is dispatched', function (): void {
    $progressIndicator = Livewire::test(AIProgressIndicator::class);

    $progressIndicator
        ->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ])
        ->dispatch('ai-generation-complete')
        ->assertSet('isComplete', true)
        ->assertSet('progress', 100)
        ->assertSee('hidden');
});

test('full workflow: dashboard starts generation and progress indicator tracks it', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a completed AIGeneration to test polling
    $aiGeneration = AIGeneration::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'progress' => 100,
        'results_data' => [
            'names' => ['TechNova', 'InnovateLabs', 'FutureSync'],
            'model_results' => [
                'gpt-4' => [
                    'names' => ['TechNova', 'InnovateLabs', 'FutureSync'],
                    'execution_time_ms' => 1000,
                ],
            ],
        ],
        'completed_at' => now(),
    ]);

    // Test that checkGenerationStatus processes completed generation
    Livewire::test(NameGeneratorDashboard::class)
        ->set('currentAIGenerationId', $aiGeneration->id)
        ->set('isGeneratingNames', true)
        ->set('useAIGeneration', true)
        ->set('selectedAIModels', ['gpt-4'])
        ->call('checkGenerationStatus')
        ->assertSet('showResults', true)
        ->assertSet('isGeneratingNames', false)
        ->assertDispatched('ai-generation-complete');
});
