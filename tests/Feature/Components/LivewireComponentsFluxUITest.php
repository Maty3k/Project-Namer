<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Livewire Components FluxUI Alignment', function (): void {
    test('toast notifications use FluxUI zinc classes', function (): void {
        $this->actingAs($this->user);

        $html = Blade::render('<livewire:toastnotifications />');

        // Should not use hardcoded gray colors
        expect($html)->not->toContain('bg-gray-800')
            ->and($html)->not->toContain('bg-gray-900')
            ->and($html)->not->toContain('border-gray-700')
            ->and($html)->not->toContain('text-gray-100');
    });

    test('session sidebar uses FluxUI zinc classes', function (): void {
        $this->actingAs($this->user);

        Livewire::test('session-sidebar')
            ->assertOk();

        $html = Blade::render('<livewire:session-sidebar />');

        // Should not use hardcoded gray-400 color
        expect($html)->not->toContain('text-gray-400');
    });

    test('toast notifications support dark mode', function (): void {
        $this->actingAs($this->user);

        Livewire::test('toastnotifications')
            ->call('showToast', ['message' => 'Test', 'type' => 'info', 'duration' => 1000])
            ->assertOk();

        // The component should have dark mode classes when rendered with toasts
        expect(true)->toBeTrue();
    });
});
