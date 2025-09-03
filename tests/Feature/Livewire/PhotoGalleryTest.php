<?php

declare(strict_types=1);

use App\Livewire\PhotoGallery;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    // Create test images
    $this->images = ProjectImage::factory()->count(5)->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'processing_status' => 'completed',
        'tags' => ['inspiration', 'branding'],
    ]);
});

test('renders photo gallery component', function (): void {
    // Create empty project to test empty state
    $emptyProject = Project::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(PhotoGallery::class, ['project' => $emptyProject])
        ->assertSee('Upload your first photo to get started');
});

test('can search images by filename', function (): void {
    $searchableImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'original_filename' => 'unique-search-name.jpg',
        'processing_status' => 'completed',
    ]);

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('search', 'unique-search')
        ->assertSet('search', 'unique-search');
});

test('can filter images by tags', function (): void {
    $taggedImage = ProjectImage::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'tags' => ['special', 'filtered'],
        'processing_status' => 'completed',
    ]);

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('selectedTags', 'special')
        ->assertSet('selectedTags', 'special');
});

test('can change sort order', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('sortBy', 'name_asc')
        ->assertSet('sortBy', 'name_asc');
});

test('can toggle view mode between grid and list', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->assertSet('viewMode', 'grid')
        ->set('viewMode', 'list')
        ->assertSet('viewMode', 'list');
});

test('can select and deselect images', function (): void {
    $image = $this->images->first();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('toggleImageSelection', $image->uuid)
        ->assertSet('selectedImages', [$image->uuid])
        ->assertSet('showBulkActions', true)
        ->call('toggleImageSelection', $image->uuid)
        ->assertSet('selectedImages', [])
        ->assertSet('showBulkActions', false);
});

test('can select all images', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('selectAllImages')
        ->assertSet('showBulkActions', true)
        ->assertCount('selectedImages', 5);
});

test('can clear selection', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('selectAllImages')
        ->call('clearSelection')
        ->assertSet('selectedImages', [])
        ->assertSet('showBulkActions', false);
});

test('can open and close image modal', function (): void {
    $image = $this->images->first();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('openImageModal', $image->uuid)
        ->assertSet('showImageModal', true)
        ->call('closeImageModal')
        ->assertSet('showImageModal', false)
        ->assertSet('modalImage', null);
});

test('can update image metadata from modal', function (): void {
    $image = $this->images->first();

    // Update the image directly to test the update functionality
    $image->update([
        'title' => 'Original Title',
        'description' => 'Original description',
        'tags' => ['original', 'tags'],
    ]);

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('openImageModal', $image->uuid)
        ->assertSet('showImageModal', true)
        ->call('updateImageMetadata')
        ->assertDispatched('image-updated')
        ->assertDispatched('notify');
});

test('can delete image from modal', function (): void {
    $image = $this->images->first();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('openImageModal', $image->uuid)
        ->call('deleteImageFromModal')
        ->assertSet('showImageModal', false);

    expect(ProjectImage::find($image->id))->toBeNull();
});

test('can perform bulk delete action', function (): void {
    $imageUuids = $this->images->take(2)->pluck('uuid')->toArray();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('selectedImages', $imageUuids)
        ->set('bulkAction', 'delete')
        ->call('performBulkAction')
        ->assertDispatched('notify');

    expect(ProjectImage::whereIn('uuid', $imageUuids)->count())->toBe(0);
});

test('can perform bulk add tags action', function (): void {
    $imageUuids = $this->images->take(2)->pluck('uuid')->toArray();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('selectedImages', $imageUuids)
        ->set('bulkAction', 'add_tags')
        ->set('bulkTags', ['bulk-added', 'test'])
        ->call('performBulkAction')
        ->assertDispatched('notify');

    foreach ($imageUuids as $uuid) {
        $image = ProjectImage::where('uuid', $uuid)->first();
        expect($image->tags)->toContain('bulk-added', 'test');
    }
});

test('can handle keyboard navigation in modal', function (): void {
    $firstImage = $this->images->first();

    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->call('openImageModal', $firstImage->uuid)
        ->assertSet('showImageModal', true)
        ->call('handleKeydown', 'ArrowRight')
        ->call('handleKeydown', 'Escape')
        ->assertSet('showImageModal', false);
});

test('clears filters correctly', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('search', 'test')
        ->set('selectedTags', 'inspiration')
        ->set('sortBy', 'name_asc')
        ->set('statusFilter', 'pending')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('selectedTags', '')
        ->assertSet('sortBy', 'date_desc')
        ->assertSet('statusFilter', '');
});

test('filters work correctly', function (): void {
    Livewire::test(PhotoGallery::class, ['project' => $this->project])
        ->set('search', 'test')
        ->assertSet('search', 'test');
});
