<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;

beforeEach(function (): void {
    $this->processor = app(SvgColorProcessor::class);
    $this->colorService = app(ColorPaletteService::class);
});

describe('SVG Color Processor', function (): void {
    it('can parse a simple SVG file', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="#FF0000" width="100" height="100"/></svg>';

        $result = $this->processor->parseSvg($svg);

        expect($result)->toBeTrue();
    });

    it('can detect colors in fill attributes', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#FF0000" width="100" height="100"/>
            <circle fill="#00FF00" cx="50" cy="50" r="40"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#FF0000')
            ->and($colors)->toContain('#00FF00');
    });

    it('can detect colors in stroke attributes', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect stroke="#0000FF" fill="none" width="100" height="100"/>
            <line stroke="#FF00FF" x1="0" y1="0" x2="100" y2="100"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#0000FF')
            ->and($colors)->toContain('#FF00FF');
    });

    it('can detect colors in style attributes', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect style="fill: #123456; stroke: #789ABC" width="100" height="100"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#123456')
            ->and($colors)->toContain('#789ABC');
    });

    it('can replace colors with a new palette', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#FF0000" width="100" height="100"/>
            <circle fill="#00FF00" cx="50" cy="50" r="40"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::OCEAN_BLUE);

        $this->processor->parseSvg($svg);
        $newSvg = $this->processor->replaceColors($palette);

        // With 2 colors, they map to darkest (primary) and lightest (neutral)
        expect($newSvg)->toContain($palette['primary'])
            ->and($newSvg)->toContain($palette['neutral'])
            ->and($newSvg)->not->toContain('#FF0000')
            ->and($newSvg)->not->toContain('#00FF00');
    });

    it('preserves SVG structure when replacing colors', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
            <rect fill="#FF0000" width="100" height="100" x="50" y="50"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::MONOCHROME);

        $this->processor->parseSvg($svg);
        $newSvg = $this->processor->replaceColors($palette);

        expect($newSvg)->toContain('width="200"')
            ->and($newSvg)->toContain('height="200"')
            ->and($newSvg)->toContain('viewBox="0 0 200 200"')
            ->and($newSvg)->toContain('x="50"')
            ->and($newSvg)->toContain('y="50"');
    });

    it('handles RGB color format', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="rgb(255, 0, 0)" width="100" height="100"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#FF0000');
    });

    it('ignores none and transparent values', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="none" stroke="transparent" width="100" height="100"/>
            <circle fill="#FF0000" cx="50" cy="50" r="40"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#FF0000')
            ->and($colors)->not->toContain('none')
            ->and($colors)->not->toContain('transparent');
    });

    it('maps colors intelligently based on luminance', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#000000" width="100" height="100"/>
            <rect fill="#FFFFFF" width="100" height="100"/>
            <rect fill="#808080" width="100" height="100"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::OCEAN_BLUE);

        $this->processor->parseSvg($svg);
        $mapping = $this->processor->createColorMapping($palette);

        // Dark color should map to primary (darkest)
        expect($mapping['#000000'])->toBe($palette['primary']);
        // Light color should map to neutral (lightest)
        expect($mapping['#FFFFFF'])->toBe($palette['neutral']);
    });

    it('handles malformed SVG gracefully', function (): void {
        $malformedSvg = '<svg><rect fill="#FF0000" width="100" height="100">';

        $result = $this->processor->parseSvg($malformedSvg);

        expect($result)->toBeFalse()
            ->and($this->processor->getErrors())->not->toBeEmpty();
    });

    it('validates SVG before processing', function (): void {
        $invalidSvg = '<div>Not an SVG</div>';

        $result = $this->processor->parseSvg($invalidSvg);

        expect($result)->toBeFalse()
            ->and($this->processor->getErrors())->toContain('Invalid SVG: root element must be svg');
    });

    it('preserves gradients when replacing colors', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1">
                    <stop offset="0%" style="stop-color:#FF0000"/>
                    <stop offset="100%" style="stop-color:#0000FF"/>
                </linearGradient>
            </defs>
            <rect fill="url(#grad1)" width="100" height="100"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::OCEAN_BLUE);

        $this->processor->parseSvg($svg);
        $newSvg = $this->processor->replaceColors($palette);

        expect($newSvg)->toContain('linearGradient')
            ->and($newSvg)->toContain('url(#grad1)');
    });

    it('can process and return clean SVG output', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#FF0000" width="100" height="100"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::FOREST_GREEN);

        $result = $this->processor->processSvg($svg, $palette);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('svg')
            ->and($result['success'])->toBeTrue()
            ->and($result['svg'])->toContain($palette['primary']);
    });

    it('returns error details for failed processing', function (): void {
        $invalidSvg = 'not valid xml';

        $palette = $this->colorService->getColorPalette(ColorScheme::MONOCHROME);

        $result = $this->processor->processSvg($invalidSvg, $palette);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('errors')
            ->and($result['success'])->toBeFalse()
            ->and($result['errors'])->not->toBeEmpty();
    });

    it('normalizes color formats to uppercase hex', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#ff0000" width="100" height="100"/>
            <rect fill="#00ff00" width="100" height="100"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#FF0000')
            ->and($colors)->toContain('#00FF00');
    });

    it('handles CSS color names', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="red" width="100" height="100"/>
            <rect fill="blue" width="100" height="100"/>
        </svg>';

        $this->processor->parseSvg($svg);
        $colors = $this->processor->detectColors();

        expect($colors)->toContain('#FF0000')
            ->and($colors)->toContain('#0000FF');
    });

    it('preserves opacity values', function (): void {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">
            <rect fill="#FF0000" fill-opacity="0.5" width="100" height="100"/>
            <rect fill="#00FF00" opacity="0.7" width="100" height="100"/>
        </svg>';

        $palette = $this->colorService->getColorPalette(ColorScheme::OCEAN_BLUE);

        $this->processor->parseSvg($svg);
        $newSvg = $this->processor->replaceColors($palette);

        expect($newSvg)->toContain('fill-opacity="0.5"')
            ->and($newSvg)->toContain('opacity="0.7"');
    });
});
