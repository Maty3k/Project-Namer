<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generated logo model.
 *
 * Represents an individual generated logo with its style, file path,
 * and metadata from the AI generation process.
 *
 * @template TFactory of \Database\Factories\GeneratedLogoFactory
 *
 * @property int $id
 * @property int $logo_generation_id
 * @property string $style
 * @property int $variation_number
 * @property string $prompt_used
 * @property string $original_file_path
 * @property int $file_size
 * @property int $image_width
 * @property int $image_height
 * @property int $generation_time_ms
 * @property string|null $api_image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LogoColorVariant<\Database\Factories\LogoColorVariantFactory>> $colorVariants
 * @property-read int|null $color_variants_count
 * @property-read \App\Models\LogoGeneration<\Database\Factories\LogoGenerationFactory> $logoGeneration
 *
 * @method static \Database\Factories\GeneratedLogoFactory factory($count = null, $state = [])
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> newModelQuery()
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> newQuery()
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> ofStyle(string $style)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> query()
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereApiImageUrl($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereCreatedAt($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereFileSize($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereGenerationTimeMs($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereId($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereImageHeight($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereImageWidth($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereLogoGenerationId($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereOriginalFilePath($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> wherePromptUsed($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereStyle($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereUpdatedAt($value)
 * @method static Builder<static>|\App\Models\GeneratedLogo<TFactory> whereVariationNumber($value)
 *
 * @mixin \Eloquent
 */
final class GeneratedLogo extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    protected $fillable = [
        'logo_generation_id',
        'style',
        'variation_number',
        'prompt_used',
        'original_file_path',
        'file_size',
        'image_width',
        'image_height',
        'generation_time_ms',
        'api_image_url',
    ];

    protected $attributes = [
        'variation_number' => 1,
        'image_width' => 1024,
        'image_height' => 1024,
    ];

    protected function casts(): array
    {
        return [
            'logo_generation_id' => 'integer',
            'variation_number' => 'integer',
            'file_size' => 'integer',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'generation_time_ms' => 'integer',
        ];
    }

    /**
     * Get the logo generation request that owns this generated logo.
     *
     * @return BelongsTo<LogoGeneration<\Database\Factories\LogoGenerationFactory>, $this>
     */
    public function logoGeneration(): BelongsTo
    {
        /** @phpstan-ignore-next-line - Laravel relationship generic type resolution */
        return $this->belongsTo(LogoGeneration::class);
    }

    /**
     * Get the color variants for this generated logo.
     *
     * @return HasMany<LogoColorVariant<\Database\Factories\LogoColorVariantFactory>, $this>
     */
    public function colorVariants(): HasMany
    {
        /** @phpstan-ignore-next-line - Laravel relationship generic type resolution */
        return $this->hasMany(LogoColorVariant::class);
    }

    /**
     * Scope to get logos of a specific style.
     *
     * @param  Builder<GeneratedLogo<TFactory>>  $query
     * @return Builder<GeneratedLogo<TFactory>>
     */
    public function scopeOfStyle(Builder $query, string $style): Builder
    {
        return $query->where('style', $style);
    }

    /**
     * Get the file extension from the file path.
     */
    public function getFileExtension(): string
    {
        return pathinfo($this->original_file_path, PATHINFO_EXTENSION);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    /**
     * Generate a download filename for this logo.
     */
    public function generateDownloadFilename(?string $colorScheme = null, ?string $format = null): string
    {
        $businessName = Str::slug($this->logoGeneration->business_name);
        $style = $this->style;
        $variation = $this->variation_number;

        $filename = "{$businessName}-{$style}-{$variation}";

        if ($colorScheme) {
            $filename .= "-{$colorScheme}";
        }

        $extension = $format ?: $this->getFileExtension();

        return "{$filename}.{$extension}";
    }

    /**
     * Check if the original file exists on disk.
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->original_file_path);
    }
}
