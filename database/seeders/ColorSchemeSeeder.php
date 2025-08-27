<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\ColorPaletteService;
use Illuminate\Database\Seeder;

/**
 * Color scheme seeder.
 *
 * Seeds the database with color scheme reference data,
 * though in this implementation the color data is managed
 * in the ColorPaletteService rather than stored in the database.
 */
final class ColorSchemeSeeder extends Seeder
{
    public function __construct(
        private readonly ColorPaletteService $colorPaletteService
    ) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Color schemes are managed by ColorPaletteService.');
        $this->command->info('Available color schemes:');

        foreach ($this->colorPaletteService->getAllColorSchemesWithMetadata() as $scheme) {
            $this->command->line("- {$scheme['name']}: {$scheme['description']}");
        }

        $this->command->info('Color palette system is ready for use.');
    }
}
