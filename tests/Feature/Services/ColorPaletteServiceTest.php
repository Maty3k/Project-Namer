<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
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

    it('returns null when project has no completed images', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        expect($result)->toBeNull();
    });

    it('returns null when project images have no dominant colors', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => null,
        ]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        expect($result)->toBeNull();
    });

    it('returns color palette description from project images', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => ['#00FF00', '#0000FF', '#FF0000'],
        ]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        expect($result)->toBeString()
            ->and($result)->toContain('IMPORTANT: Use this color palette')
            ->and($result)->toContain('green')
            ->and($result)->toContain('blue')
            ->and($result)->toContain('red')
            ->and($result)->toContain('#00FF00')
            ->and($result)->toContain('#0000FF')
            ->and($result)->toContain('#FF0000');
    });

    it('deduplicates similar colors from multiple images', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create two images with similar green colors
        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => ['#00FF00', '#FF0000'],
        ]);

        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => ['#00FF10', '#0000FF'],
        ]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        expect($result)->toBeString()
            ->and($result)->toContain('green')
            ->and($result)->toContain('red')
            ->and($result)->toContain('blue');
    });

    it('limits color palette to top 5 colors', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => [
                '#FF0000', // red
                '#00FF00', // green
                '#0000FF', // blue
                '#FFFF00', // yellow
                '#FF00FF', // purple
                '#00FFFF', // cyan
                '#FFA500', // orange
                '#800080', // purple
            ],
        ]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        // Count occurrences of hex colors in the result
        $hexMatches = [];
        preg_match_all('/#[0-9A-Fa-f]{6}/', $result, $hexMatches);

        expect($hexMatches[0])->toHaveCount(5);
    });

    it('ignores pending and failed images', function (): void {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Pending image
        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'pending',
            'dominant_colors' => ['#FF0000'],
        ]);

        // Failed image
        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'failed',
            'dominant_colors' => ['#00FF00'],
        ]);

        // Completed image
        ProjectImage::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'dominant_colors' => ['#0000FF'],
        ]);

        $result = $this->service->getColorPaletteFromImages($project->id);

        expect($result)->toContain('blue')
            ->and($result)->toContain('#0000FF')
            ->and($result)->not->toContain('#FF0000')
            ->and($result)->not->toContain('#00FF00');
    });
});
