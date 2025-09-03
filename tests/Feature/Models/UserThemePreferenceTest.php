<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;

test('can create user theme preference with required attributes', function (): void {
    $user = User::factory()->create();

    $themePreference = UserThemePreference::factory()->create([
        'user_id' => $user->id,
        'theme_name' => 'Custom Blue',
        'is_custom_theme' => true,
        'primary_color' => '#3B82F6',
        'secondary_color' => '#8B5CF6',
        'accent_color' => '#10B981',
        'background_color' => '#FFFFFF',
        'surface_color' => '#F8FAFC',
        'text_primary_color' => '#1F2937',
        'text_secondary_color' => '#6B7280',
        'dark_background_color' => '#111827',
        'dark_surface_color' => '#1F2937',
        'dark_text_primary_color' => '#F9FAFB',
        'dark_text_secondary_color' => '#D1D5DB',
        'border_radius' => 'medium',
        'font_size' => 'medium',
        'compact_mode' => false,
    ]);

    expect($themePreference->user_id)->toBe($user->id);
    expect($themePreference->theme_name)->toBe('Custom Blue');
    expect($themePreference->is_custom_theme)->toBeTrue();
    expect($themePreference->primary_color)->toBe('#3B82F6');
    expect($themePreference->border_radius)->toBe('medium');
    expect($themePreference->font_size)->toBe('medium');
    expect($themePreference->compact_mode)->toBeFalse();
});

test('belongs to user relationship works', function (): void {
    $user = User::factory()->create();
    $themePreference = UserThemePreference::factory()->create(['user_id' => $user->id]);

    expect($themePreference->user)->toBeInstanceOf(User::class);
    expect($themePreference->user->id)->toBe($user->id);
});

test('scope for theme filters correctly', function (): void {
    $blueTheme = UserThemePreference::factory()->create(['theme_name' => 'blue']);
    $redTheme = UserThemePreference::factory()->create(['theme_name' => 'red']);

    $results = UserThemePreference::forTheme('blue')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($blueTheme->id);
});

test('scope custom themes filters correctly', function (): void {
    $customTheme = UserThemePreference::factory()->create(['is_custom_theme' => true]);
    $defaultTheme = UserThemePreference::factory()->create(['is_custom_theme' => false]);

    $results = UserThemePreference::customThemes()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($customTheme->id);
});

test('get light mode colors method returns correct colors', function (): void {
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

    expect($colors)->toBe([
        'primary' => '#3B82F6',
        'secondary' => '#8B5CF6',
        'accent' => '#10B981',
        'background' => '#FFFFFF',
        'surface' => '#F8FAFC',
        'text_primary' => '#1F2937',
        'text_secondary' => '#6B7280',
    ]);
});

test('get dark mode colors method returns correct colors', function (): void {
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

    expect($colors)->toBe([
        'background' => '#111827',
        'surface' => '#1F2937',
        'text_primary' => '#F9FAFB',
        'text_secondary' => '#D1D5DB',
        'primary' => '#3B82F6',
        'secondary' => '#8B5CF6',
        'accent' => '#10B981',
    ]);
});

test('generate css variables method creates correct format', function (): void {
    $themePreference = UserThemePreference::factory()->create([
        'primary_color' => '#3B82F6',
        'secondary_color' => '#8B5CF6',
        'accent_color' => '#10B981',
        'border_radius' => 'medium',
        'font_size' => 'medium',
    ]);

    $variables = $themePreference->generateCssVariables(false);

    expect($variables)->toHaveKey('--color-primary');
    expect($variables)->toHaveKey('--color-secondary');
    expect($variables)->toHaveKey('--color-accent');
    expect($variables)->toHaveKey('--border-radius-base');
    expect($variables)->toHaveKey('--font-size-base');
    expect($variables['--color-primary'])->toBe('#3B82F6');
    expect($variables['--border-radius-base'])->toBe('0.375rem');
    expect($variables['--font-size-base'])->toBe('1rem');
});

test('validate color hex method validates colors correctly', function (): void {
    $themePreference = new UserThemePreference;

    expect($themePreference->validateColorHex('#FF0000'))->toBeTrue();
    expect($themePreference->validateColorHex('#123ABC'))->toBeTrue();
    expect($themePreference->validateColorHex('FF0000'))->toBeFalse();
    expect($themePreference->validateColorHex('#GGGGGG'))->toBeFalse();
    expect($themePreference->validateColorHex('#12345'))->toBeFalse();
});

test('get default theme method returns correct values', function (): void {
    $defaultTheme = UserThemePreference::getDefaultTheme();

    expect($defaultTheme['theme_name'])->toBe('default');
    expect($defaultTheme['is_custom_theme'])->toBeFalse();
    expect($defaultTheme['primary_color'])->toBe('#3B82F6');
    expect($defaultTheme['background_color'])->toBe('#FFFFFF');
    expect($defaultTheme['border_radius'])->toBe('medium');
    expect($defaultTheme['font_size'])->toBe('medium');
    expect($defaultTheme['compact_mode'])->toBeFalse();
});
