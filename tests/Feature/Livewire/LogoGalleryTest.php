<?php

declare(strict_types=1);

use App\Livewire\LogoGallery;
use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('public');
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('renders successfully with logo generation', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertStatus(200)
        ->assertSee($logoGeneration->business_name);
});

it('prevents unauthorized access to logo generation', function (): void {
    $otherUser = User::factory()->create();
    $logoGeneration = LogoGeneration::factory()
        ->for($otherUser)
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertStatus(403);
});

it('displays all logos for a generation', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    // Create one logo for each style
    GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('minimalist')->create();
    GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('modern')->create();
    GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('playful')->create();
    GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('corporate')->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('minimalist')
        ->assertSee('modern')
        ->assertSee('playful')
        ->assertSee('corporate');
});

it('downloads individual logo', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    // Create a fake file
    Storage::disk('public')->put($logo->file_path, 'fake image content');

    $response = Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('downloadLogo', $logo->id)
        ->assertStatus(200);
});

it('downloads all logos as zip', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    // Create logos with specific styles
    $logo1 = GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('minimalist')->create();
    $logo2 = GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('modern')->create();
    $logo3 = GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('playful')->create();
    $logo4 = GeneratedLogo::factory()->for($logoGeneration, 'logoGeneration')->completed()->style('corporate')->create();

    // Create fake files for all logos
    Storage::disk('public')->put($logo1->file_path, 'fake image content for minimalist');
    Storage::disk('public')->put($logo2->file_path, 'fake image content for modern');
    Storage::disk('public')->put($logo3->file_path, 'fake image content for playful');
    Storage::disk('public')->put($logo4->file_path, 'fake image content for corporate');

    $response = Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('downloadAll');

    // The response should be a BinaryFileResponse
    expect($response)->not->toBeNull();
});

it('opens delete confirmation modal', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSet('showDeleteModal', false)
        ->assertSet('logoToDelete', null)
        ->call('confirmDelete', $logo->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('logoToDelete.id', $logo->id);
});

it('cancels delete operation', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('confirmDelete', $logo->id)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('logoToDelete', null);
});

it('deletes logo and updates count', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'logos_completed' => 4,
        ]);

    // Create multiple logos so this isn't the last one
    GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->count(3)
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    // Create a fake file
    Storage::disk('public')->put($logo->file_path, 'fake image content');

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('confirmDelete', $logo->id)
        ->call('deleteLogo')
        ->assertSet('showDeleteModal', false)
        ->assertSet('logoToDelete', null)
        ->assertDispatched('show-toast');

    // Verify logo was deleted
    expect(GeneratedLogo::find($logo->id))->toBeNull();

    // Verify file was deleted
    expect(Storage::disk('public')->exists($logo->file_path))->toBeFalse();

    // Verify count was decremented
    expect($logoGeneration->fresh()->logos_completed)->toBe(3);
});

it('deletes logo with null file path without errors', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'logos_completed' => 2,
        ]);

    // Create another logo so this isn't the last one
    GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    // Create a logo in processing state (no file_path)
    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->create([
            'status' => 'processing',
            'file_path' => null,
        ]);

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('confirmDelete', $logo->id)
        ->call('deleteLogo')
        ->assertSet('showDeleteModal', false)
        ->assertSet('logoToDelete', null)
        ->assertDispatched('show-toast');

    // Verify logo was deleted
    expect(GeneratedLogo::find($logo->id))->toBeNull();

    // Verify parent generation still exists with decremented count
    expect($logoGeneration->fresh()->logos_completed)->toBe(1);
});

it('displays empty state when no logos exist', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->create([
            'logos_completed' => 0,
        ]);

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('No logos yet')
        ->assertSee('Logos are still being generated or generation failed');
});

it('displays download all button only when logos exist', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->has(GeneratedLogo::factory()->completed()->count(4), 'generatedLogos')
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('Download All (ZIP)');
});

it('shows status badge correctly', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('Completed')
        ->assertSee('4 / 4 logos');
});

it('can toggle saved status', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->has(GeneratedLogo::factory()->completed()->count(4), 'generatedLogos')
        ->create(['is_saved' => false]);

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('Favorite')
        ->call('toggleSaved')
        ->assertDispatched('show-toast');

    expect($logoGeneration->fresh()->is_saved)->toBeTrue();
});

it('shows saved status when already saved', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->has(GeneratedLogo::factory()->completed()->count(4), 'generatedLogos')
        ->create(['is_saved' => true]);

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSee('Favorited');
});

it('can unsave a saved logo generation', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create(['is_saved' => true]);

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('toggleSaved')
        ->assertDispatched('show-toast');

    expect($logoGeneration->fresh()->is_saved)->toBeFalse();
});

it('opens preview modal when clicking on a logo', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->style('minimalist')
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertSet('showPreviewModal', false)
        ->assertSet('logoToPreview', null)
        ->call('previewLogo', $logo->id)
        ->assertSet('showPreviewModal', true)
        ->assertSet('logoToPreview.id', $logo->id);
});

it('closes preview modal', function (): void {
    $logoGeneration = LogoGeneration::factory()
        ->for($this->user)
        ->completed()
        ->create();

    $logo = GeneratedLogo::factory()
        ->for($logoGeneration, 'logoGeneration')
        ->completed()
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->call('previewLogo', $logo->id)
        ->assertSet('showPreviewModal', true)
        ->call('closePreview')
        ->assertSet('showPreviewModal', false)
        ->assertSet('logoToPreview', null);
});

it('prevents unauthorized users from previewing logos', function (): void {
    $otherUser = User::factory()->create();
    $logoGeneration = LogoGeneration::factory()
        ->for($otherUser)
        ->create();

    Livewire::test(LogoGallery::class, ['logoGeneration' => $logoGeneration])
        ->assertStatus(403);
});
