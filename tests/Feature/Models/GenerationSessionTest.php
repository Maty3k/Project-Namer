<?php

declare(strict_types=1);

use App\Models\GenerationSession;

describe('Generation Session Model', function (): void {
    it('can generate unique session IDs', function (): void {
        $sessionId1 = GenerationSession::generateSessionId();
        $sessionId2 = GenerationSession::generateSessionId();

        expect($sessionId1)->toStartWith('session_');
        expect($sessionId2)->toStartWith('session_');
        expect($sessionId1)->not->toBe($sessionId2);
    });

    it('can create a session with all required fields', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => 'A test platform',
            'generation_mode' => 'creative',
            'deep_thinking' => true,
            'requested_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'custom_parameters' => ['temperature' => 0.8],
            'generation_strategy' => 'parallel',
        ]);

        expect($session)->toBeInstanceOf(GenerationSession::class);
        expect($session->status)->toBe('pending');
        expect($session->business_description)->toBe('A test platform');
        expect($session->deep_thinking)->toBe(true);
        expect($session->requested_models)->toBe(['gpt-4', 'claude-3.5-sonnet']);
        expect($session->custom_parameters)->toBe(['temperature' => 0.8]);
        expect($session->progress_percentage)->toBeIn([0, null]);
    });

    it('can check if session is in progress', function (): void {
        $pendingSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => 'Pending platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $runningSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Running platform',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        $completedSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Completed platform',
            'generation_mode' => 'brandable',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'comprehensive',
        ]);

        expect($pendingSession->isInProgress())->toBe(true);
        expect($runningSession->isInProgress())->toBe(true);
        expect($completedSession->isInProgress())->toBe(false);
    });

    it('can check if session is completed or failed', function (): void {
        $completedSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Completed platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $failedSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'failed',
            'business_description' => 'Failed platform',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        expect($completedSession->isCompleted())->toBe(true);
        expect($completedSession->hasFailed())->toBe(false);
        expect($failedSession->isCompleted())->toBe(false);
        expect($failedSession->hasFailed())->toBe(true);
    });

    it('can mark session as started', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => 'Starting platform',
            'generation_mode' => 'tech-focused',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        $session->markAsStarted();
        $session->refresh();

        expect($session->status)->toBe('running');
        expect($session->started_at)->not->toBeNull();
        expect($session->progress_percentage)->toBe(5);
        expect($session->current_step)->toBe('Initializing AI generation...');
    });

    it('can update session progress', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Progress platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        $session->updateProgress(50, 'Halfway through generation...');
        $session->refresh();

        expect($session->progress_percentage)->toBe(50);
        expect($session->current_step)->toBe('Halfway through generation...');

        // Test boundary conditions
        $session->updateProgress(-10, 'Negative test');
        $session->refresh();
        expect($session->progress_percentage)->toBe(0);

        $session->updateProgress(150, 'Over 100 test');
        $session->refresh();
        expect($session->progress_percentage)->toBe(100);
    });

    it('can mark session as completed with results', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Completing platform',
            'generation_mode' => 'professional',
            'deep_thinking' => true,
            'requested_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'generation_strategy' => 'comprehensive',
        ]);

        $results = ['gpt-4' => ['names' => ['TestName1', 'TestName2']]];
        $metadata = ['execution_time' => 5000, 'models_used' => 2];

        $session->markAsCompleted($results, $metadata);
        $session->refresh();

        expect($session->status)->toBe('completed');
        expect($session->results)->toBe($results);
        expect($session->execution_metadata)->toBe($metadata);
        expect($session->progress_percentage)->toBe(100);
        expect($session->current_step)->toBe('Generation completed successfully');
        expect($session->completed_at)->not->toBeNull();
    });

    it('can mark session as failed with error message', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Failing platform',
            'generation_mode' => 'brandable',
            'deep_thinking' => false,
            'requested_models' => ['grok-beta'],
            'generation_strategy' => 'quick',
        ]);

        $errorMessage = 'API quota exceeded';

        $session->markAsFailed($errorMessage);
        $session->refresh();

        expect($session->status)->toBe('failed');
        expect($session->error_message)->toBe($errorMessage);
        expect($session->current_step)->toBe('Generation failed');
        expect($session->completed_at)->not->toBeNull();
    });

    it('can calculate session duration', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Duration platform',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
            'started_at' => now()->subSeconds(120),
            'completed_at' => now(),
        ]);

        $duration = $session->getDurationInSeconds();

        expect($duration)->toBeInt();
        expect($duration)->toBeGreaterThanOrEqual(119);
        expect($duration)->toBeLessThanOrEqual(121);
    });

    it('returns null duration for sessions that never started', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => 'Never started platform',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        expect($session->getDurationInSeconds())->toBeNull();
    });

    it('can get status snapshot for real-time updates', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Snapshot platform',
            'generation_mode' => 'tech-focused',
            'deep_thinking' => true,
            'requested_models' => ['gpt-4', 'claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
            'progress_percentage' => 75,
            'current_step' => 'Processing results...',
            'started_at' => now()->subSeconds(30),
        ]);

        $snapshot = $session->getStatusSnapshot();

        expect($snapshot)->toBeArray();
        expect($snapshot)->toHaveKeys([
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
        expect($snapshot['session_id'])->toBe($session->session_id);
        expect($snapshot['status'])->toBe('running');
        expect($snapshot['progress_percentage'])->toBe(75);
        expect($snapshot['current_step'])->toBe('Processing results...');
        expect($snapshot['is_completed'])->toBe(false);
        expect($snapshot['has_failed'])->toBe(false);
        expect($snapshot['duration_seconds'])->toBeInt();
    });

    it('can get full session details', function (): void {
        $session = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Full details platform',
            'generation_mode' => 'brandable',
            'deep_thinking' => true,
            'requested_models' => ['gpt-4', 'gemini-1.5-pro'],
            'custom_parameters' => ['temperature' => 0.9],
            'results' => ['test' => 'results'],
            'execution_metadata' => ['time' => 1000],
            'generation_strategy' => 'comprehensive',
        ]);

        $details = $session->getFullDetails();

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
        expect($details['business_description'])->toBe('Full details platform');
        expect($details['generation_mode'])->toBe('brandable');
        expect($details['deep_thinking'])->toBe(true);
        expect($details['requested_models'])->toBe(['gpt-4', 'gemini-1.5-pro']);
        expect($details['results'])->toBe(['test' => 'results']);
    });

    it('can scope active sessions', function (): void {
        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'pending',
            'business_description' => 'Active pending',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'running',
            'business_description' => 'Active running',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Inactive completed',
            'generation_mode' => 'brandable',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'comprehensive',
        ]);

        $activeSessions = GenerationSession::active()->get();

        expect($activeSessions)->toHaveCount(2);
        expect($activeSessions->pluck('status')->toArray())->toContain('pending', 'running');
        expect($activeSessions->pluck('status')->toArray())->not->toContain('completed');
    });

    it('can scope sessions by status', function (): void {
        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Completed 1',
            'generation_mode' => 'creative',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'quick',
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Completed 2',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'failed',
            'business_description' => 'Failed 1',
            'generation_mode' => 'brandable',
            'deep_thinking' => false,
            'requested_models' => ['gpt-4'],
            'generation_strategy' => 'comprehensive',
        ]);

        $completedSessions = GenerationSession::byStatus('completed')->get();
        $failedSessions = GenerationSession::byStatus('failed')->get();

        expect($completedSessions)->toHaveCount(2);
        expect($failedSessions)->toHaveCount(1);
        expect($completedSessions->pluck('status')->unique()->toArray())->toBe(['completed']);
        expect($failedSessions->pluck('status')->unique()->toArray())->toBe(['failed']);
    });

    it('can scope recent sessions', function (): void {
        // Create recent session
        $recentSession = GenerationSession::create([
            'session_id' => GenerationSession::generateSessionId(),
            'status' => 'completed',
            'business_description' => 'Recent session test',
            'generation_mode' => 'professional',
            'deep_thinking' => false,
            'requested_models' => ['claude-3.5-sonnet'],
            'generation_strategy' => 'parallel',
        ]);

        $recentSessions = GenerationSession::recent()->get();

        expect($recentSessions->count())->toBeGreaterThanOrEqual(1);
        expect($recentSessions->pluck('business_description')->toArray())->toContain('Recent session test');
    });
});
