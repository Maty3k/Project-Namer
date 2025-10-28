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
     * Get predefined theme collection without hex color values.
     * Colors are now defined in CSS files located in public/css/themes/
     *
     * @return list<array<string, mixed>>
     */
    public function getPredefinedThemes(): array
    {
        return [
            [
                'name' => 'default',
                'display_name' => 'Default Blue',
                'theme_name' => 'default',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/default.png',
                'category' => 'standard',
            ],
            [
                'name' => 'dark',
                'display_name' => 'Dark Mode',
                'theme_name' => 'dark',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/dark.png',
                'category' => 'standard',
            ],
            [
                'name' => 'ocean',
                'display_name' => 'Ocean Breeze',
                'theme_name' => 'ocean',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/ocean.png',
                'category' => 'standard',
            ],
            [
                'name' => 'sunset',
                'display_name' => 'Warm Sunset',
                'theme_name' => 'sunset',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/sunset.png',
                'category' => 'standard',
            ],
            [
                'name' => 'forest',
                'display_name' => 'Forest Green',
                'theme_name' => 'forest',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/forest.png',
                'category' => 'standard',
            ],
            [
                'name' => 'cosmic-violet',
                'display_name' => 'Cosmic Violet',
                'theme_name' => 'cosmic-violet',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/cosmic-violet.png',
                'category' => 'standard',
            ],
            [
                'name' => 'coral-reef',
                'display_name' => 'Coral Reef',
                'theme_name' => 'coral-reef',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/coral-reef.png',
                'category' => 'standard',
            ],
            [
                'name' => 'cyberpunk',
                'display_name' => 'Cyberpunk 2077',
                'theme_name' => 'cyberpunk',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/cyberpunk.png',
                'category' => 'standard',
            ],
            // Seasonal Themes
            [
                'name' => 'summer',
                'display_name' => 'Summer Coral',
                'theme_name' => 'summer',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/summer.png',
                'category' => 'seasonal',
                'season' => 'summer',
            ],
            [
                'name' => 'winter',
                'display_name' => 'Winter Frost',
                'theme_name' => 'winter',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/winter.png',
                'category' => 'seasonal',
                'season' => 'winter',
            ],
            [
                'name' => 'halloween',
                'display_name' => 'Halloween Night',
                'theme_name' => 'halloween',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/halloween.png',
                'category' => 'seasonal',
                'season' => 'halloween',
            ],
            [
                'name' => 'spring',
                'display_name' => 'Spring Bloom',
                'theme_name' => 'spring',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/spring.png',
                'category' => 'seasonal',
                'season' => 'spring',
            ],
            [
                'name' => 'autumn',
                'display_name' => 'Autumn Harvest',
                'theme_name' => 'autumn',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/autumn.png',
                'category' => 'seasonal',
                'season' => 'autumn',
            ],
            // Bold & Distinctive Themes
            [
                'name' => 'neon-cyber',
                'display_name' => 'Neon Cyber',
                'theme_name' => 'neon-cyber',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/neon-cyber.png',
                'category' => 'bold',
            ],
            [
                'name' => 'electric-blue',
                'display_name' => 'Electric Blue',
                'theme_name' => 'electric-blue',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/electric-blue.png',
                'category' => 'bold',
            ],
            [
                'name' => 'hot-pink',
                'display_name' => 'Hot Pink',
                'theme_name' => 'hot-pink',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/hot-pink.png',
                'category' => 'bold',
            ],
            [
                'name' => 'lava-red',
                'display_name' => 'Lava Red',
                'theme_name' => 'lava-red',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/lava-red.png',
                'category' => 'bold',
            ],
            [
                'name' => 'lime-punch',
                'display_name' => 'Lime Punch',
                'theme_name' => 'lime-punch',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/lime-punch.png',
                'category' => 'bold',
            ],
            [
                'name' => 'gold-rush',
                'display_name' => 'Gold Rush',
                'theme_name' => 'gold-rush',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/gold-rush.png',
                'category' => 'bold',
            ],
            [
                'name' => 'matrix-green',
                'display_name' => 'Matrix Green',
                'theme_name' => 'matrix-green',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/matrix-green.png',
                'category' => 'bold',
            ],
        ];
    }

    /**
     * Get themes filtered by category.
     *
     * @return list<array<string, mixed>>
     */
    public function getThemesByCategory(string $category = 'all'): array
    {
        $themes = $this->getPredefinedThemes();

        if ($category === 'all') {
            return $themes;
        }

        return array_filter($themes, fn ($theme) => ($theme['category'] ?? 'standard') === $category);
    }

    /**
     * Get available theme categories.
     *
     * @return list<string>
     */
    public function getAvailableCategories(): array
    {
        return ['standard', 'seasonal', 'bold'];
    }

    /**
     * Get current seasonal theme recommendation based on date.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentSeasonalTheme(): ?array
    {
        $month = (int) date('n'); // 1-12

        $seasonThemes = [
            'spring' => [3, 4, 5],     // March, April, May
            'summer' => [6, 7, 8],     // June, July, August
            'autumn' => [9, 10, 11],   // September, October, November
            'winter' => [12, 1, 2],    // December, January, February
        ];

        // Special case for Halloween in October
        if ($month === 10) {
            $themes = $this->getPredefinedThemes();

            return collect($themes)->firstWhere('name', 'halloween');
        }

        foreach ($seasonThemes as $season => $months) {
            if (in_array($month, $months)) {
                $themes = $this->getPredefinedThemes();

                return collect($themes)->firstWhere('season', $season);
            }
        }

        return null;
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
     *
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

    /**
     * Calculate relative luminance for contrast calculations.
     *
     * @param  array{r: int, g: int, b: int}  $rgb
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
