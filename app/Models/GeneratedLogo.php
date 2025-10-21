<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GeneratedLogo extends Model
{
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
     */
    public function logoGeneration(): BelongsTo
    {
        return $this->belongsTo(LogoGeneration::class);
    }

    /**
     * Get the full URL to the logo file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
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
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }
}
