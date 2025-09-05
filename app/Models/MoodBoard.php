<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Mood board model for organizing project images into visual collections.
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string $layout_type
 * @property array<array-key, mixed>|null $layout_config
 * @property bool $is_public
 * @property string|null $share_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $image_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MoodBoardImage> $moodBoardImages
 * @property-read int|null $mood_board_images_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectImage> $projectImages
 * @property-read int|null $project_images_count
 * @property-read mixed $share_url
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|MoodBoard active()
 * @method static Builder<static>|MoodBoard byLayoutType(string $layoutType)
 * @method static Builder<static>|MoodBoard byShareToken(string $token)
 * @method static \Database\Factories\MoodBoardFactory factory($count = null, $state = [])
 * @method static Builder<static>|MoodBoard forProject(int $projectId)
 * @method static Builder<static>|MoodBoard newModelQuery()
 * @method static Builder<static>|MoodBoard newQuery()
 * @method static Builder<static>|MoodBoard public()
 * @method static Builder<static>|MoodBoard publiclyShared()
 * @method static Builder<static>|MoodBoard query()
 * @method static Builder<static>|MoodBoard whereCreatedAt($value)
 * @method static Builder<static>|MoodBoard whereDescription($value)
 * @method static Builder<static>|MoodBoard whereId($value)
 * @method static Builder<static>|MoodBoard whereIsPublic($value)
 * @method static Builder<static>|MoodBoard whereLayoutConfig($value)
 * @method static Builder<static>|MoodBoard whereLayoutType($value)
 * @method static Builder<static>|MoodBoard whereName($value)
 * @method static Builder<static>|MoodBoard whereProjectId($value)
 * @method static Builder<static>|MoodBoard whereShareToken($value)
 * @method static Builder<static>|MoodBoard whereUpdatedAt($value)
 * @method static Builder<static>|MoodBoard whereUserId($value)
 * @method static Builder<static>|MoodBoard whereUuid($value)
 *
 * @mixin \Eloquent
 */
class MoodBoard extends Model
{
    /** @use HasFactory<\Database\Factories\MoodBoardFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_id',
        'user_id',
        'name',
        'description',
        'layout_type',
        'layout_config',
        'is_public',
        'share_token',
    ];

    protected $visible = [
        'id',
        'uuid',
        'project_id',
        'user_id',
        'name',
        'description',
        'layout_type',
        'layout_config',
        'is_public',
        'share_token',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'layout_config' => 'array',
            'is_public' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MoodBoard $board): void {
            $board->uuid = Str::uuid()->toString();
            if ($board->is_public && ! $board->share_token) {
                $board->share_token = Str::random(32);
            }
        });

        static::updating(function (MoodBoard $board): void {
            if ($board->is_public && ! $board->share_token) {
                $board->share_token = Str::random(32);
            } elseif (! $board->is_public) {
                $board->share_token = null;
            }
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

    /** @return BelongsToMany<ProjectImage, $this> */
    public function projectImages(): BelongsToMany
    {
        return $this->belongsToMany(ProjectImage::class, 'mood_board_images')
            ->withPivot(['position', 'x_position', 'y_position', 'width', 'height', 'z_index', 'notes', 'created_at'])
            ->orderByPivot('position');
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopeByShareToken(Builder $query, string $token): Builder
    {
        return $query->where('share_token', $token);
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopeByLayoutType(Builder $query, string $layoutType): Builder
    {
        return $query->where('layout_type', $layoutType);
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopePubliclyShared(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /** @param Builder<MoodBoard> $query
     * @return Builder<MoodBoard> */
    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** @return \Illuminate\Database\Eloquent\Casts\Attribute<int, never> */
    protected function imageCount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn () => $this->moodBoardImages()->count());
    }

    /** @return \Illuminate\Database\Eloquent\Casts\Attribute<string|null, never> */
    protected function shareUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            if (! $this->is_public || ! $this->share_token) {
                return null;
            }

            return route('public.mood-boards.show', $this->share_token);
        });
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(32);
        $this->save();

        return $this->share_token;
    }

    public function revokeSharing(): void
    {
        $this->update([
            'is_public' => false,
            'share_token' => null,
        ]);
    }

    public function addImage(ProjectImage $projectImage, int $position, ?int $x = null, ?int $y = null): void
    {
        MoodBoardImage::create([
            'mood_board_id' => $this->id,
            'project_image_id' => $projectImage->id,
            'position' => $position,
            'x_position' => $x,
            'y_position' => $y,
        ]);
    }

    public function removeImage(ProjectImage $projectImage): void
    {
        MoodBoardImage::where('mood_board_id', $this->id)
            ->where('project_image_id', $projectImage->id)
            ->delete();
    }

    /** @param array<int> $imageIds */
    public function reorderImages(array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            MoodBoardImage::where('mood_board_id', $this->id)
                ->where('project_image_id', $imageId)
                ->update(['position' => $index + 1]);
        }
    }
}
