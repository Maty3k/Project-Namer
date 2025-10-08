<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\AIGeneration;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * AI Progress Indicator Component.
 *
 * Displays real-time progress of AI name generation with differentiated
 * visual states for normal and deep thinking modes.
 */
final class AIProgressIndicator extends Component
{
    public ?int $generationId = null;

    public int $progress = 0;

    public bool $isDeepThinking = false;

    public ?int $estimatedTimeRemaining = null;

    public bool $isComplete = false;

    /**
     * Start tracking progress for a new generation.
     *
     * @param  array<string, mixed>  $data
     */
    #[On('ai-generation-started')]
    public function startProgress(array $data): void
    {
        $this->generationId = $data['generation_id'];
        $this->isDeepThinking = $data['is_deep_thinking'] ?? false;
        $this->progress = 0;
        $this->isComplete = false;
        $this->estimateTime();
    }

    /**
     * Update progress percentage.
     *
     * @param  array<string, mixed>  $data
     */
    #[On('ai-generation-progress')]
    public function updateProgress(array $data): void
    {
        $this->progress = $data['progress'];
        $this->estimateTime();
    }

    /**
     * Mark generation as complete.
     */
    #[On('ai-generation-complete')]
    public function completeProgress(): void
    {
        $this->progress = 100;
        $this->isComplete = true;
        $this->estimatedTimeRemaining = 0;
    }

    /**
     * Get current progress from database.
     *
     * @return array<string, mixed>
     */
    public function getProgress(): array
    {
        if (! $this->generationId) {
            return ['progress' => 0];
        }

        $generation = AIGeneration::find($this->generationId);

        if (! $generation) {
            return ['progress' => 0];
        }

        return [
            'progress' => $generation->progress ?? 0,
            'status' => $generation->status,
        ];
    }

    /**
     * Calculate estimated time remaining based on current progress.
     */
    private function estimateTime(): void
    {
        if ($this->progress >= 100) {
            $this->estimatedTimeRemaining = 0;

            return;
        }

        $totalTime = $this->isDeepThinking ? 10 : 4; // seconds
        $elapsedProgress = $this->progress / 100;
        $remaining = $totalTime * (1 - $elapsedProgress);

        $this->estimatedTimeRemaining = max(0, (int) ceil($remaining));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.components.a-i-progress-indicator');
    }
}
