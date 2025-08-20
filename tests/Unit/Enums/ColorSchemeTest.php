<?php

declare(strict_types=1);

use App\Enums\ColorScheme;

describe('ColorScheme Enum', function (): void {
    it('has correct enum values', function (): void {
        $cases = ColorScheme::cases();

        expect($cases)->toHaveCount(10);

        $values = array_map(fn ($case) => $case->value, $cases);

        expect($values)->toContain('monochrome')
            ->and($values)->toContain('ocean_blue')
            ->and($values)->toContain('forest_green')
            ->and($values)->toContain('warm_sunset')
            ->and($values)->toContain('royal_purple')
            ->and($values)->toContain('corporate_navy')
            ->and($values)->toContain('earthy_tones')
            ->and($values)->toContain('tech_blue')
            ->and($values)->toContain('vibrant_pink')
            ->and($values)->toContain('charcoal_gold');
    });

    it('can get display names for all schemes', function (): void {
        expect(ColorScheme::MONOCHROME->getDisplayName())->toBe('Monochrome')
            ->and(ColorScheme::OCEAN_BLUE->getDisplayName())->toBe('Ocean Blue')
            ->and(ColorScheme::FOREST_GREEN->getDisplayName())->toBe('Forest Green')
            ->and(ColorScheme::WARM_SUNSET->getDisplayName())->toBe('Warm Sunset')
            ->and(ColorScheme::ROYAL_PURPLE->getDisplayName())->toBe('Royal Purple')
            ->and(ColorScheme::CORPORATE_NAVY->getDisplayName())->toBe('Corporate Navy')
            ->and(ColorScheme::EARTHY_TONES->getDisplayName())->toBe('Earthy Tones')
            ->and(ColorScheme::TECH_BLUE->getDisplayName())->toBe('Tech Blue')
            ->and(ColorScheme::VIBRANT_PINK->getDisplayName())->toBe('Vibrant Pink')
            ->and(ColorScheme::CHARCOAL_GOLD->getDisplayName())->toBe('Charcoal Gold');
    });

    it('can get descriptions for all schemes', function (): void {
        expect(ColorScheme::MONOCHROME->getDescription())->toBe('Black, white, and grays for timeless elegance')
            ->and(ColorScheme::OCEAN_BLUE->getDescription())->toBe('Deep blues and teals for trust and reliability')
            ->and(ColorScheme::FOREST_GREEN->getDescription())->toBe('Natural greens for growth and sustainability')
            ->and(ColorScheme::WARM_SUNSET->getDescription())->toBe('Oranges and warm reds for energy and creativity')
            ->and(ColorScheme::ROYAL_PURPLE->getDescription())->toBe('Purple tones for luxury and innovation')
            ->and(ColorScheme::CORPORATE_NAVY->getDescription())->toBe('Navy blue with silver accents for professionalism')
            ->and(ColorScheme::EARTHY_TONES->getDescription())->toBe('Browns and tans for authenticity and stability')
            ->and(ColorScheme::TECH_BLUE->getDescription())->toBe('Modern blues with electric accents for technology brands')
            ->and(ColorScheme::VIBRANT_PINK->getDescription())->toBe('Modern pinks for creative and lifestyle brands')
            ->and(ColorScheme::CHARCOAL_GOLD->getDescription())->toBe('Dark grays with gold accents for premium positioning');
    });

    it('can create from string value', function (): void {
        $scheme = ColorScheme::from('ocean_blue');

        expect($scheme)->toBe(ColorScheme::OCEAN_BLUE);
    });

    it('can try from string value', function (): void {
        $validScheme = ColorScheme::tryFrom('ocean_blue');
        $invalidScheme = ColorScheme::tryFrom('invalid_scheme');

        expect($validScheme)->toBe(ColorScheme::OCEAN_BLUE)
            ->and($invalidScheme)->toBeNull();
    });

    it('can get all values as array', function (): void {
        $values = ColorScheme::values();

        expect($values)->toBeArray()
            ->and($values)->toHaveCount(10)
            ->and($values)->toContain('monochrome')
            ->and($values)->toContain('ocean_blue')
            ->and($values)->toContain('charcoal_gold');
    });

    it('can get all display names as array', function (): void {
        $displayNames = ColorScheme::displayNames();

        expect($displayNames)->toBeArray()
            ->and($displayNames)->toHaveCount(10)
            ->and($displayNames['monochrome'])->toBe('Monochrome')
            ->and($displayNames['ocean_blue'])->toBe('Ocean Blue')
            ->and($displayNames['charcoal_gold'])->toBe('Charcoal Gold');
    });

    it('can check if value exists', function (): void {
        expect(ColorScheme::exists('ocean_blue'))->toBeTrue()
            ->and(ColorScheme::exists('invalid_scheme'))->toBeFalse();
    });
});
