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

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $preference = UserThemePreference::where('user_id', $user->id)->first();

            // If no detailed preference exists, create one from User model settings
            if (! $preference && ($user->prefers_dark_mode !== null || $user->current_theme !== null)) {
                $preference = new UserThemePreference([
                    'user_id' => $user->id,
                    'theme_name' => $user->current_theme ?? 'default',
                    'is_dark_mode' => $user->prefers_dark_mode ?? false,
                    'primary_color' => '#3b82f6',
                    'accent_color' => '#10b981',
                    'background_color' => ($user->prefers_dark_mode ?? false) ? '#1f2937' : '#ffffff',
                    'text_color' => ($user->prefers_dark_mode ?? false) ? '#f9fafb' : '#111827',
                ]);
            }

            return $preference;
        });
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
     * Get theme CSS variables for consistent styling.
     *
     * @return array<string, string>
     */
    public static function getThemeCssVariables(): array
    {
        $theme = self::getCurrentUserTheme();

        if (! $theme) {
            return [];
        }

        return $theme->generateCssVariables($theme->is_dark_mode);
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
     * Get theme-aware background color for components.
     */
    public static function getComponentBackgroundColor(string $componentType = 'surface'): string
    {
        $theme = self::getCurrentUserTheme();

        if (! $theme) {
            return '#ffffff';
        }

        return match ($componentType) {
            'main' => $theme->background_color,
            'surface' => $theme->surface_color ?? '#f8fafc',
            'sidebar' => $theme->is_dark_mode ? $theme->background_color : ($theme->surface_color ?? '#f8fafc'),
            default => $theme->background_color,
        };
    }

    /**
     * Get theme-aware text color.
     */
    public static function getTextColor(): string
    {
        $theme = self::getCurrentUserTheme();

        return $theme ? $theme->text_color : '#111827';
    }

    /**
     * Get theme-aware primary color.
     */
    public static function getPrimaryColor(): string
    {
        $theme = self::getCurrentUserTheme();

        return $theme ? $theme->primary_color : '#3b82f6';
    }
}
