<?php

declare(strict_types=1);

use App\Livewire\Components\AIProgressIndicator;
use Livewire\Livewire;

describe('AIProgressIndicator Animation Tests', function (): void {
    test('progress bar has smooth transition classes', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component
            ->assertSee('transition-all', false)
            ->assertSee('duration-500', false)
            ->assertSee('ease-out', false);
    });

    test('progress bar uses gradient animation for deep thinking mode', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => true,
        ]);

        $component
            ->assertSee('bg-gradient-to-r', false)
            ->assertSee('from-purple-500', false)
            ->assertSee('via-violet-500', false)
            ->assertSee('to-purple-600', false);
    });

    test('progress bar uses solid color for normal mode', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->assertSee('bg-blue-500', false);
    });

    test('icon has pulsing animation in deep thinking mode', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => true,
        ]);

        $component->assertSee('animate-pulse', false);
    });

    test('completion state shows success styling', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->dispatch('ai-generation-complete');

        $component
            ->assertSet('isComplete', true)
            ->assertSet('progress', 100);
    });

    test('component supports dark mode with appropriate classes', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component
            ->assertSee('dark:bg-gray-700', false)
            ->assertSee('dark:text-gray-300', false)
            ->assertSee('dark:text-blue-400', false);
    });

    test('progress bar container has rounded corners', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->assertSee('rounded-full', false);
    });

    test('component is mobile responsive with full width', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->assertSee('w-full', false);
    });

    test('status text has proper spacing and alignment', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component
            ->assertSee('flex items-center justify-between', false)
            ->assertSee('gap-2', false);
    });

    test('progress bar width updates with animation', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        // Initial progress
        $component->assertSee('width: 0%', false);

        // Update progress
        $component->dispatch('ai-generation-progress', [
            'progress' => 50,
        ]);

        $component->assertSee('width: 50%', false);

        // Complete
        $component->dispatch('ai-generation-complete');

        $component->assertSee('width: 100%', false);
    });

    test('completion shows green success styling', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->dispatch('ai-generation-complete');

        $component
            ->assertSee('bg-green-500', false)
            ->assertSee('Complete!', false)
            ->assertSee('text-green-600', false);
    });

    test('component fades out when complete', function (): void {
        $component = Livewire::test(AIProgressIndicator::class);

        $component->dispatch('ai-generation-started', [
            'generation_id' => 1,
            'is_deep_thinking' => false,
        ]);

        $component->assertSee('opacity-100', false);

        $component->dispatch('ai-generation-complete');

        $component->assertSee('opacity-0', false);
    });
});
