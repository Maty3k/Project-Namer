<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LogoGenerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $business_name
 * @property string|null $business_description
 * @property string $status
 * @property int $total_logos_requested
 * @property int $logos_completed
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_saved
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GeneratedLogo> $generatedLogos
 * @property-read int|null $generated_logos_count
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\LogoGenerationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereBusinessDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereIsSaved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereLogosCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereTotalLogosRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogoGeneration whereUserId($value)
 *
 * @mixin \Eloquent
 */
class LogoGeneration extends Model
{
    /** @use HasFactory<LogoGenerationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'status',
        'total_logos_requested',
        'logos_completed',
        'error_message',
        'is_saved',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'total_logos_requested' => 'integer',
            'logos_completed' => 'integer',
            'is_saved' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the logo generation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the generated logos for this generation.
     *
     * @return HasMany<GeneratedLogo, $this>
     */
    public function generatedLogos(): HasMany
    {
        return $this->hasMany(GeneratedLogo::class);
    }

    /**
     * Check if the generation is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the generation has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the generation is still processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Mark the generation as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark the generation as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
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
     * Increment the number of completed logos.
     */
    public function incrementCompletedLogos(): void
    {
        $this->increment('logos_completed');
    }

    /**
     * Get the completion progress percentage.
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_logos_requested === 0) {
            return 0;
        }

        return (int) (($this->logos_completed / $this->total_logos_requested) * 100);
    }

    /**
     * Toggle the saved status.
     */
    public function toggleSaved(): void
    {
        $this->update(['is_saved' => ! $this->is_saved]);
    }

    /**
     * Mark as saved.
     */
    public function markAsSaved(): void
    {
        $this->update(['is_saved' => true]);
    }

    /**
     * Mark as not saved.
     */
    public function markAsNotSaved(): void
    {
        $this->update(['is_saved' => false]);
    }
}
