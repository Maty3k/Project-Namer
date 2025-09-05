<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $selected_name_id
 * @property string|null $ai_generation_history
 * @property string|null $last_ai_generation_mode
 * @property int $last_ai_deep_thinking
 * @property string|null $preferred_ai_models
 * @property int $total_ai_generations
 * @property string|null $last_ai_generation_at
 * @property int $total_images
 * @property int $storage_used_bytes
 * @property int|null $default_mood_board_id
 * @property int $image_upload_enabled
 * @property-read \App\Models\MoodBoard|null $defaultMoodBoard
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MoodBoard> $moodBoards
 * @property-read int|null $mood_boards_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NameSuggestion> $nameSuggestions
 * @property-read int|null $name_suggestions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectImage> $projectImages
 * @property-read int|null $project_images_count
 * @property-read \App\Models\NameSuggestion|null $selectedName
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NameSuggestion> $visibleNameSuggestions
 * @property-read int|null $visible_name_suggestions_count
 *
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAiGenerationHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDefaultMoodBoardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereImageUploadEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLastAiDeepThinking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLastAiGenerationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereLastAiGenerationMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePreferredAiModels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSelectedNameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStorageUsedBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTotalAiGenerations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTotalImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUuid($value)
 *
 * @mixin \Eloquent
 */
final class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'user_id',
        'selected_name_id',
        'default_mood_board_id',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Project $project): void {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid();
            }

            if (empty($project->name)) {
                $project->name = 'Project '.now()->format('Y-m-d H:i');
            }
        });
    }

    /**
     * Get the user that owns the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all name suggestions for the project.
     *
     * @return HasMany<NameSuggestion, $this>
     */
    public function nameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class);
    }

    /**
     * Get the selected name for the project.
     *
     * @return BelongsTo<NameSuggestion, $this>
     */
    public function selectedName(): BelongsTo
    {
        return $this->belongsTo(NameSuggestion::class, 'selected_name_id');
    }

    /**
     * Get visible name suggestions for the project.
     *
     * @return HasMany<NameSuggestion, $this>
     */
    public function visibleNameSuggestions(): HasMany
    {
        return $this->hasMany(NameSuggestion::class)->where('is_hidden', false);
    }

    /**
     * Get all project images.
     *
     * @return HasMany<ProjectImage, $this>
     */
    public function projectImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }

    /**
     * Get all mood boards for the project.
     *
     * @return HasMany<MoodBoard, $this>
     */
    public function moodBoards(): HasMany
    {
        return $this->hasMany(MoodBoard::class);
    }

    /**
     * Get the default mood board for the project.
     *
     * @return BelongsTo<MoodBoard, $this>
     */
    public function defaultMoodBoard(): BelongsTo
    {
        return $this->belongsTo(MoodBoard::class, 'default_mood_board_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uuid' => 'string',
        ];
    }
}
