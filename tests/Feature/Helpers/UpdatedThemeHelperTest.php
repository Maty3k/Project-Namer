<?php

declare(strict_types=1);

use App\Helpers\ThemeHelper;
use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Updated ThemeHelper', function (): void {
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
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/sunset.css');
    });

    it('returns default theme CSS path when user has no preference', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/default.css');
    });

    it('returns default theme CSS path when user is not authenticated', function (): void {
        $path = ThemeHelper::getThemeCssPath();

        expect($path)->toBe('/css/themes/default.css');
    });

    it('checks if dark mode is enabled', function (): void {
        $user = User::factory()->create();
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
        $user = User::factory()->create([
            'prefers_dark_mode' => true,
        ]);

        Auth::login($user);

        expect(ThemeHelper::isDarkMode())->toBeTrue();
    });

    it('returns theme name for current user', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'cosmic-violet',
            'is_dark_mode' => true,
        ]);

        Auth::login($user);

        expect(ThemeHelper::getThemeName())->toBe('cosmic-violet');
    });

    it('returns default theme name when user has no preference', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        expect(ThemeHelper::getThemeName())->toBe('default');
    });

    it('caches theme for performance', function (): void {
        $user = User::factory()->create();
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
        $user = User::factory()->create();
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
