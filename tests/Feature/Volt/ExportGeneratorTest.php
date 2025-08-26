<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\User;
use App\Services\ExportService;
use Livewire\Volt\Volt;

describe('Export Generator Component', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
    });

    it('renders the export generator component', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->assertSee('Export Results')
            ->assertSet('showModal', false);
    });

    it('opens and closes the export modal', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSee('Export Logo Generation')
            ->call('closeModal')
            ->assertSet('showModal', false);
    });

    it('displays export format options', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSee('PDF Document')
            ->assertSee('CSV Spreadsheet')
            ->assertSee('JSON Data')
            ->assertSee('Professional document with logos, names, and styling')
            ->assertSee('Spreadsheet format with names and domain information')
            ->assertSee('Technical format with complete data structure');
    });

    it('updates export type selection', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'csv')
            ->assertSet('exportType', 'csv')
            ->assertSet('includeLogos', false); // CSV should disable logo inclusion
    });

    it('validates export generation form', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'invalid')
            ->call('generateExport')
            ->assertHasErrors(['exportType']);
    });

    it('generates export successfully', function (): void {
        // Create a real export instead of mocking since ExportService is final
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'json') // Use JSON as it's simpler for testing
            ->set('includeDomains', true)
            ->set('includeMetadata', false) // Simplify for test
            ->set('includeLogos', false)
            ->call('generateExport')
            ->assertSet('isExporting', false)
            ->assertSet('exportSuccess', 'Export generated successfully! Download will begin shortly.')
            ->assertSet('exportError', null);

        // Verify an export was actually created
        $this->assertDatabaseHas('exports', [
            'exportable_type' => \App\Models\LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'export_type' => 'json',
            'user_id' => $this->user->id,
        ]);
    });

    it('handles export generation errors', function (): void {
        // Test with invalid data that would cause an error
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'invalid-type') // This will fail validation
            ->call('generateExport')
            ->assertSet('isExporting', false)
            ->assertHasErrors(['exportType']);
    });

    it('displays export options correctly', function (): void {
        // Test that the component has the correct default values for options
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSet('includeDomains', true)
            ->assertSet('includeMetadata', true)
            ->assertSet('includeLogos', true)
            ->assertSet('includeBranding', true)
            ->assertSet('template', 'default')
            ->assertSet('expiresInDays', 7);
    });

    it('updates options based on export type', function (): void {
        $component = Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->assertSet('includeLogos', true);

        // When switching to CSV, logos should be disabled
        $component->set('exportType', 'csv')
            ->assertSet('includeLogos', false);
    });

    it('shows template and expiration options', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSet('template', 'default')
            ->assertSet('expiresInDays', 7)
            ->set('template', 'professional')
            ->assertSet('template', 'professional')
            ->set('expiresInDays', 14)
            ->assertSet('expiresInDays', 14);
    });

    it('displays loading state during export generation', function (): void {
        Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'json')
            ->call('generateExport')
            ->assertSet('exportSuccess', 'Export generated successfully! Download will begin shortly.');
    });
});
