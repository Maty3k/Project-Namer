<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use App\Models\UserThemePreference;
use Livewire\Livewire;

it('synchronizes theme settings between User and UserThemePreference models when applying theme', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeCustomizer::class)
        ->set('primaryColor', '#e11d48')
        ->set('accentColor', '#10b981')
        ->set('backgroundColor', '#1f2937')
        ->set('textColor', '#f9fafb')
        ->set('themeName', 'custom-dark')
        ->set('isDarkMode', true)
        ->call('applyTheme');

    // Check User model was updated
    $user->refresh();
    expect($user->current_theme)->toBe('custom-dark');
    expect($user->prefers_dark_mode)->toBeTrue();

    // Check UserThemePreference was updated
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->theme_name)->toBe('custom-dark');
    expect($preference->is_dark_mode)->toBeTrue();
    expect($preference->primary_color)->toBe('#e11d48');
    expect($preference->background_color)->toBe('#1f2937');
    expect($preference->text_color)->toBe('#f9fafb');
});

it('synchronizes theme settings when toggling dark mode', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'light-theme',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    // Create existing theme preference
    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'light-theme',
        'primary_color' => '#3b82f6',
        'accent_color' => '#10b981',
        'background_color' => '#ffffff',
        'text_color' => '#111827',
        'is_dark_mode' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeCustomizer::class)
        ->call('toggleDarkMode');

    // Check User model was updated
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeTrue();

    // Check UserThemePreference was updated
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference->is_dark_mode)->toBeTrue();
    expect($preference->background_color)->toBe('#1f2937');
    expect($preference->text_color)->toBe('#f9fafb');
});

it('initializes from User model when no UserThemePreference exists', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'dark-corporate',
        'prefers_dark_mode' => true,
        'theme_auto_switch' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeCustomizer::class);

    expect($component->get('themeName'))->toBe('dark-corporate');
    expect($component->get('isDarkMode'))->toBeTrue();
    expect($component->get('backgroundColor'))->toBe('#1f2937');
    expect($component->get('textColor'))->toBe('#f9fafb');
});

it('prioritizes UserThemePreference over User model when both exist', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'user-model-theme',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'preference-model-theme',
        'primary_color' => '#e11d48',
        'accent_color' => '#10b981',
        'background_color' => '#1f2937',
        'text_color' => '#f9fafb',
        'is_dark_mode' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeCustomizer::class);

    // Should use UserThemePreference values, not User model values
    expect($component->get('themeName'))->toBe('preference-model-theme');
    expect($component->get('isDarkMode'))->toBeTrue();
    expect($component->get('primaryColor'))->toBe('#e11d48');
    expect($component->get('backgroundColor'))->toBe('#1f2937');
});

it('applies preset theme and syncs both models', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    $this->actingAs($user);

    // Use the actual 'dark' preset theme from ThemeService
    Livewire::test(ThemeCustomizer::class)
        ->call('applyPreset', 'dark');

    // Check both models were updated
    $user->refresh();
    expect($user->current_theme)->toBe('dark');
    expect($user->prefers_dark_mode)->toBeTrue();

    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference->theme_name)->toBe('dark');
    expect($preference->is_dark_mode)->toBeTrue();
    expect($preference->primary_color)->toBe('#6366f1');
    expect($preference->background_color)->toBe('#111827');
    expect($preference->text_color)->toBe('#f9fafb');
});
