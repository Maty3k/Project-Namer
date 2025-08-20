<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Color scheme enumeration.
 *
 * Defines the available color schemes for logo customization,
 * matching the database enum values.
 */
enum ColorScheme: string
{
    case MONOCHROME = 'monochrome';
    case OCEAN_BLUE = 'ocean_blue';
    case FOREST_GREEN = 'forest_green';
    case WARM_SUNSET = 'warm_sunset';
    case ROYAL_PURPLE = 'royal_purple';
    case CORPORATE_NAVY = 'corporate_navy';
    case EARTHY_TONES = 'earthy_tones';
    case TECH_BLUE = 'tech_blue';
    case VIBRANT_PINK = 'vibrant_pink';
    case CHARCOAL_GOLD = 'charcoal_gold';

    /**
     * Get the display name for this color scheme.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::MONOCHROME => 'Monochrome',
            self::OCEAN_BLUE => 'Ocean Blue',
            self::FOREST_GREEN => 'Forest Green',
            self::WARM_SUNSET => 'Warm Sunset',
            self::ROYAL_PURPLE => 'Royal Purple',
            self::CORPORATE_NAVY => 'Corporate Navy',
            self::EARTHY_TONES => 'Earthy Tones',
            self::TECH_BLUE => 'Tech Blue',
            self::VIBRANT_PINK => 'Vibrant Pink',
            self::CHARCOAL_GOLD => 'Charcoal Gold',
        };
    }

    /**
     * Get the description for this color scheme.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MONOCHROME => 'Black, white, and grays for timeless elegance',
            self::OCEAN_BLUE => 'Deep blues and teals for trust and reliability',
            self::FOREST_GREEN => 'Natural greens for growth and sustainability',
            self::WARM_SUNSET => 'Oranges and warm reds for energy and creativity',
            self::ROYAL_PURPLE => 'Purple tones for luxury and innovation',
            self::CORPORATE_NAVY => 'Navy blue with silver accents for professionalism',
            self::EARTHY_TONES => 'Browns and tans for authenticity and stability',
            self::TECH_BLUE => 'Modern blues with electric accents for technology brands',
            self::VIBRANT_PINK => 'Modern pinks for creative and lifestyle brands',
            self::CHARCOAL_GOLD => 'Dark grays with gold accents for premium positioning',
        };
    }

    /**
     * Get all color scheme values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Get all display names as an associative array.
     *
     * @return array<string, string>
     */
    public static function displayNames(): array
    {
        $displayNames = [];
        
        foreach (self::cases() as $case) {
            $displayNames[$case->value] = $case->getDisplayName();
        }
        
        return $displayNames;
    }

    /**
     * Check if a color scheme value exists.
     */
    public static function exists(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}