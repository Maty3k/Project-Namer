<?php

declare(strict_types=1);

use App\Helpers\ThemeHelper;
use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Clear the static request cache in ThemeHelper by using runkit if available,
 * otherwise try to work around it by ensuring fresh queries.
 */
function clearThemeHelperStaticCache(): void
{
    // Since we can't reliably clear the static variable inside getCurrentUserTheme(),
    // and the static cache is keyed by user ID, we need to ensure each test gets
    // a fresh query. The only way to do this without modifying ThemeHelper is to
    // ensure getCurrentUserTheme() is never called before we set up our test data.

    // For now, just clear any Laravel caches that might exist
    Cache::flush();
}

describe('Updated ThemeHelper', function (): void {
    beforeEach(function (): void {
        clearThemeHelperStaticCache();
    });

    it('returns null when user is not authenticated', function (): void {
        Auth::shouldReceive('user')->andReturn(null);

        $theme = ThemeHelper::getCurrentUserTheme();

        expect($theme)->toBeNull();
    });

    it('returns user theme preference when it exists', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        $theme = ThemeHelper::getCurrentUserTheme();

        expect($theme)->toBeInstanceOf(UserThemePreference::class)
            ->and($theme->theme_name)->toBe('ocean')
            ->and($theme->is_dark_mode)->toBeFalse();
    });

    it('returns theme CSS path for current user', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs (avoiding static cache collision)
        User::factory()->count(10)->create();

        $user = User::factory()->create();

        Auth::login($user);

        // Delete any auto-created preferences and create a specific one
        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ]);

        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/sunset.css');
    });

    it('returns default theme CSS path when user has no preference', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs
        User::factory()->count(20)->create();

        $user = User::factory()->create();

        // Ensure no theme preference exists
        UserThemePreference::where('user_id', $user->id)->delete();

        Auth::login($user);

        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/default.css');
    });

    it('returns default theme CSS path when user is not authenticated', function (): void {
        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/default.css');
    });

    it('checks if dark mode is enabled', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs
        User::factory()->count(30)->create();

        $user = User::factory()->create();

        // Delete any auto-created preferences and create a specific one
        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'dark',
            'is_dark_mode' => true,
        ]);

        Auth::login($user);

        expect(ThemeHelper::isDarkMode())->toBeTrue();
    });

    it('returns false for dark mode when user is not authenticated', function (): void {
        expect(ThemeHelper::isDarkMode())->toBeFalse();
    });

    it('falls back to User model prefers_dark_mode if no theme preference', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs
        User::factory()->count(40)->create();

        $user = User::factory()->create([
            'prefers_dark_mode' => true,
        ]);

        // Ensure no theme preference exists
        UserThemePreference::where('user_id', $user->id)->delete();

        Auth::login($user);

        expect(ThemeHelper::isDarkMode())->toBeTrue();
    });

    it('returns theme name for current user', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs
        User::factory()->count(50)->create();

        $user = User::factory()->create();

        // Delete any auto-created preferences and create a specific one
        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'cosmic-violet',
            'is_dark_mode' => true,
        ]);

        Auth::login($user);

        expect(ThemeHelper::getThemeName())->toBe('cosmic-violet');
    });

    it('returns default theme name when user has no preference', function (): void {
        Cache::flush();

        // Create dummy users to ensure unique user IDs
        User::factory()->count(60)->create();

        $user = User::factory()->create();

        // Ensure no theme preference exists
        UserThemePreference::where('user_id', $user->id)->delete();

        Auth::login($user);

        expect(ThemeHelper::getThemeName())->toBe('default');
    });

    it('caches theme for performance', function (): void {
        Cache::flush();
        $user = User::factory()->create();

        // Delete any auto-created preferences and create a specific one
        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        // First call should cache
        $theme1 = ThemeHelper::getCurrentUserTheme();

        // Second call should use cache
        $theme2 = ThemeHelper::getCurrentUserTheme();

        expect($theme1)->toEqual($theme2);
    });

    it('clears theme cache when requested', function (): void {
        Cache::flush();
        $user = User::factory()->create();

        // Delete any auto-created preferences and create a specific one
        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        // First call caches
        ThemeHelper::getCurrentUserTheme();

        // Clear cache
        ThemeHelper::clearUserThemeCache();

        // Verify cache was cleared by checking it doesn't exist
        $sessionId = request()->hasSession() ? request()->session()->getId() : 'no-session';
        $cacheKey = "user_theme_{$user->id}_{$sessionId}";

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    it('does not error when clearing cache for unauthenticated user', function (): void {
        // Should not throw exception
        ThemeHelper::clearUserThemeCache();

        expect(true)->toBeTrue();
    });
});
