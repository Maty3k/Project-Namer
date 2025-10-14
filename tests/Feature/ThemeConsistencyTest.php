<?php

declare(strict_types=1);

use App\Livewire\NameGeneratorDashboard;
use App\Livewire\ThemeCustomizer;
use App\Models\User;
use App\Models\UserThemePreference;
use Livewire\Livewire;

test('theme preferences are consistently applied across components', function (): void {
    // Create a user with a theme preference
    $user = User::factory()->create();

    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'ocean',
        'is_dark_mode' => false,
    ]);

    // Act as the user
    $this->actingAs($user);

    // Test ThemeCustomizer component
    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Verify theme properties are loaded correctly
    expect($themeCustomizer->get('themeName'))->toBe('ocean');
    expect($themeCustomizer->get('isDarkMode'))->toBeFalse();

    // Test NameGeneratorDashboard component
    $dashboard = Livewire::test(NameGeneratorDashboard::class);

    // Check that the dashboard renders successfully with theme
    $dashboard->assertStatus(200);

    // Verify theme data is accessible to the dashboard
    $themeData = $dashboard->viewData('userTheme');
    expect($themeData)->not->toBeNull();
    expect($themeData->theme_name)->toBe('ocean');
    expect($themeData->is_dark_mode)->toBeFalse();
});

test('theme consistency across different UI components', function (): void {
    $user = User::factory()->create();

    // Create a dark theme preference
    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'midnight-teal',
        'is_dark_mode' => true,
    ]);

    $this->actingAs($user);

    // Test theme application consistency
    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Verify theme is loaded
    expect($themeCustomizer->get('themeName'))->toBe('midnight-teal');
    expect($themeCustomizer->get('isDarkMode'))->toBeTrue();

    // Apply a different predefined theme
    $themeCustomizer->call('applyPreset', 'sunset');

    // Verify the theme was applied
    $themeCustomizer->assertDispatched('theme-applied');
    $themeCustomizer->assertDispatched('theme-updated');

    // Check the database was updated
    $updatedTheme = UserThemePreference::where('user_id', $user->id)->first();
    expect($updatedTheme)->not->toBeNull();
    expect($updatedTheme->theme_name)->toBe('sunset');

    // Test that all UI components receive the same theme data
    $dashboard = Livewire::test(NameGeneratorDashboard::class);
    $dashboardTheme = $dashboard->viewData('userTheme');

    // Ensure theme consistency between components
    expect($dashboardTheme)->not->toBeNull();
    expect($dashboardTheme->theme_name)->toBe($updatedTheme->theme_name);
    expect($dashboardTheme->is_dark_mode)->toBe($updatedTheme->is_dark_mode);
});

test('theme CSS file path is correctly generated', function (): void {
    $user = User::factory()->create();

    $theme = UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'neon-cyber',
        'is_dark_mode' => true,
    ]);

    $this->actingAs($user);

    // Test that theme CSS path is correctly generated
    $expectedPath = '/css/themes/neon-cyber.css';
    $actualPath = $theme->getThemeCssPath();

    expect($actualPath)->toBe($expectedPath);

    // Verify the CSS file exists
    $fullPath = public_path('css/themes/neon-cyber.css');
    expect(file_exists($fullPath))->toBeTrue();
});

test('theme switching maintains consistency', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Apply different themes and verify consistency
    $themesToTest = ['dark', 'ocean', 'sunset', 'forest'];

    foreach ($themesToTest as $themeName) {
        $themeCustomizer->call('applyPreset', $themeName);

        // Verify theme was saved to database
        $savedTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($savedTheme)->not->toBeNull();
        expect($savedTheme->theme_name)->toBe($themeName);

        // Verify CSS file exists for this theme
        $cssPath = public_path("css/themes/{$themeName}.css");
        expect(file_exists($cssPath))->toBeTrue();

        // Test that dashboard gets the same theme data
        $dashboard = Livewire::test(NameGeneratorDashboard::class);
        $dashboardTheme = $dashboard->viewData('userTheme');

        if ($dashboardTheme) {
            expect($dashboardTheme->theme_name)->toBe($themeName);
        }
    }
});

test('all predefined themes have corresponding CSS files', function (): void {
    $predefinedThemes = [
        'default',
        'dark',
        'ocean',
        'sunset',
        'forest',
        'cosmic-violet',
        'coral-reef',
        'midnight-teal',
        'summer',
        'winter',
        'halloween',
        'spring',
        'autumn',
        'neon-cyber',
        'electric-blue',
        'hot-pink',
        'lava-red',
        'lime-punch',
        'gold-rush',
        'matrix-green',
    ];

    foreach ($predefinedThemes as $themeName) {
        $cssPath = public_path("css/themes/{$themeName}.css");
        expect(file_exists($cssPath))->toBeTrue(
            "CSS file for theme '{$themeName}' should exist at {$cssPath}"
        );

        // Verify CSS file has some content
        $content = file_get_contents($cssPath);
        expect(strlen($content))->toBeGreaterThan(0);
    }
});
