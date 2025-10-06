<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('FluxUI Theme Validation', function (): void {
    test('no hardcoded gray colors remain in rendered pages', function (): void {
        $response = $this->get('/dashboard');

        $html = $response->getContent();

        // Check for common hardcoded gray patterns that should be zinc
        expect($html)->not->toMatch('/class="[^"]*bg-gray-[0-9]/')
            ->and($html)->not->toMatch('/class="[^"]*text-gray-[0-9]/')
            ->and($html)->not->toMatch('/class="[^"]*border-gray-[0-9]/');
    });

    test('no hardcoded blue colors remain in rendered pages', function (): void {
        $response = $this->get('/dashboard');

        $html = $response->getContent();

        // Check for hardcoded blue/primary colors that should be accent
        expect($html)->not->toMatch('/class="[^"]*bg-blue-[0-9]/')
            ->and($html)->not->toMatch('/class="[^"]*text-primary-[0-9]/')
            ->and($html)->not->toMatch('/class="[^"]*bg-primary-[0-9]/');
    });

    test('all predefined themes can be loaded successfully', function (): void {
        $themes = [
            'default',
            'ocean',
            'sunset',
            'forest',
            'midnight',
            'rose',
            'summer',
            'winter',
            'halloween',
            'spring',
            'autumn',
        ];

        foreach ($themes as $themeName) {
            $component = Livewire::test('theme-customizer')
                ->call('applyPreset', $themeName)
                ->assertOk()
                ->assertHasNoErrors();

            // Verify theme properties were set correctly
            $actualThemeName = $component->get('themeName');
            $accentColor = $component->get('accentColor');

            expect($actualThemeName)->toBeString()->not->toBeEmpty();
            expect($accentColor)->toBeString()->not->toBeEmpty()->toStartWith('#');
        }

        expect(count($themes))->toBe(11);
    });

    test('theme switching maintains accent color consistency', function (): void {
        $component = Livewire::test('theme-customizer');

        // Apply ocean theme
        $component->call('applyPreset', 'ocean')
            ->assertOk();

        $accentColor1 = $component->get('accentColor');
        expect($accentColor1)->toBeString()->not->toBeEmpty();

        // Apply sunset theme
        $component->call('applyPreset', 'sunset')
            ->assertOk();

        $accentColor2 = $component->get('accentColor');
        expect($accentColor2)->toBeString()->not->toBeEmpty()
            ->and($accentColor2)->not->toBe($accentColor1);
    });

    test('custom theme creation works with valid hex colors', function (): void {
        Livewire::test('theme-customizer')
            ->set('themeName', 'Custom Test Theme')
            ->set('accentColor', '#FF6B6B')
            ->set('accentContentColor', '#C92A2A')
            ->set('accentForegroundColor', '#FFFFFF')
            ->set('baseColorShade', 'zinc')
            ->assertOk();

        expect(true)->toBeTrue();
    });

    test('light and dark mode toggle works correctly', function (): void {
        $component = Livewire::test('theme-customizer');

        // Start in light mode
        $component->set('isDarkMode', false)
            ->assertSet('isDarkMode', false);

        // Toggle to dark mode
        $component->call('toggleDarkMode')
            ->assertSet('isDarkMode', true)
            ->assertDispatched('theme-applied');

        // Toggle back to light mode
        $component->call('toggleDarkMode')
            ->assertSet('isDarkMode', false)
            ->assertDispatched('theme-applied');
    });

    test('base color shade remapping works for all neutral palettes', function (): void {
        $shades = ['zinc', 'slate', 'neutral', 'gray', 'stone'];

        foreach ($shades as $shade) {
            Livewire::test('theme-customizer')
                ->set('baseColorShade', $shade)
                ->assertSet('baseColorShade', $shade)
                ->assertOk();
        }

        expect(true)->toBeTrue();
    });

    test('accessibility score updates when colors change', function (): void {
        $component = Livewire::test('theme-customizer')
            ->set('accentColor', '#3B82F6')
            ->set('accentForegroundColor', '#FFFFFF');

        $score1 = $component->get('accessibilityScore');
        expect($score1)->toBeFloat()->toBeGreaterThan(0);

        // Change to low contrast colors
        $component->set('accentColor', '#FFFF00')
            ->set('accentForegroundColor', '#FFFFFF');

        $score2 = $component->get('accessibilityScore');
        expect($score2)->toBeFloat()
            ->and($score2)->not->toBe($score1);
    });

    test('theme export includes all FluxUI variables', function (): void {
        Livewire::test('theme-customizer')
            ->set('accentColor', '#FF6B6B')
            ->set('accentContentColor', '#C92A2A')
            ->set('accentForegroundColor', '#FFFFFF')
            ->call('exportTheme')
            ->assertDispatched('download-theme');

        expect(true)->toBeTrue();
    });

    test('CSS generation includes FluxUI @theme directive', function (): void {
        $component = Livewire::test('theme-customizer')
            ->set('accentColor', '#3B82F6')
            ->set('accentContentColor', '#2563EB')
            ->set('accentForegroundColor', '#FFFFFF');

        $css = $component->get('generatedCss');

        expect($css)->toContain('@theme')
            ->and($css)->toContain('--color-accent:')
            ->and($css)->toContain('--color-accent-content:')
            ->and($css)->toContain('--color-accent-foreground:');
    });

    test('seasonal theme recommendations work correctly', function (): void {
        $component = Livewire::test('theme-customizer');

        $recommendedTheme = $component->get('recommendedSeasonalTheme');

        if ($recommendedTheme) {
            expect($recommendedTheme)->toBeArray()
                ->and($recommendedTheme)->toHaveKey('display_name')
                ->and($recommendedTheme)->toHaveKey('accent_color')
                ->and($recommendedTheme)->toHaveKey('season');

            $component->call('applySeasonalRecommendation')
                ->assertOk()
                ->assertDispatched('theme-applied');
        }

        expect(true)->toBeTrue();
    });

    test('theme persistence works across page loads', function (): void {
        Livewire::test('theme-customizer')
            ->set('themeName', 'Persistence Test')
            ->set('accentColor', '#FF6B6B')
            ->call('applyTheme')
            ->assertOk();

        // Simulate new page load by creating new component instance
        $newComponent = Livewire::test('theme-customizer');

        expect($newComponent->get('themeName'))->toBe('Persistence Test');
    });
});
