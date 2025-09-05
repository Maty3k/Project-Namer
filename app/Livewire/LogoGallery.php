<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ColorScheme;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Models\UploadedLogo;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Logo Gallery component for displaying and managing generated logos.
 *
 * Handles logo display, color customization, and download functionality
 * with real-time updates during generation process.
 */
class LogoGallery extends Component
{
    use WithFileUploads;

    public int $logoGenerationId;

    protected ?LogoGeneration $logoGeneration = null;

    // Color customization
    public string $selectedColorScheme = '';

    /** @var array<int, int> */
    public array $selectedLogos = [];

    public bool $isCustomizingColors = false;

    // UI state
    public string $viewMode = 'grid'; // 'grid' or 'list'

    public bool $showColorPicker = false;

    // Search and filter properties
    public string $searchTerm = '';

    public string $filterType = 'all'; // 'all', 'generated', 'uploaded'

    public string $filterStyle = ''; // Filter by logo style

    // Modal properties
    public bool $showDetailModal = false;

    public ?int $detailLogoId = null;

    public string $detailLogoType = ''; // 'generated' or 'uploaded'

    // Error handling
    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    // File upload properties
    /** @var array<UploadedFile> */
    public array $uploadedFiles = [];

    /** @var array<UploadedFile> */
    public array $uploadQueue = [];

    public bool $isUploading = false;

    public int $uploadProgress = 0;

    public bool $isDraggedOver = false;

    public string $dragFeedback = '';

    public int $dragFileCount = 0;

    // File preview and processing properties
    /** @var array<int, array<string, mixed>> */
    public array $filePreviews = [];

    /** @var array<int, array<string, mixed>> */
    public array $duplicateWarnings = [];

    /** @var array<int, int> */
    public array $fileProgress = [];

    // Bulk operations properties
    /** @var array<int, int> */
    public array $selectedUploadedLogos = [];

    public bool $showBulkActions = false;

    /** @var array<string, mixed> */
    protected array $rules = [
        'selectedColorScheme' => 'required|string',
        'selectedLogos' => 'required|array|min:1',
        'selectedLogos.*' => 'exists:generated_logos,id',
        'uploadedFiles.*' => 'required|file|mimes:png,jpg,jpeg,svg|max:5120',
        'uploadQueue.*' => 'required|file|mimes:png,jpg,jpeg,svg|max:5120',
        'selectedUploadedLogos' => 'array',
        'selectedUploadedLogos.*' => 'exists:uploaded_logos,id',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'selectedColorScheme.required' => 'Please select a color scheme',
        'selectedLogos.required' => 'Please select at least one logo',
        'selectedLogos.min' => 'Please select at least one logo',
        'uploadedFiles.*.mimes' => 'Only PNG, JPG, and SVG files are allowed',
        'uploadedFiles.*.max' => 'File size must be less than 5MB',
        'uploadedFiles.*.dimensions' => 'Images must be at least 100x100 pixels',
        'uploadQueue.*.mimes' => 'Only PNG, JPG, and SVG files are allowed in queue',
        'uploadQueue.*.max' => 'Queued file size must be less than 5MB',
        'selectedUploadedLogos.*.exists' => 'Selected logo does not exist',
    ];

    public function mount(int $logoGenerationId): void
    {
        $this->logoGenerationId = $logoGenerationId;
        $this->loadLogoGeneration();
    }

    /**
     * Load logo generation data.
     */
    public function loadLogoGeneration(): void
    {
        $this->logoGeneration = LogoGeneration::with(['generatedLogos.colorVariants'])
            ->find($this->logoGenerationId);

        if (! $this->logoGeneration) {
            $this->errorMessage = 'Logo generation not found.';
        }
    }

    /**
     * Refresh generation status.
     */
    #[On('refresh-logo-status')]
    public function refreshStatus(): void
    {
        $this->loadLogoGeneration();

        if ($this->logoGeneration && $this->logoGeneration->status === 'completed') {
            $this->dispatch('toast', message: 'Logo generation completed!', type: 'success');
        }
    }

    /**
     * Apply color scheme to selected logos.
     */
    public function applyColorScheme(): void
    {
        $this->validate();

        if (! $this->logoGeneration) {
            $this->dispatch('toast', message: 'Logo generation not found', type: 'error');

            return;
        }

        $this->isCustomizingColors = true;
        $this->errorMessage = null;
        $successCount = 0;

        try {
            $colorPaletteService = app(ColorPaletteService::class);
            $svgProcessor = app(SvgColorProcessor::class);

            // Validate color scheme exists
            if (! $colorPaletteService->colorSchemeExists($this->selectedColorScheme)) {
                $this->addError('selectedColorScheme', 'Invalid color scheme selected');

                return;
            }

            $colorPalette = $colorPaletteService->getColorPalette(ColorScheme::from($this->selectedColorScheme));

            foreach ($this->selectedLogos as $logoId) {
                $logo = GeneratedLogo::find($logoId);

                if (! $logo || $logo->logo_generation_id !== $this->logoGenerationId) {
                    continue;
                }

                // Check if this color variant already exists
                $existingVariant = $logo->colorVariants()
                    ->where('color_scheme', $this->selectedColorScheme)
                    ->first();

                if ($existingVariant) {
                    continue; // Skip if already exists
                }

                // Read original SVG content
                if (! Storage::disk('public')->exists($logo->original_file_path)) {
                    Log::warning('Original logo file not found', ['path' => $logo->original_file_path]);

                    continue;
                }

                $originalSvg = Storage::disk('public')->get($logo->original_file_path);

                if (empty($originalSvg)) {
                    Log::warning('Empty SVG content', ['path' => $logo->original_file_path]);

                    continue;
                }

                // Process SVG with new colors
                $result = $svgProcessor->processSvg($originalSvg, $colorPalette);

                if (! $result['success']) {
                    Log::warning('SVG processing failed', [
                        'logo_id' => $logo->id,
                        'errors' => $result['errors'] ?? [],
                    ]);

                    continue;
                }

                // Generate file path for customized version
                $fileName = pathinfo((string) $logo->original_file_path, PATHINFO_FILENAME);
                $customizedPath = "logos/{$this->logoGenerationId}/customized/{$fileName}-{$this->selectedColorScheme}.svg";

                // Save customized SVG
                Storage::disk('public')->put($customizedPath, $result['svg']);

                // Create color variant record
                LogoColorVariant::create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $this->selectedColorScheme,
                    'file_path' => $customizedPath,
                ]);

                $successCount++;
            }

            $this->successMessage = "{$successCount} logos customized successfully";
            $this->dispatch('toast', message: $this->successMessage, type: 'success');

            // Reset selections
            $this->selectedLogos = [];
            $this->selectedColorScheme = '';
            $this->showColorPicker = false;

            // Reload data to show new variants
            $this->loadLogoGeneration();

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to customize logos: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
            Log::error('Logo customization failed', [
                'logo_generation_id' => $this->logoGenerationId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isCustomizingColors = false;
        }
    }

    /**
     * Download individual logo.
     */
    public function downloadLogo(int $logoId, string $format = 'svg', ?string $colorScheme = null): void
    {
        $logo = GeneratedLogo::find($logoId);

        if (! $logo || $logo->logo_generation_id !== $this->logoGenerationId) {
            $this->dispatch('toast', message: 'Logo not found', type: 'error');

            return;
        }

        $downloadUrl = route('api.logos.download', [
            'logoGeneration' => $this->logoGenerationId,
            'generatedLogo' => $logoId,
        ]);

        if ($colorScheme) {
            $downloadUrl .= "?color_scheme={$colorScheme}&format={$format}";
        } else {
            $downloadUrl .= "?format={$format}";
        }

        $this->dispatch('download-file', url: $downloadUrl);
    }

    /**
     * Download all logos as ZIP.
     */
    public function downloadBatch(?string $colorScheme = null): void
    {
        $logoGeneration = $this->getLogoGenerationProperty();
        if (! $logoGeneration || $logoGeneration->generatedLogos->isEmpty()) {
            $this->dispatch('toast', message: 'No logos available for download', type: 'error');

            return;
        }

        $downloadUrl = route('api.logos.download-batch', $this->logoGenerationId);
        if ($colorScheme) {
            $downloadUrl .= "?color_scheme={$colorScheme}";
        }

        $this->dispatch('download-file', url: $downloadUrl);
        $this->dispatch('toast', message: 'Download started!', type: 'success');
    }

    /**
     * Toggle logo selection for customization.
     */
    public function toggleLogoSelection(int $logoId): void
    {
        if (in_array($logoId, $this->selectedLogos)) {
            $this->selectedLogos = array_values(array_diff($this->selectedLogos, [$logoId]));
        } else {
            $this->selectedLogos[] = $logoId;
        }
    }

    /**
     * Clear logo selection.
     */
    public function clearSelection(): void
    {
        $this->selectedLogos = [];
        $this->selectedColorScheme = '';
        $this->showColorPicker = false;
    }

    /**
     * Toggle view mode between grid and list.
     */
    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    /**
     * Upload logo files.
     */
    public function uploadLogos(): void
    {
        $this->isUploading = true;
        $this->uploadProgress = 0;
        $this->errorMessage = null;

        // Validate uploaded files
        try {
            $this->validate([
                'uploadedFiles' => 'required|array|min:1',
                'uploadedFiles.*' => 'required|file|mimes:png,jpg,jpeg,svg|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: 'Invalid files detected', type: 'error');
            throw $e;
        }

        // Check for duplicates before starting upload
        $this->checkForDuplicates();

        // Generate file previews
        $this->generatePreviewThumbnails();

        try {

            $uploadedCount = 0;
            $totalFiles = count($this->uploadedFiles);

            foreach ($this->uploadedFiles as $index => $file) {
                // Validate image dimensions for raster images (skip SVG)
                if (in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $imageInfo = getimagesize($file->getPathname());
                    if ($imageInfo !== false && ($imageInfo[0] < 100 || $imageInfo[1] < 100)) {
                        $this->addError("uploadedFiles.{$index}", 'Images must be at least 100x100 pixels');

                        continue;
                    }
                    // If getimagesize fails, we'll skip dimension validation
                    // This can happen with fake files or corrupted images
                }

                // Generate unique file path
                $sessionId = session()->getId();
                $fileName = uniqid().'_'.time().'.'.$file->getClientOriginalExtension();
                $filePath = "logos/uploaded/{$sessionId}/{$fileName}";

                // Store the file
                $storedPath = $file->storeAs("logos/uploaded/{$sessionId}", $fileName, 'public');

                if ($storedPath) {
                    // Get image dimensions (only for raster images, not SVG)
                    $width = null;
                    $height = null;
                    if (in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg'])) {
                        $imageInfo = getimagesize($file->getPathname());
                        if ($imageInfo) {
                            $width = $imageInfo[0];
                            $height = $imageInfo[1];
                        }
                    }

                    // Create database record
                    UploadedLogo::create([
                        'session_id' => $sessionId,
                        'user_id' => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $storedPath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'image_width' => $width,
                        'image_height' => $height,
                    ]);

                    $uploadedCount++;
                }

                // Update progress
                $this->uploadProgress = (int) round((($index + 1) / $totalFiles) * 100);
            }

            if ($uploadedCount > 0) {
                $this->dispatch('toast',
                    message: "{$uploadedCount} logo(s) uploaded successfully!",
                    type: 'success'
                );

                // Clear uploaded files
                $this->uploadedFiles = [];
            } else {
                $this->dispatch('toast',
                    message: 'No files were uploaded. Please check file requirements.',
                    type: 'error'
                );
            }

        } catch (Exception $e) {
            $this->errorMessage = 'Upload failed: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
            Log::error('Logo upload failed', [
                'error' => $e->getMessage(),
                'session_id' => session()->getId(),
            ]);
        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 100;
        }
    }

    /**
     * Delete an uploaded logo.
     */
    public function deleteUploadedLogo(int $uploadedLogoId): void
    {
        try {
            $uploadedLogo = UploadedLogo::where('id', $uploadedLogoId)
                ->where('session_id', session()->getId())
                ->first();

            if (! $uploadedLogo) {
                $this->dispatch('toast', message: 'Logo not found', type: 'error');

                return;
            }

            // Delete file from storage
            if ($uploadedLogo->file_path && Storage::disk('public')->exists($uploadedLogo->file_path)) {
                Storage::disk('public')->delete($uploadedLogo->file_path);
            }

            // Delete database record
            $uploadedLogo->delete();

            $this->dispatch('toast', message: 'Logo deleted successfully', type: 'success');

        } catch (Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete logo', type: 'error');
            Log::error('Failed to delete uploaded logo', [
                'logo_id' => $uploadedLogoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Download an uploaded logo.
     */
    public function downloadUploadedLogo(int $uploadedLogoId): void
    {
        $uploadedLogo = UploadedLogo::where('id', $uploadedLogoId)
            ->where('session_id', session()->getId())
            ->first();

        if (! $uploadedLogo) {
            $this->dispatch('toast', message: 'Logo not found', type: 'error');

            return;
        }

        $downloadUrl = route('api.uploaded-logos.download', $uploadedLogo->id);
        $this->dispatch('download-file', url: $downloadUrl);
    }

    /**
     * Handle drag enter event.
     */
    public function dragEnter(): void
    {
        $this->isDraggedOver = true;
    }

    /**
     * Handle drag leave event.
     */
    public function dragLeave(): void
    {
        $this->isDraggedOver = false;
    }

    /**
     * Enhanced drag and drop methods.
     */
    public function dragEnterZone(): void
    {
        $this->isDraggedOver = true;
        $this->dragFeedback = 'Drop files to upload';
    }

    public function dragLeaveZone(): void
    {
        $this->isDraggedOver = false;
        $this->dragFeedback = '';
        $this->dragFileCount = 0;
    }

    public function dragOverWithFiles(int $fileCount): void
    {
        $this->isDraggedOver = true;
        $this->dragFileCount = $fileCount;
        $this->dragFeedback = "Drop {$fileCount} files to upload";
    }

    /**
     * Generate preview thumbnails for uploaded files.
     */
    public function generatePreviewThumbnails(): void
    {
        $this->filePreviews = [];

        foreach ($this->uploadedFiles as $index => $file) {
            $this->filePreviews[$index] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ];
        }
    }

    /**
     * Check for duplicate files before upload.
     */
    public function checkForDuplicates(): void
    {
        $this->duplicateWarnings = [];
        $sessionId = session()->getId();

        foreach ($this->uploadedFiles as $index => $file) {
            $existingLogo = UploadedLogo::where('session_id', $sessionId)
                ->where('original_name', $file->getClientOriginalName())
                ->first();

            if ($existingLogo) {
                $this->duplicateWarnings[$index] = [
                    'message' => 'File "'.$file->getClientOriginalName().'" already exists',
                    'action' => 'replace', // 'skip' or 'replace'
                ];
            }
        }
    }

    /**
     * Process batch upload with queue management.
     */
    public function processBatchUpload(): void
    {
        $this->validate([
            'uploadQueue' => 'required|array|min:1',
            'uploadQueue.*' => 'required|file|mimes:png,jpg,jpeg,svg|max:5120',
        ]);

        $this->uploadedFiles = $this->uploadQueue;
        $this->uploadLogos();
        $this->uploadQueue = [];
    }

    /**
     * Start batch upload with individual file progress tracking.
     */
    public function startBatchUpload(): void
    {
        $this->fileProgress = [];

        foreach ($this->uploadQueue as $index => $file) {
            $this->fileProgress[$index] = 0;
        }

        $this->processBatchUpload();
    }

    /**
     * Bulk delete uploaded logos.
     */
    public function bulkDeleteUploadedLogos(): void
    {
        if (empty($this->selectedUploadedLogos)) {
            $this->dispatch('toast', message: 'No logos selected', type: 'error');

            return;
        }

        $sessionId = session()->getId();
        $deletedCount = 0;

        foreach ($this->selectedUploadedLogos as $logoId) {
            $uploadedLogo = UploadedLogo::where('id', $logoId)
                ->where('session_id', $sessionId)
                ->first();

            if ($uploadedLogo) {
                // Delete file from storage
                if (Storage::disk('public')->exists($uploadedLogo->file_path)) {
                    Storage::disk('public')->delete($uploadedLogo->file_path);
                }

                // Delete database record
                $uploadedLogo->delete();
                $deletedCount++;
            }
        }

        $this->selectedUploadedLogos = [];
        $this->showBulkActions = false;

        $this->dispatch('toast',
            message: "{$deletedCount} logos deleted successfully",
            type: 'success'
        );
    }

    /**
     * Toggle uploaded logo selection for bulk operations.
     */
    public function toggleUploadedLogoSelection(int $logoId): void
    {
        if (in_array($logoId, $this->selectedUploadedLogos)) {
            $this->selectedUploadedLogos = array_diff($this->selectedUploadedLogos, [$logoId]);
        } else {
            $this->selectedUploadedLogos[] = $logoId;
        }

        $this->showBulkActions = ! empty($this->selectedUploadedLogos);
    }

    /**
     * Select all uploaded logos.
     */
    public function selectAllUploadedLogos(): void
    {
        $uploadedLogos = $this->getUploadedLogosProperty();
        $this->selectedUploadedLogos = $uploadedLogos->pluck('id')->toArray();
        $this->showBulkActions = true;
    }

    /**
     * Clear all logo selections.
     */
    public function clearLogoSelection(): void
    {
        $this->selectedUploadedLogos = [];
        $this->showBulkActions = false;
    }

    /**
     * Get available color schemes.
     */
    /**
     * @return array<string, mixed>
     */
    public function getColorSchemesProperty(): array
    {
        return app(ColorPaletteService::class)->getAllColorSchemesWithMetadata();
    }

    /**
     * Get logos grouped by style.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLogosByStyleProperty(): array
    {
        if (! $this->logoGeneration) {
            return [];
        }

        return $this->logoGeneration->generatedLogos
            ->groupBy('style')
            ->map(fn ($logos, $style) => [
                'style' => $style,
                'display_name' => ucfirst((string) $style),
                'logos' => $logos,
            ])->values()->toArray();
    }

    /**
     * Get filtered logos grouped by style.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFilteredLogosByStyleProperty(): array
    {
        if (! $this->logoGeneration) {
            return [];
        }

        $filteredLogos = $this->getFilteredGeneratedLogosProperty();

        return $filteredLogos
            ->groupBy('style')
            ->map(fn ($logos, $style) => [
                'style' => $style,
                'display_name' => ucfirst((string) $style),
                'logos' => $logos,
            ])->values()->toArray();
    }

    /**
     * Get the logo generation model.
     */
    public function getLogoGenerationProperty(): ?LogoGeneration
    {
        if (! $this->logoGeneration && $this->logoGenerationId) {
            $this->loadLogoGeneration();
        }

        return $this->logoGeneration;
    }

    /**
     * Get generation progress data.
     */
    /**
     * @return array<string, int>
     */
    public function getProgressProperty(): array
    {
        if (! $this->logoGeneration) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }

        $total = $this->logoGeneration->total_logos_requested;
        $completed = $this->logoGeneration->logos_completed;

        return [
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    /**
     * Get uploaded logos for the current session.
     */
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UploadedLogo>
     */
    public function getUploadedLogosProperty()
    {
        return UploadedLogo::forSession(session()->getId())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get filtered generated logos.
     */
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, GeneratedLogo>
     */
    public function getFilteredGeneratedLogosProperty()
    {
        if (! $this->logoGeneration) {
            return collect();
        }

        $query = $this->logoGeneration->generatedLogos();

        // Filter by style if specified
        if (! empty($this->filterStyle)) {
            $query->where('style', $this->filterStyle);
        }

        $logos = $query->get();

        // Apply search filter if specified
        if (! empty($this->searchTerm)) {
            $logos = $logos->filter(fn ($logo) => str_contains(strtolower($logo->style ?? ''), strtolower($this->searchTerm)) ||
                   str_contains(strtolower($logo->prompt_used ?? ''), strtolower($this->searchTerm)));
        }

        return $logos;
    }

    /**
     * Get filtered uploaded logos.
     */
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UploadedLogo>
     */
    public function getFilteredUploadedLogosProperty()
    {
        $logos = $this->getUploadedLogosProperty();

        // Apply search filter if specified
        if (! empty($this->searchTerm)) {
            $logos = $logos->filter(fn ($logo) => str_contains(strtolower((string) $logo->original_name), strtolower($this->searchTerm)) ||
                   str_contains(strtolower((string) $logo->description), strtolower($this->searchTerm)));
        }

        return $logos;
    }

    /**
     * Get all available logo styles for filtering.
     */
    /**
     * @return array<string>
     */
    public function getAvailableStylesProperty(): array
    {
        if (! $this->logoGeneration) {
            return [];
        }

        return $this->logoGeneration->generatedLogos()
            ->distinct('style')
            ->pluck('style')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Clear all filters and search.
     */
    public function clearFilters(): void
    {
        $this->searchTerm = '';
        $this->filterType = 'all';
        $this->filterStyle = '';
    }

    /**
     * Show logo detail modal.
     */
    public function showLogoDetail(int $logoId, string $type): void
    {
        $this->detailLogoId = $logoId;
        $this->detailLogoType = $type;
        $this->showDetailModal = true;
    }

    /**
     * Hide logo detail modal.
     */
    public function hideLogoDetail(): void
    {
        $this->showDetailModal = false;
        $this->detailLogoId = null;
        $this->detailLogoType = '';
    }

    /**
     * Get the current logo being viewed in detail.
     */
    public function getDetailLogoProperty(): \App\Models\GeneratedLogo|\App\Models\UploadedLogo|null
    {
        if (! $this->detailLogoId || ! $this->detailLogoType) {
            return null;
        }

        if ($this->detailLogoType === 'generated') {
            return GeneratedLogo::find($this->detailLogoId);
        } elseif ($this->detailLogoType === 'uploaded') {
            return UploadedLogo::find($this->detailLogoId);
        }

        return null;
    }

    public function toggleSelectAll(): void
    {
        // Handle generated logos selection
        if ($this->logoGeneration->status === 'completed' && $this->logoGeneration->generatedLogos->isNotEmpty()) {
            if (count($this->selectedLogos) === $this->logoGeneration->generatedLogos->count()) {
                $this->selectedLogos = [];
            } else {
                $this->selectedLogos = $this->logoGeneration->generatedLogos->pluck('id')->toArray();
            }
        }

        // Handle uploaded logos selection
        $filteredUploadedLogos = $this->getFilteredUploadedLogosProperty();
        if ($filteredUploadedLogos->isNotEmpty()) {
            if (count($this->selectedUploadedLogos) === $filteredUploadedLogos->count()) {
                $this->selectedUploadedLogos = [];
            } else {
                $this->selectedUploadedLogos = $filteredUploadedLogos->pluck('id')->toArray();
            }
        }
    }

    public function render(): View
    {
        return view('livewire.logo-gallery');
    }
}
