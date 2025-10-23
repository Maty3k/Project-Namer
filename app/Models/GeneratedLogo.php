<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GeneratedLogoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $logo_generation_id
 * @property string|null $file_path
 * @property string $style
 * @property string|null $prompt
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\LogoGeneration $logoGeneration
 * @property-read mixed $url
 *
 * @method static \Database\Factories\GeneratedLogoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereLogoGenerationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo wherePrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeneratedLogo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GeneratedLogo extends Model
{
    /** @use HasFactory<GeneratedLogoFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'logo_generation_id',
        'file_path',
        'style',
        'prompt',
        'status',
        'error_message',
    ];

    /**
     * Get the logo generation that owns this logo.
     *
     * @return BelongsTo<LogoGeneration, $this>
     */
    public function logoGeneration(): BelongsTo
    {
        return $this->belongsTo(LogoGeneration::class);
    }

    /**
     * Get the full URL to the logo file.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => Storage::disk('public')->url($this->file_path));
    }

    /**
     * Check if the logo generation is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the logo generation has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the logo as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark the logo as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark the logo as failed with an error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Delete the logo file from storage.
     */
    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}
