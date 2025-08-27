<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Virtual Scrolling', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('initializes with correct virtual scrolling state', function (): void {
        Livewire::test(SessionSidebar::class)
            ->assertSet('offset', 0)
            ->assertSet('limit', 20)
            ->assertSet('hasMoreSessions', true)
            ->assertSet('isLoadingMore', false);
    });

    it('loads more sessions when loadMore is called', function (): void {
        // Create 50 sessions
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Initially shows first 20
        expect($component->get('sessions')->count())->toBe(20);
        expect($component->get('offset'))->toBe(0);
        expect($component->get('hasMoreSessions'))->toBe(true);

        // Call loadMore
        $component->call('loadMore');

        // Should now show 40 sessions (first batch + second batch)
        expect($component->get('sessions')->count())->toBe(40);
        expect($component->get('offset'))->toBe(20);
        expect($component->get('hasMoreSessions'))->toBe(true);
    });

    it('stops loading when no more sessions are available', function (): void {
        // Create exactly 25 sessions (1.25 pages)
        NamingSession::factory()->count(25)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Initially shows first 20
        expect($component->get('sessions')->count())->toBe(20);

        // Load more
        $component->call('loadMore');

        // Should now show all 25 sessions and hasMoreSessions should be false
        expect($component->get('sessions')->count())->toBe(25);
        expect($component->get('hasMoreSessions'))->toBe(false);
    });

    it('prevents multiple concurrent loadMore calls', function (): void {
        // Create 50 sessions
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Set isLoadingMore to true
        $component->set('isLoadingMore', true);

        $initialOffset = $component->get('offset');

        // Try to load more while already loading
        $component->call('loadMore');

        // Offset should not have changed
        expect($component->get('offset'))->toBe($initialOffset);
    });

    it('resets pagination when search query is updated', function (): void {
        // Create sessions
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Load more sessions first
        $component->call('loadMore');
        expect($component->get('offset'))->toBe(20);

        // Update search query
        $component->set('searchQuery', 'test');

        // Pagination should be reset
        expect($component->get('offset'))->toBe(0);
        expect($component->get('hasMoreSessions'))->toBe(true);
    });

    it('renders virtual scrolling trigger correctly', function (): void {
        // Create more sessions than initial limit to ensure hasMoreSessions is true
        NamingSession::factory()->count(50)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Should show load more trigger when hasMoreSessions is true
        $component->assertSee('x-ref="loadMore"', false);

        // Load more sessions
        $component->call('loadMore');
        
        // Still should have more sessions
        expect($component->get('hasMoreSessions'))->toBe(true);
        
        // Load more again to get to the end
        $component->call('loadMore');
        
        // Now should not have more sessions
        expect($component->get('hasMoreSessions'))->toBe(false);
    });

    it('shows loading state during loadMore operation', function (): void {
        // Create sessions
        NamingSession::factory()->count(30)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Set loading state
        $component->set('isLoadingMore', true);

        // Should show loading spinner
        $component->assertSee('Loading more sessions...');
        $component->assertSee('animate-spin');
    });

    it('maintains session grouping with virtual scrolling', function (): void {
        // Create sessions with different dates
        NamingSession::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(0), // Today
        ]);

        NamingSession::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(1), // Yesterday
        ]);

        NamingSession::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(7), // Last week
        ]);

        $component = Livewire::test(SessionSidebar::class);

        // Initially shows 20 sessions
        $groupedSessions = $component->get('groupedSessions');
        
        expect($groupedSessions)->toBeArray();
        expect(array_sum(array_map('count', $groupedSessions)))->toBe(20);

        // Load more
        $component->call('loadMore');

        // Should now show more sessions while maintaining grouping
        $newGroupedSessions = $component->get('groupedSessions');
        expect(array_sum(array_map('count', $newGroupedSessions)))->toBe(40);
    });

    it('handles virtual scrolling with starred filter', function (): void {
        // Create mix of starred and unstarred sessions
        NamingSession::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        NamingSession::factory()->count(30)->create([
            'user_id' => $this->user->id,
            'is_starred' => false,
        ]);

        $component = Livewire::test(SessionSidebar::class);

        // Enable starred filter
        $component->set('showStarredOnly', true);

        // Should only show starred sessions
        expect($component->get('sessions')->count())->toBe(15);
        expect($component->get('sessions')->every(fn($session) => $session->is_starred))->toBe(true);
    });

    it('handles virtual scrolling performance with large datasets', function (): void {
        // Create a large number of sessions
        NamingSession::factory()->count(200)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        
        $component = Livewire::test(SessionSidebar::class);
        
        // Initial load should be fast
        $component->assertSet('offset', 0);
        expect($component->get('sessions')->count())->toBe(20);

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        // Should load quickly even with large dataset
        expect($loadTime)->toBeLessThan(300); // Under 300ms

        // Load more should also be fast
        $startTime = microtime(true);
        $component->call('loadMore');
        $endTime = microtime(true);
        
        $loadMoreTime = ($endTime - $startTime) * 1000;
        expect($loadMoreTime)->toBeLessThan(200); // Under 200ms
    });
});