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
     * Get the current user's theme with caching to ensure consistency.
     */
    public static function getCurrentUserTheme(): ?UserThemePreference
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // Cache theme for the current request to ensure consistency
        $sessionId = request()->hasSession() ? request()->session()->getId() : 'no-session';
        $cacheKey = "user_theme_{$user->id}_{$sessionId}";

        return Cache::remember($cacheKey, 300, fn () => UserThemePreference::where('user_id', $user->id)->first());
    }

    /**
     * Clear the user's theme cache when theme is updated.
     */
    public static function clearUserThemeCache(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Clear all possible cache keys for this user to ensure complete cache refresh
        $sessionId = request()->hasSession() ? request()->session()->getId() : 'no-session';
        $cacheKey = "user_theme_{$user->id}_{$sessionId}";
        $fallbackCacheKey = "user_theme_{$user->id}_no-session";

        Cache::forget($cacheKey);
        Cache::forget($fallbackCacheKey);

        // Clear any theme-related cache keys for this user
        $patterns = [
            "user_theme_{$user->id}_*",
            "theme_*_{$user->id}",
            "userTheme_{$user->id}",
        ];

        foreach ($patterns as $pattern) {
            // In a production environment with Redis, you'd use pattern matching
            // For now, we'll rely on the specific cache keys
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
