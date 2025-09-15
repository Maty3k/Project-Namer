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
            return UserThemePreference::where('user_id', $user->id)->first();
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

        $sessionId = request()->hasSession() ? request()->session()->getId() : 'no-session';
        $cacheKey = "user_theme_{$user->id}_{$sessionId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get theme CSS variables for consistent styling.
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
        
        return $theme ? $theme->is_dark_mode : false;
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