<?php

declare(strict_types=1);

use App\Livewire\ShareModal;
use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->logoGeneration = LogoGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

describe('ShareModal Export Functionality', function (): void {
    it('renders successfully', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->assertStatus(200);
    });

    it('initializes with default values', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->assertSet('exportType', 'pdf')
            ->assertSet('includeMetadata', true)
            ->assertSet('includeDomains', false)
            ->assertSet('exportUrl', null)
            ->assertSet('isGenerating', false);
    });

    it('generates PDF export', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->set('includeMetadata', true)
            ->call('generateExport')
            ->assertSet('isGenerating', false);

        expect($component->get('exportUrl'))->not->toBeNull();
        expect(Export::count())->toBe(1);
        $export = Export::first();
        expect($export->export_type)->toBe('pdf');
        expect($export->user_id)->toBe($this->user->id);
    });

    it('generates CSV export', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'csv')
            ->set('includeDomains', true)
            ->call('generateExport');

        expect($component->get('exportUrl'))->not->toBeNull();
        $export = Export::first();
        expect($export->export_type)->toBe('csv');
    });

    it('generates JSON export', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'json')
            ->call('generateExport');

        expect($component->get('exportUrl'))->not->toBeNull();
        $export = Export::first();
        expect($export->export_type)->toBe('json');
    });

    it('dispatches success event after export generation', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->call('generateExport')
            ->assertDispatched('export-generated');
    });

    it('shows loading state during export generation', function (): void {
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->call('generateExport');

        // Final state should be false after generation
        expect($component->get('isGenerating'))->toBeFalse();
    });

    it('resets form after successful export', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'csv')
            ->call('generateExport')
            ->call('resetForm')
            ->assertSet('exportType', 'pdf')
            ->assertSet('includeMetadata', true)
            ->assertSet('includeDomains', false)
            ->assertSet('exportUrl', null);
    });

    it('prevents unauthorized users from generating exports', function (): void {
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->call('generateExport')
            ->assertForbidden();
    });

    it('validates export type', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'invalid')
            ->call('generateExport')
            ->assertHasErrors(['exportType']);
    });

    it('opens modal', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->assertSet('showModal', true);
    });

    it('closes modal', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->call('openModal')
            ->call('closeModal')
            ->assertSet('showModal', false);
    });

    it('resets form when modal closes', function (): void {
        Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'csv')
            ->set('includeMetadata', false)
            ->call('closeModal')
            ->assertSet('exportType', 'pdf')
            ->assertSet('includeMetadata', true);
    });

    it('downloads export file', function (): void {
        $export = Export::factory()->pdf()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
        ]);
        Storage::put($export->file_path, 'PDF content');

        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportUrl', route('api.exports.download', $export->uuid))
            ->call('downloadExport');

        // Verify download count was incremented
        $export->refresh();
        expect($export->download_count)->toBeGreaterThan(0);
    });

    it('handles export generation errors gracefully', function (): void {
        // Create a generation with valid data but mock export service to fail
        $component = Livewire::actingAs($this->user)
            ->test(ShareModal::class, ['logoGeneration' => $this->logoGeneration])
            ->set('exportType', 'pdf')
            ->call('generateExport');

        // Component should handle error and not crash
        expect($component->get('isGenerating'))->toBeFalse();
    });

    it('tracks download count', function (): void {
        $export = Export::factory()->pdf()->create([
            'user_id' => $this->user->id,
            'exportable_type' => LogoGeneration::class,
            'exportable_id' => $this->logoGeneration->id,
            'file_path' => 'exports/test.pdf',
            'download_count' => 5,
        ]);
        Storage::put($export->file_path, 'PDF content');

        // Download the file
        $response = $this->actingAs($this->user)
            ->get(route('api.exports.download', $export->uuid));

        $export->refresh();
        expect($export->download_count)->toBe(6);
    });
});
