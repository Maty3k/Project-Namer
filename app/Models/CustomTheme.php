<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Custom theme model for user-created themes.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $primary_color
 * @property string $accent_color
 * @property bool $is_dark_mode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereAccentColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereIsDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme wherePrimaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomTheme whereUserId($value)
 *
 * @mixin \Eloquent
 */
class CustomTheme extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'primary_color',
        'accent_color',
        'is_dark_mode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_dark_mode' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this theme.
     *
     * @return BelongsTo<User, CustomTheme>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate theme identifier for use in theme system.
     */
    public function getThemeIdentifier(): string
    {
        return "custom-{$this->id}";
    }

    /**
     * Generate CSS content for this custom theme.
     */
    public function generateCss(): string
    {
        $primary = $this->primary_color;
        $accent = $this->accent_color;

        // Generate complementary colors based on primary
        $background = $this->is_dark_mode ? '#111827' : '#ffffff';
        $surface = $this->is_dark_mode ? '#282f3c' : '#f3f3f3';
        $textPrimary = $this->is_dark_mode ? '#f9fafb' : '#111827';
        $textSecondary = $this->is_dark_mode ? '#d4d5d6' : '#6b7280';
        $border = $this->is_dark_mode ? '#404652' : '#d9d9d9';

        $css = "/* Custom Theme: {$this->name} */\n\n";
        $css .= "/* Light Mode */\n";
        $css .= ":root {\n";
        $css .= "  --color-primary: {$primary};\n";
        $css .= "  --color-secondary: {$primary};\n";
        $css .= "  --color-accent: {$accent};\n";
        $css .= "  --color-background: {$background};\n";
        $css .= "  --color-surface: {$surface};\n";
        $css .= "  --color-text-primary: {$textPrimary};\n";
        $css .= "  --color-text-secondary: {$textSecondary};\n";
        $css .= "  --color-border: {$border};\n";
        $css .= "}\n\n";

        // Dark mode variant
        $darkBackground = '#111827';
        $darkSurface = '#282f3c';
        $darkTextPrimary = '#f9fafb';
        $darkTextSecondary = '#d4d5d6';
        $darkBorder = '#404652';

        $css .= "/* Dark Mode */\n";
        $css .= ":root.dark {\n";
        $css .= "  --color-primary: {$primary};\n";
        $css .= "  --color-secondary: {$primary};\n";
        $css .= "  --color-accent: {$accent};\n";
        $css .= "  --color-background: {$darkBackground};\n";
        $css .= "  --color-surface: {$darkSurface};\n";
        $css .= "  --color-text-primary: {$darkTextPrimary};\n";
        $css .= "  --color-text-secondary: {$darkTextSecondary};\n";
        $css .= "  --color-border: {$darkBorder};\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Save CSS file for this theme.
     */
    public function saveCssFile(): string
    {
        $filename = $this->getThemeIdentifier().'.css';
        $path = public_path("css/themes/{$filename}");

        file_put_contents($path, $this->generateCss());

        return $filename;
    }

    /**
     * Delete CSS file for this theme.
     */
    public function deleteCssFile(): void
    {
        $filename = $this->getThemeIdentifier().'.css';
        $path = public_path("css/themes/{$filename}");

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
