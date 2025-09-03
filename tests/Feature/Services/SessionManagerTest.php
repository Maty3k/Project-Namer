<?php

declare(strict_types=1);

use App\Models\GenerationSession;
use App\Services\SessionManager;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function (): void {
    $this->service = app(SessionManager::class);
});

describe('Session Manager', function (): void {
    it('can create a new generation session', function (): void {
        $session = $this->service->startSession(
            'A project management platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'professional',
            true,
            ['temperature' => 0.8],
            'parallel'
        );

        expect($session)->toBeInstanceOf(GenerationSession::class);
        expect($session->session_id)->toStartWith('session_');
        expect($session->status)->toBe('pending');
        expect($session->business_description)->toBe('A project management platform');
        expect($session->generation_mode)->toBe('professional');
        expect($session->deep_thinking)->toBe(true);
        expect($session->requested_models)->toBe(['gpt-4', 'claude-3.5-sonnet']);
        expect($session->custom_parameters)->toBe(['temperature' => 0.8]);
        expect($session->generation_strategy)->toBe('parallel');
        expect($session->progress_percentage)->toBe(0);
    });

    it('can execute a session with progress tracking', function (): void {
        $sessionResponse = "1. SessionFlow\n2. TrackCraft\n3. ProgressHub\n4. StatusCore\n5. SessionLab\n6. TrackForge\n7. ProgressStream\n8. StatusFlow\n9. SessionCraft\n10. TrackHub";

        Prism::fake([
            TextResponseFake::make()->withText($sessionResponse),
            TextResponseFake::make()->withText($sessionResponse),
        ]);

        $session = $this->service->startSession(
            'A session tracking platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'tech-focused'
        );

        $completedSession = $this->service->executeSession($session);

        expect($completedSession->status)->toBe('completed');
        expect($completedSession->progress_percentage)->toBe(100);
        expect($completedSession->current_step)->toBe('Generation completed successfully');
        expect($completedSession->started_at)->not->toBeNull();
        expect($completedSession->completed_at)->not->toBeNull();
        expect($completedSession->results)->not->toBeNull();
        expect($completedSession->execution_metadata)->not->toBeNull();
    });

    it('can execute quick generation strategy', function (): void {
        $quickResponse = "1. QuickSession\n2. FastTrack\n3. RapidFlow\n4. SpeedCore\n5. QuickLab\n6. FastForge\n7. RapidStream\n8. SpeedFlow\n9. QuickCraft\n10. FastHub";

        Prism::fake([
            TextResponseFake::make()->withText($quickResponse),
            TextResponseFake::make()->withText($quickResponse),
        ]);

        $session = $this->service->startSession(
            'A fast development platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'tech-focused',
            false,
            [],
            'quick'
        );

        $completedSession = $this->service->executeSession($session);

        expect($completedSession->status)->toBe('completed');
        expect($completedSession->generation_strategy)->toBe('quick');
        expect($completedSession->results)->not->toBeNull();
    });

    it('can execute comprehensive generation strategy', function (): void {
        $comprehensiveResponse = "1. ComprehensiveFlow\n2. FullStackCraft\n3. CompleteHub\n4. TotalCore\n5. UltimateLab\n6. MasterForge\n7. ComprehensiveStream\n8. FullFlow\n9. CompleteCraft\n10. TotalHub";

        Prism::fake([
            TextResponseFake::make()->withText($comprehensiveResponse),
            TextResponseFake::make()->withText($comprehensiveResponse),
            TextResponseFake::make()->withText($comprehensiveResponse),
            TextResponseFake::make()->withText($comprehensiveResponse),
        ]);

        $session = $this->service->startSession(
            'A comprehensive solution platform',
            ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'],
            'professional',
            true,
            [],
            'comprehensive'
        );

        $completedSession = $this->service->executeSession($session);

        expect($completedSession->status)->toBe('completed');
        expect($completedSession->generation_strategy)->toBe('comprehensive');
        expect($completedSession->deep_thinking)->toBe(true);
        expect($completedSession->results)->not->toBeNull();
    });

    it('handles session execution failures gracefully', function (): void {
        // Create session with invalid parameters to trigger failure
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => '', // Empty description should cause validation failure
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => [],
            'generation_strategy' => 'parallel',
            'progress_percentage' => 0,
            'current_step' => 'Session created',
        ]);

        $completedSession = $this->service->executeSession($session);

        expect($completedSession->status)->toBe('failed');
        expect($completedSession->error_message)->not->toBeNull();
        expect($completedSession->current_step)->toBe('Generation failed');
        expect($completedSession->completed_at)->not->toBeNull();
    });

    it('can retrieve session status for real-time updates', function (): void {
        $session = $this->service->startSession(
            'A status tracking platform',
            ['gpt-4'],
            'tech-focused'
        );

        $status = $this->service->getSessionStatus($session->session_id);

        expect($status)->toBeArray();
        expect($status)->toHaveKeys([
            'session_id',
            'status',
            'progress_percentage',
            'current_step',
            'duration_seconds',
            'is_completed',
            'has_failed',
            'error_message',
            'updated_at',
        ]);
        expect($status['session_id'])->toBe($session->session_id);
        expect($status['status'])->toBe('pending');
    });

    it('can retrieve full session details', function (): void {
        $session = $this->service->startSession(
            'A detailed tracking platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'professional',
            true,
            ['temperature' => 0.9]
        );

        $details = $this->service->getSessionDetails($session->session_id);

        expect($details)->toBeArray();
        expect($details)->toHaveKeys([
            'session_id',
            'status',
            'business_description',
            'generation_mode',
            'deep_thinking',
            'requested_models',
            'generation_strategy',
            'results',
            'execution_metadata',
            'created_at',
            'started_at',
            'completed_at',
        ]);
        expect($details['business_description'])->toBe('A detailed tracking platform');
        expect($details['deep_thinking'])->toBe(true);
        expect($details['requested_models'])->toBe(['gpt-4', 'claude-3.5-sonnet']);
    });

    it('can cancel a running session', function (): void {
        $session = $this->service->startSession(
            'A cancellable platform',
            ['gpt-4'],
            'creative'
        );

        $session->markAsStarted();

        $cancelled = $this->service->cancelSession($session->session_id);

        expect($cancelled)->toBe(true);

        $updatedSession = GenerationSession::where('session_id', $session->session_id)->first();
        expect($updatedSession->status)->toBe('failed');
        expect($updatedSession->error_message)->toContain('cancelled by user');
    });

    it('returns false when trying to cancel non-existent session', function (): void {
        $cancelled = $this->service->cancelSession('non-existent-session-id');

        expect($cancelled)->toBe(false);
    });

    it('can retrieve active sessions', function (): void {
        // Create multiple sessions with different statuses
        $activeSession1 = $this->service->startSession('Platform 1', ['gpt-4'], 'creative');
        $activeSession2 = $this->service->startSession('Platform 2', ['claude-3.5-sonnet'], 'professional');
        $activeSession2->markAsStarted();

        $completedSession = $this->service->startSession('Platform 3', ['gpt-4'], 'brandable');
        $completedSession->markAsCompleted(['test' => 'results']);

        $activeSessions = $this->service->getActiveSessions();

        expect($activeSessions)->toHaveCount(2);
        expect(collect($activeSessions)->pluck('session_id')->toArray())
            ->toContain($activeSession1->session_id)
            ->toContain($activeSession2->session_id)
            ->not->toContain($completedSession->session_id);
    });

    it('can retrieve recent session history', function (): void {
        // Create sessions
        $session1 = $this->service->startSession('Recent Platform 1', ['gpt-4'], 'creative');
        $session2 = $this->service->startSession('Recent Platform 2', ['claude-3.5-sonnet'], 'professional');

        $recentSessions = $this->service->getRecentSessions(5);

        expect($recentSessions)->toBeArray();
        expect(count($recentSessions))->toBeGreaterThanOrEqual(2);
        expect(collect($recentSessions)->pluck('session_id')->toArray())
            ->toContain($session1->session_id)
            ->toContain($session2->session_id);
    });

    it('can clean up old sessions', function (): void {
        // Test that cleanup method exists and returns integer
        $deletedCount = $this->service->cleanupOldSessions(7);

        expect($deletedCount)->toBeInt();
        expect($deletedCount)->toBeGreaterThanOrEqual(0); // Should be 0 or more (no old sessions to clean)
    });

    it('can generate session statistics', function (): void {
        // Create sessions with different statuses
        $this->service->startSession('Stats Platform 1', ['gpt-4'], 'creative');

        $completedSession = $this->service->startSession('Stats Platform 2', ['claude-3.5-sonnet'], 'professional');
        $completedSession->markAsCompleted(['test' => 'results']);

        $failedSession = $this->service->startSession('Stats Platform 3', ['gpt-4'], 'brandable');
        $failedSession->markAsFailed('Test failure');

        $stats = $this->service->getSessionStatistics();

        expect($stats)->toBeArray();
        expect($stats)->toHaveKeys([
            'total_sessions',
            'completed_sessions',
            'failed_sessions',
            'active_sessions',
            'success_rate',
            'recent_completed',
            'average_duration_seconds',
        ]);
        expect($stats['total_sessions'])->toBeGreaterThanOrEqual(3);
        expect($stats['completed_sessions'])->toBeGreaterThanOrEqual(1);
        expect($stats['failed_sessions'])->toBeGreaterThanOrEqual(1);
        expect($stats['active_sessions'])->toBeGreaterThanOrEqual(1);
        expect($stats['success_rate'])->toBeFloat();
    });

    it('can create and execute session immediately', function (): void {
        $immediateResponse = "1. ImmediateFlow\n2. InstantCraft\n3. QuickHub\n4. FastCore\n5. ImmediateLab\n6. InstantForge\n7. QuickStream\n8. FastFlow\n9. ImmediateCraft\n10. InstantHub";

        Prism::fake([
            TextResponseFake::make()->withText($immediateResponse),
            TextResponseFake::make()->withText($immediateResponse),
        ]);

        $session = $this->service->createAndExecuteSession(
            'An immediate execution platform',
            'parallel',
            [
                'models' => ['gpt-4', 'claude-3.5-sonnet'],
                'mode' => 'tech-focused',
                'deep_thinking' => true,
                'custom_params' => ['temperature' => 0.7],
            ]
        );

        expect($session->status)->toBe('completed');
        expect($session->business_description)->toBe('An immediate execution platform');
        expect($session->generation_mode)->toBe('tech-focused');
        expect($session->deep_thinking)->toBe(true);
        expect($session->requested_models)->toBe(['gpt-4', 'claude-3.5-sonnet']);
        expect($session->results)->not->toBeNull();
    });

    it('handles missing session gracefully', function (): void {
        $status = $this->service->getSessionStatus('non-existent-session');
        $details = $this->service->getSessionDetails('non-existent-session');

        expect($status)->toBeNull();
        expect($details)->toBeNull();
    });

    it('tracks session progress during execution', function (): void {
        $progressResponse = "1. ProgressFlow\n2. TrackCraft\n3. UpdateHub\n4. ProgressCore\n5. TrackLab\n6. UpdateForge\n7. ProgressStream\n8. TrackFlow\n9. UpdateCraft\n10. ProgressHub";

        Prism::fake([
            TextResponseFake::make()->withText($progressResponse),
            TextResponseFake::make()->withText($progressResponse),
        ]);

        $session = $this->service->startSession(
            'A progress tracking platform',
            ['gpt-4', 'claude-3.5-sonnet'],
            'professional'
        );

        // Mock progress updates by checking status at different points
        $initialStatus = $this->service->getSessionStatus($session->session_id);
        expect($initialStatus['progress_percentage'])->toBe(0);

        // Execute session
        $completedSession = $this->service->executeSession($session);

        // Check final status
        $finalStatus = $this->service->getSessionStatus($completedSession->session_id);
        expect($finalStatus['progress_percentage'])->toBe(100);
        expect($finalStatus['status'])->toBe('completed');
    });
});
