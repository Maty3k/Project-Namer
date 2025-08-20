<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Logo generation request model.
 *
 * Represents a user request to generate logos for a business name,
 * tracking the generation status and progress.
 *
 * @property int $id
 * @property string $session_id
 * @property string $business_name
 * @property string|null $business_description
 * @property string $status
 * @property int $total_logos_requested
 * @property int $logos_completed
 * @property string $api_provider
 * @property int $cost_cents
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GeneratedLogo> $generatedLogos
 * @property-read int|null $generated_logos_count
 *
 * @method static Builder<static>|LogoGeneration completed()
 * @method static Builder<static>|LogoGeneration failed()
 * @method static Builder<static>|LogoGeneration forSession(string $sessionId)
 * @method static Builder<static>|LogoGeneration newModelQuery()
 * @method static Builder<static>|LogoGeneration newQuery()
 * @method static Builder<static>|LogoGeneration pending()
 * @method static Builder<static>|LogoGeneration processing()
 * @method static Builder<static>|LogoGeneration query()
 * @method static Builder<static>|LogoGeneration whereApiProvider($value)
 * @method static Builder<static>|LogoGeneration whereBusinessDescription($value)
 * @method static Builder<static>|LogoGeneration whereBusinessName($value)
 * @method static Builder<static>|LogoGeneration whereCostCents($value)
 * @method static Builder<static>|LogoGeneration whereCreatedAt($value)
 * @method static Builder<static>|LogoGeneration whereErrorMessage($value)
 * @method static Builder<static>|LogoGeneration whereId($value)
 * @method static Builder<static>|LogoGeneration whereLogosCompleted($value)
 * @method static Builder<static>|LogoGeneration whereSessionId($value)
 * @method static Builder<static>|LogoGeneration whereStatus($value)
 * @method static Builder<static>|LogoGeneration whereTotalLogosRequested($value)
 * @method static Builder<static>|LogoGeneration whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class LogoGeneration extends Model
{
    protected $fillable = [
        'session_id',
        'business_name',
        'business_description',
        'status',
        'total_logos_requested',
        'logos_completed',
        'api_provider',
        'cost_cents',
        'error_message',
    ];

    protected $attributes = [
        'status' => 'pending',
        'total_logos_requested' => 12,
        'logos_completed' => 0,
        'api_provider' => 'openai',
        'cost_cents' => 0,
    ];

    protected function casts(): array
    {
        return [
            'total_logos_requested' => 'integer',
            'logos_completed' => 'integer',
            'cost_cents' => 'integer',
        ];
    }

    /**
     * Get the generated logos for this generation request.
     *
     * @return HasMany<GeneratedLogo>
     */
    public function generatedLogos(): HasMany
    {
        return $this->hasMany(GeneratedLogo::class);
    }

    /**
     * Scope to get generations for a specific session.
     *
     * @param  Builder<LogoGeneration>  $query
     * @return Builder<LogoGeneration>
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get pending generations.
     *
     * @param  Builder<LogoGeneration>  $query
     * @return Builder<LogoGeneration>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing generations.
     *
     * @param  Builder<LogoGeneration>  $query
     * @return Builder<LogoGeneration>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed generations.
     *
     * @param  Builder<LogoGeneration>  $query
     * @return Builder<LogoGeneration>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed generations.
     *
     * @param  Builder<LogoGeneration>  $query
     * @return Builder<LogoGeneration>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if the generation is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed' ||
               $this->logos_completed >= $this->total_logos_requested;
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentage(): int
    {
        if ($this->total_logos_requested === 0) {
            return 0;
        }

        return (int) round(($this->logos_completed / $this->total_logos_requested) * 100);
    }

    /**
     * Mark the generation as failed with an error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Increment the logos completed count.
     */
    public function incrementLogosCompleted(): void
    {
        $this->increment('logos_completed');
    }

    /**
     * Add to the cost tracking.
     */
    public function addCost(int $costCents): void
    {
        $this->increment('cost_cents', $costCents);
    }
}
