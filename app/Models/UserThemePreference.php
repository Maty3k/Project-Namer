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
 * @property bool $is_dark_mode
 * @property string $border_radius
 * @property string $font_size
 * @property bool $compact_mode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|UserThemePreference forTheme(string $themeName)
 * @method static \Database\Factories\UserThemePreferenceFactory factory($count = null, $state = [])
 * @method static Builder<static>|UserThemePreference newModelQuery()
 * @method static Builder<static>|UserThemePreference newQuery()
 * @method static Builder<static>|UserThemePreference query()
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
        'is_dark_mode',
        'border_radius',
        'font_size',
        'compact_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_dark_mode' => 'boolean',
            'compact_mode' => 'boolean',
        ];
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

    /**
     * Get the CSS file path for this theme preference.
     */
    public function getThemeCssPath(): string
    {
        return "/css/themes/{$this->theme_name}.css";
    }

    /** @return array<string, mixed> */
    public static function getDefaultTheme(): array
    {
        return [
            'theme_name' => 'default',
            'is_dark_mode' => false,
            'border_radius' => 'medium',
            'font_size' => 'medium',
            'compact_mode' => false,
        ];
    }
}
