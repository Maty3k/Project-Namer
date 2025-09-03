<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links images used as context for name/logo generation.
 *
 * @property int $id
 * @property int $project_image_id
 * @property int $generation_session_id
 * @property string $generation_type
 * @property array<array-key, mixed>|null $vision_analysis
 * @property numeric|null $influence_score
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read mixed|null $analysis
 * @property-read \App\Models\GenerationSession $generationSession
 * @property-read \App\Models\ProjectImage $projectImage
 * @method static \Database\Factories\ImageGenerationContextFactory factory($count = null, $state = [])
 * @method static Builder<static>|ImageGenerationContext forGenerationType(string $type)
 * @method static Builder<static>|ImageGenerationContext forSession(int $sessionId)
 * @method static Builder<static>|ImageGenerationContext highInfluence(float $threshold = 0.7)
 * @method static Builder<static>|ImageGenerationContext newModelQuery()
 * @method static Builder<static>|ImageGenerationContext newQuery()
 * @method static Builder<static>|ImageGenerationContext query()
 * @method static Builder<static>|ImageGenerationContext whereCreatedAt($value)
 * @method static Builder<static>|ImageGenerationContext whereGenerationSessionId($value)
 * @method static Builder<static>|ImageGenerationContext whereGenerationType($value)
 * @method static Builder<static>|ImageGenerationContext whereId($value)
 * @method static Builder<static>|ImageGenerationContext whereInfluenceScore($value)
 * @method static Builder<static>|ImageGenerationContext whereProjectImageId($value)
 * @method static Builder<static>|ImageGenerationContext whereVisionAnalysis($value)
 * @mixin \Eloquent
 */
class ImageGenerationContext extends Model
{
    /** @use HasFactory<\Database\Factories\ImageGenerationContextFactory> */
    use HasFactory;

    const UPDATED_AT = null; // Only track created_at

    protected $table = 'image_generation_context';

    protected $fillable = [
        'project_image_id',
        'generation_session_id',
        'generation_type',
        'vision_analysis',
        'influence_score',
    ];

    protected function casts(): array
    {
        return [
            'vision_analysis' => 'array',
            'influence_score' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ProjectImage, $this> */
    public function projectImage(): BelongsTo
    {
        return $this->belongsTo(ProjectImage::class);
    }

    /** @return BelongsTo<GenerationSession, $this> */
    public function generationSession(): BelongsTo
    {
        return $this->belongsTo(GenerationSession::class);
    }

    /** @param Builder<ImageGenerationContext> $query
     * @return Builder<ImageGenerationContext> */
    protected function scopeForSession(Builder $query, int $sessionId): Builder
    {
        return $query->where('generation_session_id', $sessionId);
    }

    /** @param Builder<ImageGenerationContext> $query
     * @return Builder<ImageGenerationContext> */
    protected function scopeForGenerationType(Builder $query, string $type): Builder
    {
        return $query->where('generation_type', $type);
    }

    /** @param Builder<ImageGenerationContext> $query
     * @return Builder<ImageGenerationContext> */
    protected function scopeHighInfluence(Builder $query, float $threshold = 0.7): Builder
    {
        return $query->where('influence_score', '>=', $threshold);
    }

    public function isHighInfluence(): bool
    {
        return $this->influence_score >= 0.7;
    }

    /** @return \Illuminate\Database\Eloquent\Casts\Attribute<array<string, mixed>|null, never> */
    protected function analysis(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            if (! $this->vision_analysis) {
                return null;
            }

            return [
                'color_palette' => $this->vision_analysis['color_palette'] ?? [],
                'style_elements' => $this->vision_analysis['style_elements'] ?? [],
                'mood_keywords' => $this->vision_analysis['mood_keywords'] ?? [],
                'complexity_score' => $this->vision_analysis['complexity_score'] ?? 0.0,
            ];
        });
    }

    public function setInfluenceScore(float $score): void
    {
        $this->update(['influence_score' => max(0, min(1, $score))]);
    }

    protected function getAnalysisAttribute(string $key): mixed
    {
        return $this->vision_analysis[$key] ?? null;
    }
}
