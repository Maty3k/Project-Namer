<?php

declare(strict_types=1);

use App\Livewire\NameGeneratorDashboard;
use App\Livewire\ThemeCustomizer;
use App\Models\User;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Livewire;

test('theme colors are consistently applied across entire UI', function (): void {
    // Create a user with a custom theme
    $user = User::factory()->create();

    $customTheme = UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'test-custom',
        'is_custom_theme' => true,
        'primary_color' => '#FF6B6B',
        'secondary_color' => '#4ECDC4',
        'accent_color' => '#45B7D1',
        'background_color' => '#F8F9FA',
        'surface_color' => '#FFFFFF',
        'text_primary_color' => '#2C3E50',
        'text_secondary_color' => '#7F8C8D',
        'text_color' => '#2C3E50',
        'dark_background_color' => '#1A202C',
        'dark_surface_color' => '#2D3748',
        'dark_text_primary_color' => '#F7FAFC',
        'dark_text_secondary_color' => '#E2E8F0',
        'border_radius' => 'medium',
        'font_size' => 'medium',
        'compact_mode' => false,
        'is_dark_mode' => false,
    ]);

    // Act as the user
    $this->actingAs($user);

    // Test ThemeCustomizer component
    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Verify theme properties are loaded correctly
    expect($themeCustomizer->get('primaryColor'))->toBe('#FF6B6B');
    expect($themeCustomizer->get('textColor'))->toBe('#2C3E50');
    expect($themeCustomizer->get('backgroundColor'))->toBe('#F8F9FA');
    expect($themeCustomizer->get('accentColor'))->toBe('#45B7D1');

    // Test NameGeneratorDashboard component
    $dashboard = Livewire::test(NameGeneratorDashboard::class);

    // Check that the dashboard renders successfully with theme
    $dashboard->assertStatus(200);

    // Verify theme colors are accessible to the dashboard
    $themeData = $dashboard->viewData('userTheme');
    expect($themeData)->not->toBeNull();
    expect($themeData->primary_color)->toBe('#FF6B6B');
    expect($themeData->text_color)->toBe('#2C3E50');
    expect($themeData->background_color)->toBe('#F8F9FA');

    // Test CSS generation consistency
    $themeService = app(ThemeService::class);
    $generatedCss = $themeService->generateCssProperties([
        'primary_color' => $customTheme->primary_color,
        'accent_color' => $customTheme->accent_color,
        'background_color' => $customTheme->background_color,
        'text_color' => $customTheme->text_color,
    ]);

    // Verify CSS contains the correct colors
    expect($generatedCss)->toContain('#FF6B6B'); // primary color
    expect($generatedCss)->toContain('#2C3E50'); // text color
    expect($generatedCss)->toContain('#F8F9FA'); // background color
    expect($generatedCss)->toContain('#45B7D1'); // accent color
});

test('theme consistency across different UI components', function (): void {
    $user = User::factory()->create();

    // Create a custom dark theme
    $darkTheme = UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'dark-custom',
        'is_custom_theme' => true,
        'primary_color' => '#8B5CF6',
        'secondary_color' => '#10B981',
        'accent_color' => '#F59E0B',
        'background_color' => '#1F2937',
        'surface_color' => '#374151',
        'text_primary_color' => '#F9FAFB',
        'text_secondary_color' => '#D1D5DB',
        'text_color' => '#F9FAFB',
        'dark_background_color' => '#1F2937',
        'dark_surface_color' => '#374151',
        'dark_text_primary_color' => '#F9FAFB',
        'dark_text_secondary_color' => '#D1D5DB',
        'border_radius' => 'large',
        'font_size' => 'large',
        'compact_mode' => false,
        'is_dark_mode' => true,
    ]);

    $this->actingAs($user);

    // Test theme application consistency
    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Apply a predefined theme
    $themeCustomizer->call('applyPreset', 'dark');

    // Verify the theme was applied
    $themeCustomizer->assertDispatched('theme-applied');
    $themeCustomizer->assertDispatched('theme-updated');

    // Check the database was updated
    $updatedTheme = UserThemePreference::where('user_id', $user->id)->first();
    expect($updatedTheme)->not->toBeNull();

    // Test that all UI components receive the same theme data
    $dashboard = Livewire::test(NameGeneratorDashboard::class);
    $dashboardTheme = $dashboard->viewData('userTheme');

    // Ensure theme consistency between components
    expect($dashboardTheme)->not->toBeNull();
    expect($dashboardTheme->theme_name)->toBe($updatedTheme->theme_name);
    expect($dashboardTheme->primary_color)->toBe($updatedTheme->primary_color);
    expect($dashboardTheme->text_color)->toBe($updatedTheme->text_color);
});

test('theme colors cascade properly through UI hierarchy', function (): void {
    $user = User::factory()->create();

    $theme = UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'cascade-test',
        'is_custom_theme' => true,
        'primary_color' => '#E53E3E',
        'secondary_color' => '#38A169',
        'accent_color' => '#3182CE',
        'background_color' => '#FFFFFF',
        'surface_color' => '#F7FAFC',
        'text_primary_color' => '#1A202C',
        'text_secondary_color' => '#4A5568',
        'text_color' => '#1A202C',
        'dark_background_color' => '#1A202C',
        'dark_surface_color' => '#2D3748',
        'dark_text_primary_color' => '#F7FAFC',
        'dark_text_secondary_color' => '#E2E8F0',
        'border_radius' => 'small',
        'font_size' => 'small',
        'compact_mode' => true,
        'is_dark_mode' => false,
    ]);

    $this->actingAs($user);

    // Test that theme variables are consistent across components
    $themeService = app(ThemeService::class);

    // Test light mode CSS generation
    $lightCss = $themeService->generateCssProperties([
        'primary_color' => $theme->primary_color,
        'background_color' => $theme->background_color,
        'text_color' => $theme->text_color,
    ]);

    // Verify CSS variable naming consistency
    expect($lightCss)->toContain('--color-primary');
    expect($lightCss)->toContain('--color-background');
    expect($lightCss)->toContain('--color-text');

    // Test that the same colors appear in all CSS variables
    expect($lightCss)->toContain($theme->primary_color);
    expect($lightCss)->toContain($theme->background_color);
    expect($lightCss)->toContain($theme->text_color);

    // Verify theme application in dashboard
    $dashboard = Livewire::test(NameGeneratorDashboard::class);

    // Verify dashboard renders successfully with theme
    $dashboard->assertStatus(200);

    // Verify the dashboard has theme data
    $dashboardTheme = $dashboard->viewData('userTheme');
    expect($dashboardTheme)->not->toBeNull();
});

test('theme switching maintains color consistency', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $themeCustomizer = Livewire::test(ThemeCustomizer::class);

    // Apply different themes and verify consistency
    $themesToTest = ['dark', 'ocean', 'sunset', 'forest'];

    foreach ($themesToTest as $themeName) {
        $themeCustomizer->call('applyPreset', $themeName);

        // Get the current theme state
        $primaryColor = $themeCustomizer->get('primaryColor');
        $backgroundColor = $themeCustomizer->get('backgroundColor');
        $textColor = $themeCustomizer->get('textColor');

        // Verify colors are valid hex colors
        expect($primaryColor)->toMatch('/^#[0-9A-Fa-f]{6}$/');
        expect($backgroundColor)->toMatch('/^#[0-9A-Fa-f]{6}$/');
        expect($textColor)->toMatch('/^#[0-9A-Fa-f]{6}$/');

        // Verify theme was saved to database
        $savedTheme = UserThemePreference::where('user_id', $user->id)->first();
        expect($savedTheme)->not->toBeNull();
        expect($savedTheme->primary_color)->toBe($primaryColor);
        expect($savedTheme->background_color)->toBe($backgroundColor);
        expect($savedTheme->text_color)->toBe($textColor);

        // Test that dashboard gets the same theme data
        $dashboard = Livewire::test(NameGeneratorDashboard::class);
        $dashboardTheme = $dashboard->viewData('userTheme');

        if ($dashboardTheme) {
            expect($dashboardTheme->primary_color)->toBe($primaryColor);
            expect($dashboardTheme->background_color)->toBe($backgroundColor);
            expect($dashboardTheme->text_color)->toBe($textColor);
        }
    }
});

test('theme CSS variables are properly generated and consistent', function (): void {
    $user = User::factory()->create();

    $theme = UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'css-test',
        'is_custom_theme' => true,
        'primary_color' => '#6366F1',
        'secondary_color' => '#EC4899',
        'accent_color' => '#10B981',
        'background_color' => '#F9FAFB',
        'surface_color' => '#FFFFFF',
        'text_primary_color' => '#111827',
        'text_secondary_color' => '#6B7280',
        'text_color' => '#111827',
        'dark_background_color' => '#111827',
        'dark_surface_color' => '#1F2937',
        'dark_text_primary_color' => '#F9FAFB',
        'dark_text_secondary_color' => '#D1D5DB',
        'border_radius' => 'large',
        'font_size' => 'large',
        'compact_mode' => false,
        'is_dark_mode' => false,
    ]);

    // Test CSS variable generation for light mode
    $lightVars = $theme->generateCssVariables(false);

    expect($lightVars)->toHaveKey('--color-primary');
    expect($lightVars)->toHaveKey('--color-background');
    expect($lightVars)->toHaveKey('--color-text-primary');
    expect($lightVars)->toHaveKey('--border-radius-base');
    expect($lightVars)->toHaveKey('--font-size-base');

    expect($lightVars['--color-primary'])->toBe('#6366F1');
    expect($lightVars['--color-background'])->toBe('#F9FAFB');
    expect($lightVars['--color-text-primary'])->toBe('#111827');
    expect($lightVars['--border-radius-base'])->toBe('0.75rem');
    expect($lightVars['--font-size-base'])->toBe('1.125rem');

    // Test CSS variable generation for dark mode
    $darkVars = $theme->generateCssVariables(true);

    expect($darkVars['--color-background'])->toBe('#111827');
    expect($darkVars['--color-text-primary'])->toBe('#F9FAFB');
    expect($darkVars['--color-primary'])->toBe('#6366F1'); // Primary should be same in dark mode
});
