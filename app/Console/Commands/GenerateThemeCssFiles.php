<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateThemeCssFiles extends Command
{
    protected $signature = 'theme:generate-css';

    protected $description = 'Generate CSS files for all predefined themes with light and dark mode variants';

    public function __construct(protected ThemeService $themeService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Generating theme CSS files...');

        $themesDirectory = public_path('css/themes');

        if (! File::exists($themesDirectory)) {
            File::makeDirectory($themesDirectory, 0755, true);
            $this->info('Created themes directory');
        }

        $themes = $this->themeService->getPredefinedThemes();
        $generatedCount = 0;

        foreach ($themes as $theme) {
            $cssContent = $this->generateThemeCss($theme);
            $fileName = "{$theme['name']}.css";
            $filePath = "{$themesDirectory}/{$fileName}";

            File::put($filePath, $cssContent);

            $this->line("✓ Generated: {$fileName}");
            $generatedCount++;
        }

        $this->newLine();
        $this->info("Successfully generated {$generatedCount} theme CSS files!");

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $theme
     */
    protected function generateThemeCss(array $theme): string
    {
        $isDarkTheme = $theme['is_dark_mode'] ?? false;

        // For themes primarily light, use their colors for :root and generate dark variants
        // For themes primarily dark, use their colors for :root.dark and generate light variants
        if ($isDarkTheme) {
            return $this->generateDarkPrimaryTheme($theme);
        }

        return $this->generateLightPrimaryTheme($theme);
    }

    /**
     * @param array<string, mixed> $theme
     */
    protected function generateLightPrimaryTheme(array $theme): string
    {
        $name = $theme['display_name'] ?? ucfirst((string) $theme['name']);

        // Light mode (default) uses the theme's defined colors
        $lightPrimary = $theme['primary_color'];
        $lightSecondary = $this->calculateSecondary($theme);
        $lightAccent = $theme['accent_color'];
        $lightBackground = $theme['background_color'];
        $lightSurface = $this->calculateSurface($theme['background_color'], false);
        $lightTextPrimary = $theme['text_primary_color'] ?? $theme['text_color'];
        $lightTextSecondary = $theme['text_secondary_color'];
        $lightBorder = $this->calculateBorder($theme['background_color'], false);

        // Dark mode inverts background/text colors but keeps primary/accent
        $darkPrimary = $lightPrimary;
        $darkSecondary = $lightSecondary;
        $darkAccent = $lightAccent;
        $darkBackground = $this->invertToDarkBackground($lightBackground);
        $darkSurface = $this->calculateSurface($darkBackground, true);
        $darkTextPrimary = $this->invertToLightText($lightTextPrimary);
        $darkTextSecondary = $this->calculateSecondaryText($darkTextPrimary);
        $darkBorder = $this->calculateBorder($darkBackground, true);

        return <<<CSS
/* {$name} Theme */

/* Light Mode (Default) */
:root {
  --color-primary: {$lightPrimary};
  --color-secondary: {$lightSecondary};
  --color-accent: {$lightAccent};
  --color-background: {$lightBackground};
  --color-surface: {$lightSurface};
  --color-text-primary: {$lightTextPrimary};
  --color-text-secondary: {$lightTextSecondary};
  --color-border: {$lightBorder};
}

/* Dark Mode */
:root.dark {
  --color-primary: {$darkPrimary};
  --color-secondary: {$darkSecondary};
  --color-accent: {$darkAccent};
  --color-background: {$darkBackground};
  --color-surface: {$darkSurface};
  --color-text-primary: {$darkTextPrimary};
  --color-text-secondary: {$darkTextSecondary};
  --color-border: {$darkBorder};
}

CSS;
    }

    /**
     * @param array<string, mixed> $theme
     */
    protected function generateDarkPrimaryTheme(array $theme): string
    {
        $name = $theme['display_name'] ?? ucfirst((string) $theme['name']);

        // For dark-primary themes, use their colors for dark mode and generate light variants
        $darkPrimary = $theme['primary_color'];
        $darkSecondary = $this->calculateSecondary($theme);
        $darkAccent = $theme['accent_color'];
        $darkBackground = $theme['background_color'];
        $darkSurface = $this->calculateSurface($theme['background_color'], true);
        $darkTextPrimary = $theme['text_primary_color'] ?? $theme['text_color'];
        $darkTextSecondary = $theme['text_secondary_color'];
        $darkBorder = $this->calculateBorder($theme['background_color'], true);

        // Light mode inverts background/text colors but keeps primary/accent
        $lightPrimary = $darkPrimary;
        $lightSecondary = $darkSecondary;
        $lightAccent = $darkAccent;
        $lightBackground = $this->invertToLightBackground($darkBackground);
        $lightSurface = $this->calculateSurface($lightBackground, false);
        $lightTextPrimary = $this->invertToDarkText($darkTextPrimary);
        $lightTextSecondary = $this->calculateSecondaryText($lightTextPrimary);
        $lightBorder = $this->calculateBorder($lightBackground, false);

        return <<<CSS
/* {$name} Theme */

/* Light Mode (Default) */
:root {
  --color-primary: {$lightPrimary};
  --color-secondary: {$lightSecondary};
  --color-accent: {$lightAccent};
  --color-background: {$lightBackground};
  --color-surface: {$lightSurface};
  --color-text-primary: {$lightTextPrimary};
  --color-text-secondary: {$lightTextSecondary};
  --color-border: {$lightBorder};
}

/* Dark Mode */
:root.dark {
  --color-primary: {$darkPrimary};
  --color-secondary: {$darkSecondary};
  --color-accent: {$darkAccent};
  --color-background: {$darkBackground};
  --color-surface: {$darkSurface};
  --color-text-primary: {$darkTextPrimary};
  --color-text-secondary: {$darkTextSecondary};
  --color-border: {$darkBorder};
}

CSS;
    }

    /**
     * @param array<string, mixed> $theme
     */
    protected function calculateSecondary(array $theme): string
    {
        // If theme doesn't have explicit secondary, derive from primary
        return $theme['secondary_color'] ?? $theme['primary_color'];
    }

    protected function calculateSurface(string $background, bool $isDark): string
    {
        // Surface is slightly different from background
        if ($isDark) {
            // For dark backgrounds, surface is slightly lighter
            return $this->lightenColor($background, 10);
        }

        // For light backgrounds, surface is slightly darker
        return $this->darkenColor($background, 5);
    }

    protected function calculateBorder(string $background, bool $isDark): string
    {
        if ($isDark) {
            return $this->lightenColor($background, 20);
        }

        return $this->darkenColor($background, 15);
    }

    protected function calculateSecondaryText(string $primaryText): string
    {
        // Secondary text is slightly more transparent/muted
        $rgb = $this->hexToRgb($primaryText);
        $luminance = $this->calculateLuminance($rgb);

        if ($luminance > 0.5) {
            // Light text - make slightly darker
            return $this->darkenColor($primaryText, 15);
        }

        // Dark text - make slightly lighter
        return $this->lightenColor($primaryText, 15);
    }

    /**
     * @param array{r: int, g: int, b: int} $rgb
     */
    protected function calculateLuminance(array $rgb): float
    {
        $r = $this->linearizeColorComponent($rgb['r'] / 255);
        $g = $this->linearizeColorComponent($rgb['g'] / 255);
        $b = $this->linearizeColorComponent($rgb['b'] / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    protected function linearizeColorComponent(float $component): float
    {
        return $component <= 0.03928
            ? $component / 12.92
            : (($component + 0.055) / 1.055) ** 2.4;
    }

    protected function invertToDarkBackground(string $lightBg): string
    {
        // Convert light background to dark
        return '#111827'; // Gray-900
    }

    protected function invertToLightBackground(string $darkBg): string
    {
        // Convert dark background to light
        return '#ffffff';
    }

    protected function invertToLightText(string $darkText): string
    {
        // Convert dark text to light
        return '#f9fafb'; // Gray-50
    }

    protected function invertToDarkText(string $lightText): string
    {
        // Convert light text to dark
        return '#111827'; // Gray-900
    }

    /**
     * @return array{r: int, g: int, b: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    protected function lightenColor(string $hex, int $percent): string
    {
        $rgb = $this->hexToRgb($hex);

        $r = min(255, $rgb['r'] + (int) (($percent / 100) * (255 - $rgb['r'])));
        $g = min(255, $rgb['g'] + (int) (($percent / 100) * (255 - $rgb['g'])));
        $b = min(255, $rgb['b'] + (int) (($percent / 100) * (255 - $rgb['b'])));

        return $this->rgbToHex($r, $g, $b);
    }

    protected function darkenColor(string $hex, int $percent): string
    {
        $rgb = $this->hexToRgb($hex);

        $r = max(0, $rgb['r'] - (int) (($percent / 100) * $rgb['r']));
        $g = max(0, $rgb['g'] - (int) (($percent / 100) * $rgb['g']));
        $b = max(0, $rgb['b'] - (int) (($percent / 100) * $rgb['b']));

        return $this->rgbToHex($r, $g, $b);
    }
}
