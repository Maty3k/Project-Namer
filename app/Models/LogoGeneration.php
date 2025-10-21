<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoGeneration extends Model
{
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
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'total_logos_requested' => 'integer',
            'logos_completed' => 'integer',
        ];
    }

    /**
     * Get the user that owns the logo generation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the generated logos for this generation.
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
}
