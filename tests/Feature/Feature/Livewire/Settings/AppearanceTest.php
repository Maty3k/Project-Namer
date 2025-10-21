<?php

declare(strict_types=1);

use App\Helpers\ThemeHelper;
use App\Livewire\Appearance;
use App\Models\User;
use App\Models\UserThemePreference;
use App\Services\ThemeService;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'current_theme' => 'default',
        'prefers_dark_mode' => false,
    ]);

    actingAs($this->user);
});

test('component loads current theme from user preference', function (): void {
    UserThemePreference::create([
        'user_id' => $this->user->id,
        'theme_name' => 'ocean',
        'is_dark_mode' => false,
    ]);

    Livewire::test(Appearance::class)
        ->assertSet('currentTheme', 'ocean');
});

test('component loads current theme from user model if no preference', function (): void {
    $this->user->update(['current_theme' => 'sunset']);

    Livewire::test(Appearance::class)
        ->assertSet('currentTheme', 'sunset');
});

test('component defaults to default theme if no preference or user theme', function (): void {
    Livewire::test(Appearance::class)
        ->assertSet('currentTheme', 'default');
});

test('selectTheme updates user current_theme', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean');

    expect($this->user->fresh()->current_theme)->toBe('ocean');
});

test('selectTheme updates user prefers_dark_mode based on theme', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'dark');

    expect($this->user->fresh()->prefers_dark_mode)->toBeTrue();
});

test('selectTheme creates user theme preference if not exists', function (): void {
    expect(UserThemePreference::where('user_id', $this->user->id)->exists())->toBeFalse();

    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean');

    $preference = UserThemePreference::where('user_id', $this->user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->theme_name)->toBe('ocean');
    expect($preference->is_dark_mode)->toBeFalse();
});

test('selectTheme updates existing user theme preference', function (): void {
    UserThemePreference::create([
        'user_id' => $this->user->id,
        'theme_name' => 'default',
        'is_dark_mode' => false,
    ]);

    Livewire::test(Appearance::class)
        ->call('selectTheme', 'dark');

    $preference = UserThemePreference::where('user_id', $this->user->id)->first();
    expect($preference->theme_name)->toBe('dark');
    expect($preference->is_dark_mode)->toBeTrue();
});

test('selectTheme updates component currentTheme property', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean')
        ->assertSet('currentTheme', 'ocean');
});

test('selectTheme does nothing for invalid theme name', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'invalid-theme');

    expect($this->user->fresh()->current_theme)->toBe('default');
    expect(UserThemePreference::where('user_id', $this->user->id)->exists())->toBeFalse();
});

test('selectTheme redirects to reload page', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean')
        ->assertRedirect(route('appearance'));
});

test('render passes all themes to view', function (): void {
    $component = Livewire::test(Appearance::class);
    $themes = $component->viewData('themes');

    expect($themes)->toBeArray();
    expect(count($themes))->toBeGreaterThan(0);

    // Verify theme structure
    $firstTheme = $themes[0];
    expect($firstTheme)->toHaveKeys(['name', 'display_name', 'is_dark_mode']);
});

test('render passes isDarkMode to view', function (): void {
    ThemeHelper::clearUserThemeCache();

    $this->user->update(['prefers_dark_mode' => true]);

    $component = Livewire::test(Appearance::class);
    $isDarkMode = $component->viewData('isDarkMode');

    expect($isDarkMode)->toBeTrue();
});

test('selectTheme respects light mode themes', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean'); // ocean is light mode

    expect($this->user->fresh()->prefers_dark_mode)->toBeFalse();

    $preference = UserThemePreference::where('user_id', $this->user->id)->first();
    expect($preference->is_dark_mode)->toBeFalse();
});

test('selectTheme respects dark mode themes', function (): void {
    Livewire::test(Appearance::class)
        ->call('selectTheme', 'dark'); // dark is dark mode

    expect($this->user->fresh()->prefers_dark_mode)->toBeTrue();

    $preference = UserThemePreference::where('user_id', $this->user->id)->first();
    expect($preference->is_dark_mode)->toBeTrue();
});

test('all predefined themes are available in view', function (): void {
    $themeService = app(ThemeService::class);
    $predefinedThemes = $themeService->getPredefinedThemes();

    $component = Livewire::test(Appearance::class);
    $viewThemes = $component->viewData('themes');

    expect(count($viewThemes))->toBe(count($predefinedThemes));
});

test('component requires authentication', function (): void {
    auth()->logout();

    Livewire::test(Appearance::class)
        ->call('selectTheme', 'ocean');

    // Should not create preference or update anything
    expect(UserThemePreference::count())->toBe(0);
});
