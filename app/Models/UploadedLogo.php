<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uploaded logo model.
 *
 * Represents a logo file uploaded by a user, with metadata
 * and file management capabilities.
 *
 * @property int $id
 * @property string $session_id
 * @property int|null $user_id
 * @property string $original_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int|null $image_width
 * @property int|null $image_height
 * @property string|null $category
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 *
 * @method static \Database\Factories\UploadedLogoFactory factory($count = null, $state = [])
 * @method static Builder<static>|UploadedLogo forSession(string $sessionId)
 * @method static Builder<static>|UploadedLogo forUser(int $userId)
 * @method static Builder<static>|UploadedLogo newModelQuery()
 * @method static Builder<static>|UploadedLogo newQuery()
 * @method static Builder<static>|UploadedLogo ofCategory(string $category)
 * @method static Builder<static>|UploadedLogo ofMimeType(string $mimeType)
 * @method static Builder<static>|UploadedLogo query()
 * @method static Builder<static>|UploadedLogo whereCategory($value)
 * @method static Builder<static>|UploadedLogo whereCreatedAt($value)
 * @method static Builder<static>|UploadedLogo whereDescription($value)
 * @method static Builder<static>|UploadedLogo whereFilePath($value)
 * @method static Builder<static>|UploadedLogo whereFileSize($value)
 * @method static Builder<static>|UploadedLogo whereId($value)
 * @method static Builder<static>|UploadedLogo whereImageHeight($value)
 * @method static Builder<static>|UploadedLogo whereImageWidth($value)
 * @method static Builder<static>|UploadedLogo whereMimeType($value)
 * @method static Builder<static>|UploadedLogo whereOriginalName($value)
 * @method static Builder<static>|UploadedLogo whereSessionId($value)
 * @method static Builder<static>|UploadedLogo whereUpdatedAt($value)
 * @method static Builder<static>|UploadedLogo whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class UploadedLogo extends Model
{
    /** @use HasFactory<\Database\Factories\UploadedLogoFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'image_width',
        'image_height',
        'category',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Get the user that owns the uploaded logo.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get uploaded logos for a specific session.
     *
     * @param  Builder<UploadedLogo>  $query
     * @return Builder<UploadedLogo>
     */
    protected function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get uploaded logos for a specific user.
     *
     * @param  Builder<UploadedLogo>  $query
     * @return Builder<UploadedLogo>
     */
    protected function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logos of a specific category.
     *
     * @param  Builder<UploadedLogo>  $query
     * @return Builder<UploadedLogo>
     */
    protected function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get logos of a specific MIME type.
     *
     * @param  Builder<UploadedLogo>  $query
     * @return Builder<UploadedLogo>
     */
    protected function scopeOfMimeType(Builder $query, string $mimeType): Builder
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Get the file extension from the original name.
     */
    public function getFileExtension(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    /**
     * Check if the file exists on disk.
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get the full URL to the uploaded logo file.
     */
    public function getFileUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Generate a download filename for this logo.
     */
    public function generateDownloadFilename(?string $format = null): string
    {
        $baseName = pathinfo($this->original_name, PATHINFO_FILENAME);
        $extension = $format ?: $this->getFileExtension();

        // Sanitize the filename
        $baseName = Str::slug($baseName, '-');
        $baseName = Str::limit($baseName, 50, '');

        return "{$baseName}.{$extension}";
    }

    /**
     * Check if this is an SVG file.
     */
    public function isSvg(): bool
    {
        return $this->mime_type === 'image/svg+xml';
    }

    /**
     * Check if this is a raster image (PNG/JPG).
     */
    public function isRasterImage(): bool
    {
        return in_array($this->mime_type, ['image/png', 'image/jpeg', 'image/jpg']);
    }

    /**
     * Get a display name for the logo.
     */
    public function getDisplayName(): string
    {
        return pathinfo($this->original_name, PATHINFO_FILENAME);
    }
}
