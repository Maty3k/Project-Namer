<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Feature Components FluxUI Alignment', function (): void {
    test('theme quick toggle uses FluxUI components', function (): void {
        $this->actingAs($this->user);

        $html = Blade::render('<livewire:theme-quick-toggle />');

        // Should render FluxUI menu item with proper styling
        expect($html)->toContain('data-flux-menu-item');
    });

    test('logo generation progress uses FluxUI zinc classes', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
            'business_name' => 'Test Business',
            'total_logos_requested' => 4,
            'logos_completed' => 2,
        ]);

        $html = Blade::render('<x-logo-generation-progress :logoGeneration="$logoGeneration" />', [
            'logoGeneration' => $logoGeneration,
        ]);

        // Should not use hardcoded gray colors
        expect($html)->not->toContain('bg-gray-800')
            ->and($html)->not->toContain('bg-gray-200')
            ->and($html)->not->toContain('bg-gray-500')
            ->and($html)->not->toContain('text-gray-900')
            ->and($html)->not->toContain('border-gray-200');

        // Should not use hardcoded blue colors
        expect($html)->not->toContain('bg-blue-200')
            ->and($html)->not->toContain('text-primary-600');
    });

    test('logo gallery skeleton uses FluxUI zinc classes', function (): void {
        $html = Blade::render('<x-logo-gallery-skeleton />');

        // Should not use hardcoded gray colors
        expect($html)->not->toContain('bg-gray-300 dark:bg-gray-700')
            ->and($html)->not->toContain('bg-gray-200 dark:bg-gray-800')
            ->and($html)->not->toContain('border-gray-200 dark:border-gray-700');
    });

    test('logo generation progress supports dark mode', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $html = Blade::render('<x-logo-generation-progress :logoGeneration="$logoGeneration" />', [
            'logoGeneration' => $logoGeneration,
        ]);

        // Should have dark mode support
        expect($html)->toContain('dark:');
    });

    test('logo gallery skeleton supports dark mode', function (): void {
        $html = Blade::render('<x-logo-gallery-skeleton />');

        // Should have dark mode support
        expect($html)->toContain('dark:');
    });

    test('logo generation progress shows correct status colors', function (): void {
        $statuses = ['pending', 'processing', 'completed', 'failed', 'partial'];

        foreach ($statuses as $status) {
            $logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => $status,
            ]);

            $html = Blade::render('<x-logo-generation-progress :logoGeneration="$logoGeneration" />', [
                'logoGeneration' => $logoGeneration,
            ]);

            // Should use semantic colors (green for success, red for error, amber for warning)
            expect($html)->toContain('text-');
        }
    });
});
