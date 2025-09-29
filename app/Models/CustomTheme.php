<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $theme_name
 * @property string $primary_color
 * @property string|null $accent_color
 * @property string $background_color
 * @property string $text_color
 * @property bool $is_dark_mode
 * @property bool $is_imported
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereAccentColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereBackgroundColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereIsDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereIsImported($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme wherePrimaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereTextColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereThemeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class CustomTheme extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'theme_name',
        'primary_color',
        'accent_color',
        'background_color',
        'text_color',
        'is_dark_mode',
        'is_imported',
    ];

    /**
     * Get the user that owns the custom theme.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_dark_mode' => 'boolean',
            'is_imported' => 'boolean',
        ];
    }
}
