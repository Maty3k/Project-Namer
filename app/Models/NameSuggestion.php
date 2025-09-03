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
 * @property string|null $ai_model_used
 * @property string|null $ai_generation_mode
 * @property bool $ai_deep_thinking
 * @property int|null $ai_response_time_ms
 * @property int|null $ai_tokens_used
 * @property int|null $ai_cost_cents
 * @property string|null $ai_generation_session_id
 * @property array<array-key, mixed>|null $ai_prompt_metadata
 * @property-read \App\Models\Project $project
 * @method static Builder<static>|NameSuggestion aiGenerated()
 * @method static Builder<static>|NameSuggestion byAiModel(string $modelName)
 * @method static \Database\Factories\NameSuggestionFactory factory($count = null, $state = [])
 * @method static Builder<static>|NameSuggestion hidden()
 * @method static Builder<static>|NameSuggestion newModelQuery()
 * @method static Builder<static>|NameSuggestion newQuery()
 * @method static Builder<static>|NameSuggestion query()
 * @method static Builder<static>|NameSuggestion visible()
 * @method static Builder<static>|NameSuggestion whereAiCostCents($value)
 * @method static Builder<static>|NameSuggestion whereAiDeepThinking($value)
 * @method static Builder<static>|NameSuggestion whereAiGenerationMode($value)
 * @method static Builder<static>|NameSuggestion whereAiGenerationSessionId($value)
 * @method static Builder<static>|NameSuggestion whereAiModelUsed($value)
 * @method static Builder<static>|NameSuggestion whereAiPromptMetadata($value)
 * @method static Builder<static>|NameSuggestion whereAiResponseTimeMs($value)
 * @method static Builder<static>|NameSuggestion whereAiTokensUsed($value)
 * @method static Builder<static>|NameSuggestion whereCreatedAt($value)
 * @method static Builder<static>|NameSuggestion whereDomains($value)
 * @method static Builder<static>|NameSuggestion whereGenerationMetadata($value)
 * @method static Builder<static>|NameSuggestion whereId($value)
 * @method static Builder<static>|NameSuggestion whereIsHidden($value)
 * @method static Builder<static>|NameSuggestion whereLogos($value)
 * @method static Builder<static>|NameSuggestion whereName($value)
 * @method static Builder<static>|NameSuggestion whereProjectId($value)
 * @method static Builder<static>|NameSuggestion whereUpdatedAt($value)
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
        'ai_model_used',
        'ai_generation_mode',
        'ai_deep_thinking',
        'ai_response_time_ms',
        'ai_tokens_used',
        'ai_cost_cents',
        'ai_generation_session_id',
        'ai_prompt_metadata',
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
     * Scope a query to only include AI-generated suggestions.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeAiGenerated(Builder $query): Builder
    {
        return $query->whereNotNull('ai_model_used');
    }

    /**
     * Scope a query to filter by AI model.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeByAiModel(Builder $query, string $modelName): Builder
    {
        return $query->where('ai_model_used', $modelName);
    }

    /**
     * Check if this suggestion was AI-generated.
     */
    public function isAiGenerated(): bool
    {
        return ! is_null($this->ai_model_used);
    }

    /**
     * Get AI generation summary for this suggestion.
     *
     * @return array<string, mixed>|null
     */
    public function getAiGenerationSummary(): ?array
    {
        if (! $this->isAiGenerated()) {
            return null;
        }

        return [
            'model_used' => $this->ai_model_used,
            'generation_mode' => $this->ai_generation_mode,
            'deep_thinking' => $this->ai_deep_thinking,
            'response_time_ms' => $this->ai_response_time_ms,
            'tokens_used' => $this->ai_tokens_used,
            'cost_cents' => $this->ai_cost_cents,
            'session_id' => $this->ai_generation_session_id,
        ];
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
            'ai_deep_thinking' => 'boolean',
            'ai_response_time_ms' => 'integer',
            'ai_tokens_used' => 'integer',
            'ai_cost_cents' => 'integer',
            'ai_prompt_metadata' => 'array',
        ];
    }
}
