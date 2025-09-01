<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NameSuggestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domains' => 'array',
        'logos' => 'array',
        'is_hidden' => 'boolean',
        'generation_metadata' => 'array',
    ];

    /**
     * Get the project that owns the name suggestion.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to only include visible suggestions.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope a query to only include hidden suggestions.
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }
}
