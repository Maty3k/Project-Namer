<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI generation tracking model.
 *
 * Tracks individual AI generation requests with comprehensive metadata,
 * status management, and performance analytics.
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $generation_session_id
 * @property array<array-key, mixed> $models_requested
 * @property string $generation_mode
 * @property bool $deep_thinking
 * @property string $status
 * @property string $prompt_used
 * @property array<array-key, mixed>|null $results_data
 * @property array<array-key, mixed>|null $execution_metadata
 * @property int $total_names_generated
 * @property int|null $total_response_time_ms
 * @property int|null $total_tokens_used
 * @property int|null $total_cost_cents
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|AIGeneration active()
 * @method static Builder<static>|AIGeneration completed()
 * @method static \Database\Factories\AIGenerationFactory factory($count = null, $state = [])
 * @method static Builder<static>|AIGeneration failed()
 * @method static Builder<static>|AIGeneration forProject(int $projectId)
 * @method static Builder<static>|AIGeneration forUser(int $userId)
 * @method static Builder<static>|AIGeneration newModelQuery()
 * @method static Builder<static>|AIGeneration newQuery()
 * @method static Builder<static>|AIGeneration query()
 * @method static Builder<static>|AIGeneration recent()
 * @method static Builder<static>|AIGeneration whereCompletedAt($value)
 * @method static Builder<static>|AIGeneration whereCreatedAt($value)
 * @method static Builder<static>|AIGeneration whereDeepThinking($value)
 * @method static Builder<static>|AIGeneration whereErrorMessage($value)
 * @method static Builder<static>|AIGeneration whereExecutionMetadata($value)
 * @method static Builder<static>|AIGeneration whereFailedAt($value)
 * @method static Builder<static>|AIGeneration whereGenerationMode($value)
 * @method static Builder<static>|AIGeneration whereGenerationSessionId($value)
 * @method static Builder<static>|AIGeneration whereId($value)
 * @method static Builder<static>|AIGeneration whereModelsRequested($value)
 * @method static Builder<static>|AIGeneration whereProjectId($value)
 * @method static Builder<static>|AIGeneration wherePromptUsed($value)
 * @method static Builder<static>|AIGeneration whereResultsData($value)
 * @method static Builder<static>|AIGeneration whereStartedAt($value)
 * @method static Builder<static>|AIGeneration whereStatus($value)
 * @method static Builder<static>|AIGeneration whereTotalCostCents($value)
 * @method static Builder<static>|AIGeneration whereTotalNamesGenerated($value)
 * @method static Builder<static>|AIGeneration whereTotalResponseTimeMs($value)
 * @method static Builder<static>|AIGeneration whereTotalTokensUsed($value)
 * @method static Builder<static>|AIGeneration whereUpdatedAt($value)
 * @method static Builder<static>|AIGeneration whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class AIGeneration extends Model
{
    /** @use HasFactory<\Database\Factories\AIGenerationFactory> */
    use HasFactory;

    protected $table = 'ai_generations';

    protected $fillable = [
        'project_id',
        'user_id',
        'generation_session_id',
        'models_requested',
        'generation_mode',
        'deep_thinking',
        'status',
        'prompt_used',
        'results_data',
        'execution_metadata',
        'total_names_generated',
        'total_response_time_ms',
        'total_tokens_used',
        'total_cost_cents',
        'started_at',
        'completed_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'models_requested' => 'array',
            'deep_thinking' => 'boolean',
            'results_data' => 'array',
            'execution_metadata' => 'array',
            'total_names_generated' => 'integer',
            'total_response_time_ms' => 'integer',
            'total_tokens_used' => 'integer',
            'total_cost_cents' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns the AI generation.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that owns the AI generation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active generations (pending or running).
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'running']);
    }

    /**
     * Scope to get completed generations.
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed generations.
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get recent generations (last 24 hours).
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Scope to get generations for a specific project.
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get generations for a specific user.
     *
     * @param  Builder<AIGeneration>  $query
     * @return Builder<AIGeneration>
     */
    protected function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if generation is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    /**
     * Check if generation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if generation has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark generation as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark generation as completed.
     *
     * @param  array<string, mixed>  $results
     * @param  array<string, mixed>|null  $metadata
     */
    public function markAsCompleted(array $results, ?array $metadata = null): void
    {
        $this->update([
            'status' => 'completed',
            'results_data' => $results,
            'execution_metadata' => $metadata,
            'completed_at' => now(),
            'total_names_generated' => count($results['names'] ?? []),
        ]);
    }

    /**
     * Mark generation as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Get generation duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();

        return (int) $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get generation status for real-time updates.
     *
     * @return array<string, mixed>
     */
    public function getStatusSnapshot(): array
    {
        return [
            'id' => $this->id,
            'generation_session_id' => $this->generation_session_id,
            'status' => $this->status,
            'total_names_generated' => $this->total_names_generated,
            'duration_seconds' => $this->getDurationInSeconds(),
            'is_completed' => $this->isCompleted(),
            'has_failed' => $this->hasFailed(),
            'error_message' => $this->error_message,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Check if this generation can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->isInProgress();
    }

    /**
     * Check if this generation can be deleted by a specific user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id && $this->canBeDeleted();
    }

    /**
     * Delete this AI generation with proper cleanup of associated records.
     */
    public function deleteWithCleanup(): bool
    {
        if (! $this->canBeDeleted()) {
            return false;
        }

        try {
            \DB::transaction(function (): void {
                // Delete associated name suggestions
                NameSuggestion::where('ai_generation_session_id', $this->generation_session_id)->delete();

                // Clear related cache entries
                $this->clearRelatedCache();

                // Delete the generation record
                $this->delete();
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete AI generation with cleanup', [
                'generation_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bulk delete multiple AI generations with proper cleanup.
     *
     * @param  array<int>  $generationIds
     * @return int Number of generations successfully deleted
     */
    public static function bulkDeleteWithCleanup(array $generationIds, User $user): int
    {
        $deletedCount = 0;

        try {
            \DB::transaction(function () use ($generationIds, $user, &$deletedCount): void {
                $generations = self::whereIn('id', $generationIds)
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['pending', 'running'])
                    ->get();

                foreach ($generations as $generation) {
                    // Delete associated name suggestions
                    NameSuggestion::where('ai_generation_session_id', $generation->generation_session_id)->delete();

                    // Clear related cache
                    $generation->clearRelatedCache();

                    // Delete the generation
                    $generation->delete();
                    $deletedCount++;
                }
            });
        } catch (\Exception $e) {
            \Log::error('Failed to bulk delete AI generations', [
                'generation_ids' => $generationIds,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $deletedCount;
    }

    /**
     * Clear cache entries related to this generation.
     */
    protected function clearRelatedCache(): void
    {
        $cacheKeys = [
            "ai_generation_{$this->id}",
            "ai_generation_results_{$this->generation_session_id}",
            "project_generations_{$this->project_id}",
            "user_generations_{$this->user_id}",
        ];

        foreach ($cacheKeys as $key) {
            \Cache::forget($key);
        }
    }
}
