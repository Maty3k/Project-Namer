<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logo color variant model.
 *
 * Represents a color-customized version of a generated logo,
 * with a specific color scheme applied.
 *
 * @property int $id
 * @property int $generated_logo_id
 * @property string $color_scheme
 * @property string $file_path
 * @property int $file_size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GeneratedLogo $generatedLogo
 *
 * @method static \Database\Factories\LogoColorVariantFactory factory($count = null, $state = [])
 * @method static Builder<static>|LogoColorVariant newModelQuery()
 * @method static Builder<static>|LogoColorVariant newQuery()
 * @method static Builder<static>|LogoColorVariant query()
 * @method static Builder<static>|LogoColorVariant whereColorScheme($value)
 * @method static Builder<static>|LogoColorVariant whereCreatedAt($value)
 * @method static Builder<static>|LogoColorVariant whereFilePath($value)
 * @method static Builder<static>|LogoColorVariant whereFileSize($value)
 * @method static Builder<static>|LogoColorVariant whereGeneratedLogoId($value)
 * @method static Builder<static>|LogoColorVariant whereId($value)
 * @method static Builder<static>|LogoColorVariant whereUpdatedAt($value)
 * @method static Builder<static>|LogoColorVariant withColorScheme(string $colorScheme)
 *
 * @mixin \Eloquent
 */
final class LogoColorVariant extends Model
{
    /** @use HasFactory<\Database\Factories\LogoColorVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'generated_logo_id',
        'color_scheme',
        'file_path',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'generated_logo_id' => 'integer',
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the generated logo that owns this color variant.
     *
     * @return BelongsTo<GeneratedLogo, $this>
     */
    public function generatedLogo(): BelongsTo
    {
        return $this->belongsTo(GeneratedLogo::class);
    }

    /**
     * Scope to get variants with a specific color scheme.
     *
     * @param  Builder<LogoColorVariant>  $query
     * @return Builder<LogoColorVariant>
     */
    protected function scopeWithColorScheme(Builder $query, string $colorScheme): Builder
    {
        return $query->where('color_scheme', $colorScheme);
    }

    /**
     * Find a color variant by logo and color scheme.
     */
    public static function findByLogoAndScheme(int $generatedLogoId, string $colorScheme): ?self
    {
        return self::where('generated_logo_id', $generatedLogoId)
            ->where('color_scheme', $colorScheme)
            ->first();
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
     * Get the file extension from the file path.
     */
    public function getFileExtension(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Generate a download filename for this color variant.
     */
    public function generateDownloadFilename(?string $format = null): string
    {
        $businessName = Str::slug($this->generatedLogo->logoGeneration->business_name);
        $style = $this->generatedLogo->style;
        $variation = $this->generatedLogo->variation_number;
        $colorScheme = $this->color_scheme;

        $extension = $format ?: $this->getFileExtension();

        return "{$businessName}-{$style}-{$variation}-{$colorScheme}.{$extension}";
    }

    /**
     * Check if the color variant file exists on disk.
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get the display name for the color scheme.
     */
    public function getColorSchemeDisplayName(): string
    {
        return match ($this->color_scheme) {
            'monochrome' => 'Monochrome',
            'ocean_blue' => 'Ocean Blue',
            'forest_green' => 'Forest Green',
            'warm_sunset' => 'Warm Sunset',
            'royal_purple' => 'Royal Purple',
            'corporate_navy' => 'Corporate Navy',
            'earthy_tones' => 'Earthy Tones',
            'tech_blue' => 'Tech Blue',
            'vibrant_pink' => 'Vibrant Pink',
            'charcoal_gold' => 'Charcoal Gold',
            default => Str::title(str_replace('_', ' ', $this->color_scheme)),
        };
    }
}
