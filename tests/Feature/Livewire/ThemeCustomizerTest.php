<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(ThemeCustomizer::class)
        ->assertStatus(200);
});

it('displays seasonal themes correctly', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->assertSee('Summer Coral')
        ->assertSee('Winter Frost')
        ->assertSee('Halloween Night')
        ->assertSee('Spring Bloom')
        ->assertSee('Autumn Harvest');
});

it('can filter themes by category', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Test filtering by seasonal category
    $component->call('changeCategory', 'seasonal')
        ->assertSet('selectedCategory', 'seasonal')
        ->assertSee('Summer Coral')
        ->assertSee('Winter Frost');

    // Test filtering by standard category
    $component->call('changeCategory', 'standard')
        ->assertSet('selectedCategory', 'standard')
        ->assertSee('Default Blue')
        ->assertSee('Ocean Breeze');

    // Test filtering by bold category
    $component->call('changeCategory', 'bold')
        ->assertSet('selectedCategory', 'bold')
        ->assertSee('Neon Cyber')
        ->assertSee('Electric Blue');
});

it('can apply seasonal themes', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->call('applyPreset', 'summer')
        ->assertSet('themeName', 'summer')
        ->assertSet('isDarkMode', false);
});

it('can apply bold themes', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->call('applyPreset', 'neon-cyber')
        ->assertSet('themeName', 'neon-cyber')
        ->assertSet('isDarkMode', true);
});

it('shows seasonal recommendation when available', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Check if seasonal recommendation is loaded
    $recommendation = $component->get('recommendedSeasonalTheme');

    if ($recommendation) {
        $component->assertSee('Recommended:')
            ->assertSee($recommendation['display_name']);
    }
});

it('can apply seasonal recommendation', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    $recommendation = $component->get('recommendedSeasonalTheme');

    if ($recommendation) {
        $component->call('applySeasonalRecommendation')
            ->assertSet('themeName', $recommendation['name'])
            ->assertSet('isDarkMode', $recommendation['is_dark_mode']);
    } else {
        // If no recommendation, just check that the method doesn't throw
        $component->call('applySeasonalRecommendation');
        expect(true)->toBeTrue(); // Test passes if no exception
    }
});

test('user can save theme preferences', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('themeName', 'ocean')
        ->set('isDarkMode', false)
        ->call('save')
        ->assertDispatched('theme-saved')
        ->assertDispatched('theme-updated');

    // Verify database record was created
    expect(\App\Models\UserThemePreference::where('user_id', $user->id)->first())
        ->theme_name->toBe('ocean')
        ->is_dark_mode->toBeFalse();
});

test('user can reset theme to defaults', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('themeName', 'ocean')
        ->call('resetToDefault')
        ->assertSet('themeName', 'default')
        ->assertSet('isDarkMode', false)
        ->assertDispatched('theme-updated');
});

test('user can toggle dark mode', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('isDarkMode', false)
        ->call('toggleDarkMode')
        ->assertSet('isDarkMode', true)
        ->assertDispatched('theme-saved');
});

test('theme changes persist after save', function (): void {
    $user = User::factory()->create();

    // Save initial theme
    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('themeName', 'sunset')
        ->set('isDarkMode', true)
        ->call('save');

    // Create new component instance (simulating page reload)
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Verify theme was loaded from database
    expect($component->get('themeName'))->toBe('sunset');
    expect($component->get('isDarkMode'))->toBeTrue();
});

test('guest user cannot save themes', function (): void {
    Livewire::test(ThemeCustomizer::class)
        ->set('themeName', 'ocean')
        ->call('save')
        ->assertDispatched('theme-error');
});

test('theme customizer displays current theme info', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('themeName', 'forest')
        ->set('isDarkMode', false);

    $component->assertSee('Current Theme')
        ->assertSee('forest')
        ->assertSee('Light Mode');
});

test('can change between different themes', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    $component->call('applyPreset', 'default')
        ->assertSet('themeName', 'default')
        ->assertDispatched('theme-applied');

    $component->call('applyPreset', 'ocean')
        ->assertSet('themeName', 'ocean')
        ->assertDispatched('theme-applied');

    $component->call('applyPreset', 'sunset')
        ->assertSet('themeName', 'sunset')
        ->assertDispatched('theme-applied');
});

test('theme name validation works', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('themeName', str_repeat('a', 51)) // Too long
        ->call('save')
        ->assertHasErrors(['themeName']);
});

test('predefined themes are loaded correctly', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    $themes = $component->get('predefinedThemes');
    expect($themes)->toBeArray();
    expect(count($themes))->toBeGreaterThan(0);
});

test('available categories are loaded correctly', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    $categories = $component->get('availableCategories');
    expect($categories)->toBeArray();
    expect(count($categories))->toBeGreaterThan(0);
});
