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
 * @property bool $dns_checked
 * @property bool|null $dns_has_records
 * @property \Illuminate\Support\Carbon|null $dns_checked_at
 * @property-read \App\Models\Project $project
 *
 * @method static Builder<static>|NameSuggestion aiGenerated()
 * @method static Builder<static>|NameSuggestion availableDns()
 * @method static Builder<static>|NameSuggestion byAiModel(string $modelName)
 * @method static Builder<static>|NameSuggestion dnsChecked()
 * @method static \Database\Factories\NameSuggestionFactory factory($count = null, $state = [])
 * @method static Builder<static>|NameSuggestion hidden()
 * @method static Builder<static>|NameSuggestion newModelQuery()
 * @method static Builder<static>|NameSuggestion newQuery()
 * @method static Builder<static>|NameSuggestion pendingDnsCheck()
 * @method static Builder<static>|NameSuggestion query()
 * @method static Builder<static>|NameSuggestion takenDns()
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
 * @method static Builder<static>|NameSuggestion whereDnsChecked($value)
 * @method static Builder<static>|NameSuggestion whereDnsCheckedAt($value)
 * @method static Builder<static>|NameSuggestion whereDnsHasRecords($value)
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
        'ai_model_used',
        'ai_generation_mode',
        'ai_deep_thinking',
        'ai_response_time_ms',
        'ai_tokens_used',
        'ai_cost_cents',
        'ai_generation_session_id',
        'ai_prompt_metadata',
        'dns_checked',
        'dns_has_records',
        'dns_checked_at',
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
     * Scope a query to only include suggestions with DNS checked.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeDnsChecked(Builder $query): Builder
    {
        return $query->where('dns_checked', true);
    }

    /**
     * Scope a query to only include suggestions without DNS records.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeAvailableDns(Builder $query): Builder
    {
        return $query->where('dns_checked', true)
            ->where('dns_has_records', false);
    }

    /**
     * Scope a query to only include suggestions with DNS records (taken).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopeTakenDns(Builder $query): Builder
    {
        return $query->where('dns_checked', true)
            ->where('dns_has_records', true);
    }

    /**
     * Scope a query to only include suggestions pending DNS check.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function scopePendingDnsCheck(Builder $query): Builder
    {
        return $query->where('dns_checked', false);
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
     * Check if DNS has been checked for this suggestion.
     */
    public function isDnsChecked(): bool
    {
        return $this->dns_checked;
    }

    /**
     * Check if this suggestion has DNS records (is taken).
     */
    public function hasDnsRecords(): bool
    {
        return $this->dns_checked && $this->dns_has_records === true;
    }

    /**
     * Check if this suggestion appears to be available (no DNS records).
     */
    public function appearsDnsAvailable(): bool
    {
        return $this->dns_checked && $this->dns_has_records === false;
    }

    /**
     * Get DNS status summary for this suggestion.
     *
     * @return array<string, mixed>
     */
    public function getDnsStatus(): array
    {
        return [
            'checked' => $this->dns_checked,
            'has_records' => $this->dns_has_records,
            'checked_at' => $this->dns_checked_at?->toISOString(),
            'appears_available' => $this->appearsDnsAvailable(),
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
            'dns_checked' => 'boolean',
            'dns_has_records' => 'boolean',
            'dns_checked_at' => 'datetime',
        ];
    }
}
