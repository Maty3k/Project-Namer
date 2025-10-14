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
        ->set('themeName', 'sunset')
        ->set('isDarkMode', false)
        ->call('applyTheme');

    // Check User model was updated
    $user->refresh();
    expect($user->current_theme)->toBe('sunset');
    expect($user->prefers_dark_mode)->toBeFalse();
    expect($user->theme_auto_switch)->toBeFalse(); // Should be disabled when manually applying

    // Check UserThemePreference was created/updated
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->theme_name)->toBe('sunset');
    expect($preference->is_dark_mode)->toBeFalse();
});

it('synchronizes theme settings when toggling dark mode', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'ocean',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    // Create existing theme preference
    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'ocean',
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
    expect($preference->theme_name)->toBe('ocean'); // Theme name should remain
});

it('initializes from User model when no UserThemePreference exists', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'dark',
        'prefers_dark_mode' => true,
        'theme_auto_switch' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeCustomizer::class);

    expect($component->get('themeName'))->toBe('dark');
    expect($component->get('isDarkMode'))->toBeTrue();
});

it('prioritizes UserThemePreference over User model when both exist', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true,
    ]);

    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'forest',
        'is_dark_mode' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeCustomizer::class);

    // Should use UserThemePreference values, not User model values
    expect($component->get('themeName'))->toBe('forest');
    expect($component->get('isDarkMode'))->toBeFalse();
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
});

it('disables theme auto-switch when manually applying theme', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
        'theme_auto_switch' => true, // Initially enabled
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeCustomizer::class)
        ->call('applyPreset', 'ocean');

    // Check that auto-switch was disabled
    $user->refresh();
    expect($user->theme_auto_switch)->toBeFalse();
});

it('creates UserThemePreference if it does not exist when applying theme', function (): void {
    $user = User::factory()->create();

    // Ensure no preference exists
    expect(UserThemePreference::where('user_id', $user->id)->count())->toBe(0);

    $this->actingAs($user);

    Livewire::test(ThemeCustomizer::class)
        ->call('applyPreset', 'sunset');

    // Verify preference was created
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->theme_name)->toBe('sunset');
    expect($preference->is_dark_mode)->toBeFalse();
});
