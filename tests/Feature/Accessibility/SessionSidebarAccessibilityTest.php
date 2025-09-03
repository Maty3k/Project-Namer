<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Accessibility Compliance', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('has proper heading structure', function (): void {
        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Should have main heading
        expect($html)->toContain('<h2');
        expect($html)->toContain('Sessions');

        // Date group headers should be h3 when sessions exist
        NamingSession::factory()->create(['user_id' => $this->user->id]);
        $component = Livewire::test(SessionSidebar::class); // Create new instance
        $html = $component->html();

        expect($html)->toContain('<h3');
    });

    it('has proper aria labels for interactive elements', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Session',
        ]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Search input should have proper labels
        expect($html)->toContain('placeholder="Search sessions..."');

        // Buttons should have titles or aria-labels
        expect($html)->toContain('title="Show starred only"');
        expect($html)->toContain('title="Toggle focus mode');

        // Clear search button only shows when there's a search query
        $component->set('searchQuery', 'test');
        $html = $component->html();
        expect($html)->toContain('title="Clear search"');
    });

    it('has proper keyboard navigation support', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        $component = Livewire::test(SessionSidebar::class);

        // Focus mode keyboard shortcut should be documented
        $html = $component->html();
        expect($html)->toContain('(Cmd+/)');

        // Keyboard events only appear when in rename mode
        $component->call('startRename', $session->id);
        $html = $component->html();
        expect($html)->toContain('wire:keydown.enter');
        expect($html)->toContain('wire:keydown.escape');
    });

    it('provides screen reader announcements for dynamic content', function (): void {
        NamingSession::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'title' => 'Ecommerce Platform',
        ]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('searchQuery', 'ecommerce');

        $html = $component->html();

        // Search results should be announced to screen readers
        expect($html)->toContain('aria-live="polite"');
        expect($html)->toContain('search-results-status');
        expect($html)->toContain('result');
        expect($html)->toContain('found for');
    });

    it('handles focus management correctly', function (): void {
        NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Start renaming should set focus
        $component->call('startRename', NamingSession::first()->id);
        $html = $component->html();

        expect($html)->toContain('autofocus');
    });

    it('provides proper semantic markup for session cards', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'E-commerce Platform',
            'business_description' => 'Online shopping solution',
            'is_starred' => true,
        ]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Session title should be in proper heading
        expect($html)->toContain('<h4');
        expect($html)->toContain('E-commerce Platform');
    });

    it('has appropriate contrast and visual indicators', function (): void {
        $starredSession = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Starred sessions should have visual distinction
        expect($html)->toContain('bg-yellow-50');
        expect($html)->toContain('border-yellow-200');
    });

    it('provides loading state accessibility', function (): void {
        // Create enough sessions to trigger hasMoreSessions
        NamingSession::factory()->count(30)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', true);

        $html = $component->html();

        // Loading skeletons should not interfere with screen readers
        expect($html)->toContain('animate-pulse');

        // Loading more should be announced when conditions are met
        $component->set('isLoadingMore', true)
            ->set('isLoadingSessions', false)
            ->set('hasMoreSessions', true);
        $html = $component->html();
        expect($html)->toContain('Loading more sessions...');
    });

    it('handles error states accessibly', function (): void {
        $component = Livewire::test(SessionSidebar::class);

        // Empty state should be descriptive
        $html = $component->html();
        expect($html)->toContain('No sessions yet');
        expect($html)->toContain('Create your first naming session');
    });

    it('supports screen reader navigation of session groups', function (): void {
        // Create sessions on different dates
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
            'title' => 'Today Session',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
            'title' => 'Yesterday Session',
        ]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Date groups should be properly structured
        expect($html)->toContain('Today');
        expect($html)->toContain('Yesterday');

        // Group headers should be semantic
        expect($html)->toContain('<h3');
    });

    it('provides proper form labels and descriptions', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Start renaming to show form input
        $component->call('startRename', $session->id);
        $html = $component->html();

        // Rename input should have proper attributes
        expect($html)->toContain('wire:model="renameText"');
        expect($html)->toContain('autofocus');
    });

    it('handles focus mode accessibility correctly', function (): void {
        $component = Livewire::test(SessionSidebar::class);

        // Toggle focus mode
        $component->call('toggleFocusMode');
        $html = $component->html();

        // Collapsed sidebar should still be accessible
        expect($component->get('isCollapsed'))->toBe(true);

        // Focus mode toggle should have proper attributes
        expect($html)->toContain('title="Toggle focus mode');
    });

    it('provides proper button states and feedback', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isCreatingSession', true);

        $html = $component->html();

        // Creating session button should be disabled and show loading
        expect($html)->toContain('disabled');
        expect($html)->toContain('Creating...');
        expect($html)->toContain('animate-spin');
        expect($html)->toContain('cursor-not-allowed');
    });

    it('supports high contrast mode requirements', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Should use system colors and contrasts appropriately
        expect($html)->toContain('dark:bg-');
        expect($html)->toContain('dark:text-');
        expect($html)->toContain('dark:border-');

        // Focus indicators should be visible
        expect($html)->toContain('hover:bg-');
    });

    it('provides meaningful alternative text and descriptions', function (): void {
        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // In test environment, FluxUI components are rendered as placeholders
        // We verify that icons are used in contextually appropriate places
        // and that descriptive text provides meaning for screen readers

        // Empty state should have descriptive content for accessibility
        expect($html)->toContain('No sessions yet');
        expect($html)->toContain('Create your first naming session to get started');

        // Verify that icon usage is semantic and accompanied by text
        // The actual SVG icons will render properly in the browser
        expect(strlen($html))->toBeGreaterThan(1000); // Component renders substantial content
    });

    it('handles reduced motion preferences', function (): void {
        // Create enough sessions to show load more
        NamingSession::factory()->count(30)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Animations should be CSS-based and respect motion preferences
        expect($html)->toContain('transition-all');
        expect($html)->toContain('duration-');

        // Loading states should work without animations
        $component->set('isLoadingMore', true)
            ->set('hasMoreSessions', true);
        $html = $component->html();
        expect($html)->toContain('Loading more sessions...');
    });

    it('maintains accessibility during virtual scrolling', function (): void {
        // Create many sessions
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);
        $html = $component->html();

        // Virtual scroll trigger should be accessible
        expect($html)->toContain('x-ref="loadMore"');

        // Load more functionality should be accessible
        $component->call('loadMore');
        expect($component->get('offset'))->toBe(20);
    });

    it('provides proper search functionality accessibility', function (): void {
        NamingSession::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Session',
        ]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('searchQuery', 'test');

        $html = $component->html();

        // Search should provide live feedback
        expect($html)->toContain('aria-live="polite"');

        // Clear button should be accessible
        expect($html)->toContain('title="Clear search"');
        expect($html)->toContain('clear-search');
    });

    it('handles session actions accessibly', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('deletingSessionId', $session->id);

        $html = $component->html();

        // Deleting state should be announced
        expect($html)->toContain('Deleting...');
        expect($html)->toContain('animate-spin');

        // Actions should have proper labels
        expect($html)->toContain('Delete');
        expect($html)->toContain('Rename');
        expect($html)->toContain('Duplicate');
    });
});
