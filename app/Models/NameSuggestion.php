<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property array<array-key, mixed>|null $domains
 * @property array<array-key, mixed>|null $logos
 * @property bool $is_hidden
 * @property array<array-key, mixed>|null $generation_metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 *
 * @method static \Database\Factories\NameSuggestionFactory factory($count = null, $state = [])
 * @method static Builder<static>|NameSuggestion hidden()
 * @method static Builder<static>|NameSuggestion newModelQuery()
 * @method static Builder<static>|NameSuggestion newQuery()
 * @method static Builder<static>|NameSuggestion query()
 * @method static Builder<static>|NameSuggestion visible()
 * @method static Builder<static>|NameSuggestion whereCreatedAt($value)
 * @method static Builder<static>|NameSuggestion whereDomains($value)
 * @method static Builder<static>|NameSuggestion whereGenerationMetadata($value)
 * @method static Builder<static>|NameSuggestion whereId($value)
 * @method static Builder<static>|NameSuggestion whereIsHidden($value)
 * @method static Builder<static>|NameSuggestion whereLogos($value)
 * @method static Builder<static>|NameSuggestion whereName($value)
 * @method static Builder<static>|NameSuggestion whereProjectId($value)
 * @method static Builder<static>|NameSuggestion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class NameSuggestion extends Model
{
    /** @use HasFactory<\Database\Factories\NameSuggestionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'domains',
        'logos',
        'is_hidden',
        'generation_metadata',
    ];

    /**
     * Get the project that owns the name suggestion.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to only include visible suggestions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope a query to only include hidden suggestions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'logos' => 'array',
            'is_hidden' => 'boolean',
            'generation_metadata' => 'array',
        ];
    }
}
