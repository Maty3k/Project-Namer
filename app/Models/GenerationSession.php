<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * AI generation session tracking model.
 *
 * Tracks the progress and status of AI name generation sessions
 * with real-time status updates and comprehensive metadata.
 *
 * @property int $id
 * @property string $session_id
 * @property string $status
 * @property string $business_description
 * @property string $generation_mode
 * @property bool $deep_thinking
 * @property array<array-key, mixed> $requested_models
 * @property array<array-key, mixed>|null $custom_parameters
 * @property array<array-key, mixed>|null $results
 * @property array<array-key, mixed>|null $execution_metadata
 * @property int $progress_percentage
 * @property string|null $current_step
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property string $generation_strategy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $user_id
 * @property int|null $project_id
 * @property array<array-key, mixed>|null $image_context_ids
 * @property int $used_image_context
 * @property int $context_image_count
 * @property-read \App\Models\User|null $user
 *
 * @method static Builder<static>|GenerationSession active()
 * @method static Builder<static>|GenerationSession byStatus(string $status)
 * @method static \Database\Factories\GenerationSessionFactory factory($count = null, $state = [])
 * @method static Builder<static>|GenerationSession newModelQuery()
 * @method static Builder<static>|GenerationSession newQuery()
 * @method static Builder<static>|GenerationSession query()
 * @method static Builder<static>|GenerationSession recent()
 * @method static Builder<static>|GenerationSession whereBusinessDescription($value)
 * @method static Builder<static>|GenerationSession whereCompletedAt($value)
 * @method static Builder<static>|GenerationSession whereContextImageCount($value)
 * @method static Builder<static>|GenerationSession whereCreatedAt($value)
 * @method static Builder<static>|GenerationSession whereCurrentStep($value)
 * @method static Builder<static>|GenerationSession whereCustomParameters($value)
 * @method static Builder<static>|GenerationSession whereDeepThinking($value)
 * @method static Builder<static>|GenerationSession whereErrorMessage($value)
 * @method static Builder<static>|GenerationSession whereExecutionMetadata($value)
 * @method static Builder<static>|GenerationSession whereGenerationMode($value)
 * @method static Builder<static>|GenerationSession whereGenerationStrategy($value)
 * @method static Builder<static>|GenerationSession whereId($value)
 * @method static Builder<static>|GenerationSession whereImageContextIds($value)
 * @method static Builder<static>|GenerationSession whereProgressPercentage($value)
 * @method static Builder<static>|GenerationSession whereProjectId($value)
 * @method static Builder<static>|GenerationSession whereRequestedModels($value)
 * @method static Builder<static>|GenerationSession whereResults($value)
 * @method static Builder<static>|GenerationSession whereSessionId($value)
 * @method static Builder<static>|GenerationSession whereStartedAt($value)
 * @method static Builder<static>|GenerationSession whereStatus($value)
 * @method static Builder<static>|GenerationSession whereUpdatedAt($value)
 * @method static Builder<static>|GenerationSession whereUsedImageContext($value)
 * @method static Builder<static>|GenerationSession whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class GenerationSession extends Model
{
    /** @use HasFactory<\Database\Factories\GenerationSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'business_description',
        'generation_mode',
        'deep_thinking',
        'requested_models',
        'custom_parameters',
        'results',
        'execution_metadata',
        'progress_percentage',
        'current_step',
        'started_at',
        'completed_at',
        'error_message',
        'generation_strategy',
        'project_id',
        'image_context_ids',
    ];

    protected function casts(): array
    {
        return [
            'deep_thinking' => 'boolean',
            'requested_models' => 'array',
            'custom_parameters' => 'array',
            'results' => 'array',
            'execution_metadata' => 'array',
            'progress_percentage' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'image_context_ids' => 'array',
        ];
    }

    /**
     * Generate a unique session ID.
     */
    public static function generateSessionId(): string
    {
        return 'session_'.Str::uuid()->toString();
    }

    /**
     * Scope to get active sessions (running or pending).
     *
     * @param  Builder<GenerationSession>  $query
     * @return Builder<GenerationSession>
     */
    protected function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'running']);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<GenerationSession>  $query
     * @return Builder<GenerationSession>
     */
    protected function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent sessions (last 24 hours).
     *
     * @param  Builder<GenerationSession>  $query
     * @return Builder<GenerationSession>
     */
    protected function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Check if session is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    /**
     * Check if session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if session has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark session as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'progress_percentage' => 5,
            'current_step' => 'Initializing AI generation...',
        ]);
    }

    /**
     * Update session progress.
     */
    public function updateProgress(int $percentage, string $currentStep): void
    {
        $this->update([
            'progress_percentage' => max(0, min(100, $percentage)),
            'current_step' => $currentStep,
        ]);
    }

    /**
     * Mark session as completed with results.
     *
     * @param  array<string, mixed>  $results
     * @param  array<string, mixed>|null  $executionMetadata
     */
    public function markAsCompleted(array $results, ?array $executionMetadata = null): void
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'execution_metadata' => $executionMetadata,
            'progress_percentage' => 100,
            'current_step' => 'Generation completed successfully',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark session as failed with error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'current_step' => 'Generation failed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Get session duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return (int) $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get session status for real-time updates.
     *
     * @return array<string, mixed>
     */
    public function getStatusSnapshot(): array
    {
        return [
            'session_id' => $this->session_id,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'current_step' => $this->current_step,
            'duration_seconds' => $this->getDurationInSeconds(),
            'is_completed' => $this->isCompleted(),
            'has_failed' => $this->hasFailed(),
            'error_message' => $this->error_message,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get full session details including results.
     *
     * @return array<string, mixed>
     */
    public function getFullDetails(): array
    {
        return array_merge($this->getStatusSnapshot(), [
            'business_description' => $this->business_description,
            'generation_mode' => $this->generation_mode,
            'deep_thinking' => $this->deep_thinking,
            'requested_models' => $this->requested_models,
            'generation_strategy' => $this->generation_strategy,
            'results' => $this->results,
            'execution_metadata' => $this->execution_metadata,
            'created_at' => $this->created_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ]);
    }

    /**
     * Get project images used as context for this session.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectImage>
     */
    public function getImageContexts(): Collection
    {
        if ($this->image_context_ids === null || empty($this->image_context_ids)) {
            return new Collection;
        }

        return ProjectImage::whereIn('id', $this->image_context_ids)->get();
    }

    /**
     * Get the user that owns this session.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
