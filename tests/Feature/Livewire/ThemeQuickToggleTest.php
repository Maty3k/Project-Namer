<?php

declare(strict_types=1);

use App\Livewire\ThemeQuickToggle;
use App\Models\User;
use App\Models\UserThemePreference;
use Livewire\Livewire;

it('toggle theme is disabled to enforce theme customizer only', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(ThemeQuickToggle::class)
        ->call('toggleTheme');

    // Check User model was NOT updated (theme toggle is disabled)
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeFalse();

    // Check no UserThemePreference was created by quick toggle
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference)->toBeNull();
});

it('theme toggle is disabled regardless of existing preferences', function (): void {
    $user = User::factory()->create([
        'current_theme' => 'dark',
        'prefers_dark_mode' => true,
    ]);

    $originalPreference = UserThemePreference::create([
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

    // Check User model was NOT changed (theme toggle is disabled)
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeTrue();

    // Check UserThemePreference was NOT changed
    $preference = UserThemePreference::where('user_id', $user->id)->first();
    expect($preference->is_dark_mode)->toBeTrue();
    expect($preference->background_color)->toBe('#1f2937');
    expect($preference->text_color)->toBe('#f9fafb');
});

it('displays disabled theme toggle with instructional text', function (): void {
    $user = User::factory()->create([
        'prefers_dark_mode' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ThemeQuickToggle::class);

    // Should show disabled text with instruction to use theme customizer
    $component->assertSee('Dark Mode (Use Theme Customizer)');

    // Toggle attempt should do nothing since it's disabled
    $component->call('toggleTheme');

    // Verify the user preference was NOT changed
    $user->refresh();
    expect($user->prefers_dark_mode)->toBeFalse();
});

it('does nothing when user is not authenticated', function (): void {
    Livewire::test(ThemeQuickToggle::class)
        ->call('toggleTheme');

    // Should not create any preferences for guest users
    expect(UserThemePreference::count())->toBe(0);
});
