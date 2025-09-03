<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\MoodBoardLayout;
use App\Models\MoodBoard;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class MoodBoardCanvas extends Component
{
    public Project $project;

    public ?MoodBoard $activeMoodBoard = null;

    /** @var array<string, mixed> */
    public array $layoutConfig = [
        'background_color' => '#ffffff',
        'grid_size' => 20,
        'snap_to_grid' => true,
        'images' => [],
    ];

    public string $newMoodBoardName = '';

    public string $newMoodBoardDescription = '';

    public string $newMoodBoardLayout = 'freeform';

    public bool $showCreateModal = false;

    public bool $showExportModal = false;

    public string $exportFormat = 'pdf';

    /** @var array<string, mixed> */
    public array $exportOptions = [
        'quality' => 90,
        'width' => 1200,
        'height' => 800,
        'include_metadata' => true,
    ];

    /** @var array<string, mixed> */
    public array $selectedImagePositions = [];

    public bool $isEditMode = false;

    /**
     * Get available layout options.
     *
     * @return array<string, string>
     */
    public function getLayoutOptionsProperty(): array
    {
        return [
            MoodBoardLayout::Freeform->value => 'Freeform - Position images anywhere',
            MoodBoardLayout::Grid->value => 'Grid - Organized layout with snap-to-grid',
            MoodBoardLayout::Collage->value => 'Collage - Overlapping artistic arrangement',
        ];
    }

    /**
     * Get mood boards for current project.
     *
     * @return Collection<int, MoodBoard>
     */
    public function getMoodBoardsProperty(): Collection
    {
        return MoodBoard::where('project_id', $this->project->id)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get available images for mood board.
     *
     * @return Collection<int, ProjectImage>
     */
    public function getAvailableImagesProperty(): Collection
    {
        return ProjectImage::where('project_id', $this->project->id)
            ->where('processing_status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get current canvas images with their positions.
     *
     * @return Collection<int|string, array{image: ProjectImage, position: mixed}>
     */
    public function getLayoutImagesProperty(): Collection
    {
        if (! $this->activeMoodBoard) {
            return collect();
        }

        $imagesData = $this->layoutConfig['images'] ?? [];
        $imagePositions = new Collection($imagesData);
        $imageUuids = $imagePositions->pluck('image_uuid');

        $images = ProjectImage::where('project_id', $this->project->id)
            ->whereIn('uuid', $imageUuids)
            ->get()
            ->keyBy('uuid');

        return $imagePositions->map(function ($position) use ($images) {
            $image = $images->get($position['image_uuid']);
            if (! $image) {
                return null;
            }

            return [
                'image' => $image,
                'position' => $position,
            ];
        })->filter();
    }

    /**
     * Create new mood board.
     */
    public function createMoodBoard(): void
    {
        $this->validate([
            'newMoodBoardName' => ['required', 'string', 'max:255'],
            'newMoodBoardDescription' => ['nullable', 'string', 'max:1000'],
            'newMoodBoardLayout' => ['required', 'string', 'in:freeform,grid,collage'],
        ]);

        $moodBoard = MoodBoard::create([
            'project_id' => $this->project->id,
            'user_id' => auth()->id(),
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => $this->newMoodBoardName,
            'description' => $this->newMoodBoardDescription,
            'layout_type' => $this->newMoodBoardLayout,
            'layout_config' => [
                'background_color' => '#ffffff',
                'grid_size' => 20,
                'snap_to_grid' => $this->newMoodBoardLayout === 'grid',
                'images' => [],
            ],
        ]);

        $this->loadMoodBoard($moodBoard->uuid);
        $this->resetCreateModal();

        $this->dispatch('notify', message: 'Mood board created successfully', type: 'success');
    }

    /**
     * Load specific mood board.
     */
    public function loadMoodBoard(string $uuid): void
    {
        $this->activeMoodBoard = MoodBoard::where('project_id', $this->project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $this->layoutConfig = $this->activeMoodBoard->layout_config ?? [
            'background_color' => '#ffffff',
            'grid_size' => 20,
            'snap_to_grid' => true,
            'images' => [],
        ];

        $this->isEditMode = false;
    }

    /**
     * Update canvas configuration.
     */
    public function updateCanvas(): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        $this->validate([
            'layoutConfig.background_color' => ['nullable', 'string', 'regex:/^#[0-9a-f]{6}$/i'],
            'layoutConfig.grid_size' => ['nullable', 'integer', 'min:10', 'max:50'],
            'layoutConfig.snap_to_grid' => ['nullable', 'boolean'],
        ]);

        $this->activeMoodBoard->update(['layout_config' => $this->layoutConfig]);

        $this->dispatch('notify', message: 'Canvas updated successfully', type: 'success');
    }

    /**
     * Add image to mood board at specified position.
     *
     * @param  array<string, mixed>  $position
     */
    public function addImageToCanvas(string $imageUuid, array $position = []): void
    {
        if (! $this->activeMoodBoard) {
            $this->dispatch('notify', message: 'Please create or select a mood board first', type: 'error');

            return;
        }

        $image = ProjectImage::where('project_id', $this->project->id)
            ->where('uuid', $imageUuid)
            ->first();

        if (! $image) {
            $this->dispatch('notify', message: 'Image not found', type: 'error');

            return;
        }

        // Check if image already exists in canvas
        $existingImagesData = $this->layoutConfig['images'] ?? [];
        $existingImages = new Collection($existingImagesData);
        if ($existingImages->contains('image_uuid', $imageUuid)) {
            $this->dispatch('notify', message: 'Image already added to mood board', type: 'warning');

            return;
        }

        // Add image to canvas config
        $newPosition = [
            'image_uuid' => $imageUuid,
            'x' => $position['x'] ?? 100,
            'y' => $position['y'] ?? 100,
            'width' => $position['width'] ?? 200,
            'height' => $position['height'] ?? 200,
            'rotation' => $position['rotation'] ?? 0,
            'z_index' => $position['z_index'] ?? count($this->layoutConfig['images']) + 1,
        ];

        $this->layoutConfig['images'][] = $newPosition;
        $this->activeMoodBoard->update(['layout_config' => $this->layoutConfig]);

        // Update the pivot relationship
        $this->activeMoodBoard->projectImages()->syncWithoutDetaching([$image->id]);

        $this->dispatch('notify', message: 'Image added to mood board', type: 'success');
    }

    /**
     * Update image position on canvas.
     *
     * @param  array<string, mixed>  $position
     */
    public function updateImagePosition(string $imageUuid, array $position): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        $imageData = $this->layoutConfig['images'] ?? [];
        $images = new Collection($imageData);
        $imageIndex = $images->search(fn ($img) => $img['image_uuid'] === $imageUuid);

        if ($imageIndex !== false) {
            $this->layoutConfig['images'][$imageIndex] = array_merge(
                $this->layoutConfig['images'][$imageIndex],
                $position
            );

            $this->activeMoodBoard->update(['layout_config' => $this->layoutConfig]);
        }
    }

    /**
     * Remove image from canvas.
     */
    public function removeImageFromCanvas(string $imageUuid): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        // Remove from canvas config
        $imageConfigData = $this->layoutConfig['images'] ?? [];
        $imageCollection = new Collection($imageConfigData);
        $this->layoutConfig['images'] = $imageCollection
            ->reject(fn ($img) => $img['image_uuid'] === $imageUuid)
            ->values()
            ->toArray();

        $this->activeMoodBoard->update(['layout_config' => $this->layoutConfig]);

        // Remove from pivot relationship
        $image = ProjectImage::where('uuid', $imageUuid)->first();
        if ($image) {
            $this->activeMoodBoard->projectImages()->detach($image->id);
        }

        $this->dispatch('notify', message: 'Image removed from mood board', type: 'success');
    }

    /**
     * Delete mood board.
     */
    public function deleteMoodBoard(string $uuid): void
    {
        $moodBoard = MoodBoard::where('project_id', $this->project->id)
            ->where('uuid', $uuid)
            ->first();

        if (! $moodBoard) {
            return;
        }

        if ($this->activeMoodBoard && $this->activeMoodBoard->id === $moodBoard->id) {
            $this->activeMoodBoard = null;
            $this->layoutConfig = [
                'background_color' => '#ffffff',
                'grid_size' => 20,
                'snap_to_grid' => true,
                'images' => [],
            ];
        }

        $moodBoard->delete();
        $this->dispatch('notify', message: 'Mood board deleted successfully', type: 'success');
    }

    /**
     * Toggle edit mode.
     */
    public function toggleEditMode(): void
    {
        $this->isEditMode = ! $this->isEditMode;
    }

    /**
     * Show create modal.
     */
    public function showCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    /**
     * Reset create modal.
     */
    public function resetCreateModal(): void
    {
        $this->reset(['newMoodBoardName', 'newMoodBoardDescription', 'newMoodBoardLayout']);
        $this->showCreateModal = false;
    }

    /**
     * Show export modal.
     */
    public function showExportModal(): void
    {
        if (! $this->activeMoodBoard) {
            $this->dispatch('notify', message: 'Please select a mood board to export', type: 'error');

            return;
        }

        $this->showExportModal = true;
    }

    /**
     * Export mood board.
     */
    public function exportMoodBoard(): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        $this->validate([
            'exportFormat' => ['required', 'string', 'in:pdf,png,jpg'],
            'exportOptions.quality' => ['integer', 'min:60', 'max:100'],
            'exportOptions.width' => ['integer', 'min:800', 'max:4000'],
            'exportOptions.height' => ['integer', 'min:600', 'max:4000'],
        ]);

        // Call API endpoint to generate export
        $this->dispatch('export-mood-board', [
            'uuid' => $this->activeMoodBoard->uuid,
            'format' => $this->exportFormat,
            'options' => $this->exportOptions,
        ]);

        $this->showExportModal = false;
        $this->dispatch('notify', message: 'Export started. Download will begin shortly.', type: 'success');
    }

    /**
     * Handle drag and drop events.
     *
     * @param  array<string, mixed>  $position
     */
    #[On('image-dropped')]
    public function handleImageDrop(string $imageUuid, array $position): void
    {
        if (! $this->activeMoodBoard) {
            $this->dispatch('notify', message: 'Please create or select a mood board first', type: 'error');

            return;
        }

        // Check if image is already on canvas
        $layoutImagesData = $this->layoutConfig['images'] ?? [];
        $existingImages = new Collection($layoutImagesData);
        $existingIndex = $existingImages->search(fn ($img) => $img['image_uuid'] === $imageUuid);

        if ($existingIndex !== false) {
            // Update existing position
            $this->updateImagePosition($imageUuid, $position);
        } else {
            // Add new image
            $this->addImageToCanvas($imageUuid, $position);
        }
    }

    /**
     * Handle canvas background updates.
     */
    public function updateCanvasBackground(string $backgroundColor): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        $this->layoutConfig['background_color'] = $backgroundColor;
        $this->updateCanvas();
    }

    /**
     * Handle grid settings updates.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateGridSettings(array $settings): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        if (isset($settings['grid_size'])) {
            $this->layoutConfig['grid_size'] = $settings['grid_size'];
        }

        if (isset($settings['snap_to_grid'])) {
            $this->layoutConfig['snap_to_grid'] = $settings['snap_to_grid'];
        }

        $this->updateCanvas();
    }

    /**
     * Apply layout template.
     */
    public function applyLayoutTemplate(string $layout): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        $images = $this->getLayoutImagesProperty();
        if ($images->isEmpty()) {
            $this->dispatch('notify', message: 'Add some images to the mood board first', type: 'warning');

            return;
        }

        $imageModels = $images->pluck('image');
        $newPositions = $this->calculateLayoutPositions($layout, $imageModels);

        foreach ($newPositions as $index => $position) {
            if (isset($this->layoutConfig['images'][$index])) {
                $this->layoutConfig['images'][$index] = array_merge(
                    $this->layoutConfig['images'][$index],
                    $position
                );
            }
        }

        $this->activeMoodBoard->update(['layout_config' => $this->layoutConfig]);
        $this->dispatch('notify', message: 'Layout template applied', type: 'success');
    }

    /**
     * Calculate positions for layout template.
     *
     * @param  Collection<int, ProjectImage>  $images
     * @return array<int, array<string, mixed>>
     */
    protected function calculateLayoutPositions(string $layout, Collection $images): array
    {
        $positions = [];
        $imageCount = $images->count();

        switch ($layout) {
            case MoodBoardLayout::Grid->value:
                $cols = ceil(sqrt($imageCount));
                $rows = ceil($imageCount / $cols);
                $spacing = 220; // 200px image + 20px gap

                foreach ($images as $index => $imageData) {
                    $col = $index % $cols;
                    $row = floor($index / $cols);

                    $positions[] = [
                        'x' => $col * $spacing + 50,
                        'y' => $row * $spacing + 50,
                        'width' => 200,
                        'height' => 200,
                        'rotation' => 0,
                        'z_index' => $index + 1,
                    ];
                }
                break;

            case MoodBoardLayout::Collage->value:
                // Overlapping artistic arrangement
                $centerX = 600;
                $centerY = 400;

                foreach ($images as $index => $imageData) {
                    $angle = ($index / $imageCount) * 2 * M_PI;
                    $radius = 100 + ($index % 3) * 80;

                    $positions[] = [
                        'x' => $centerX + cos($angle) * $radius,
                        'y' => $centerY + sin($angle) * $radius,
                        'width' => 180 + ($index % 40),
                        'height' => 180 + ($index % 40),
                        'rotation' => ($index % 5 - 2) * 15, // -30 to +30 degrees
                        'z_index' => $index + 1,
                    ];
                }
                break;

            case MoodBoardLayout::Freeform->value:
            default:
                // Random but aesthetically pleasing positions
                foreach ($images as $index => $imageData) {
                    $positions[] = [
                        'x' => 50 + ($index * 100) % 800,
                        'y' => 50 + (($index * 137) % 600), // Use golden ratio for spacing
                        'width' => 200,
                        'height' => 200,
                        'rotation' => 0,
                        'z_index' => $index + 1,
                    ];
                }
                break;
        }

        return $positions;
    }

    /**
     * Toggle sharing for mood board.
     */
    public function toggleSharing(): void
    {
        if (! $this->activeMoodBoard) {
            return;
        }

        if ($this->activeMoodBoard->is_public) {
            // Revoke sharing
            $this->activeMoodBoard->update([
                'is_public' => false,
                'share_token' => null,
            ]);

            $this->dispatch('notify', message: 'Public sharing disabled', type: 'success');
        } else {
            // Enable sharing
            $shareToken = \Illuminate\Support\Str::random(32);
            $this->activeMoodBoard->update([
                'is_public' => true,
                'share_token' => $shareToken,
            ]);

            $sharingUrl = url("/share/mood-board/{$shareToken}");
            $this->dispatch('notify', message: 'Public sharing enabled. Link copied to clipboard.', type: 'success');
            $this->dispatch('copy-to-clipboard', text: $sharingUrl);
        }
    }

    public function render()
    {
        return view('livewire.mood-board-canvas', [
            'moodBoards' => $this->moodBoards,
            'availableImages' => $this->availableImages,
            'layoutImages' => $this->layoutImages,
            'canvasImages' => $this->layoutImages, // Same as layoutImages  
            'layoutOptions' => $this->layoutOptions,
        ]);
    }
}
