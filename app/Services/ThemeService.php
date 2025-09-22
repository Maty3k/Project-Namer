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
                'accent_color' => '#059669',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'text_primary_color' => '#111827',
                'text_secondary_color' => '#6b7280',
                'theme_name' => 'default',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/default.png',
                'category' => 'standard',
            ],
            [
                'name' => 'dark',
                'display_name' => 'Dark Mode',
                'primary_color' => '#6366f1',
                'accent_color' => '#8b5cf6',
                'background_color' => '#111827',
                'text_color' => '#f9fafb',
                'text_primary_color' => '#f9fafb',
                'text_secondary_color' => '#d1d5db',
                'theme_name' => 'dark',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/dark.png',
                'category' => 'standard',
            ],
            [
                'name' => 'ocean',
                'display_name' => 'Ocean Breeze',
                'primary_color' => '#0284c7',
                'accent_color' => '#0891b2',
                'background_color' => '#f0f9ff',
                'text_color' => '#0c4a6e',
                'text_primary_color' => '#0c4a6e',
                'text_secondary_color' => '#075985',
                'theme_name' => 'ocean',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/ocean.png',
                'category' => 'standard',
            ],
            [
                'name' => 'sunset',
                'display_name' => 'Warm Sunset',
                'primary_color' => '#d97706',
                'accent_color' => '#dc2626',
                'background_color' => '#fffbeb',
                'text_color' => '#92400e',
                'text_primary_color' => '#92400e',
                'text_secondary_color' => '#a16207',
                'theme_name' => 'sunset',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/sunset.png',
                'category' => 'standard',
            ],
            [
                'name' => 'forest',
                'display_name' => 'Forest Green',
                'primary_color' => '#059669',
                'accent_color' => '#4d7c0f',
                'background_color' => '#f0fdf4',
                'text_color' => '#14532d',
                'text_primary_color' => '#14532d',
                'text_secondary_color' => '#166534',
                'theme_name' => 'forest',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/forest.png',
                'category' => 'standard',
            ],
            [
                'name' => 'cosmic-violet',
                'display_name' => 'Cosmic Violet',
                'primary_color' => '#a855f7',
                'accent_color' => '#c084fc',
                'background_color' => '#0c0a09',
                'text_color' => '#f3e8ff',
                'text_primary_color' => '#f3e8ff',
                'text_secondary_color' => '#ddd6fe',
                'theme_name' => 'cosmic-violet',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/cosmic-violet.png',
                'category' => 'standard',
            ],
            [
                'name' => 'coral-reef',
                'display_name' => 'Coral Reef',
                'primary_color' => '#ea580c',
                'accent_color' => '#0891b2',
                'background_color' => '#fefcfb',
                'text_color' => '#9a3412',
                'text_primary_color' => '#9a3412',
                'text_secondary_color' => '#c2410c',
                'theme_name' => 'coral-reef',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/coral-reef.png',
                'category' => 'standard',
            ],
            [
                'name' => 'midnight-teal',
                'display_name' => 'Midnight Teal',
                'primary_color' => '#0891b2',
                'accent_color' => '#22d3ee',
                'background_color' => '#0f172a',
                'text_color' => '#e0f2fe',
                'text_primary_color' => '#e0f2fe',
                'text_secondary_color' => '#bae6fd',
                'theme_name' => 'midnight-teal',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/midnight-teal.png',
                'category' => 'standard',
            ],
            // Seasonal Themes
            [
                'name' => 'summer',
                'display_name' => 'Summer Coral',
                'primary_color' => '#dc2626',
                'accent_color' => '#0891b2',
                'background_color' => '#fff5f5',
                'text_color' => '#2d3748',
                'text_primary_color' => '#2d3748',
                'text_secondary_color' => '#4a5568',
                'theme_name' => 'summer',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/summer.png',
                'category' => 'seasonal',
                'season' => 'summer',
            ],
            [
                'name' => 'winter',
                'display_name' => 'Winter Frost',
                'primary_color' => '#4a90e2',
                'accent_color' => '#1e40af',
                'background_color' => '#f8fafc',
                'text_color' => '#1a202c',
                'text_primary_color' => '#1a202c',
                'text_secondary_color' => '#2d3748',
                'theme_name' => 'winter',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/winter.png',
                'category' => 'seasonal',
                'season' => 'winter',
            ],
            [
                'name' => 'halloween',
                'display_name' => 'Halloween Night',
                'primary_color' => '#ff8c00',
                'accent_color' => '#f97316',
                'background_color' => '#2d1b69',
                'text_color' => '#f7fafc',
                'text_primary_color' => '#f7fafc',
                'text_secondary_color' => '#e2e8f0',
                'theme_name' => 'halloween',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/halloween.png',
                'category' => 'seasonal',
                'season' => 'halloween',
            ],
            [
                'name' => 'spring',
                'display_name' => 'Spring Bloom',
                'primary_color' => '#059669',
                'accent_color' => '#be185d',
                'background_color' => '#f0fff4',
                'text_color' => '#2d3748',
                'text_primary_color' => '#2d3748',
                'text_secondary_color' => '#4a5568',
                'theme_name' => 'spring',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/spring.png',
                'category' => 'seasonal',
                'season' => 'spring',
            ],
            [
                'name' => 'autumn',
                'display_name' => 'Autumn Harvest',
                'primary_color' => '#b45309',
                'accent_color' => '#c53030',
                'background_color' => '#fffaf0',
                'text_color' => '#744210',
                'text_primary_color' => '#744210',
                'text_secondary_color' => '#92400e',
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
                'primary_color' => '#00ff88',
                'accent_color' => '#ff0080',
                'background_color' => '#0a0a0a',
                'text_color' => '#ffffff',
                'text_primary_color' => '#ffffff',
                'text_secondary_color' => '#e0e0e0',
                'theme_name' => 'neon-cyber',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/neon-cyber.png',
                'category' => 'bold',
            ],
            [
                'name' => 'electric-blue',
                'display_name' => 'Electric Blue',
                'primary_color' => '#0099ff',
                'accent_color' => '#00ddff',
                'background_color' => '#001122',
                'text_color' => '#ffffff',
                'text_primary_color' => '#ffffff',
                'text_secondary_color' => '#f0f9ff',
                'theme_name' => 'electric-blue',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/electric-blue.png',
                'category' => 'bold',
            ],
            [
                'name' => 'hot-pink',
                'display_name' => 'Hot Pink',
                'primary_color' => '#e11d48',
                'accent_color' => '#ec4899',
                'background_color' => '#fdf2f8',
                'text_color' => '#831843',
                'text_primary_color' => '#831843',
                'text_secondary_color' => '#9d174d',
                'theme_name' => 'hot-pink',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/hot-pink.png',
                'category' => 'bold',
            ],
            [
                'name' => 'lava-red',
                'display_name' => 'Lava Red',
                'primary_color' => '#dc2626',
                'accent_color' => '#f97316',
                'background_color' => '#1c1917',
                'text_color' => '#fef2f2',
                'text_primary_color' => '#fef2f2',
                'text_secondary_color' => '#fecaca',
                'theme_name' => 'lava-red',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/lava-red.png',
                'category' => 'bold',
            ],
            [
                'name' => 'lime-punch',
                'display_name' => 'Lime Punch',
                'primary_color' => '#4d7c0f',
                'accent_color' => '#4d7c0f',
                'background_color' => '#f7fee7',
                'text_color' => '#365314',
                'text_primary_color' => '#365314',
                'text_secondary_color' => '#4d7c0f',
                'theme_name' => 'lime-punch',
                'is_dark_mode' => false,
                'preview_url' => '/images/theme-previews/lime-punch.png',
                'category' => 'bold',
            ],
            [
                'name' => 'gold-rush',
                'display_name' => 'Gold Rush',
                'primary_color' => '#eab308',
                'accent_color' => '#f59e0b',
                'background_color' => '#451a03',
                'text_color' => '#fef3c7',
                'text_primary_color' => '#fef3c7',
                'text_secondary_color' => '#fde68a',
                'theme_name' => 'gold-rush',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/gold-rush.png',
                'category' => 'bold',
            ],
            [
                'name' => 'matrix-green',
                'display_name' => 'Matrix Green',
                'primary_color' => '#22c55e',
                'accent_color' => '#16a34a',
                'background_color' => '#000000',
                'text_color' => '#ffffff',
                'text_primary_color' => '#ffffff',
                'text_secondary_color' => '#dcfce7',
                'theme_name' => 'matrix-green',
                'is_dark_mode' => true,
                'preview_url' => '/images/theme-previews/matrix-green.png',
                'category' => 'bold',
            ],
        ];
    }

    /**
     * Get themes filtered by category with improved contrast.
     *
     * @return list<array<string, mixed>>
     */
    public function getThemesByCategory(string $category = 'all'): array
    {
        $themes = $this->getPredefinedThemes();

        // Ensure all themes have good text visibility
        $themes = array_map(fn ($theme) => $this->ensureThemeVisibility($theme), $themes);

        if ($category === 'all') {
            return $themes;
        }

        return array_filter($themes, fn ($theme) => ($theme['category'] ?? 'standard') === $category);
    }

    /**
     * Ensure theme has proper text visibility and contrast.
     *
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    protected function ensureThemeVisibility(array $theme): array
    {
        $backgroundColor = $theme['background_color'] ?? '#ffffff';
        $textColor = $theme['text_color'] ?? '#111827';

        // Calculate contrast ratio
        $contrastRatio = $this->calculateContrastRatio($textColor, $backgroundColor);

        // If contrast is poor, auto-adjust text color
        if ($contrastRatio < 4.5) {
            // Determine if background is light or dark
            $backgroundLuminance = $this->calculateLuminance($this->hexToRgb($backgroundColor));

            if ($backgroundLuminance > 0.5) {
                // Light background - use dark text
                $theme['text_color'] = '#111827';
                $theme['text_primary_color'] = '#111827';
                $theme['text_secondary_color'] = '#4b5563';
            } else {
                // Dark background - use light text
                $theme['text_color'] = '#ffffff';
                $theme['text_primary_color'] = '#ffffff';
                $theme['text_secondary_color'] = '#e5e7eb';
            }
        }

        return $theme;
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
