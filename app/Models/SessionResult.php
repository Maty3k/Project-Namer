<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $session_id
 * @property array<array-key, mixed> $generated_names
 * @property array<array-key, mixed> $domain_results
 * @property array<array-key, mixed>|null $selected_for_logos
 * @property \Illuminate\Support\Carbon $generation_timestamp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $available_domains_count
 * @property-read mixed $generated_names_count
 * @property-read \App\Models\NamingSession $session
 * @method static \Database\Factories\SessionResultFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereDomainResults($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereGeneratedNames($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereGenerationTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereSelectedForLogos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SessionResult whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SessionResult extends Model
{
    /** @use HasFactory<\Database\Factories\SessionResultFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'session_id',
        'generated_names',
        'domain_results',
        'selected_for_logos',
        'generation_timestamp',
    ];

    /**
     * Get the session that owns the result.
     *
     * @return BelongsTo<NamingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(NamingSession::class, 'session_id');
    }

    /**
     * Get the count of generated names.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<int, never>
     */
    protected function generatedNamesCount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn () => count($this->generated_names ?? []));
    }

    /**
     * Get the count of available domains.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<int, never>
     */
    protected function availableDomainsCount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            if (! $this->domain_results) {
                return 0;
            }

            return collect($this->domain_results)
                ->filter(fn ($domain) => $domain['available'] ?? false)
                ->count();
        });
    }

    /**
     * Check if any names are selected for logos.
     */
    public function hasLogoSelections(): bool
    {
        return ! empty($this->selected_for_logos);
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'generated_names' => 'array',
            'domain_results' => 'array',
            'selected_for_logos' => 'array',
            'generation_timestamp' => 'datetime',
        ];
    }
}
