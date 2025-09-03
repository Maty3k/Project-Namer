<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;

test('can create user theme preference with required attributes', function (): void {
    $user = User::factory()->create();

    $themePreference = UserThemePreference::factory()->create([
        'user_id' => $user->id,
        'theme_name' => 'custom-blue',
        'is_custom_theme' => true,
        'primary_color' => '#3B82F6',
        'secondary_color' => '#8B5CF6',
    ]);

    expect($themePreference->user_id)->toBe($user->id);
    expect($themePreference->theme_name)->toBe('custom-blue');
    expect($themePreference->is_custom_theme)->toBeTrue();
    expect($themePreference->primary_color)->toBe('#3B82F6');
    expect($themePreference->secondary_color)->toBe('#8B5CF6');
});

test('belongs to user relationship works', function (): void {
    $user = User::factory()->create();
    $themePreference = UserThemePreference::factory()->create(['user_id' => $user->id]);

    expect($themePreference->user)->toBeInstanceOf(User::class);
    expect($themePreference->user->id)->toBe($user->id);
});

test('scope for theme filters correctly', function (): void {
    $theme1 = UserThemePreference::factory()->create(['theme_name' => 'dark']);
    $theme2 = UserThemePreference::factory()->create(['theme_name' => 'light']);

    $results = UserThemePreference::forTheme('dark')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($theme1->id);
});

test('scope custom themes filters correctly', function (): void {
    $customTheme = UserThemePreference::factory()->create(['is_custom_theme' => true]);
    $defaultTheme = UserThemePreference::factory()->create(['is_custom_theme' => false]);

    $results = UserThemePreference::customThemes()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($customTheme->id);
});

test('validate color hex returns true for valid colors', function (): void {
    $themePreference = UserThemePreference::factory()->create();

    expect($themePreference->validateColorHex('#3B82F6'))->toBeTrue();
    expect($themePreference->validateColorHex('#FFFFFF'))->toBeTrue();
    expect($themePreference->validateColorHex('#000000'))->toBeTrue();
});

test('validate color hex returns false for invalid colors', function (): void {
    $themePreference = UserThemePreference::factory()->create();

    expect($themePreference->validateColorHex('3B82F6'))->toBeFalse(); // Missing #
    expect($themePreference->validateColorHex('#3B82F'))->toBeFalse(); // Too short
    expect($themePreference->validateColorHex('#3B82F6G'))->toBeFalse(); // Invalid character
    expect($themePreference->validateColorHex('#3b82f6g'))->toBeFalse(); // Invalid character lowercase
});

test('get light mode colors returns correct array', function (): void {
    $themePreference = UserThemePreference::factory()->create([
        'primary_color' => '#3B82F6',
        'secondary_color' => '#8B5CF6',
        'accent_color' => '#10B981',
        'background_color' => '#FFFFFF',
        'surface_color' => '#F8FAFC',
        'text_primary_color' => '#1F2937',
        'text_secondary_color' => '#6B7280',
    ]);

    $colors = $themePreference->getLightModeColors();

    expect($colors)->toEqual([
        'primary' => '#3B82F6',
        'secondary' => '#8B5CF6',
        'accent' => '#10B981',
        'background' => '#FFFFFF',
        'surface' => '#F8FAFC',
        'text_primary' => '#1F2937',
        'text_secondary' => '#6B7280',
    ]);
});

test('get dark mode colors returns correct array', function (): void {
    $themePreference = UserThemePreference::factory()->create([
        'primary_color' => '#3B82F6',
        'secondary_color' => '#8B5CF6',
        'accent_color' => '#10B981',
        'dark_background_color' => '#111827',
        'dark_surface_color' => '#1F2937',
        'dark_text_primary_color' => '#F9FAFB',
        'dark_text_secondary_color' => '#D1D5DB',
    ]);

    $colors = $themePreference->getDarkModeColors();

    expect($colors)->toEqual([
        'background' => '#111827',
        'surface' => '#1F2937',
        'text_primary' => '#F9FAFB',
        'text_secondary' => '#D1D5DB',
        'primary' => '#3B82F6',
        'secondary' => '#8B5CF6',
        'accent' => '#10B981',
    ]);
});

test('generate css variables for light mode works correctly', function (): void {
    $themePreference = UserThemePreference::factory()->create([
        'primary_color' => '#3B82F6',
        'background_color' => '#FFFFFF',
        'text_primary_color' => '#1F2937',
        'border_radius' => 'medium',
        'font_size' => 'large',
    ]);

    $variables = $themePreference->generateCssVariables(false);

    expect($variables)->toHaveKey('--color-primary');
    expect($variables['--color-primary'])->toBe('#3B82F6');
    expect($variables)->toHaveKey('--color-background');
    expect($variables['--color-background'])->toBe('#FFFFFF');
    expect($variables)->toHaveKey('--border-radius-base');
    expect($variables['--border-radius-base'])->toBe('0.375rem');
    expect($variables)->toHaveKey('--font-size-base');
    expect($variables['--font-size-base'])->toBe('1.125rem');
});

test('generate css variables for dark mode works correctly', function (): void {
    $themePreference = UserThemePreference::factory()->create([
        'primary_color' => '#3B82F6',
        'dark_background_color' => '#111827',
        'dark_text_primary_color' => '#F9FAFB',
        'border_radius' => 'large',
        'font_size' => 'small',
    ]);

    $variables = $themePreference->generateCssVariables(true);

    expect($variables)->toHaveKey('--color-primary');
    expect($variables['--color-primary'])->toBe('#3B82F6');
    expect($variables)->toHaveKey('--color-background');
    expect($variables['--color-background'])->toBe('#111827');
    expect($variables)->toHaveKey('--border-radius-base');
    expect($variables['--border-radius-base'])->toBe('0.75rem');
    expect($variables)->toHaveKey('--font-size-base');
    expect($variables['--font-size-base'])->toBe('0.875rem');
});

test('get default theme returns correct configuration', function (): void {
    $defaultTheme = UserThemePreference::getDefaultTheme();

    expect($defaultTheme)->toBeArray();
    expect($defaultTheme['theme_name'])->toBe('default');
    expect($defaultTheme['is_custom_theme'])->toBeFalse();
    expect($defaultTheme['primary_color'])->toBe('#3B82F6');
    expect($defaultTheme['background_color'])->toBe('#FFFFFF');
    expect($defaultTheme['dark_background_color'])->toBe('#111827');
    expect($defaultTheme['border_radius'])->toBe('medium');
    expect($defaultTheme['font_size'])->toBe('medium');
    expect($defaultTheme['compact_mode'])->toBeFalse();
});
