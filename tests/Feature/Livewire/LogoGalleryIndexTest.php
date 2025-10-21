<?php

declare(strict_types=1);

use App\Livewire\LogoGalleryIndex;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('LogoGalleryIndex Component', function (): void {
    it('can mount and display logo generations', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->assertOk()
            ->assertSee('Test Company')
            ->assertSee('Logo Gallery');
    });

    it('only displays logo generations for authenticated user', function (): void {
        $otherUser = User::factory()->create();

        $userGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'My Company',
        ]);

        $otherGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other Company',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->assertSee('My Company')
            ->assertDontSee('Other Company');
    });

    it('can search logo generations by business name', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Coffee Shop',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Pizza Restaurant',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->set('search', 'Coffee')
            ->assertSee('Coffee Shop')
            ->assertDontSee('Pizza Restaurant');
    });

    it('can filter logo generations by status', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Completed Business',
            'status' => 'completed',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Processing Business',
            'status' => 'processing',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->set('statusFilter', 'completed')
            ->assertSee('Completed Business')
            ->assertDontSee('Processing Business');
    });

    it('can clear all filters', function (): void {
        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Coffee Shop',
            'status' => 'completed',
        ]);

        LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Pizza Restaurant',
            'status' => 'processing',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->set('search', 'Coffee')
            ->set('statusFilter', 'completed')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSee('Coffee Shop')
            ->assertSee('Pizza Restaurant');
    });

    it('displays logo previews for completed generations', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $logo = GeneratedLogo::factory()->create([
            'logo_generation_id' => $logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->assertSee($logoGeneration->business_name);
    });

    it('displays empty state when no logo generations exist', function (): void {
        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->assertSee('No Logo Generations Found');
    });
});

describe('LogoGalleryIndex Download Functionality', function (): void {
    it('can download logos for a logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
            'status' => 'completed',
        ]);

        GeneratedLogo::factory()->count(3)->create([
            'logo_generation_id' => $logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('downloadLogos', $logoGeneration->id)
            ->assertDispatched('download-file')
            ->assertDispatched('toast', message: 'Download started!', type: 'success');
    });

    it('cannot download logos for another users logo generation', function (): void {
        $otherUser = User::factory()->create();

        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other Company',
            'status' => 'completed',
        ]);

        GeneratedLogo::factory()->count(3)->create([
            'logo_generation_id' => $logoGeneration->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('downloadLogos', $logoGeneration->id)
            ->assertDispatched('toast', message: 'Logo generation not found', type: 'error')
            ->assertNotDispatched('download-file');
    });

    it('handles download of logo generation with no logos gracefully', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('downloadLogos', $logoGeneration->id)
            ->assertDispatched('toast', message: 'No logos available for download', type: 'error')
            ->assertNotDispatched('download-file');
    });

    it('handles download of non-existent logo generation gracefully', function (): void {
        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('downloadLogos', 99999)
            ->assertDispatched('toast', message: 'Logo generation not found', type: 'error')
            ->assertNotDispatched('download-file');
    });
});

describe('LogoGalleryIndex Delete Functionality', function (): void {
    it('can delete a logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
        ]);

        expect(LogoGeneration::count())->toBe(1);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('confirmDelete', $logoGeneration->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('generationToDelete', $logoGeneration->id)
            ->call('deleteGeneration')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('generationToDelete', null)
            ->assertDispatched('toast', message: "Deleted logos for 'Test Company'", type: 'success');

        expect(LogoGeneration::count())->toBe(0);
    });

    it('deletes associated logos when deleting a logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
        ]);

        $logos = GeneratedLogo::factory()->count(3)->create([
            'logo_generation_id' => $logoGeneration->id,
        ]);

        expect(GeneratedLogo::count())->toBe(3);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('confirmDelete', $logoGeneration->id)
            ->call('deleteGeneration');

        expect(LogoGeneration::count())->toBe(0);
        expect(GeneratedLogo::count())->toBe(0);
    });

    it('cannot delete another users logo generation', function (): void {
        $otherUser = User::factory()->create();

        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Other Company',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('confirmDelete', $logoGeneration->id)
            ->call('deleteGeneration')
            ->assertDispatched('toast', message: 'Logo generation not found', type: 'error');

        expect(LogoGeneration::count())->toBe(1);
    });

    it('handles deletion of non-existent logo generation gracefully', function (): void {
        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('confirmDelete', 99999)
            ->call('deleteGeneration')
            ->assertDispatched('toast', message: 'Logo generation not found', type: 'error');
    });

    it('can cancel delete operation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Company',
        ]);

        Livewire::actingAs($this->user)
            ->test(LogoGalleryIndex::class)
            ->call('confirmDelete', $logoGeneration->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('generationToDelete', $logoGeneration->id)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('generationToDelete', null);

        expect(LogoGeneration::count())->toBe(1);
    });
});
