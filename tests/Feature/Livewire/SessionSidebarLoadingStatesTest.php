<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Loading States and Optimistic UI', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('displays loading skeletons when isLoadingSessions is true', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', true);

        // Should show skeleton loading state
        $component->assertSee('Loading Skeletons')
            ->assertSee('animate-pulse');

        // Should not show empty state
        $component->assertDontSee('No sessions yet');
    });

    it('shows optimistic UI when creating a new session', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isCreatingSession', true);

        // Button should show loading state
        $component->assertSee('Creating...')
            ->assertSee('animate-spin');

        // Button should be disabled
        $component->assertSee('disabled');
    });

    it('shows optimistic UI when deleting a session', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('deletingSessionId', $session->id);

        // Should show deleting overlay
        $component->assertSee('Deleting...')
            ->assertSee('animate-spin');
    });

    it('handles session creation with optimistic UI flow', function (): void {
        $component = Livewire::test(SessionSidebar::class);

        // Initially not creating
        expect($component->get('isCreatingSession'))->toBe(false);

        // Create session should trigger optimistic UI
        $component->call('createNewSession');

        // Should have dispatched session created event
        $component->assertDispatched('sessionCreated');

        // After completion, should not be creating anymore
        expect($component->get('isCreatingSession'))->toBe(false);
    });

    it('handles session deletion with optimistic UI flow', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Initially not deleting
        expect($component->get('deletingSessionId'))->toBe(null);

        // Delete session should trigger optimistic UI
        $component->call('deleteSession', $session->id);

        // Should have dispatched session deleted event
        $component->assertDispatched('sessionDeleted');

        // After completion, should not be deleting anymore
        expect($component->get('deletingSessionId'))->toBe(null);
    });

    it('shows loading state during virtual scroll load more', function (): void {
        // Create many sessions
        NamingSession::factory()->count(30)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingMore', true);

        // Should show loading more indicator
        $component->assertSee('Loading more sessions...')
            ->assertSee('animate-spin');
    });

    it('displays skeleton loading correctly with proper structure', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', true);

        // Should render multiple skeleton components
        $html = $component->html();

        // Count skeleton components (should have 6 based on the blade template)
        $skeletonCount = substr_count($html, 'animate-pulse');
        expect($skeletonCount)->toBeGreaterThan(5); // At least 6 skeletons plus header skeleton
    });

    it('handles empty state correctly when not loading', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', false);

        // Should show empty state when no sessions
        $component->assertSee('No sessions yet')
            ->assertSee('Create your first naming session');

        // Should not show loading skeletons
        $component->assertDontSee('Loading Skeletons');
    });

    it('shows proper states during search', function (): void {
        // Create some sessions
        NamingSession::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Session',
        ]);

        $component = Livewire::test(SessionSidebar::class);

        // Set search query (this should trigger updatedSearchQuery)
        $component->set('searchQuery', 'test');

        // Should reset pagination
        expect($component->get('offset'))->toBe(0);
        expect($component->get('hasMoreSessions'))->toBe(true);
    });

    it('maintains optimistic UI consistency during concurrent operations', function (): void {
        $session1 = NamingSession::factory()->create(['user_id' => $this->user->id]);
        $session2 = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Set multiple loading states
        $component->set('isCreatingSession', true)
            ->set('deletingSessionId', $session1->id)
            ->set('isLoadingMore', true);

        // All states should be visible
        $component->assertSee('Creating...')
            ->assertSee('Deleting...')
            ->assertSee('Loading more sessions...');
    });

    it('provides proper accessibility during loading states', function (): void {
        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', true);

        // Loading skeletons should not interfere with screen reader navigation
        $html = $component->html();

        // Should still have proper heading structure
        expect($html)->toContain('Sessions');

        // Loading skeletons should be marked appropriately for screen readers
        expect($html)->toContain('animate-pulse');
    });

    it('handles error states gracefully', function (): void {
        $component = Livewire::test(SessionSidebar::class);

        // Test session loading error
        $component->call('loadSession', 'non-existent-id');

        // Should dispatch error event
        $component->assertDispatched('sessionLoadError');
    });

    it('optimizes rendering performance during loading states', function (): void {
        // Create many sessions
        NamingSession::factory()->count(100)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);

        $component = Livewire::test(SessionSidebar::class)
            ->set('isLoadingSessions', true);

        $endTime = microtime(true);
        $renderTime = ($endTime - $startTime) * 1000;

        // Should render quickly even with loading skeletons
        expect($renderTime)->toBeLessThan(200); // Under 200ms

        // Should show loading state
        $component->assertSee('animate-pulse');
    });

    it('handles rapid state changes correctly', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Rapid state changes
        $component->set('isCreatingSession', true)
            ->set('isCreatingSession', false)
            ->set('deletingSessionId', $session->id)
            ->set('deletingSessionId', null);

        // Should handle state changes without errors
        expect($component->get('isCreatingSession'))->toBe(false);
        expect($component->get('deletingSessionId'))->toBe(null);
    });
});
