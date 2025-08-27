<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Search Functionality', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test sessions with different titles and descriptions
        $this->sessions = [
            NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'E-commerce Platform',
                'business_description' => 'Online marketplace for handmade crafts and artisan goods',
            ]),
            NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Food Delivery App',
                'business_description' => 'Mobile application for local restaurant delivery service',
            ]),
            NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'SaaS Analytics Tool',
                'business_description' => 'Business intelligence dashboard for small companies',
            ]),
            NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Fitness Tracker',
                'business_description' => 'Personal wellness and workout tracking mobile app',
            ]),
        ];
    });

    describe('Search Bar UI', function (): void {
        it('displays search input field', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('wire:model.live.debounce.300ms="searchQuery"')
                ->assertSeeHtml('placeholder="Search sessions..."')
                ->assertSeeHtml('type="search"');
        });

        it('shows search icon in input field', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('search-icon');
        });

        it('displays clear button when search has text', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'test search');

            $component->assertSeeHtml('wire:click="clearSearch"')
                ->assertSeeHtml('clear-search');
        });

        it('hides clear button when search is empty', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', '');

            $component->assertDontSeeHtml('clear-search');
        });
    });

    describe('Search Functionality', function (): void {
        it('searches sessions by title', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'E-commerce');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(1);
            expect($sessions->first()->title)->toBe('E-commerce Platform');
        });

        it('searches sessions by business description', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'mobile');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(2);
            expect($sessions->pluck('title')->toArray())->toContain('Food Delivery App', 'Fitness Tracker');
        });

        it('performs case-insensitive search', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'SAAS analytics');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(1);
            expect($sessions->first()->title)->toBe('SaaS Analytics Tool');
        });

        it('handles partial word matching', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'deliver');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(1);
            expect($sessions->first()->title)->toBe('Food Delivery App');
        });

        it('returns empty results for no matches', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'nonexistent search term');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(0);
        });

        it('returns all sessions when search is empty', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', '');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(4);
        });

        it('trims whitespace from search query', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', '  E-commerce  ');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(1);
            expect($sessions->first()->title)->toBe('E-commerce Platform');
        });
    });

    describe('Search Performance', function (): void {
        it('handles search with debouncing', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            // Verify the debounce attribute is set
            $component->assertSeeHtml('wire:model.live.debounce.300ms="searchQuery"');
        });

        it('searches efficiently with large dataset', function (): void {
            // Create 50 additional sessions
            NamingSession::factory(50)->create(['user_id' => $this->user->id]);

            $startTime = microtime(true);

            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'E-commerce');

            $endTime = microtime(true);
            $searchTime = $endTime - $startTime;

            // Search should complete within 0.5 seconds
            expect($searchTime)->toBeLessThan(0.5);
        });
    });

    describe('Search State Management', function (): void {
        it('preserves search query across component updates', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'analytics')
                ->call('$refresh');

            expect($component->get('searchQuery'))->toBe('analytics');
        });

        it('clears search when clearSearch method is called', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'test search')
                ->call('clearSearch');

            expect($component->get('searchQuery'))->toBe('');
        });

        it('updates grouped sessions when search changes', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            // Initially should have grouped sessions
            $groupedSessions = $component->viewData('groupedSessions');
            expect($groupedSessions)->toBeArray();
            expect(array_sum(array_map('count', $groupedSessions)))->toBe(4);

            // After search, should have filtered grouped sessions
            $component->set('searchQuery', 'E-commerce');
            $groupedSessions = $component->viewData('groupedSessions');
            expect(array_sum(array_map('count', $groupedSessions)))->toBe(1);
        });
    });

    describe('Search Accessibility', function (): void {
        it('includes proper ARIA labels for search input', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('aria-label="Search sessions"')
                ->assertSeeHtml('role="searchbox"');
        });

        it('provides screen reader feedback for search results', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'analytics');

            $component->assertSeeHtml('aria-live="polite"')
                ->assertSeeHtml('search-results-status');
        });

        it('includes keyboard navigation support', function (): void {
            $component = Livewire::test(SessionSidebar::class);

            $component->assertSeeHtml('wire:keydown.escape="clearSearch"');
        });
    });

    describe('Search Error Handling', function (): void {
        it('handles search with special characters', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', '@#$%^&*()');

            // Should not throw error and return empty results
            $sessions = $component->viewData('sessions');
            expect($sessions)->toHaveCount(0);
        });

        it('handles very long search queries', function (): void {
            $longQuery = str_repeat('test ', 100);

            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', $longQuery);

            // Should handle gracefully without errors
            expect($component->get('searchQuery'))->toBe($longQuery);
        });

        it('handles SQL injection attempts', function (): void {
            $maliciousQuery = "'; DROP TABLE naming_sessions; --";

            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', $maliciousQuery);

            // Should safely handle the query without SQL injection
            $sessions = $component->viewData('sessions');
            expect($sessions)->toHaveCount(0);

            // Verify sessions table still exists by checking count
            expect(NamingSession::count())->toBe(4);
        });
    });

    describe('Search Integration', function (): void {
        it('integrates with session service search method', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'platform');

            $sessions = $component->viewData('sessions');

            expect($sessions)->toHaveCount(1);
            expect($sessions->first()->title)->toBe('E-commerce Platform');
        });

        it('maintains search state when creating new sessions', function (): void {
            $component = Livewire::test(SessionSidebar::class)
                ->set('searchQuery', 'analytics');

            // Create a new session (this would be done via the dashboard)
            NamingSession::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'New Analytics Platform',
                'business_description' => 'Advanced analytics for enterprises',
            ]);

            // Refresh component to reflect new session
            $component->call('$refresh');
            $sessions = $component->viewData('sessions');

            // Should now show both matching sessions
            expect($sessions)->toHaveCount(2);
            expect($component->get('searchQuery'))->toBe('analytics');
        });
    });
});
