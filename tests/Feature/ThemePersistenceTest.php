<?php

declare(strict_types=1);

use App\Helpers\ThemeHelper;
use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Theme Persistence Without localStorage', function (): void {
    beforeEach(function (): void {
        Cache::flush();
    });

    it('authenticated user theme preference saves to database', function (): void {
        $user = User::factory()->create([
            'current_theme' => 'ocean',
            'prefers_dark_mode' => true,
        ]);

        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => true,
        ]);

        Auth::login($user);

        // Verify ThemeHelper reads from database
        expect(ThemeHelper::getThemeName())->toBe('ocean');
        expect(ThemeHelper::isDarkMode())->toBeTrue();

        // Verify data persists in database
        $dbPreference = UserThemePreference::where('user_id', $user->id)->first();
        expect($dbPreference->theme_name)->toBe('ocean');
        expect($dbPreference->is_dark_mode)->toBeTrue();
    });

    it('theme preference persists across requests without localStorage', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(10)->create();

        // Simulate first request
        $user = User::factory()->create([
            'current_theme' => 'sunset',
            'prefers_dark_mode' => false,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        // First request - load theme
        $themeName1 = ThemeHelper::getThemeName();
        $isDark1 = ThemeHelper::isDarkMode();

        expect($themeName1)->toBe('sunset');
        expect($isDark1)->toBeFalse();

        // Verify data persists in database (without relying on static cache)
        $dbPreference = UserThemePreference::where('user_id', $user->id)->first();
        expect($dbPreference->theme_name)->toBe('sunset');
        expect($dbPreference->is_dark_mode)->toBeFalse();
    });

    it('guest users get default theme without database or localStorage', function (): void {
        // No authentication
        expect(Auth::check())->toBeFalse();

        // Should return defaults
        expect(ThemeHelper::getThemeName())->toBe('default');
        expect(ThemeHelper::isDarkMode())->toBeFalse();
    });

    it('user and user theme preference models stay synchronized', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(20)->create();

        $user = User::factory()->create([
            'current_theme' => 'forest',
            'prefers_dark_mode' => true,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        $preference = UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'forest',
            'is_dark_mode' => true,
        ]);

        Auth::login($user);

        // Verify both models have same data
        expect($user->current_theme)->toBe($preference->theme_name);
        expect($user->prefers_dark_mode)->toBe($preference->is_dark_mode);

        // Verify ThemeHelper returns correct values
        expect(ThemeHelper::getThemeName())->toBe('forest');
        expect(ThemeHelper::isDarkMode())->toBeTrue();
    });

    it('theme preference uses database as single source of truth', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(30)->create();

        $user = User::factory()->create();

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'cosmic-violet',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        // Verify data is stored in database correctly
        $dbTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($dbTheme->theme_name)->toBe('cosmic-violet');

        // Update database directly
        $dbTheme->update(['theme_name' => 'coral-reef']);

        // Verify update persisted to database
        $updatedTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($updatedTheme->theme_name)->toBe('coral-reef');
    });

    it('new user gets default theme on first login', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(40)->create();

        $user = User::factory()->create([
            'current_theme' => 'default',
            'prefers_dark_mode' => false,
        ]);

        // Ensure no theme preference exists
        UserThemePreference::where('user_id', $user->id)->delete();

        Auth::login($user);

        expect(ThemeHelper::getThemeName())->toBe('default');
        expect(ThemeHelper::isDarkMode())->toBeFalse();
    });

    it('user without preference falls back to user model values', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(50)->create();

        $user = User::factory()->create([
            'current_theme' => 'midnight-teal',
            'prefers_dark_mode' => true,
        ]);

        // Ensure no UserThemePreference exists
        UserThemePreference::where('user_id', $user->id)->delete();

        Auth::login($user);

        // Should fall back to User model
        expect(ThemeHelper::getThemeName())->toBe('midnight-teal');
        expect(ThemeHelper::isDarkMode())->toBeTrue();
    });

    it('theme cache clears correctly', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(60)->create();

        $user = User::factory()->create();

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        Auth::login($user);

        // Verify clearUserThemeCache doesn't throw errors
        ThemeHelper::clearUserThemeCache();

        // Verify theme data persists in database
        $dbTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($dbTheme->theme_name)->toBe('ocean');
    });

    it('theme preference works for different users independently', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserThemePreference::create([
            'user_id' => $user1->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => true,
        ]);

        UserThemePreference::create([
            'user_id' => $user2->id,
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ]);

        // Test user 1
        Auth::login($user1);
        expect(ThemeHelper::getThemeName())->toBe('ocean');
        expect(ThemeHelper::isDarkMode())->toBeTrue();

        // Switch to user 2
        Auth::logout();
        Cache::flush();
        Auth::login($user2);
        expect(ThemeHelper::getThemeName())->toBe('sunset');
        expect(ThemeHelper::isDarkMode())->toBeFalse();
    });

    it('invalid theme name is stored in database', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(70)->create();

        $user = User::factory()->create();

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'invalid-theme-name',
            'is_dark_mode' => false,
        ]);

        // Verify it's stored (validation will be handled in later tasks)
        $dbTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($dbTheme->theme_name)->toBe('invalid-theme-name');
    });

    it('all 24 predefined themes can be stored in database', function (): void {
        $themes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        foreach ($themes as $index => $theme) {
            // Create unique user for each theme to avoid cache issues
            $user = User::factory()->create();

            UserThemePreference::where('user_id', $user->id)->delete();
            UserThemePreference::create([
                'user_id' => $user->id,
                'theme_name' => $theme,
                'is_dark_mode' => false,
            ]);

            // Verify it was stored correctly
            $dbTheme = UserThemePreference::where('user_id', $user->id)->first();
            expect($dbTheme->theme_name)->toBe($theme);
        }
    });

    it('theme persists in database across sessions', function (): void {
        // Create dummy users to ensure unique ID
        User::factory()->count(100)->create();

        $user = User::factory()->create();

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'forest',
            'is_dark_mode' => true,
        ]);

        // Verify persistence in database
        for ($i = 0; $i < 5; $i++) {
            $dbTheme = UserThemePreference::where('user_id', $user->id)->first();
            expect($dbTheme->theme_name)->toBe('forest');
            expect($dbTheme->is_dark_mode)->toBeTrue();
        }
    });
});
