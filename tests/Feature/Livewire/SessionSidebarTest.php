<?php

declare(strict_types=1);

use App\Livewire\SessionSidebar;
use App\Models\NamingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('SessionSidebar Component', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('renders successfully', function (): void {
        Livewire::test(SessionSidebar::class)
            ->assertStatus(200);
    });

    it('displays user sessions in the sidebar', function (): void {
        $sessions = NamingSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Session',
        ]);

        Livewire::test(SessionSidebar::class)
            ->assertSee('Test Session')
            ->assertSee($sessions->first()->getPreviewText());
    });

    it('groups sessions by date correctly', function (): void {
        // Create sessions for different time periods
        $today = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Today Session',
            'created_at' => now(),
        ]);

        $yesterday = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Yesterday Session',
            'created_at' => now()->subDay(),
        ]);

        $lastWeek = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Last Week Session',
            'created_at' => now()->subDays(5),
        ]);

        Livewire::test(SessionSidebar::class)
            ->assertSee('Today')
            ->assertSee('Yesterday')
            ->assertSee('Previous 7 Days')
            ->assertSee('Today Session')
            ->assertSee('Yesterday Session')
            ->assertSee('Last Week Session');
    });

    it('creates new session when new button is clicked', function (): void {
        expect(NamingSession::count())->toBe(0);

        Livewire::test(SessionSidebar::class)
            ->call('createNewSession')
            ->assertDispatched('sessionCreated');

        expect(NamingSession::count())->toBe(1);
        $session = NamingSession::first();
        expect($session->user_id)->toBe($this->user->id);
        expect($session->title)->toContain('New Session');
    });

    it('loads session when clicked', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'last_accessed_at' => null,
        ]);

        Livewire::test(SessionSidebar::class)
            ->call('loadSession', $session->id)
            ->assertDispatched('sessionLoaded', ['sessionId' => $session->id]);

        expect($session->fresh()->last_accessed_at)->not->toBeNull();
    });

    it('deletes session with confirmation', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        expect(NamingSession::count())->toBe(1);

        Livewire::test(SessionSidebar::class)
            ->call('deleteSession', $session->id)
            ->assertDispatched('sessionDeleted');

        expect(NamingSession::count())->toBe(0);
    });

    it('duplicates session correctly', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Session',
        ]);

        expect(NamingSession::count())->toBe(1);

        Livewire::test(SessionSidebar::class)
            ->call('duplicateSession', $session->id)
            ->assertDispatched('sessionCreated');

        expect(NamingSession::count())->toBe(2);

        $duplicated = NamingSession::where('title', 'Copy of Original Session')->first();
        expect($duplicated)->not->toBeNull();
        expect($duplicated->user_id)->toBe($this->user->id);
    });

    it('toggles session star status', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'is_starred' => false,
        ]);

        Livewire::test(SessionSidebar::class)
            ->call('toggleStar', $session->id);

        expect($session->fresh()->is_starred)->toBeTrue();

        Livewire::test(SessionSidebar::class)
            ->call('toggleStar', $session->id);

        expect($session->fresh()->is_starred)->toBeFalse();
    });

    it('searches sessions by query', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'AI Startup Ideas',
            'business_description' => 'Innovative AI solutions',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'E-commerce Platform',
            'business_description' => 'Online shopping platform',
        ]);

        $component = Livewire::test(SessionSidebar::class)
            ->set('searchQuery', 'AI')
            ->assertSee('AI Startup Ideas')
            ->assertDontSee('E-commerce Platform');

        $component->set('searchQuery', 'platform')
            ->assertSee('E-commerce Platform')
            ->assertDontSee('AI Startup Ideas');
    });

    it('clears search when query is empty', function (): void {
        NamingSession::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::test(SessionSidebar::class)
            ->set('searchQuery', 'nonexistent')
            ->set('searchQuery', '')
            ->assertViewHas('groupedSessions');
    });

    it('renames session inline', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Old Title',
        ]);

        Livewire::test(SessionSidebar::class)
            ->call('startRename', $session->id)
            ->call('saveRename', $session->id, 'New Title');

        expect($session->fresh()->title)->toBe('New Title');
    });

    it('cancels rename operation', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        Livewire::test(SessionSidebar::class)
            ->call('startRename', $session->id)
            ->call('cancelRename');

        expect($session->fresh()->title)->toBe('Original Title');
    });

    it('filters starred sessions only', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Starred Session',
            'is_starred' => true,
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Regular Session',
            'is_starred' => false,
        ]);

        Livewire::test(SessionSidebar::class)
            ->call('toggleStarredFilter')
            ->assertSee('Starred Session')
            ->assertDontSee('Regular Session');
    });

    it('loads more sessions with pagination', function (): void {
        NamingSession::factory()->count(25)->create(['user_id' => $this->user->id]);

        $component = Livewire::test(SessionSidebar::class);

        // Should initially load first batch
        expect($component->viewData('sessions'))->toHaveCount(20);

        $component->call('loadMore');

        expect($component->viewData('sessions'))->toHaveCount(25);
    });

    it('toggles sidebar visibility', function (): void {
        Livewire::test(SessionSidebar::class)
            ->assertSet('isCollapsed', false)
            ->call('toggleSidebar')
            ->assertSet('isCollapsed', true)
            ->call('toggleSidebar')
            ->assertSet('isCollapsed', false);
    });

    it('only shows sessions for authenticated user', function (): void {
        $otherUser = User::factory()->create();

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'My Session',
        ]);

        NamingSession::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Session',
        ]);

        Livewire::test(SessionSidebar::class)
            ->assertSee('My Session')
            ->assertDontSee('Other User Session');
    });

    it('displays empty state when no sessions exist', function (): void {
        Livewire::test(SessionSidebar::class)
            ->assertSee('No sessions yet')
            ->assertSee('Create your first naming session');
    });

    it('handles session loading errors gracefully', function (): void {
        Livewire::test(SessionSidebar::class)
            ->call('loadSession', 'nonexistent-id')
            ->assertDispatched('sessionLoadError');
    });

    it('preserves focus mode state', function (): void {
        Livewire::test(SessionSidebar::class)
            ->call('toggleFocusMode')
            ->assertDispatched('focusModeToggled', ['enabled' => true]);
    });
});
