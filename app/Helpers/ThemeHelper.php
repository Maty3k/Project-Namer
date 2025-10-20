<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\UserThemePreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized theme helper to ensure consistent theme loading across all components.
 */
final class ThemeHelper
{
    /**
     * Get the current user's theme - always fresh from database.
     * No time-based caching to avoid stale data issues during navigation.
     */
    public static function getCurrentUserTheme(): ?UserThemePreference
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // Always query fresh from database to ensure accuracy
        // Use static property for request-scoped caching only
        static $requestCache = [];
        $requestKey = "user_{$user->id}";

        if (! isset($requestCache[$requestKey])) {
            $requestCache[$requestKey] = UserThemePreference::where('user_id', $user->id)->first();
        }

        return $requestCache[$requestKey];
    }

    /**
     * Clear the user's theme cache when theme is updated.
     * Forces next call to getCurrentUserTheme() to query the database.
     */
    public static function clearUserThemeCache(): void
    {
        // The static cache in getCurrentUserTheme() is automatically cleared
        // between requests, so we just need to clear any Laravel cache
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Clear any Laravel cache entries that might exist
        try {
            Cache::forget("user_theme_{$user->id}");
            if (function_exists('cache')) {
                if (method_exists(cache()->getStore(), 'tags')) {
                    cache()->tags(['user_themes', "user_{$user->id}"])->flush();
                }
            }
        } catch (\Exception $e) {
            // Silently continue if cache operations fail
            logger()->debug('Cache clearing failed: '.$e->getMessage());
        }
    }

    /**
     * Check if the current theme is dark mode.
     */
    public static function isDarkMode(): bool
    {
        $theme = self::getCurrentUserTheme();

        if ($theme) {
            return $theme->is_dark_mode;
        }

        // Fall back to User model if no theme preference
        $user = Auth::user();

        return $user ? ($user->prefers_dark_mode ?? false) : false;
    }

    /**
     * Get the theme CSS file path for the current user.
     */
    public static function getThemeCssPath(): string
    {
        $theme = self::getCurrentUserTheme();

        if ($theme) {
            return $theme->getThemeCssPath();
        }

        // Default theme for unauthenticated users
        return '/css/themes/default.css';
    }

    /**
     * Get the theme name for the current user.
     */
    public static function getThemeName(): string
    {
        $theme = self::getCurrentUserTheme();

        if ($theme) {
            return $theme->theme_name;
        }

        // Fall back to User model if no theme preference
        $user = Auth::user();

        return $user && $user->current_theme ? $user->current_theme : 'default';
    }
}
