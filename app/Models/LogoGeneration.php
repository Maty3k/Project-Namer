<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Logo generation request model.
 *
 * Represents a user request to generate logos for a business name,
 * tracking the generation status and progress.
 *
 * @template TFactory of \Database\Factories\LogoGenerationFactory
 *
 * @property int $id
 * @property int|null $user_id
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
 * @property int $share_count
 * @property string|null $last_shared_at
 * @property int|null $progress
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $estimated_completion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GeneratedLogo<\Database\Factories\GeneratedLogoFactory>> $generatedLogos
 * @property-read int|null $generated_logos_count
 *
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> completed()
 * @method static \Database\Factories\LogoGenerationFactory factory($count = null, $state = [])
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> failed()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> forSession(string $sessionId)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> newModelQuery()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> newQuery()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> pending()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> processing()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> query()
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereApiProvider($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereBusinessDescription($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereBusinessName($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereCostCents($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereCreatedAt($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereErrorMessage($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereEstimatedCompletion($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereId($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereLastSharedAt($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereLogosCompleted($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereProgress($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereSessionId($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereShareCount($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereStartedAt($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereStatus($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereTotalLogosRequested($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereUpdatedAt($value)
 * @method static Builder<static>|\App\Models\LogoGeneration<TFactory> whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class LogoGeneration extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'business_name',
        'business_description',
        'status',
        'total_logos_requested',
        'logos_completed',
        'progress',
        'api_provider',
        'cost_cents',
        'error_message',
        'started_at',
        'estimated_completion',
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
            'progress' => 'integer',
            'cost_cents' => 'integer',
            'started_at' => 'datetime',
            'estimated_completion' => 'datetime',
        ];
    }

    /**
     * Get the generated logos for this generation request.
     *
     * @return HasMany<GeneratedLogo<\Database\Factories\GeneratedLogoFactory>, $this>
     */
    public function generatedLogos(): HasMany
    {
        /** @phpstan-ignore-next-line - Laravel relationship generic type resolution */
        return $this->hasMany(GeneratedLogo::class);
    }

    /**
     * Scope to get generations for a specific session.
     *
     * @param  Builder<LogoGeneration<TFactory>>  $query
     * @return Builder<LogoGeneration<TFactory>>
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get pending generations.
     *
     * @param  Builder<LogoGeneration<TFactory>>  $query
     * @return Builder<LogoGeneration<TFactory>>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing generations.
     *
     * @param  Builder<LogoGeneration<TFactory>>  $query
     * @return Builder<LogoGeneration<TFactory>>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed generations.
     *
     * @param  Builder<LogoGeneration<TFactory>>  $query
     * @return Builder<LogoGeneration<TFactory>>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed generations.
     *
     * @param  Builder<LogoGeneration<TFactory>>  $query
     * @return Builder<LogoGeneration<TFactory>>
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
