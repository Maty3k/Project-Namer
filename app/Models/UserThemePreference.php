<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User theme preference model for storing UI customization settings.
 *
 * @property int $id
 * @property int $user_id
 * @property string $theme_name
 * @property bool $is_custom_theme
 * @property string $primary_color
 * @property string $secondary_color
 * @property string $accent_color
 * @property string $background_color
 * @property string $surface_color
 * @property string $text_primary_color
 * @property string $text_secondary_color
 * @property string $dark_background_color
 * @property string $dark_surface_color
 * @property string $dark_text_primary_color
 * @property string $dark_text_secondary_color
 * @property string $border_radius
 * @property string $font_size
 * @property bool $compact_mode
 * @property array<array-key, mixed>|null $theme_config
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $text_color
 * @property bool $is_dark_mode
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|UserThemePreference customThemes()
 * @method static \Database\Factories\UserThemePreferenceFactory factory($count = null, $state = [])
 * @method static Builder<static>|UserThemePreference forTheme(string $themeName)
 * @method static Builder<static>|UserThemePreference newModelQuery()
 * @method static Builder<static>|UserThemePreference newQuery()
 * @method static Builder<static>|UserThemePreference query()
 * @method static Builder<static>|UserThemePreference whereAccentColor($value)
 * @method static Builder<static>|UserThemePreference whereBackgroundColor($value)
 * @method static Builder<static>|UserThemePreference whereBorderRadius($value)
 * @method static Builder<static>|UserThemePreference whereCompactMode($value)
 * @method static Builder<static>|UserThemePreference whereCreatedAt($value)
 * @method static Builder<static>|UserThemePreference whereDarkBackgroundColor($value)
 * @method static Builder<static>|UserThemePreference whereDarkSurfaceColor($value)
 * @method static Builder<static>|UserThemePreference whereDarkTextPrimaryColor($value)
 * @method static Builder<static>|UserThemePreference whereDarkTextSecondaryColor($value)
 * @method static Builder<static>|UserThemePreference whereFontSize($value)
 * @method static Builder<static>|UserThemePreference whereId($value)
 * @method static Builder<static>|UserThemePreference whereIsCustomTheme($value)
 * @method static Builder<static>|UserThemePreference whereIsDarkMode($value)
 * @method static Builder<static>|UserThemePreference wherePrimaryColor($value)
 * @method static Builder<static>|UserThemePreference whereSecondaryColor($value)
 * @method static Builder<static>|UserThemePreference whereSurfaceColor($value)
 * @method static Builder<static>|UserThemePreference whereTextColor($value)
 * @method static Builder<static>|UserThemePreference whereTextPrimaryColor($value)
 * @method static Builder<static>|UserThemePreference whereTextSecondaryColor($value)
 * @method static Builder<static>|UserThemePreference whereThemeConfig($value)
 * @method static Builder<static>|UserThemePreference whereThemeName($value)
 * @method static Builder<static>|UserThemePreference whereUpdatedAt($value)
 * @method static Builder<static>|UserThemePreference whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserThemePreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserThemePreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme_name',
        'is_custom_theme',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'surface_color',
        'text_primary_color',
        'text_secondary_color',
        'dark_background_color',
        'dark_surface_color',
        'dark_text_primary_color',
        'dark_text_secondary_color',
        'border_radius',
        'font_size',
        'compact_mode',
        'theme_config',
        'text_color',
        'is_dark_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_custom_theme' => 'boolean',
            'compact_mode' => 'boolean',
            'is_dark_mode' => 'boolean',
            'theme_config' => 'array',
        ];
    }

    /**
     * Get text_color attribute (alias for text_primary_color).
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<string, string>
     */
    protected function textColor(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn () => $this->text_primary_color, set: function (?string $value) {
            $this->text_primary_color = $value;

            return [];
        });
    }

    /**
     * Get is_dark_mode attribute based on background colors.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<bool, bool>
     */
    protected function isDarkMode(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: fn () => (bool) ($this->attributes['is_dark_mode'] ?? false), set: fn (bool $value) => ['is_dark_mode' => $value]);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<UserThemePreference> $query
     * @return Builder<UserThemePreference> */
    protected function scopeForTheme(Builder $query, string $themeName): Builder
    {
        return $query->where('theme_name', $themeName);
    }

    /** @param Builder<UserThemePreference> $query
     * @return Builder<UserThemePreference> */
    protected function scopeCustomThemes(Builder $query): Builder
    {
        return $query->where('is_custom_theme', true);
    }

    public function validateColorHex(string $color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6})$/', $color) === 1;
    }

    /** @return array<string, string> */
    public function getLightModeColors(): array
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'background' => $this->background_color,
            'surface' => $this->surface_color,
            'text_primary' => $this->text_primary_color,
            'text_secondary' => $this->text_secondary_color,
        ];
    }

    /** @return array<string, string> */
    public function getDarkModeColors(): array
    {
        return [
            'background' => $this->dark_background_color,
            'surface' => $this->dark_surface_color,
            'text_primary' => $this->dark_text_primary_color,
            'text_secondary' => $this->dark_text_secondary_color,
            'primary' => $this->primary_color, // Use same primary colors in dark mode
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
        ];
    }

    /** @return array<string, string> */
    public function generateCssVariables(bool $darkMode = false): array
    {
        $colors = $darkMode ? $this->getDarkModeColors() : $this->getLightModeColors();
        $variables = [];

        foreach ($colors as $name => $value) {
            $cssVarName = '--color-'.str_replace('_', '-', $name);
            $variables[$cssVarName] = $value;
        }

        // Add UI preference variables
        $variables['--border-radius-base'] = match ($this->border_radius) {
            'none' => '0px',
            'small' => '0.125rem',
            'medium' => '0.375rem',
            'large' => '0.75rem',
            'full' => '9999px',
            default => '0.375rem'
        };

        $variables['--font-size-base'] = match ($this->font_size) {
            'small' => '0.875rem',
            'medium' => '1rem',
            'large' => '1.125rem',
            default => '1rem'
        };

        return $variables;
    }

    /** @return array<string, mixed> */
    public static function getDefaultTheme(): array
    {
        return [
            'theme_name' => 'default',
            'is_custom_theme' => false,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#8B5CF6',
            'accent_color' => '#10B981',
            'background_color' => '#FFFFFF',
            'surface_color' => '#F8FAFC',
            'text_primary_color' => '#1F2937',
            'text_secondary_color' => '#6B7280',
            'dark_background_color' => '#111827',
            'dark_surface_color' => '#1F2937',
            'dark_text_primary_color' => '#F9FAFB',
            'dark_text_secondary_color' => '#D1D5DB',
            'border_radius' => 'medium',
            'font_size' => 'medium',
            'compact_mode' => false,
        ];
    }
}
