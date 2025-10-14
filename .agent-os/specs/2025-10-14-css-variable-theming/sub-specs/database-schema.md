# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-10-14-css-variable-theming/spec.md

> Created: 2025-10-14
> Version: 1.0.0

## Database Changes

### Simplify user_theme_preferences Table

The current table stores individual hex color values for each user, which will be replaced with a simpler structure that only stores theme selection and dark mode preference.

### New Schema Structure

```php
Schema::create('user_theme_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

    // Theme selection (references CSS filename)
    $table->string('theme_name', 100)->default('default');

    // Dark mode preference
    $table->boolean('is_dark_mode')->default(false);

    // UI preferences (kept from original schema)
    $table->enum('border_radius', ['none', 'small', 'medium', 'large', 'full'])->default('medium');
    $table->enum('font_size', ['small', 'medium', 'large'])->default('medium');
    $table->boolean('compact_mode')->default(false);

    $table->timestamps();

    // Indexes
    $table->index(['theme_name', 'is_dark_mode']);
});
```

### Columns to Remove

The following columns will be removed as they are no longer needed:

- `is_custom_theme` (no longer supporting custom themes)
- `primary_color`
- `secondary_color`
- `accent_color`
- `background_color`
- `surface_color`
- `text_primary_color`
- `text_secondary_color`
- `dark_background_color`
- `dark_surface_color`
- `dark_text_primary_color`
- `dark_text_secondary_color`
- `text_color`
- `theme_config` (JSON column no longer needed)

### Migration File

**Up Migration:**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old columns
        Schema::table('user_theme_preferences', function (Blueprint $table) {
            $table->dropColumn([
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
                'text_color',
                'theme_config',
            ]);
        });

        // Add composite index for efficient queries
        Schema::table('user_theme_preferences', function (Blueprint $table) {
            $table->index(['theme_name', 'is_dark_mode']);
        });
    }

    public function down(): void
    {
        // Restore old columns for rollback
        Schema::table('user_theme_preferences', function (Blueprint $table) {
            $table->boolean('is_custom_theme')->default(false);
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#8B5CF6');
            $table->string('accent_color', 7)->default('#10B981');
            $table->string('background_color', 7)->default('#FFFFFF');
            $table->string('surface_color', 7)->default('#F8FAFC');
            $table->string('text_primary_color', 7)->default('#1F2937');
            $table->string('text_secondary_color', 7)->default('#6B7280');
            $table->string('dark_background_color', 7)->default('#111827');
            $table->string('dark_surface_color', 7)->default('#1F2937');
            $table->string('dark_text_primary_color', 7)->default('#F9FAFB');
            $table->string('dark_text_secondary_color', 7)->default('#D1D5DB');
            $table->string('text_color', 7)->nullable();
            $table->json('theme_config')->nullable();

            $table->dropIndex(['theme_name', 'is_dark_mode']);
        });
    }
};
```

### Data Migration Strategy

**No Migration of Existing Data:**

As per requirements, existing user theme preferences will NOT be migrated. The migration will:

1. Drop all existing theme preference records
2. Users will be prompted to select a new theme on next login
3. Default theme will be applied until user makes a selection

**Alternative: Soft Migration:**

If we want to preserve some user preferences, we could:

1. Map `is_dark_mode` from existing records based on theme name
2. Keep `theme_name` if it matches a new predefined theme
3. Default to 'default' theme for unmapped cases

**Rationale for No Migration:**

The current implementation has inconsistencies and bugs, so a fresh start ensures all users experience the new, corrected theme system without carrying forward any corrupted state.

### Model Updates Required

The `UserThemePreference` model will need significant simplification:

**Simplified Model:**

```php
class UserThemePreference extends Model
{
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the CSS file path for this theme preference
     */
    public function getThemeCssPath(): string
    {
        return "/css/themes/{$this->theme_name}.css";
    }

    /**
     * Get default theme preference
     */
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
```

### Removed Methods

The following methods will be removed from UserThemePreference model:

- `validateColorHex()`
- `getLightModeColors()`
- `getDarkModeColors()`
- `generateCssVariables()`

These methods are no longer needed since colors are defined in CSS files, not stored in the database.

## Performance Considerations

### Index Strategy

A composite index on `(theme_name, is_dark_mode)` will optimize queries that look up user preferences for theme loading.

### Query Optimization

```php
// Efficient query to load user theme
$userTheme = UserThemePreference::where('user_id', $userId)
    ->select(['theme_name', 'is_dark_mode'])
    ->first();
```

This query is highly efficient with minimal columns and proper indexing.

## Testing Strategy

- Factory updates to generate valid theme names only
- Test that invalid theme names are rejected
- Verify cascade delete when user is removed
- Test default theme application for new users
- Ensure composite index improves query performance
