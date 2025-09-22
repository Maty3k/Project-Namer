<?php

declare(strict_types=1);

use App\Livewire\ThemeQuickToggle;
use App\Models\User;
use App\Models\UserThemePreference;
use Livewire\Livewire;

it('can toggle dark mode and sync both models', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeQuickToggle::class)
        ->call('toggleTheme');

    // Check User model was updated
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeTrue();

    // Check UserThemePreference was created
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->is_dark_mode)->toBeTrue();
    expect($preference->background_color)->toBe('#1f2937');
    expect($preference->text_color)->toBe('#f9fafb');
});

it('can toggle from dark to light mode', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'dark',
        'prefers_dark_mode' => true,
    ]);

    UserThemePreference::create([
        'user_id' => $user->id,
        'theme_name' => 'dark',
        'primary_color' => '#3b82f6',
        'accent_color' => '#10b981',
        'background_color' => '#1f2937',
        'text_color' => '#f9fafb',
        'is_dark_mode' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeQuickToggle::class)
        ->call('toggleTheme');

    // Check User model was updated
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeFalse();

    // Check UserThemePreference was updated
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference->is_dark_mode)->toBeFalse();
    expect($preference->background_color)->toBe('#ffffff');
    expect($preference->text_color)->toBe('#111827');
});

it('displays correct icon and text for current theme', function (): void {
    $user = User::factory()->create([
        'prefers_dark_mode' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeQuickToggle::class);

    // Should show "Dark Mode" option when in light mode
    $component->assertSee('Dark Mode');

    // Toggle to dark mode
    $component->call('toggleTheme');

    // Verify the user preference was updated
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeTrue();
});

it('does nothing when user is not authenticated', function (): void {
    Livewire::test(ThemeQuickToggle::class)
        ->call('toggleTheme');

    // Should not create any preferences for guest users
    expect(UserThemePreference::count())->toBe(0);
});
