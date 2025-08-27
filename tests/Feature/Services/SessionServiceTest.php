<?php

declare(strict_types=1);

use App\Models\NamingSession;
use App\Models\SessionResult;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SessionService', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->sessionService = new SessionService;
    });

    it('creates a new session with default values', function (): void {
        $sessionData = [
            'business_description' => 'AI-powered business name generator',
            'generation_mode' => 'creative',
        ];

        $session = $this->sessionService->createSession($this->user, $sessionData);

        expect($session)->toBeInstanceOf(NamingSession::class);
        expect($session->user_id)->toBe($this->user->id);
        expect($session->business_description)->toBe('AI-powered business name generator');
        expect($session->generation_mode)->toBe('creative');
        expect($session->is_active)->toBeTrue();
        expect($session->title)->toContain('AI-powered business name generator');
    });

    it('creates session with auto-generated title when none provided', function (): void {
        $sessionData = [
            'business_description' => 'A very long business description that should be truncated for the title display in the user interface',
        ];

        $session = $this->sessionService->createSession($this->user, $sessionData);

        expect($session->title)->not->toBeNull();
        expect(strlen((string) $session->title))->toBeLessThanOrEqual(53);
        expect($session->title)->toEndWith('...');
    });

    it('loads an existing session by id', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'last_accessed_at' => now()->subHour(),
        ]);

        $loadedSession = $this->sessionService->loadSession($this->user, $session->id);

        expect($loadedSession)->toBeInstanceOf(NamingSession::class);
        expect($loadedSession->id)->toBe($session->id);
        expect($loadedSession->fresh()->last_accessed_at->timestamp)
            ->toBeGreaterThan($session->last_accessed_at->timestamp);
    });

    it('returns null when loading non-existent session', function (): void {
        $loadedSession = $this->sessionService->loadSession($this->user, 'non-existent-id');

        expect($loadedSession)->toBeNull();
    });

    it('returns null when user tries to load another users session', function (): void {
        $otherUser = User::factory()->create();
        $session = NamingSession::factory()->create(['user_id' => $otherUser->id]);

        $loadedSession = $this->sessionService->loadSession($this->user, $session->id);

        expect($loadedSession)->toBeNull();
    });

    it('saves session data updates', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'business_description' => 'Updated business description',
            'generation_mode' => 'professional',
            'deep_thinking' => true,
        ];

        $updatedSession = $this->sessionService->saveSession($this->user, $session->id, $updateData);

        expect($updatedSession->business_description)->toBe('Updated business description');
        expect($updatedSession->generation_mode)->toBe('professional');
        expect($updatedSession->deep_thinking)->toBeTrue();
    });

    it('auto-updates title when business description changes', function (): void {
        $session = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'business_description' => 'Original description',
            'title' => 'Original description...',
        ]);

        $updateData = [
            'business_description' => 'Brand new business description for testing',
        ];

        $updatedSession = $this->sessionService->saveSession($this->user, $session->id, $updateData);

        expect($updatedSession->title)->toBe('Brand new business description for testing');
    });

    it('returns null when saving non-existent session', function (): void {
        $result = $this->sessionService->saveSession($this->user, 'non-existent-id', []);

        expect($result)->toBeNull();
    });

    it('deletes a session and its results', function (): void {
        $session = NamingSession::factory()->create(['user_id' => $this->user->id]);
        SessionResult::factory()->count(3)->create(['session_id' => $session->id]);

        expect(NamingSession::find($session->id))->not->toBeNull();
        expect(SessionResult::where('session_id', $session->id)->count())->toBe(3);

        $deleted = $this->sessionService->deleteSession($this->user, $session->id);

        expect($deleted)->toBeTrue();
        expect(NamingSession::find($session->id))->toBeNull();
        expect(SessionResult::where('session_id', $session->id)->count())->toBe(0);
    });

    it('returns false when deleting non-existent session', function (): void {
        $deleted = $this->sessionService->deleteSession($this->user, 'non-existent-id');

        expect($deleted)->toBeFalse();
    });

    it('returns false when user tries to delete another users session', function (): void {
        $otherUser = User::factory()->create();
        $session = NamingSession::factory()->create(['user_id' => $otherUser->id]);

        $deleted = $this->sessionService->deleteSession($this->user, $session->id);

        expect($deleted)->toBeFalse();
        expect(NamingSession::find($session->id))->not->toBeNull();
    });

    it('searches sessions by title and description', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'AI Startup Ideas',
            'business_description' => 'Innovative AI solutions for businesses',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'E-commerce Platform',
            'business_description' => 'Online shopping platform with modern features',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Mobile App Development',
            'business_description' => 'Creating innovative mobile applications',
        ]);

        $aiResults = $this->sessionService->searchSessions($this->user, 'AI');
        $innovativeResults = $this->sessionService->searchSessions($this->user, 'innovative');
        $platformResults = $this->sessionService->searchSessions($this->user, 'platform');

        expect($aiResults)->toHaveCount(1);
        expect($aiResults->first()->title)->toBe('AI Startup Ideas');

        expect($innovativeResults)->toHaveCount(2);

        expect($platformResults)->toHaveCount(1);
        expect($platformResults->first()->title)->toBe('E-commerce Platform');
    });

    it('returns empty results when searching non-existent content', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Session',
        ]);

        $results = $this->sessionService->searchSessions($this->user, 'nonexistent');

        expect($results)->toHaveCount(0);
    });

    it('filters sessions by generation mode', function (): void {
        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'generation_mode' => 'creative',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'generation_mode' => 'professional',
        ]);

        NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'generation_mode' => 'creative',
        ]);

        $creativeResults = $this->sessionService->filterSessions($this->user, ['generation_mode' => 'creative']);
        $professionalResults = $this->sessionService->filterSessions($this->user, ['generation_mode' => 'professional']);

        expect($creativeResults)->toHaveCount(2);
        expect($professionalResults)->toHaveCount(1);
    });

    it('filters sessions by starred status', function (): void {
        NamingSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_starred' => false,
        ]);

        NamingSession::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_starred' => true,
        ]);

        $starredResults = $this->sessionService->filterSessions($this->user, ['is_starred' => true]);
        $unstarredResults = $this->sessionService->filterSessions($this->user, ['is_starred' => false]);

        expect($starredResults)->toHaveCount(2);
        expect($unstarredResults)->toHaveCount(3);
    });

    it('duplicates session with new title', function (): void {
        $originalSession = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Session',
            'business_description' => 'Test business description',
            'is_starred' => true,
        ]);

        SessionResult::factory()->create(['session_id' => $originalSession->id]);

        $duplicatedSession = $this->sessionService->duplicateSession($this->user, $originalSession->id);

        expect($duplicatedSession)->toBeInstanceOf(NamingSession::class);
        expect($duplicatedSession->id)->not->toBe($originalSession->id);
        expect($duplicatedSession->title)->toBe('Copy of Original Session');
        expect($duplicatedSession->business_description)->toBe($originalSession->business_description);
        expect($duplicatedSession->is_starred)->toBeFalse();
        expect($duplicatedSession->results)->toHaveCount(1);
    });

    it('returns null when duplicating non-existent session', function (): void {
        $result = $this->sessionService->duplicateSession($this->user, 'non-existent-id');

        expect($result)->toBeNull();
    });

    it('gets user sessions with proper ordering', function (): void {
        $oldest = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        $newest = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $middle = NamingSession::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $sessions = $this->sessionService->getUserSessions($this->user);

        expect($sessions)->toHaveCount(3);
        expect($sessions->first()->id)->toBe($newest->id);
        expect($sessions->last()->id)->toBe($oldest->id);
    });

    it('gets user sessions with pagination', function (): void {
        NamingSession::factory()->count(25)->create(['user_id' => $this->user->id]);

        $firstPage = $this->sessionService->getUserSessions($this->user, limit: 10, offset: 0);
        $secondPage = $this->sessionService->getUserSessions($this->user, limit: 10, offset: 10);

        expect($firstPage)->toHaveCount(10);
        expect($secondPage)->toHaveCount(10);

        // Ensure no overlap between pages
        $firstIds = $firstPage->pluck('id')->toArray();
        $secondIds = $secondPage->pluck('id')->toArray();
        expect(array_intersect($firstIds, $secondIds))->toBeEmpty();
    });

    it('only returns sessions for the authenticated user', function (): void {
        $otherUser = User::factory()->create();

        NamingSession::factory()->count(3)->create(['user_id' => $this->user->id]);
        NamingSession::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $userSessions = $this->sessionService->getUserSessions($this->user);
        $otherUserSessions = $this->sessionService->getUserSessions($otherUser);

        expect($userSessions)->toHaveCount(3);
        expect($otherUserSessions)->toHaveCount(2);
    });
});
