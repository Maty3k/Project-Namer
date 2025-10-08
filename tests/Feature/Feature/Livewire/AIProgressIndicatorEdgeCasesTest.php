<?php

declare(strict_types=1);

use App\Livewire\Components\AIProgressIndicator;
use App\Models\AIGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('AIProgressIndicator Edge Cases', function (): void {
    test('component handles generation failure gracefully', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'failed',
            'progress' => 50,
        ]);

        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => false,
        ]);

        // Component should still function normally
        $component
            ->assertSet('generationId', $generation->id)
            ->assertSet('progress', 0);

        // Even if generation fails, component should handle it
        $result = $component->instance()->getProgress();
        expect($result)->toBeArray()
            ->and($result['progress'])->toBe(50)
            ->and($result['status'])->toBe('failed');
    });

    test('component handles non-existent generation id', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->set('generationId', 99999);

        $result = $component->instance()->getProgress();

        expect($result)->toBeArray()
            ->and($result['progress'])->toBe(0);
    });

    test('component handles rapid completion (progress jumps to 100 immediately)', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        // Immediately complete
        $component->dispatch('ai-generation-complete');

        $component
            ->assertSet('progress', 100)
            ->assertSet('isComplete', true)
            ->assertSet('estimatedTimeRemaining', 0);
    });

    test('component handles slow generation with multiple progress updates', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $generation = AIGeneration::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'running',
            'progress' => 0,
        ]);

        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => $generation->id,
            'is_deep_thinking' => true,
        ]);

        // Simulate slow progress updates
        $progressSteps = [10, 20, 30, 40, 50, 60, 70, 80, 90, 95, 100];

        foreach ($progressSteps as $progress) {
            $generation->update(['progress' => $progress]);

            $component->dispatch('ai-generation-progress', [
                'progress' => $progress,
            ]);

            if ($progress < 100) {
                expect($component->instance()->estimatedTimeRemaining)->toBeGreaterThan(0);
            }
        }

        $component
            ->assertSet('progress', 100)
            ->assertSet('estimatedTimeRemaining', 0);
    });

    test('component handles missing is_deep_thinking parameter', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            // Missing is_deep_thinking parameter
        ]);

        $component
            ->assertSet('isDeepThinking', false)
            ->assertSet('generationId', 1);
    });

    test('component resets properly when new generation starts', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Start first generation
        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => true,
        ]);

        $component->dispatch('ai-generation-progress', [
            'progress' => 50,
        ]);

        $component->assertSet('progress', 50);

        // Start new generation
        $component->dispatch('ai-generation-started', [
            'generation_id' => 2,
            'is_deep_thinking' => false,
        ]);

        $component
            ->assertSet('generationId', 2)
            ->assertSet('progress', 0)
            ->assertSet('isDeepThinking', false)
            ->assertSet('isComplete', false);
    });

    test('component handles completion event without start event', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Dispatch complete without starting
        $component->dispatch('ai-generation-complete');

        $component
            ->assertSet('progress', 100)
            ->assertSet('isComplete', true)
            ->assertSet('estimatedTimeRemaining', 0);
    });

    test('component handles progress update without start event', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Dispatch progress without starting
        $component->dispatch('ai-generation-progress', [
            'progress' => 50,
        ]);

        $component->assertSet('progress', 50);
    });

    test('component calculates time correctly at various progress points', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Normal mode (4 seconds total)
        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        // At 0%, should have ~4 seconds remaining
        expect($component->instance()->estimatedTimeRemaining)->toBe(4);

        // At 50%, should have ~2 seconds remaining
        $component->dispatch('ai-generation-progress', ['progress' => 50]);
        expect($component->instance()->estimatedTimeRemaining)->toBe(2);

        // At 75%, should have ~1 second remaining
        $component->dispatch('ai-generation-progress', ['progress' => 75]);
        expect($component->instance()->estimatedTimeRemaining)->toBe(1);

        // At 100%, should have 0 seconds remaining
        $component->dispatch('ai-generation-progress', ['progress' => 100]);
        expect($component->instance()->estimatedTimeRemaining)->toBe(0);
    });

    test('component calculates time correctly for deep thinking mode', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Deep thinking mode (10 seconds total)
        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => true,
        ]);

        // At 0%, should have ~10 seconds remaining
        expect($component->instance()->estimatedTimeRemaining)->toBe(10);

        // At 50%, should have ~5 seconds remaining
        $component->dispatch('ai-generation-progress', ['progress' => 50]);
        expect($component->instance()->estimatedTimeRemaining)->toBe(5);

        // At 90%, should have ~1 second remaining
        $component->dispatch('ai-generation-progress', ['progress' => 90]);
        expect($component->instance()->estimatedTimeRemaining)->toBe(1);
    });

    test('component handles backward progress updates gracefully', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        // Progress forward
        $component->dispatch('ai-generation-progress', ['progress' => 75]);
        $component->assertSet('progress', 75);

        // Progress backward (shouldn't happen but test resilience)
        $component->dispatch('ai-generation-progress', ['progress' => 50]);
        $component->assertSet('progress', 50);

        // Component should still function
        expect($component->instance()->estimatedTimeRemaining)->toBeGreaterThan(0);
    });

    test('component visibility toggles correctly through lifecycle', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Initially has opacity-100 (default state)
        $component->assertSee('opacity-100', false);

        // Visible when started
        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);
        $component->assertSee('opacity-100', false);

        // Fades out when complete
        $component->dispatch('ai-generation-complete');
        $component->assertSee('opacity-0', false);
    });

    test('component handles null generation id in getProgress', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Don't set generationId
        $result = $component->instance()->getProgress();

        expect($result)->toBeArray()
            ->and($result['progress'])->toBe(0);
    });

    test('component has comprehensive ARIA attributes for screen readers', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->dispatch('ai-generation-progress', ['progress' => 50]);

        // Verify all essential ARIA attributes are present
        $component
            ->assertSee('role="progressbar"', false)
            ->assertSee('aria-valuenow="50"', false)
            ->assertSee('aria-valuemin="0"', false)
            ->assertSee('aria-valuemax="100"', false)
            ->assertSee('aria-label=', false);
    });

    test('component ARIA label changes based on mode', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        // Normal mode
        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->assertSee('AI Name Generation Progress', false);

        // Deep thinking mode
        $component->dispatch('ai-generation-started', [
            'generation_id' => 2,
            'is_deep_thinking' => true,
        ]);

        $component->assertSee('Deep Thinking Mode Progress', false);
    });

    test('component aria-valuenow updates with progress', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        // Check aria-valuenow at different progress points
        $component->assertSee('aria-valuenow="0"', false);

        $component->dispatch('ai-generation-progress', ['progress' => 25]);
        $component->assertSee('aria-valuenow="25"', false);

        $component->dispatch('ai-generation-progress', ['progress' => 75]);
        $component->assertSee('aria-valuenow="75"', false);

        $component->dispatch('ai-generation-complete');
        $component->assertSee('aria-valuenow="100"', false);
    });
});
