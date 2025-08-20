<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ColorScheme;
use InvalidArgumentException;

/**
 * Color palette service.
 *
 * Manages color schemes and provides color palette data
 * for logo customization functionality.
 */
final class ColorPaletteService
{
    /**
     * Color palette data with primary, secondary, accent, and neutral colors.
     *
     * @var array<string, array<string, string>>
     */
    private const COLOR_PALETTES = [
        'monochrome' => [
            'primary' => '#000000',   // Black
            'secondary' => '#666666', // Dark gray
            'accent' => '#999999',    // Medium gray
            'neutral' => '#FFFFFF',   // White
        ],
        'ocean_blue' => [
            'primary' => '#003366',   // Deep navy blue
            'secondary' => '#0066CC', // Ocean blue
            'accent' => '#3399FF',    // Light blue
            'neutral' => '#E6F2FF',   // Very light blue
        ],
        'forest_green' => [
            'primary' => '#003d1a',   // Deep forest green
            'secondary' => '#00664d', // Forest green
            'accent' => '#009970',    // Emerald green
            'neutral' => '#E6F5F0',   // Very light green
        ],
        'warm_sunset' => [
            'primary' => '#CC3300',   // Deep red-orange
            'secondary' => '#FF6600', // Orange
            'accent' => '#FF9933',    // Light orange
            'neutral' => '#FFF2E6',   // Very light orange
        ],
        'royal_purple' => [
            'primary' => '#330066',   // Deep purple
            'secondary' => '#6600CC', // Royal purple
            'accent' => '#9933FF',    // Light purple
            'neutral' => '#F2E6FF',   // Very light purple
        ],
        'corporate_navy' => [
            'primary' => '#1a237e',   // Deep navy
            'secondary' => '#3f51b5', // Corporate blue
            'accent' => '#C0C0C0',    // Silver
            'neutral' => '#F5F5F5',   // Light gray
        ],
        'earthy_tones' => [
            'primary' => '#3E2723',   // Dark brown
            'secondary' => '#8D6E63', // Medium brown
            'accent' => '#BCAAA4',    // Light brown
            'neutral' => '#EFEBE9',   // Very light brown
        ],
        'tech_blue' => [
            'primary' => '#0D47A1',   // Deep tech blue
            'secondary' => '#2196F3', // Tech blue
            'accent' => '#00E5FF',    // Electric cyan
            'neutral' => '#E3F2FD',   // Very light blue
        ],
        'vibrant_pink' => [
            'primary' => '#AD1457',   // Deep pink
            'secondary' => '#E91E63', // Vibrant pink
            'accent' => '#FF4081',    // Light pink
            'neutral' => '#FCE4EC',   // Very light pink
        ],
        'charcoal_gold' => [
            'primary' => '#212121',   // Charcoal
            'secondary' => '#424242', // Dark gray
            'accent' => '#FFD700',    // Gold
            'neutral' => '#FAFAFA',   // Very light gray
        ],
    ];

    /**
     * Get all available color schemes with their metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllColorSchemesWithMetadata(): array
    {
        $schemes = [];
        
        foreach (ColorScheme::cases() as $colorScheme) {
            $schemes[$colorScheme->value] = [
                'id' => $colorScheme->value,
                'name' => $colorScheme->getDisplayName(),
                'description' => $colorScheme->getDescription(),
                'colors' => $this->getColorPalette($colorScheme),
            ];
        }
        
        return $schemes;
    }

    /**
     * Get all color schemes as a simple array.
     *
     * @return array<string, array<string, string>>
     */
    public function getAllColorSchemes(): array
    {
        $schemes = [];
        
        foreach (ColorScheme::cases() as $colorScheme) {
            $schemes[$colorScheme->value] = $this->getColorPalette($colorScheme);
        }
        
        return $schemes;
    }

    /**
     * Get color palette for a specific scheme.
     *
     * @param ColorScheme|string $colorScheme
     * @return array<string, string>
     */
    public function getColorPalette(ColorScheme|string $colorScheme): array
    {
        if (is_string($colorScheme)) {
            if (!ColorScheme::exists($colorScheme)) {
                throw new InvalidArgumentException("Invalid color scheme: {$colorScheme}");
            }
            $colorScheme = ColorScheme::from($colorScheme);
        }

        return self::COLOR_PALETTES[$colorScheme->value];
    }

    /**
     * Get display name for a color scheme.
     */
    public function getDisplayName(ColorScheme|string $colorScheme): string
    {
        if (is_string($colorScheme)) {
            if (!ColorScheme::exists($colorScheme)) {
                throw new InvalidArgumentException("Invalid color scheme: {$colorScheme}");
            }
            $colorScheme = ColorScheme::from($colorScheme);
        }

        return $colorScheme->getDisplayName();
    }

    /**
     * Get description for a color scheme.
     */
    public function getDescription(ColorScheme|string $colorScheme): string
    {
        if (is_string($colorScheme)) {
            if (!ColorScheme::exists($colorScheme)) {
                throw new InvalidArgumentException("Invalid color scheme: {$colorScheme}");
            }
            $colorScheme = ColorScheme::from($colorScheme);
        }

        return $colorScheme->getDescription();
    }

    /**
     * Get palette colors as a simple array.
     *
     * @param ColorScheme|string $colorScheme
     * @return array<string>
     */
    public function getPaletteColorsAsArray(ColorScheme|string $colorScheme): array
    {
        $palette = $this->getColorPalette($colorScheme);
        
        return array_values($palette);
    }

    /**
     * Validate hex color format.
     */
    public function isValidHexColor(string $color): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
    }

    /**
     * Get color scheme options for form dropdowns.
     *
     * @return array<string, string>
     */
    public function getColorSchemeOptions(): array
    {
        return ColorScheme::displayNames();
    }

    /**
     * Check if a color scheme exists.
     */
    public function colorSchemeExists(string $colorScheme): bool
    {
        return ColorScheme::exists($colorScheme);
    }
}