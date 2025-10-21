<?php

declare(strict_types=1);

use App\Services\ThemeService;

test('light theme modes are correctly identified', function (): void {
    $themeService = app(ThemeService::class);
    $lightThemes = collect($themeService->getPredefinedThemes())
        ->reject(fn ($theme): bool => (bool) ($theme['is_dark_mode'] ?? false));

    foreach ($lightThemes as $theme) {
        // Verify light themes have dark mode disabled
        expect($theme['is_dark_mode'])->toBeFalse(
            "Theme '{$theme['name']}' should be a light theme with dark mode disabled"
        );

        // Verify CSS file exists for this theme
        $cssPath = public_path("css/themes/{$theme['name']}.css");
        expect(file_exists($cssPath))->toBeTrue(
            "CSS file should exist for theme '{$theme['name']}'"
        );
    }
});

test('dark theme modes are correctly identified', function (): void {
    $themeService = app(ThemeService::class);
    $darkThemes = collect($themeService->getPredefinedThemes())
        ->filter(fn ($theme) => $theme['is_dark_mode'] ?? false);

    foreach ($darkThemes as $theme) {
        // Verify dark themes have dark mode enabled
        expect($theme['is_dark_mode'])->toBeTrue(
            "Theme '{$theme['name']}' should be a dark theme with dark mode enabled"
        );

        // Verify CSS file exists for this theme
        $cssPath = public_path("css/themes/{$theme['name']}.css");
        expect(file_exists($cssPath))->toBeTrue(
            "CSS file should exist for theme '{$theme['name']}'"
        );
    }
});

test('all themes have valid CSS files with color variables', function (): void {
    $themeService = app(ThemeService::class);
    $allThemes = $themeService->getPredefinedThemes();

    foreach ($allThemes as $theme) {
        $cssPath = public_path("css/themes/{$theme['name']}.css");

        // Verify CSS file exists
        expect(file_exists($cssPath))->toBeTrue(
            "CSS file should exist for theme '{$theme['name']}'"
        );

        // Read CSS content
        $cssContent = file_get_contents($cssPath);
        expect($cssContent)->not->toBeEmpty();

        // Verify essential CSS variables are present using PHPUnit assertions
        test()->assertStringContainsString('--color-primary', $cssContent,
            "Theme '{$theme['name']}' CSS should contain --color-primary variable"
        );
        test()->assertStringContainsString('--color-text-primary', $cssContent,
            "Theme '{$theme['name']}' CSS should contain --color-text-primary variable"
        );
        test()->assertStringContainsString('--color-background', $cssContent,
            "Theme '{$theme['name']}' CSS should contain --color-background variable"
        );

        // Verify dark mode variants if this is a dark theme
        if ($theme['is_dark_mode']) {
            test()->assertStringContainsString('.dark', $cssContent,
                "Dark theme '{$theme['name']}' CSS should contain .dark class rules"
            );
        }
    }
});
