<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Junction model for mood board images with positioning data.
 *
 * @property int $id
 * @property int $mood_board_id
 * @property int $project_image_id
 * @property int $position
 * @property int|null $x_position
 * @property int|null $y_position
 * @property int|null $width
 * @property int|null $height
 * @property int $z_index
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\MoodBoard $moodBoard
 * @property-read \App\Models\ProjectImage $projectImage
 * @method static Builder<static>|MoodBoardImage byMoodBoard(int $moodBoardId)
 * @method static \Database\Factories\MoodBoardImageFactory factory($count = null, $state = [])
 * @method static Builder<static>|MoodBoardImage newModelQuery()
 * @method static Builder<static>|MoodBoardImage newQuery()
 * @method static Builder<static>|MoodBoardImage orderedByPosition()
 * @method static Builder<static>|MoodBoardImage query()
 * @method static Builder<static>|MoodBoardImage whereCreatedAt($value)
 * @method static Builder<static>|MoodBoardImage whereHeight($value)
 * @method static Builder<static>|MoodBoardImage whereId($value)
 * @method static Builder<static>|MoodBoardImage whereMoodBoardId($value)
 * @method static Builder<static>|MoodBoardImage whereNotes($value)
 * @method static Builder<static>|MoodBoardImage wherePosition($value)
 * @method static Builder<static>|MoodBoardImage whereProjectImageId($value)
 * @method static Builder<static>|MoodBoardImage whereWidth($value)
 * @method static Builder<static>|MoodBoardImage whereXPosition($value)
 * @method static Builder<static>|MoodBoardImage whereYPosition($value)
 * @method static Builder<static>|MoodBoardImage whereZIndex($value)
 * @method static Builder<static>|MoodBoardImage withinBounds(int $x, int $y, int $width, int $height)
 * @mixin \Eloquent
 */
class MoodBoardImage extends Model
{
    /** @use HasFactory<\Database\Factories\MoodBoardImageFactory> */
    use HasFactory;

    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'mood_board_id',
        'project_image_id',
        'position',
        'x_position',
        'y_position',
        'width',
        'height',
        'z_index',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'x_position' => 'integer',
            'y_position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'z_index' => 'integer',
        ];
    }

    /** @return BelongsTo<MoodBoard, $this> */
    public function moodBoard(): BelongsTo
    {
        return $this->belongsTo(MoodBoard::class);
    }

    /** @return BelongsTo<ProjectImage, $this> */
    public function projectImage(): BelongsTo
    {
        return $this->belongsTo(ProjectImage::class);
    }

    /** @param Builder<MoodBoardImage> $query
     * @return Builder<MoodBoardImage> */
    protected function scopeByMoodBoard(Builder $query, int $moodBoardId): Builder
    {
        return $query->where('mood_board_id', $moodBoardId);
    }

    /** @param Builder<MoodBoardImage> $query
     * @return Builder<MoodBoardImage> */
    protected function scopeOrderedByPosition(Builder $query): Builder
    {
        return $query->orderBy('position');
    }

    /** @param Builder<MoodBoardImage> $query
     * @return Builder<MoodBoardImage> */
    protected function scopeWithinBounds(Builder $query, int $x, int $y, int $width, int $height): Builder
    {
        return $query->where('x_position', '>=', $x)
            ->where('y_position', '>=', $y)
            ->where('x_position', '<=', $x + $width)
            ->where('y_position', '<=', $y + $height);
    }

    public function updatePosition(int $position, ?int $x = null, ?int $y = null): void
    {
        $this->update([
            'position' => $position,
            'x_position' => $x,
            'y_position' => $y,
        ]);
    }

    public function updateDimensions(int $width, int $height): void
    {
        $this->update([
            'width' => $width,
            'height' => $height,
        ]);
    }
}
