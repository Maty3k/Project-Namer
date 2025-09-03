<?php

declare(strict_types=1);

namespace App\Services;

final class ThemeService
{
    /**
     * Validate hex color format.
     */
    public function validateColor(string $color): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    }

    /**
     * Calculate contrast ratio between two colors.
     */
    public function calculateContrastRatio(string $color1, string $color2): float
    {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        $luminance1 = $this->calculateLuminance($rgb1);
        $luminance2 = $this->calculateLuminance($rgb2);

        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get WCAG accessibility level for contrast ratio.
     */
    public function getWcagLevel(float $contrastRatio): string
    {
        if ($contrastRatio >= 7.0) {
            return 'AAA';
        }

        if ($contrastRatio >= 4.5) {
            return 'AA';
        }

        if ($contrastRatio >= 3.0) {
            return 'A';
        }

        return 'FAIL';
    }

    /**
     * Calculate accessibility score (0-1).
     */
    public function calculateAccessibilityScore(string $primaryColor, string $backgroundColor, string $textColor): float
    {
        $primaryBgRatio = $this->calculateContrastRatio($primaryColor, $backgroundColor);
        $textBgRatio = $this->calculateContrastRatio($textColor, $backgroundColor);

        $primaryScore = min($primaryBgRatio / 7.0, 1.0);
        $textScore = min($textBgRatio / 7.0, 1.0);

        return ($primaryScore + $textScore) / 2;
    }

    /**
     * Generate CSS custom properties for theme.
     *
     * @param  array<string, mixed>  $themeData
     */
    public function generateCssProperties(array $themeData): string
    {
        $properties = [
            '--color-primary' => $themeData['primary_color'],
            '--color-accent' => $themeData['accent_color'] ?? $themeData['primary_color'],
            '--color-background' => $themeData['background_color'],
            '--color-text' => $themeData['text_color'],
        ];

        $css = ":root {\n";
        foreach ($properties as $property => $value) {
            $css .= "  {$property}: {$value};\n";
        }
        $css .= '}';

        return $css;
    }

    /**
     * Get predefined theme collection.
     *
     * @return list<array<string, mixed>>
     */
    public function getPredefinedThemes(): array
    {
        return [
            [
                'name' => 'default',
                'display_name' => 'Default Blue',
                'primary_color' => '#3b82f6',
                'accent_color' => '#10b981',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'theme_name' => 'default',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/default.png',
            ],
            [
                'name' => 'dark',
                'display_name' => 'Dark Mode',
                'primary_color' => '#6366f1',
                'accent_color' => '#8b5cf6',
                'background_color' => '#111827',
                'text_color' => '#f9fafb',
                'theme_name' => 'dark',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/dark.png',
            ],
            [
                'name' => 'ocean',
                'display_name' => 'Ocean Breeze',
                'primary_color' => '#0ea5e9',
                'accent_color' => '#06b6d4',
                'background_color' => '#f0f9ff',
                'text_color' => '#0c4a6e',
                'theme_name' => 'ocean',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/ocean.png',
            ],
            [
                'name' => 'sunset',
                'display_name' => 'Warm Sunset',
                'primary_color' => '#f59e0b',
                'accent_color' => '#ef4444',
                'background_color' => '#fffbeb',
                'text_color' => '#92400e',
                'theme_name' => 'sunset',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/sunset.png',
            ],
            [
                'name' => 'forest',
                'display_name' => 'Forest Green',
                'primary_color' => '#059669',
                'accent_color' => '#65a30d',
                'background_color' => '#f0fdf4',
                'text_color' => '#14532d',
                'theme_name' => 'forest',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/forest.png',
            ],
        ];
    }

    /**
     * Generate accessibility warnings and suggestions.
     *
     * @return array<string, array<string>>
     */
    public function generateAccessibilityFeedback(string $primaryColor, string $backgroundColor, string $textColor): array
    {
        $warnings = [];
        $suggestions = [];

        $primaryBgRatio = $this->calculateContrastRatio($primaryColor, $backgroundColor);
        $textBgRatio = $this->calculateContrastRatio($textColor, $backgroundColor);

        if ($primaryBgRatio < 4.5) {
            $warnings[] = 'Primary color has insufficient contrast with background';
            $suggestions[] = 'Consider darkening primary color or lightening background';
        }

        if ($textBgRatio < 4.5) {
            $warnings[] = 'Text color has insufficient contrast with background';
            $suggestions[] = 'Consider using darker text or lighter background';
        }

        if ($primaryBgRatio < 3.0) {
            $warnings[] = 'Primary color contrast is below minimum accessibility standards';
        }

        if ($textBgRatio < 7.0 && $textBgRatio >= 4.5) {
            $suggestions[] = 'Text contrast meets AA but could be improved for AAA compliance';
        }

        return [
            'warnings' => $warnings,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Convert hex color to RGB array.
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

    /**
     * Calculate relative luminance for contrast calculations.
     */
    protected function calculateLuminance(array $rgb): float
    {
        $r = $this->linearizeColorComponent($rgb['r'] / 255);
        $g = $this->linearizeColorComponent($rgb['g'] / 255);
        $b = $this->linearizeColorComponent($rgb['b'] / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Linearize color component for luminance calculation.
     */
    protected function linearizeColorComponent(float $component): float
    {
        return $component <= 0.03928
            ? $component / 12.92
            : (($component + 0.055) / 1.055) ** 2.4;
    }
}