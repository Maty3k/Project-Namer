<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Export model for managing file exports.
 *
 * Handles creation and management of exported files in various formats
 * (PDF, CSV, JSON) with download tracking and expiration support.
 *
 * @property int $id
 * @property string $uuid
 * @property string $exportable_type
 * @property int $exportable_id
 * @property int|null $user_id
 * @property string $export_type
 * @property string|null $file_path
 * @property int|null $file_size
 * @property int $download_count
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $last_downloaded_at
 * @property-read \Illuminate\Database\Eloquent\Model $exportable
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export active()
 * @method static \Database\Factories\ExportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereDownloadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereExportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereExportableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereExportableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereLastDownloadedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Export whereUuid($value)
 *
 * @mixin \Eloquent
 */
final class Export extends Model
{
    /** @use HasFactory<\Database\Factories\ExportFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'exportable_type',
        'exportable_id',
        'user_id',
        'export_type',
        'file_path',
        'file_size',
        'expires_at',
        'last_downloaded_at',
    ];

    protected $attributes = [
        'download_count' => 0,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $export): void {
            if (empty($export->uuid)) {
                $export->uuid = (string) Str::uuid();
            }
        });

        self::deleting(function (self $export): void {
            // Delete associated file when export is deleted
            if ($export->file_path && Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
            }
        });
    }

    /**
     * User who created the export.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to exportable models.
     *
     * @return MorphTo<Model, $this>
     */
    public function exportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate the download URL for this export.
     */
    public function getDownloadUrl(): string
    {
        return url("/api/exports/{$this->uuid}/download");
    }

    /**
     * Check if the export is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Increment the download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Format file size for display.
     */
    public function getFormattedFileSize(): string
    {
        if (! $this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists(): bool
    {
        return $this->file_path && Storage::exists($this->file_path);
    }

    /**
     * Get the content type based on export type.
     */
    public function getContentType(): string
    {
        return match ($this->export_type) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }

    /**
     * Generate appropriate filename for download.
     */
    public function generateFilename(): string
    {
        $date = $this->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $extension = $this->export_type;

        if ($this->exportable instanceof LogoGeneration) {
            $businessName = Str::slug($this->exportable->business_name ?? 'logos', '-');
            $businessName = Str::limit($businessName, 30, '');

            return "{$date}_{$businessName}.{$extension}";
        }

        return "{$date}_export.{$extension}";
    }

    /**
     * Scope to active (non-expired) exports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Export>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Export>
     */
    protected function scopeActive($query)
    {
        return $query->where(function ($q): void {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope by export type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Export>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Export>
     */
    protected function scopeOfType($query, string $type)
    {
        return $query->where('export_type', $type);
    }

    /**
     * Scope to recent exports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Export>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Export>
     */
    protected function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'file_size' => 'integer',
            'download_count' => 'integer',
            'exportable_id' => 'integer',
            'user_id' => 'integer',
        ];
    }
}
