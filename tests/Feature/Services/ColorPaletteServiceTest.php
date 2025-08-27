<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Services\ColorPaletteService;

beforeEach(function (): void {
    $this->service = app(ColorPaletteService::class);
});

describe('Color Palette Service', function (): void {
    it('can retrieve all available color schemes', function (): void {
        $schemes = $this->service->getAllColorSchemes();

        expect($schemes)->toHaveCount(10)
            ->and($schemes)->toHaveKeys([
                'monochrome', 'ocean_blue', 'forest_green', 'warm_sunset',
                'royal_purple', 'corporate_navy', 'earthy_tones', 'tech_blue',
                'vibrant_pink', 'charcoal_gold',
            ]);
    });

    it('can retrieve a specific color palette', function (): void {
        $palette = $this->service->getColorPalette(ColorScheme::OCEAN_BLUE);

        expect($palette)->toHaveKeys(['primary', 'secondary', 'accent', 'neutral'])
            ->and($palette['primary'])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($palette['secondary'])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($palette['accent'])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($palette['neutral'])->toMatch('/^#[0-9A-Fa-f]{6}$/');
    });

    it('returns correct colors for monochrome palette', function (): void {
        $palette = $this->service->getColorPalette(ColorScheme::MONOCHROME);

        expect($palette['primary'])->toBe('#000000')
            ->and($palette['secondary'])->toBe('#666666')
            ->and($palette['accent'])->toBe('#999999')
            ->and($palette['neutral'])->toBe('#FFFFFF');
    });

    it('returns correct colors for ocean blue palette', function (): void {
        $palette = $this->service->getColorPalette(ColorScheme::OCEAN_BLUE);

        expect($palette['primary'])->toBe('#003366')
            ->and($palette['secondary'])->toBe('#0066CC')
            ->and($palette['accent'])->toBe('#3399FF')
            ->and($palette['neutral'])->toBe('#E6F2FF');
    });

    it('can get display name for color scheme', function (): void {
        $displayName = $this->service->getDisplayName(ColorScheme::OCEAN_BLUE);

        expect($displayName)->toBe('Ocean Blue');
    });

    it('can get description for color scheme', function (): void {
        $description = $this->service->getDescription(ColorScheme::OCEAN_BLUE);

        expect($description)->toBe('Deep blues and teals for trust and reliability');
    });

    it('can get all color schemes with metadata', function (): void {
        $schemesWithMetadata = $this->service->getAllColorSchemesWithMetadata();

        expect($schemesWithMetadata)->toHaveCount(10);

        $oceanBlue = $schemesWithMetadata['ocean_blue'];
        expect($oceanBlue)->toHaveKeys(['id', 'name', 'description', 'colors'])
            ->and($oceanBlue['id'])->toBe('ocean_blue')
            ->and($oceanBlue['name'])->toBe('Ocean Blue')
            ->and($oceanBlue['description'])->toBe('Deep blues and teals for trust and reliability')
            ->and($oceanBlue['colors'])->toHaveKeys(['primary', 'secondary', 'accent', 'neutral']);
    });

    it('validates hex color format correctly', function (): void {
        expect($this->service->isValidHexColor('#FF0000'))->toBeTrue()
            ->and($this->service->isValidHexColor('#123456'))->toBeTrue()
            ->and($this->service->isValidHexColor('#ABCDEF'))->toBeTrue()
            ->and($this->service->isValidHexColor('FF0000'))->toBeFalse()
            ->and($this->service->isValidHexColor('#GG0000'))->toBeFalse()
            ->and($this->service->isValidHexColor('#12345'))->toBeFalse()
            ->and($this->service->isValidHexColor('#1234567'))->toBeFalse();
    });

    it('can get palette colors as array', function (): void {
        $colors = $this->service->getPaletteColorsAsArray(ColorScheme::WARM_SUNSET);

        expect($colors)->toBeArray()
            ->and($colors)->toHaveCount(4)
            ->and($colors[0])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($colors[1])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($colors[2])->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($colors[3])->toMatch('/^#[0-9A-Fa-f]{6}$/');
    });

    it('throws exception for invalid color scheme', function (): void {
        expect(fn () => $this->service->getColorPalette('invalid_scheme'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('returns all color schemes as options for forms', function (): void {
        $options = $this->service->getColorSchemeOptions();

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(10)
            ->and($options['ocean_blue'])->toBe('Ocean Blue')
            ->and($options['monochrome'])->toBe('Monochrome');
    });

    it('can check if color scheme exists', function (): void {
        expect($this->service->colorSchemeExists('ocean_blue'))->toBeTrue()
            ->and($this->service->colorSchemeExists('invalid_scheme'))->toBeFalse();
    });
});
