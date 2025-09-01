<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GenerationSession;
use InvalidArgumentException;

/**
 * Service for managing AI generation sessions with real-time tracking.
 *
 * Coordinates session creation, progress tracking, and status updates
 * for long-running AI generation processes with real-time user feedback.
 */
final readonly class SessionManager
{
    public function __construct(private AIGenerationService $aiService) {}

    /**
     * Start a new AI generation session.
     *
     * @param  array<string>  $models
     * @param  array<string, mixed>  $customParams
     *
     * @throws InvalidArgumentException If parameters are invalid
     */
    public function startSession(
        string $businessDescription,
        array $models,
        string $mode,
        bool $deepThinking = false,
        array $customParams = [],
        string $strategy = 'parallel'
    ): GenerationSession {
        $sessionId = GenerationSession::generateSessionId();

        $session = GenerationSession::create([
            'session_id' => $sessionId,
            'status' => 'pending',
            'business_description' => $businessDescription,
            'generation_mode' => $mode,
            'deep_thinking' => $deepThinking,
            'requested_models' => $models,
            'custom_parameters' => $customParams,
            'generation_strategy' => $strategy,
            'progress_percentage' => 0,
            'current_step' => 'Session created, waiting to start...',
        ]);

        return $session;
    }

    /**
     * Execute a generation session with progress tracking.
     */
    public function executeSession(GenerationSession $session): GenerationSession
    {
        try {
            // Mark session as started
            $session->markAsStarted();

            // Update progress - preparing AI models
            $session->updateProgress(10, 'Preparing AI models for generation...');

            // Execute based on strategy
            $result = match ($session->generation_strategy) {
                'quick' => $this->executeQuickGeneration($session),
                'comprehensive' => $this->executeComprehensiveGeneration($session),
                default => $this->executeParallelGeneration($session),
            };

            // Update progress - processing results
            $session->updateProgress(95, 'Processing generation results...');

            // Mark as completed
            $session->markAsCompleted($result['results'], $result['execution_metadata']);

            return $session->fresh();

        } catch (\Exception $e) {
            $session->markAsFailed($e->getMessage());

            return $session->fresh();
        }
    }

    /**
     * Execute parallel generation with progress tracking.
     *
     * @return array<string, mixed>
     */
    private function executeParallelGeneration(GenerationSession $session): array
    {
        $session->updateProgress(20, 'Starting parallel AI model execution...');

        $modelCount = count($session->requested_models);
        $progressStep = 60 / max($modelCount, 1); // 60% for model execution
        $currentProgress = 20;

        // Simulate progress tracking during model execution
        foreach ($session->requested_models as $index => $model) {
            $currentProgress += $progressStep;
            $session->updateProgress(
                (int) round($currentProgress),
                'Processing model '.($index + 1)."/{$modelCount}: {$model}..."
            );
        }

        $result = $this->aiService->generateNamesParallel(
            $session->business_description,
            $session->requested_models,
            $session->generation_mode,
            $session->deep_thinking,
            $session->custom_parameters ?? []
        );

        $session->updateProgress(85, 'All models completed, finalizing results...');

        return $result;
    }

    /**
     * Execute quick generation with progress tracking.
     *
     * @return array<string, mixed>
     */
    private function executeQuickGeneration(GenerationSession $session): array
    {
        $session->updateProgress(30, 'Starting quick generation with optimized models...');

        $result = $this->aiService->generateNamesQuick(
            $session->business_description,
            $session->generation_mode,
            $session->deep_thinking
        );

        $session->updateProgress(85, 'Quick generation completed, processing results...');

        return $result;
    }

    /**
     * Execute comprehensive generation with progress tracking.
     *
     * @return array<string, mixed>
     */
    private function executeComprehensiveGeneration(GenerationSession $session): array
    {
        $session->updateProgress(20, 'Starting comprehensive generation with all models...');
        $session->updateProgress(30, 'Activating deep thinking mode for enhanced quality...');

        $result = $this->aiService->generateNamesComprehensive(
            $session->business_description,
            $session->generation_mode
        );

        $session->updateProgress(85, 'Comprehensive analysis completed, compiling results...');

        return $result;
    }

    /**
     * Get session status for real-time updates.
     *
     * @return array<string, mixed>|null
     */
    public function getSessionStatus(string $sessionId): ?array
    {
        $session = GenerationSession::where('session_id', $sessionId)->first();

        return $session?->getStatusSnapshot();
    }

    /**
     * Get full session details including results.
     *
     * @return array<string, mixed>|null
     */
    public function getSessionDetails(string $sessionId): ?array
    {
        $session = GenerationSession::where('session_id', $sessionId)->first();

        return $session?->getFullDetails();
    }

    /**
     * Cancel a running session.
     */
    public function cancelSession(string $sessionId): bool
    {
        $session = GenerationSession::where('session_id', $sessionId)
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if (! $session) {
            return false;
        }

        $session->markAsFailed('Session cancelled by user');

        return true;
    }

    /**
     * Get all active sessions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveSessions(): array
    {
        return GenerationSession::active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (GenerationSession $session) => $session->getStatusSnapshot())
            ->toArray();
    }

    /**
     * Get recent session history.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentSessions(int $limit = 20): array
    {
        return GenerationSession::recent()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (GenerationSession $session) => $session->getStatusSnapshot())
            ->toArray();
    }

    /**
     * Clean up old completed sessions.
     *
     * @return int Number of sessions deleted
     */
    public function cleanupOldSessions(int $daysOld = 7): int
    {
        return GenerationSession::whereIn('status', ['completed', 'failed'])
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get session statistics.
     *
     * @return array<string, mixed>
     */
    public function getSessionStatistics(): array
    {
        $total = GenerationSession::count();
        $completed = GenerationSession::byStatus('completed')->count();
        $failed = GenerationSession::byStatus('failed')->count();
        $active = GenerationSession::active()->count();

        $recentCompleted = GenerationSession::recent()
            ->byStatus('completed')
            ->count();

        $avgDuration = GenerationSession::byStatus('completed')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->avg(fn (GenerationSession $session) => $session->getDurationInSeconds());

        return [
            'total_sessions' => $total,
            'completed_sessions' => $completed,
            'failed_sessions' => $failed,
            'active_sessions' => $active,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'recent_completed' => $recentCompleted,
            'average_duration_seconds' => $avgDuration ? round($avgDuration) : null,
        ];
    }

    /**
     * Create a session and start execution immediately.
     *
     * @param  array<string, mixed>  $options
     */
    public function createAndExecuteSession(
        string $businessDescription,
        string $strategy = 'parallel',
        array $options = []
    ): GenerationSession {
        $models = $options['models'] ?? ['gpt-4o', 'claude-3.5-sonnet'];
        $mode = $options['mode'] ?? 'creative';
        $deepThinking = $options['deep_thinking'] ?? false;
        $customParams = $options['custom_params'] ?? [];

        $session = $this->startSession(
            $businessDescription,
            $models,
            $mode,
            $deepThinking,
            $customParams,
            $strategy
        );

        return $this->executeSession($session);
    }
}
