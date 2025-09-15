<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Focus Mode Functionality', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    describe('Focus Mode Toggle', function (): void {
        it('starts with sidebar visible by default', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            expect($component->get('isCollapsed'))->toBeFalse();
        });

        it('can toggle focus mode to collapse sidebar', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->call('toggleFocusMode');

            expect($component->get('isCollapsed'))->toBeTrue();
        });

        it('can toggle focus mode to expand sidebar', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true)
                ->call('toggleFocusMode');

            expect($component->get('isCollapsed'))->toBeFalse();
        });

        it('dispatches focusModeToggled event when toggled', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->call('toggleFocusMode');

            $component->assertDispatched('focusModeToggled', ['enabled' => true]);
        });

        it('dispatches correct event data when toggling off', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true)
                ->call('toggleFocusMode');

            $component->assertDispatched('focusModeToggled', ['enabled' => false]);
        });
    });

    describe('Focus Mode UI Elements', function (): void {
        it('shows focus mode toggle button in header', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('wire:click="toggleFocusMode"')
                ->assertSeeHtml('title="Toggle focus mode (Cmd+/)"');
        });

        it('applies collapsed styles when in focus mode', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true);

            $component->assertSeeHtml('w-0 opacity-0 overflow-hidden');
        });

        it('applies expanded styles when not in focus mode', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', false);

            $component->assertSeeHtml('w-80');
        });

        it('shows floating toggle button when collapsed', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true);

            $component->assertSeeHtml('fixed top-4 left-4')
                ->assertSeeHtml('wire:click="toggleFocusMode"')
                ->assertSeeHtml('title="Show sidebar (Cmd+/)"');
        });

        it('hides floating toggle button when expanded', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', false);

            $component->assertDontSeeHtml('fixed top-4 left-4');
        });
    });

    describe('Focus Mode Keyboard Shortcuts', function (): void {
        it('includes keyboard shortcut handling in template', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('x-on:keydown.window="handleKeydown"')
                ->assertSeeHtml('(event.metaKey || event.ctrlKey) && event.key === \'/\'')
                ->assertSeeHtml('$wire.toggleFocusMode()');
        });

        it('prevents default behavior for keyboard shortcut', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('event.preventDefault()');
        });

        it('updates Alpine data when focus mode changes', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('x-on:focus-mode-toggled="focusMode = $event.detail.enabled"');
        });
    });

    describe('Focus Mode State Management', function (): void {
        it('preserves focus mode state across component updates', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true)
                ->call('$refresh');

            expect($component->get('isCollapsed'))->toBeTrue();
        });

        it('handles multiple rapid toggles correctly', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            // Toggle multiple times rapidly
            $component->call('toggleFocusMode') // true
                ->call('toggleFocusMode')       // false
                ->call('toggleFocusMode')       // true
                ->call('toggleFocusMode');      // false

            expect($component->get('isCollapsed'))->toBeFalse();
        });

        it('initializes with correct Alpine data', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('focusMode: $wire.isCollapsed');
        });
    });

    describe('Focus Mode Accessibility', function (): void {
        it('includes proper ARIA labels for focus toggle', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('title="Toggle focus mode (Cmd+/)"');
        });

        it('includes proper ARIA labels for floating button', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true);

            $component->assertSeeHtml('title="Show sidebar (Cmd+/)"');
        });

        it('maintains keyboard navigation when collapsed', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true);

            // Floating button should be keyboard accessible
            $component->assertSeeHtml('wire:click="toggleFocusMode"');
        });
    });

    describe('Focus Mode Integration', function (): void {
        it('works with search functionality when collapsed', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true)
                ->set('searchQuery', 'test');

            // Should maintain search state even when collapsed
            expect($component->get('searchQuery'))->toBe('test');
        });

        it('dispatches loadSidebarState event on mount', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertDispatched('loadSidebarState');
        });

        it('maintains session creation functionality when collapsed', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true)
                ->call('createNewSession');

            $component->assertDispatched('sessionCreated');
        });
    });

    describe('Focus Mode Animation Classes', function (): void {
        it('includes transition classes for smooth animation', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('transition-all duration-300 ease-in-out');
        });

        it('includes hover effects for toggle buttons', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('hover:bg-gray-100 dark:hover:bg-gray-800');
        });

        it('includes proper z-index for floating button', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('isCollapsed', true);

            $component->assertSeeHtml('z-50');
        });
    });
});
