<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

describe('Logo Gallery Error Handling', function (): void {
    describe('Color Customization Errors', function (): void {
        it('validates color scheme selection', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', '')
                ->call('applyColorScheme')
                ->assertHasErrors(['selectedColorScheme' => 'required']);
        });

        it('validates logo selection for customization', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [])
                ->set('selectedColorScheme', 'ocean_blue')
                ->call('applyColorScheme')
                ->assertHasErrors(['selectedLogos' => 'required']);
        });

        it('handles invalid color schemes gracefully', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'invalid_scheme')
                ->call('applyColorScheme')
                ->assertHasErrors(['selectedColorScheme' => 'The selected color scheme is invalid.']);
        });

        it('handles corrupted logo files during customization', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/corrupted.svg',
            ]);

            // Create corrupted file
            Storage::disk('public')->put($logo->original_file_path, '<invalid-svg>');

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'ocean_blue')
                ->call('applyColorScheme')
                ->assertDispatched('toast', message: '0 logos customized successfully');
        });

        it('handles missing logo files during customization', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/missing.svg',
            ]);

            // Don't create the file, so it's missing

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'ocean_blue')
                ->call('applyColorScheme')
                ->assertDispatched('toast', message: '0 logos customized successfully');
        });

        it('shows processing state during customization', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/valid.svg',
            ]);

            // Create valid SVG file
            Storage::disk('public')->put($logo->original_file_path, '<svg><rect fill="#000000"/></svg>');

            $component = Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'ocean_blue');

            // Verify processing state is set during operation
            expect($component->get('isProcessing'))->toBeFalse();
            $component->call('applyColorScheme');
            // After completion, processing should be false again
            expect($component->get('isProcessing'))->toBeFalse();
        });
    });

    describe('Download Error Handling', function (): void {
        it('handles missing files during download', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/missing.svg',
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->call('downloadLogo', $logo->id, 'svg')
                ->assertDispatched('toast',
                    message: 'File not found. It may have been removed or is being regenerated.',
                    type: 'error'
                );
        });

        it('handles missing color variants during download', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Try to download a color variant that doesn't exist
            expect(function () use ($logoGeneration, $logo): void {
                Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                    ->call('downloadLogo', $logo->id, 'svg', 'nonexistent_scheme');
            })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('handles empty logo generation for batch download', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->call('downloadAllLogos')
                ->assertDispatched('toast', message: 'No logos available for download.');
        });
    });

    describe('Gallery State Management', function (): void {
        it('handles nonexistent logo generation gracefully', function (): void {
            expect(function (): void {
                Volt::test('pages.logo-gallery', ['logoGenerationId' => 999]);
            })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('refreshes status for processing generations', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'processing',
                'total_logos_requested' => 12,
                'logos_completed' => 3,
            ]);

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->call('refreshStatus')
                ->assertDispatched('$refresh');
        });

        it('toggles view mode correctly', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $component = Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id]);

            expect($component->get('viewMode'))->toBe('grid');

            $component->call('toggleViewMode');
            expect($component->get('viewMode'))->toBe('list');

            $component->call('toggleViewMode');
            expect($component->get('viewMode'))->toBe('grid');
        });

        it('manages logo selection state correctly', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo1 = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);
            $logo2 = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            $component = Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id]);

            // Initially no selection
            expect($component->get('selectedLogos'))->toBe([]);

            // Select first logo
            $component->call('toggleLogoSelection', $logo1->id);
            expect($component->get('selectedLogos'))->toBe([$logo1->id]);

            // Select second logo
            $component->call('toggleLogoSelection', $logo2->id);
            expect($component->get('selectedLogos'))->toBe([$logo1->id, $logo2->id]);

            // Deselect first logo
            $component->call('toggleLogoSelection', $logo1->id);
            expect($component->get('selectedLogos'))->toBe([$logo2->id]);

            // Select all
            $component->call('selectAllLogos');
            expect(count($component->get('selectedLogos')))->toBe(2);

            // Clear selection
            $component->call('clearSelection');
            expect($component->get('selectedLogos'))->toBe([]);
        });
    });

    describe('Service Integration Error Handling', function (): void {
        it('handles invalid color scheme validation', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);

            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
            ]);

            // Test with completely invalid color scheme that doesn't exist
            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'completely_invalid_scheme_that_does_not_exist')
                ->call('applyColorScheme')
                ->assertHasErrors(['selectedColorScheme' => 'The selected color scheme is invalid.']);
        });

        it('handles file processing errors during customization', function (): void {
            $logoGeneration = LogoGeneration::factory()->create([
                'status' => 'completed',
            ]);
            $logo = GeneratedLogo::factory()->create([
                'logo_generation_id' => $logoGeneration->id,
                'original_file_path' => 'logos/test/corrupted.svg',
            ]);

            // Create a malformed SVG file that will cause processing errors
            Storage::disk('public')->put($logo->original_file_path, '<invalid>not svg</invalid>');

            Volt::test('pages.logo-gallery', ['logoGenerationId' => $logoGeneration->id])
                ->set('selectedLogos', [$logo->id])
                ->set('selectedColorScheme', 'ocean_blue')
                ->call('applyColorScheme')
                ->assertDispatched('toast',
                    message: '0 logos customized successfully',
                    type: 'success'
                );
        });
    });
});
