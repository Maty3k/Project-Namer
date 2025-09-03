<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Project image model for storing and managing uploaded images.
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $user_id
 * @property string $original_filename
 * @property string $stored_filename
 * @property string $file_path
 * @property string|null $thumbnail_path
 * @property int $file_size
 * @property string $mime_type
 * @property int|null $width
 * @property int|null $height
 * @property numeric|null $aspect_ratio
 * @property array<array-key, mixed>|null $dominant_colors
 * @property string|null $title
 * @property string|null $description
 * @property array<array-key, mixed>|null $tags
 * @property array<array-key, mixed>|null $ai_analysis
 * @property string $processing_status
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ImageGenerationContext> $generationContexts
 * @property-read int|null $generation_contexts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MoodBoardImage> $moodBoardImages
 * @property-read int|null $mood_board_images_count
 * @property-read \App\Models\Project $project
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|ProjectImage completed()
 * @method static \Database\Factories\ProjectImageFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProjectImage forProject(int $projectId)
 * @method static Builder<static>|ProjectImage newModelQuery()
 * @method static Builder<static>|ProjectImage newQuery()
 * @method static Builder<static>|ProjectImage processed()
 * @method static Builder<static>|ProjectImage processing()
 * @method static Builder<static>|ProjectImage public()
 * @method static Builder<static>|ProjectImage query()
 * @method static Builder<static>|ProjectImage recent()
 * @method static Builder<static>|ProjectImage whereAiAnalysis($value)
 * @method static Builder<static>|ProjectImage whereAspectRatio($value)
 * @method static Builder<static>|ProjectImage whereCreatedAt($value)
 * @method static Builder<static>|ProjectImage whereDescription($value)
 * @method static Builder<static>|ProjectImage whereDominantColors($value)
 * @method static Builder<static>|ProjectImage whereFilePath($value)
 * @method static Builder<static>|ProjectImage whereFileSize($value)
 * @method static Builder<static>|ProjectImage whereHeight($value)
 * @method static Builder<static>|ProjectImage whereId($value)
 * @method static Builder<static>|ProjectImage whereIsPublic($value)
 * @method static Builder<static>|ProjectImage whereMimeType($value)
 * @method static Builder<static>|ProjectImage whereOriginalFilename($value)
 * @method static Builder<static>|ProjectImage whereProcessingStatus($value)
 * @method static Builder<static>|ProjectImage whereProjectId($value)
 * @method static Builder<static>|ProjectImage whereStoredFilename($value)
 * @method static Builder<static>|ProjectImage whereTags($value)
 * @method static Builder<static>|ProjectImage whereThumbnailPath($value)
 * @method static Builder<static>|ProjectImage whereTitle($value)
 * @method static Builder<static>|ProjectImage whereUpdatedAt($value)
 * @method static Builder<static>|ProjectImage whereUserId($value)
 * @method static Builder<static>|ProjectImage whereUuid($value)
 * @method static Builder<static>|ProjectImage whereWidth($value)
 * @method static Builder<static>|ProjectImage withTags(array $tags)
 *
 * @mixin \Eloquent
 */
class ProjectImage extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectImageFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_id',
        'user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'thumbnail_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'aspect_ratio',
        'dominant_colors',
        'title',
        'description',
        'tags',
        'processing_status',
        'is_public',
        'ai_analysis',
    ];

    protected function casts(): array
    {
        return [
            'dominant_colors' => 'array',
            'tags' => 'array',
            'is_public' => 'boolean',
            'aspect_ratio' => 'decimal:2',
            'ai_analysis' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProjectImage $image): void {
            $image->uuid = Str::uuid()->toString();
        });
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<MoodBoardImage, $this> */
    public function moodBoardImages(): HasMany
    {
        return $this->hasMany(MoodBoardImage::class);
    }

    /** @return HasMany<ImageGenerationContext, $this> */
    public function generationContexts(): HasMany
    {
        return $this->hasMany(ImageGenerationContext::class);
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopeCompleted(Builder $query): Builder
    {
        return $query->where('processing_status', 'completed');
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopeProcessing(Builder $query): Builder
    {
        return $query->where('processing_status', 'processing');
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /** @param Builder<ProjectImage> $query
     * @param array<int,string> $tags
     * @return Builder<ProjectImage> */
    protected function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->whereJsonContains('tags', $tags);
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopeProcessed(Builder $query): Builder
    {
        return $query->where('processing_status', 'completed');
    }

    /** @param Builder<ProjectImage> $query
     * @return Builder<ProjectImage> */
    protected function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function updateProcessingStatus(string $status): void
    {
        $this->update(['processing_status' => $status]);
    }
}
