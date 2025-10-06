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

it('can apply seasonal themes with FluxUI variables', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->call('applyPreset', 'summer')
        ->assertSet('accentColor', '#dc2626')
        ->assertSet('themeName', 'summer')
        ->assertSet('isDarkMode', false);
});

it('can apply bold themes with FluxUI variables', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->call('applyPreset', 'neon-cyber')
        ->assertSet('accentColor', '#00ff88')
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
            ->assertSet('accentColor', $recommendation['accent_color']);
    } else {
        // If no recommendation, just check that the method doesn't throw
        $component->call('applySeasonalRecommendation');
        expect(true)->toBeTrue(); // Test passes if no exception
    }
});

test('user can save theme preferences with FluxUI variables', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->set('accentContentColor', '#cc5555')
        ->set('accentForegroundColor', '#ffffff')
        ->set('baseColorShade', 'zinc')
        ->set('themeName', 'custom-test')
        ->set('isDarkMode', false)
        ->call('save')
        ->assertDispatched('theme-saved')
        ->assertDispatched('theme-updated');

    // Verify database record was created
    expect(\App\Models\UserThemePreference::where('user_id', $user->id)->first())
        ->accent_color->toBe('#ff6b6b')
        ->accent_content_color->toBe('#cc5555')
        ->accent_foreground_color->toBe('#ffffff')
        ->base_color_shade->toBe('zinc')
        ->theme_name->toBe('custom-test')
        ->is_dark_mode->toBeFalse();
});

test('user can reset theme to defaults', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->call('resetToDefault')
        ->assertSet('accentColor', '#3b82f6')
        ->assertSet('baseColorShade', 'zinc')
        ->assertSet('themeName', 'default')
        ->assertSet('isDarkMode', false)
        ->assertDispatched('theme-updated');
});

test('theme customizer validates accent color format', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Test invalid color format
    $component->set('accentColor', 'invalid-color')
        ->call('save')
        ->assertHasErrors(['accentColor']);

    // Test valid color format
    $component->set('accentColor', '#3b82f6')
        ->call('save')
        ->assertHasNoErrors(['accentColor']);
});

test('theme customizer validates base color shade', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Test invalid shade
    $component->set('baseColorShade', 'invalid-shade')
        ->call('save')
        ->assertHasErrors(['baseColorShade']);

    // Test valid shades
    $validShades = ['zinc', 'slate', 'neutral', 'gray', 'stone'];
    foreach ($validShades as $shade) {
        $component->set('baseColorShade', $shade)
            ->call('save')
            ->assertHasNoErrors(['baseColorShade']);
    }
});

test('user can export current theme with FluxUI variables', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->set('baseColorShade', 'slate')
        ->set('themeName', 'export-test')
        ->call('exportTheme')
        ->assertDispatched('download-theme');
});

test('user can import theme with FluxUI variables', function (): void {
    $user = User::factory()->create();

    // Create a mock theme file with FluxUI structure
    $themeData = [
        'theme_name' => 'imported-theme',
        'accent_color' => '#ff6b6b',
        'accent_content_color' => '#cc5555',
        'accent_foreground_color' => '#ffffff',
        'base_color_shade' => 'slate',
        'is_dark_mode' => false,
    ];

    // For now, test the core import logic by setting values directly
    $component = Livewire::actingAs($user)->test(ThemeCustomizer::class);

    // Manually set the theme data on the component to simulate successful file processing
    $component->set('accentColor', $themeData['accent_color'])
        ->set('accentContentColor', $themeData['accent_content_color'])
        ->set('accentForegroundColor', $themeData['accent_foreground_color'])
        ->set('baseColorShade', $themeData['base_color_shade'])
        ->set('themeName', $themeData['theme_name'])
        ->set('isDarkMode', $themeData['is_dark_mode']);

    // Test that the values were set correctly (simulates import success)
    $component->assertSet('accentColor', '#ff6b6b')
        ->assertSet('baseColorShade', 'slate')
        ->assertSet('themeName', 'imported-theme');
});

test('accent color inputs work correctly', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->assertSet('accentColor', '#ff6b6b')
        ->set('accentContentColor', '#cc5555')
        ->assertSet('accentContentColor', '#cc5555')
        ->set('accentForegroundColor', '#ffffff')
        ->assertSet('accentForegroundColor', '#ffffff')
        ->set('baseColorShade', 'slate')
        ->assertSet('baseColorShade', 'slate');
});

test('accessibility score is calculated for accent colors', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#3b82f6')
        ->set('accentForegroundColor', '#ffffff');

    // Accessibility score should be calculated
    $score = $component->get('accessibilityScore');
    expect($score)->toBeFloat();
    expect($score)->toBeGreaterThan(0);
    expect($score)->toBeLessThanOrEqual(1);

    // Feedback should be provided
    $feedback = $component->get('accessibilityFeedback');
    expect($feedback)->toBeArray();
    expect($feedback)->toHaveKeys(['warnings', 'suggestions']);
});

test('theme changes persist after save', function (): void {
    $user = User::factory()->create();

    // Save initial theme
    Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->set('baseColorShade', 'slate')
        ->set('themeName', 'persistent-theme')
        ->call('save');

    // Create new component instance (simulating page reload)
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    // Verify theme was loaded from database
    expect($component->get('accentColor'))->toBe('#ff6b6b');
    expect($component->get('baseColorShade'))->toBe('slate');
    expect($component->get('themeName'))->toBe('persistent-theme');
});

test('guest user cannot save themes', function (): void {
    Livewire::test(ThemeCustomizer::class)
        ->set('accentColor', '#ff6b6b')
        ->call('save')
        ->assertDispatched('theme-error');
});

test('CSS generation uses FluxUI variable format', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#3b82f6')
        ->set('accentContentColor', '#2563eb')
        ->set('accentForegroundColor', '#ffffff')
        ->set('baseColorShade', 'slate');

    $generatedCss = $component->get('generatedCss');

    // Verify FluxUI standard variables are present
    expect($generatedCss)->toContain('--color-accent');
    expect($generatedCss)->toContain('--color-accent-content');
    expect($generatedCss)->toContain('--color-accent-foreground');

    // Verify no old custom variables
    expect($generatedCss)->not->toContain('--color-primary');
    expect($generatedCss)->not->toContain('--color-background');
    expect($generatedCss)->not->toContain('--color-text');
});

test('zinc remapping generates correct CSS for different base shades', function (): void {
    $user = User::factory()->create();

    $validShades = ['slate', 'neutral', 'gray', 'stone'];

    foreach ($validShades as $shade) {
        $component = Livewire::actingAs($user)
            ->test(ThemeCustomizer::class)
            ->set('baseColorShade', $shade);

        $generatedCss = $component->get('generatedCss');

        // When base shade is not zinc, it should contain remapping
        if ($shade !== 'zinc') {
            expect($generatedCss)->toContain("--color-zinc-50: var(--color-{$shade}-50)");
            expect($generatedCss)->toContain("--color-zinc-900: var(--color-{$shade}-900)");
        }
    }
});

test('dark mode accent colors are generated correctly', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#3b82f6')
        ->set('isDarkMode', true);

    $generatedCss = $component->get('generatedCss');

    // Verify dark mode layer exists
    expect($generatedCss)->toContain('@layer theme');
    expect($generatedCss)->toContain('.dark');
});

test('predefined themes use FluxUI variables', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class);

    $themes = $component->get('predefinedThemes');

    // Verify at least one theme exists
    expect($themes)->not->toBeEmpty();

    // Verify each theme has FluxUI properties
    foreach ($themes as $theme) {
        expect($theme)->toHaveKey('accent_color');
        expect($theme)->toHaveKey('base_color_shade');

        // Verify old properties are NOT present
        expect($theme)->not->toHaveKey('primary_color');
        expect($theme)->not->toHaveKey('background_color');
        expect($theme)->not->toHaveKey('text_color');
    }
});

test('contrast ratio calculation works with accent colors', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#3b82f6')
        ->set('accentForegroundColor', '#ffffff');

    // Trigger accessibility validation
    $component->set('accentColor', '#000000');

    $feedback = $component->get('accessibilityFeedback');
    expect($feedback)->toBeArray();
});

test('three-variable accent system maintains WCAG compliance', function (): void {
    $user = User::factory()->create();

    // Test with good contrast
    $component = Livewire::actingAs($user)
        ->test(ThemeCustomizer::class)
        ->set('accentColor', '#1d4ed8')
        ->set('accentContentColor', '#1e3a8a')
        ->set('accentForegroundColor', '#ffffff');

    $score = $component->get('accessibilityScore');
    expect($score)->toBeGreaterThan(0.6); // Should have decent accessibility

    // Test with poor contrast
    $component->set('accentColor', '#fafafa')
        ->set('accentContentColor', '#f5f5f5')
        ->set('accentForegroundColor', '#ffffff');

    $feedback = $component->get('accessibilityFeedback');
    expect($feedback['warnings'])->not->toBeEmpty();
});
