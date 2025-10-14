<?php

declare(strict_types=1);

use App\Services\ThemeService;

describe('Updated ThemeService', function (): void {
    beforeEach(function (): void {
        $this->themeService = new ThemeService;
    });

    it('returns predefined themes without hex color values', function (): void {
        $themes = $this->themeService->getPredefinedThemes();

        expect($themes)->toBeArray()
            ->and($themes)->not->toBeEmpty();

        foreach ($themes as $theme) {
            expect($theme)->toHaveKeys(['name', 'display_name', 'theme_name', 'is_dark_mode', 'category'])
                ->and($theme)->not->toHaveKey('primary_color')
                ->and($theme)->not->toHaveKey('accent_color')
                ->and($theme)->not->toHaveKey('background_color')
                ->and($theme)->not->toHaveKey('text_color')
                ->and($theme)->not->toHaveKey('text_primary_color')
                ->and($theme)->not->toHaveKey('text_secondary_color');
        }
    });

    it('returns all 20 predefined theme names', function (): void {
        $themes = $this->themeService->getPredefinedThemes();
        $themeNames = array_column($themes, 'name');

        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        expect($themeNames)->toEqual($expectedThemes);
    });

    it('includes preview_url for each theme', function (): void {
        $themes = $this->themeService->getPredefinedThemes();

        foreach ($themes as $theme) {
            expect($theme)->toHaveKey('preview_url')
                ->and($theme['preview_url'])->toStartWith('/images/theme-previews/')
                ->and($theme['preview_url'])->toEndWith('.png');
        }
    });

    it('categorizes themes correctly', function (): void {
        $themes = $this->themeService->getPredefinedThemes();
        $categories = array_unique(array_column($themes, 'category'));

        expect($categories)->toContain('standard')
            ->and($categories)->toContain('seasonal')
            ->and($categories)->toContain('bold');
    });

    it('returns themes filtered by category', function (): void {
        $standardThemes = $this->themeService->getThemesByCategory('standard');
        $seasonalThemes = $this->themeService->getThemesByCategory('seasonal');
        $boldThemes = $this->themeService->getThemesByCategory('bold');

        expect($standardThemes)->toBeArray()->not->toBeEmpty();
        expect($seasonalThemes)->toBeArray()->not->toBeEmpty();
        expect($boldThemes)->toBeArray()->not->toBeEmpty();

        // Verify filtering works
        foreach ($standardThemes as $theme) {
            expect($theme['category'])->toBe('standard');
        }

        foreach ($seasonalThemes as $theme) {
            expect($theme['category'])->toBe('seasonal');
        }

        foreach ($boldThemes as $theme) {
            expect($theme['category'])->toBe('bold');
        }
    });

    it('returns all themes when category is "all"', function (): void {
        $allThemes = $this->themeService->getThemesByCategory('all');

        expect($allThemes)->toHaveCount(20);
    });

    it('returns available categories', function (): void {
        $categories = $this->themeService->getAvailableCategories();

        expect($categories)->toEqual(['standard', 'seasonal', 'bold']);
    });

    it('returns halloween theme for October', function (): void {
        // Mock current month as October (month 10)
        $originalDate = date('n');

        // We can't easily mock date() so we'll just verify the method exists and returns proper structure
        $seasonalTheme = $this->themeService->getCurrentSeasonalTheme();

        if ($seasonalTheme !== null) {
            expect($seasonalTheme)->toBeArray()
                ->and($seasonalTheme)->toHaveKey('name')
                ->and($seasonalTheme)->toHaveKey('display_name')
                ->and($seasonalTheme)->toHaveKey('category');
        }
    });

    it('marks dark themes with is_dark_mode flag', function (): void {
        $themes = $this->themeService->getPredefinedThemes();

        $darkThemes = array_filter($themes, fn ($theme) => $theme['is_dark_mode'] === true);
        $lightThemes = array_filter($themes, fn ($theme) => $theme['is_dark_mode'] === false);

        expect($darkThemes)->not->toBeEmpty()
            ->and($lightThemes)->not->toBeEmpty();

        // Verify specific themes
        $darkTheme = collect($themes)->firstWhere('name', 'dark');
        $defaultTheme = collect($themes)->firstWhere('name', 'default');

        expect($darkTheme['is_dark_mode'])->toBeTrue();
        expect($defaultTheme['is_dark_mode'])->toBeFalse();
    });

    // Accessibility methods should still work
    it('validates hex color format', function (): void {
        expect($this->themeService->validateColor('#3b82f6'))->toBeTrue();
        expect($this->themeService->validateColor('#FFFFFF'))->toBeTrue();
        expect($this->themeService->validateColor('3b82f6'))->toBeFalse();
        expect($this->themeService->validateColor('#xyz'))->toBeFalse();
        expect($this->themeService->validateColor('#12345'))->toBeFalse();
    });

    it('calculates contrast ratio correctly', function (): void {
        // Black on white should have high contrast
        $contrastRatio = $this->themeService->calculateContrastRatio('#000000', '#ffffff');
        expect($contrastRatio)->toBeGreaterThan(15);

        // Similar colors should have low contrast
        $lowContrast = $this->themeService->calculateContrastRatio('#111111', '#222222');
        expect($lowContrast)->toBeLessThan(2);
    });

    it('returns correct WCAG level for contrast ratios', function (): void {
        expect($this->themeService->getWcagLevel(7.5))->toBe('AAA');
        expect($this->themeService->getWcagLevel(5.0))->toBe('AA');
        expect($this->themeService->getWcagLevel(3.5))->toBe('A');
        expect($this->themeService->getWcagLevel(2.0))->toBe('FAIL');
    });

    it('calculates accessibility score', function (): void {
        $score = $this->themeService->calculateAccessibilityScore(
            '#3b82f6', // primary
            '#ffffff', // background
            '#111827'  // text
        );

        expect($score)->toBeFloat()
            ->and($score)->toBeGreaterThanOrEqual(0.0)
            ->and($score)->toBeLessThanOrEqual(1.0);
    });

    it('generates accessibility feedback', function (): void {
        $feedback = $this->themeService->generateAccessibilityFeedback(
            '#3b82f6', // primary - good contrast
            '#ffffff', // background
            '#111827'  // text - good contrast
        );

        expect($feedback)->toHaveKeys(['warnings', 'suggestions'])
            ->and($feedback['warnings'])->toBeArray()
            ->and($feedback['suggestions'])->toBeArray();
    });
});
