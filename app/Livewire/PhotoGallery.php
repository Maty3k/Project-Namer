<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PhotoGallery extends Component
{
    use WithPagination;

    public Project $project;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 't')]
    public string $selectedTags = '';

    #[Url(as: 's')]
    public string $sortBy = 'date_desc';

    #[Url(as: 'st')]
    public string $statusFilter = '';

    #[Url(as: 'v')]
    public string $viewMode = 'grid';

    /** @var array<string> */
    public array $selectedImages = [];

    public bool $showBulkActions = false;

    public bool $showImageModal = false;

    public ?ProjectImage $modalImage = null;

    public string $bulkAction = '';

    /** @var array<string> */
    public array $bulkTags = [];

    public bool $bulkIsPublic = false;

    /**
     * Mount the component with a project.
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Available sort options.
     *
     * @return array<string, string>
     */
    public function getSortOptions(): array
    {
        return [
            'date_desc' => 'Newest First',
            'date_asc' => 'Oldest First',
            'name_asc' => 'Name A-Z',
            'name_desc' => 'Name Z-A',
            'size_asc' => 'Smallest First',
            'size_desc' => 'Largest First',
        ];
    }

    /**
     * Available status filter options.
     *
     * @return array<string, string>
     */
    public function getStatusOptions(): array
    {
        return [
            '' => 'All Statuses',
            'pending' => 'Processing',
            'processing' => 'In Progress',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
    }

    /**
     * Get available tags for filtering.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getAvailableTagsProperty(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Support\Collection<int, string> $tags */
        $tags = ProjectImage::where('project_id', $this->project->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return $tags;
    }

    /**
     * Get images for the current filters and pagination.
     */
    public function getImagesProperty(): mixed
    {
        $query = ProjectImage::where('project_id', $this->project->id);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('original_filename', 'like', "%{$this->search}%")
                    ->orWhere('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Apply tag filter
        if ($this->selectedTags) {
            $tags = explode(',', $this->selectedTags);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        // Apply status filter
        if ($this->statusFilter) {
            $query->where('processing_status', $this->statusFilter);
        }

        // Apply sorting
        match ($this->sortBy) {
            'date_asc' => $query->orderBy('created_at', 'asc'),
            'name_asc' => $query->orderBy('original_filename', 'asc'),
            'name_desc' => $query->orderBy('original_filename', 'desc'),
            'size_asc' => $query->orderBy('file_size', 'asc'),
            'size_desc' => $query->orderBy('file_size', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(20);
    }

    /**
     * Clear all filters and reset to default state.
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedTags', 'sortBy', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * Toggle image selection for bulk actions.
     */
    public function toggleImageSelection(string $imageUuid): void
    {
        if (in_array($imageUuid, $this->selectedImages)) {
            $this->selectedImages = array_values(array_filter($this->selectedImages, fn ($uuid) => $uuid !== $imageUuid));
        } else {
            $this->selectedImages[] = $imageUuid;
        }

        $this->showBulkActions = count($this->selectedImages) > 0;
    }

    /**
     * Select all visible images.
     */
    public function selectAllImages(): void
    {
        $currentImages = $this->getImagesProperty();
        $this->selectedImages = (new \Illuminate\Support\Collection($currentImages->items()))->pluck('uuid')->toArray();
        $this->showBulkActions = true;
    }

    /**
     * Clear all image selections.
     */
    public function clearSelection(): void
    {
        $this->selectedImages = [];
        $this->showBulkActions = false;
    }

    /**
     * Open image detail modal.
     */
    public function openImageModal(string $imageUuid): void
    {
        $this->modalImage = ProjectImage::where('uuid', $imageUuid)
            ->where('project_id', $this->project->id)
            ->firstOrFail();

        $this->showImageModal = true;
    }

    /**
     * Close image detail modal.
     */
    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->modalImage = null;
    }

    /**
     * Update image metadata from modal.
     */
    public function updateImageMetadata(): void
    {
        if (! $this->modalImage) {
            return;
        }

        $this->validate([
            'modalImage.title' => ['nullable', 'string', 'max:255'],
            'modalImage.description' => ['nullable', 'string', 'max:1000'],
            'modalImage.tags' => ['nullable', 'array', 'max:20'],
            'modalImage.tags.*' => ['string', 'max:50'],
            'modalImage.is_public' => ['boolean'],
        ]);

        $this->modalImage->save();

        $this->dispatch('image-updated', imageId: $this->modalImage->id);
        $this->dispatch('notify', message: 'Image updated successfully', type: 'success');
    }

    /**
     * Delete single image from modal.
     */
    public function deleteImageFromModal(): void
    {
        if (! $this->modalImage) {
            return;
        }

        $this->deleteImages([$this->modalImage->uuid]);
        $this->closeImageModal();
    }

    /**
     * Perform bulk action on selected images.
     */
    public function performBulkAction(): void
    {
        if (empty($this->selectedImages)) {
            $this->dispatch('notify', message: 'Please select images first', type: 'error');

            return;
        }

        $this->validate([
            'bulkAction' => ['required', 'string', Rule::in(['delete', 'add_tags', 'remove_tags', 'toggle_public'])],
            'bulkTags' => ['required_if:bulkAction,add_tags,remove_tags', 'array', 'max:20'],
            'bulkTags.*' => ['string', 'max:50'],
        ]);

        switch ($this->bulkAction) {
            case 'delete':
                $this->deleteImages($this->selectedImages);
                break;
            case 'add_tags':
                $this->addTagsToImages($this->selectedImages, $this->bulkTags);
                break;
            case 'remove_tags':
                $this->removeTagsFromImages($this->selectedImages, $this->bulkTags);
                break;
            case 'toggle_public':
                $this->toggleImagesPublic($this->selectedImages, $this->bulkIsPublic);
                break;
        }

        $this->clearSelection();
        $this->reset(['bulkAction', 'bulkTags', 'bulkIsPublic']);
    }

    /**
     * Delete images by UUIDs.
     *
     * @param  array<string>  $imageUuids
     */
    protected function deleteImages(array $imageUuids): void
    {
        $images = ProjectImage::where('project_id', $this->project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        foreach ($images as $image) {
            // Delete files from storage
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }
            if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                Storage::disk('public')->delete($image->thumbnail_path);
            }

            // Update project counters
            $this->project->decrement('total_images');
            $this->project->decrement('storage_used_bytes', $image->file_size);
        }

        ProjectImage::whereIn('uuid', $imageUuids)->delete();

        $count = count($imageUuids);
        $this->dispatch('notify', message: "Successfully deleted {$count} images", type: 'success');
    }

    /**
     * Add tags to images.
     *
     * @param  array<string>  $imageUuids
     * @param  array<string>  $newTags
     */
    protected function addTagsToImages(array $imageUuids, array $newTags): void
    {
        $images = ProjectImage::where('project_id', $this->project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        foreach ($images as $image) {
            $existingTags = $image->tags ?? [];
            $mergedTags = array_unique(array_merge($existingTags, $newTags));
            $image->update(['tags' => $mergedTags]);
        }

        $count = $images->count();
        $this->dispatch('notify', message: "Tags added to {$count} images", type: 'success');
    }

    /**
     * Remove tags from images.
     *
     * @param  array<string>  $imageUuids
     * @param  array<string>  $tagsToRemove
     */
    protected function removeTagsFromImages(array $imageUuids, array $tagsToRemove): void
    {
        $images = ProjectImage::where('project_id', $this->project->id)
            ->whereIn('uuid', $imageUuids)
            ->get();

        foreach ($images as $image) {
            $existingTags = $image->tags ?? [];
            $filteredTags = array_values(array_diff($existingTags, $tagsToRemove));
            $image->update(['tags' => $filteredTags]);
        }

        $count = $images->count();
        $this->dispatch('notify', message: "Tags removed from {$count} images", type: 'success');
    }

    /**
     * Toggle public status of images.
     *
     * @param  array<string>  $imageUuids
     */
    protected function toggleImagesPublic(array $imageUuids, bool $isPublic): void
    {
        ProjectImage::where('project_id', $this->project->id)
            ->whereIn('uuid', $imageUuids)
            ->update(['is_public' => $isPublic]);

        $count = count($imageUuids);
        $status = $isPublic ? 'public' : 'private';
        $this->dispatch('notify', message: "Marked {$count} images as {$status}", type: 'success');
    }

    /**
     * Handle keyboard navigation.
     */
    public function handleKeydown(string $key): void
    {
        if ($this->showImageModal && $this->modalImage) {
            $currentImages = $this->getImagesProperty();
            $imageCollection = new \Illuminate\Support\Collection($currentImages->items());
            $currentIndex = $imageCollection->search(fn ($img) => $img->id === $this->modalImage->id);

            switch ($key) {
                case 'ArrowLeft':
                    if ($currentIndex > 0) {
                        $this->modalImage = $imageCollection->get($currentIndex - 1);
                    }
                    break;
                case 'ArrowRight':
                    if ($currentIndex < $imageCollection->count() - 1) {
                        $this->modalImage = $imageCollection->get($currentIndex + 1);
                    }
                    break;
                case 'Escape':
                    $this->closeImageModal();
                    break;
            }
        }
    }

    /**
     * Listen for new image uploads to refresh gallery.
     */
    #[On('image-uploaded')]
    public function refreshGallery(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'selectedTags', 'sortBy', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    /**
     * Handle Livewire serialization to prevent toJSON errors.
     */
    protected function serializeProperty(string $property): mixed
    {
        if ($this->$property instanceof Project) {
            return $this->$property->id;
        }

        if ($this->$property instanceof ProjectImage) {
            return $this->$property->uuid;
        }

        if ($this->$property instanceof \Illuminate\Database\Eloquent\Collection) {
            return $this->$property->pluck('uuid')->toArray();
        }

        if ($this->$property instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return [
                'items' => $this->$property->getCollection()->pluck('uuid')->toArray(),
                'current_page' => $this->$property->currentPage(),
                'total' => $this->$property->total(),
            ];
        }

        return $this->$property;
    }

    /**
     * Handle Livewire hydration to restore objects from serialized data.
     */
    protected function hydrateProperty(string $property, mixed $value): mixed
    {
        if ($property === 'project' && is_int($value)) {
            return Project::find($value);
        }

        if ($property === 'modalImage' && is_string($value)) {
            return ProjectImage::where('uuid', $value)->first();
        }

        // Don't hydrate computed properties - let them be computed fresh
        if (in_array($property, ['images', 'availableTags'])) {
            return null;
        }

        return $value;
    }

    /**
     * Serialize component state to JSON.
     */
    public function toJSON(): string
    {
        return json_encode([
            'project_id' => $this->project->id,
            'search' => $this->search,
            'selectedTags' => $this->selectedTags,
            'sortBy' => $this->sortBy,
            'statusFilter' => $this->statusFilter,
            'viewMode' => $this->viewMode,
            'selectedImages' => $this->selectedImages,
            'showBulkActions' => $this->showBulkActions,
            'showImageModal' => $this->showImageModal,
            'modalImage' => $this->modalImage?->uuid,
            'bulkAction' => $this->bulkAction,
            'bulkTags' => $this->bulkTags,
            'bulkIsPublic' => $this->bulkIsPublic,
        ]);
    }

    /**
     * Render the photo gallery component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.photo-gallery', [
            'images' => $this->getImagesProperty(),
            'availableTags' => $this->getAvailableTagsProperty(),
            'sortOptions' => $this->getSortOptions(),
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }
}
