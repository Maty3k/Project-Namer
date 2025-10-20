# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-10-20-simplify-theme-system/spec.md

> Created: 2025-10-20
> Version: 1.0.0

## Overview

This spec does not create new tables. Instead, it simplifies how we use existing theme-related columns in the `users` and `user_theme_preferences` tables.

## Existing Schema

### `users` table

**Active Columns** (will be used):
- `id` - Primary key
- `prefers_dark_mode` (boolean) - Whether user prefers dark mode
- `current_theme` (string) - The theme name (e.g., 'ocean', 'sunset', 'default')

**Deprecated Columns** (will be ignored):
- `theme_auto_switch` (boolean) - Previously used for system preference auto-switching

### `user_theme_preferences` table

**Active Columns** (will be used):
- `id` - Primary key
- `user_id` (bigint, foreign key) - References users.id
- `theme_name` (string) - The theme name (e.g., 'ocean', 'sunset', 'default')
- `is_dark_mode` (boolean) - Whether dark mode is enabled
- `created_at` (timestamp)
- `updated_at` (timestamp)

**Deprecated Columns** (will be ignored):
- `border_radius` (string) - Previously used for custom theme creation
- `font_size` (string) - Previously used for custom theme creation
- `compact_mode` (boolean) - Previously used for custom theme creation

## Data Consistency Rules

### Synchronization Between Tables

The `User` model and `UserThemePreference` model must be kept in sync:

```php
// When saving theme preference:
$user->current_theme = 'ocean';
$user->prefers_dark_mode = true;
$user->save();

// AND
$userThemePreference->theme_name = 'ocean';
$userThemePreference->is_dark_mode = true;
$userThemePreference->save();
```

### ThemeHelper Query Logic

The `ThemeHelper` class should query data in this priority:

1. **First**: Check `UserThemePreference` for the authenticated user
2. **Fallback**: Use `User` model columns if no preference record exists
3. **Default**: Fall back to 'default' theme with `is_dark_mode = false` for guests

### Request-Level Caching

To prevent N+1 queries, theme preferences must be cached within the request:

```php
// In ThemeHelper.php
protected static array $requestCache = [];

public static function getThemeName(?User $user = null): string
{
    $userId = $user?->id ?? auth()->id() ?? 'guest';

    if (isset(static::$requestCache[$userId]['theme_name'])) {
        return static::$requestCache[$userId]['theme_name'];
    }

    // Query database and cache result
    // ...
}
```

## Valid Theme Names

The following 24 theme names are valid values for `theme_name` and `current_theme`:

**Standard Themes:**
- `default`
- `dark`
- `ocean`
- `sunset`
- `forest`
- `cosmic-violet`
- `coral-reef`
- `midnight-teal`

**Seasonal Themes:**
- `summer`
- `winter`
- `halloween`
- `spring`
- `autumn`

**Bold Themes:**
- `neon-cyber`
- `electric-blue`
- `hot-pink`
- `lava-red`
- `lime-punch`
- `gold-rush`
- `matrix-green`

Any invalid theme name should fall back to `'default'`.

## Valid Dark Mode Values

The `is_dark_mode` and `prefers_dark_mode` columns accept boolean values:
- `true` - Dark mode enabled (adds `dark` class to `<html>`)
- `false` - Light mode enabled (no `dark` class)

## Migration Requirements

**No new migrations required.**

The existing schema already supports all necessary columns. However, we should consider:

1. **Optional**: Add a migration to set `theme_auto_switch = false` for all users (since we're deprecating this feature)
2. **Optional**: Add a migration to clear `border_radius`, `font_size`, and `compact_mode` columns (since custom themes are removed)

These migrations are optional cleanup and not required for functionality.

## Indexes

Existing indexes should be sufficient:
- `user_theme_preferences.user_id` should have an index for fast lookups
- `users.id` is already the primary key

No additional indexes needed.

## Data Migration Strategy

### For Existing Users

Users who have existing theme preferences will keep them:
- Their `theme_name` and `is_dark_mode` values are preserved
- Their `theme_auto_switch` will be ignored (no code changes needed)
- Their custom theme settings (`border_radius`, etc.) will be ignored

### For New Users

New users will get default theme preferences:
- `theme_name = 'default'`
- `is_dark_mode = false`

This can be handled in the User model factory or via database defaults.

## Example Queries

### Get User's Theme Preference

```php
// Via ThemeHelper (recommended)
$themeName = ThemeHelper::getThemeName();
$isDarkMode = ThemeHelper::isDarkMode();

// Direct query (not recommended, bypasses caching)
$preference = UserThemePreference::where('user_id', auth()->id())->first();
$themeName = $preference->theme_name ?? 'default';
$isDarkMode = $preference->is_dark_mode ?? false;
```

### Update User's Theme Preference

```php
// In ThemeQuickToggle component
public function toggleTheme(): void
{
    $user = auth()->user();

    // Toggle dark mode
    $newDarkMode = !$user->prefers_dark_mode;

    // Update User model
    $user->prefers_dark_mode = $newDarkMode;
    $user->save();

    // Update UserThemePreference model
    $preference = UserThemePreference::firstOrCreate(['user_id' => $user->id]);
    $preference->is_dark_mode = $newDarkMode;
    $preference->save();

    // Clear cache
    ThemeHelper::clearUserThemeCache();

    // Refresh page
    $this->dispatch('$refresh');
}
```

## Cache Clearing

After any theme update, the following caches must be cleared:

1. **Request cache**: `ThemeHelper::$requestCache` (automatically clears between requests)
2. **Laravel cache**: `Cache::forget("user_theme_{$userId}")`
3. **Tagged cache** (if supported): `Cache::tags(['user_themes', "user_{$userId}"])->flush()`

The `ThemeHelper::clearUserThemeCache()` method should handle all of these.
