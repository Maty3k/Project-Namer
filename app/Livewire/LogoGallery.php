<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @property \Illuminate\Database\Eloquent\Collection<int, GeneratedLogo> $logos
 */
class LogoGallery extends Component
{
    public LogoGeneration $logoGeneration;

    public bool $showDeleteModal = false;

    public ?GeneratedLogo $logoToDelete = null;

    public bool $showDeleteAllModal = false;

    public bool $showPreviewModal = false;

    public ?GeneratedLogo $logoToPreview = null;

    /**
     * Mount the component with a logo generation.
     */
    public function mount(LogoGeneration $logoGeneration): void
    {
        // Authorize that the user can view this logo generation
        Gate::authorize('view', $logoGeneration);

        $this->logoGeneration = $logoGeneration;
    }

    /**
     * Get the logos for this generation.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GeneratedLogo>
     */
    public function getLogosProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->logoGeneration->generatedLogos()->oldest()
            ->get();
    }

    /**
     * Download a specific logo.
     */
    public function downloadLogo(int $logoId): StreamedResponse
    {
        $logo = GeneratedLogo::findOrFail($logoId);

        // Verify this logo belongs to the current generation
        if ($logo->logo_generation_id !== $this->logoGeneration->id) {
            throw new HttpException(403, 'Forbidden');
        }

        $filePath = $logo->file_path;

        if (! Storage::disk('public')->exists($filePath)) {
            $this->dispatch('show-toast', [
                'message' => 'Logo file not found',
                'type' => 'error',
            ]);
            abort(404);
        }

        $filename = basename((string) $filePath);

        return Storage::disk('public')->download($filePath, $filename);
    }

    /**
     * Download all logos as a zip file.
     */
    public function downloadAll(): StreamedResponse|BinaryFileResponse
    {
        $logos = $this->logos;

        if ($logos->isEmpty()) {
            $this->dispatch('show-toast', [
                'message' => 'No logos to download',
                'type' => 'error',
            ]);

            return response()->stream(function (): void {}, 200);
        }

        $zipFileName = "logos-{$this->logoGeneration->business_name}-".now()->format('Y-m-d').'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        // Create temp directory if it doesn't exist
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($logos as $logo) {
                if ($logo->file_path && Storage::disk('public')->exists($logo->file_path)) {
                    $fileContents = Storage::disk('public')->get($logo->file_path);
                    $zip->addFromString(basename((string) $logo->file_path), $fileContents);
                }
            }
            $zip->close();
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $logoId): void
    {
        $this->logoToDelete = GeneratedLogo::findOrFail($logoId);

        // Verify this logo belongs to the current generation
        if ($this->logoToDelete->logo_generation_id !== $this->logoGeneration->id) {
            throw new HttpException(403, 'Forbidden');
        }

        $this->showDeleteModal = true;
    }

    /**
     * Cancel delete operation.
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->logoToDelete = null;
    }

    /**
     * Open preview modal for a specific logo.
     */
    public function previewLogo(int $logoId): void
    {
        $this->logoToPreview = GeneratedLogo::findOrFail($logoId);

        // Verify this logo belongs to the current generation
        if ($this->logoToPreview->logo_generation_id !== $this->logoGeneration->id) {
            throw new HttpException(403, 'Forbidden');
        }

        $this->showPreviewModal = true;
    }

    /**
     * Close preview modal.
     */
    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->logoToPreview = null;
    }

    /**
     * Toggle saved status for this logo generation.
     */
    public function toggleSaved(): void
    {
        $this->logoGeneration->toggleSaved();

        $this->dispatch('show-toast', [
            'message' => $this->logoGeneration->is_saved
                ? 'Added to favorites successfully'
                : 'Removed from favorites',
            'type' => 'success',
        ]);
    }

    /**
     * Delete a specific logo.
     */
    public function deleteLogo(): void
    {
        if (! $this->logoToDelete) {
            return;
        }

        // Delete the file from storage
        $this->logoToDelete->deleteFile();

        // Delete the database record
        $this->logoToDelete->delete();

        // Decrement the completed logos count
        $this->logoGeneration->decrement('logos_completed');

        // Check if this was the last logo - if so, delete the parent generation
        $this->logoGeneration->refresh();
        $remainingLogos = $this->logoGeneration->generatedLogos()->count();

        if ($remainingLogos === 0) {
            $businessName = $this->logoGeneration->business_name;
            $this->logoGeneration->delete();

            $this->dispatch('show-toast', [
                'message' => "Last logo deleted. Generation \"{$businessName}\" has been removed.",
                'type' => 'success',
            ]);

            $this->showDeleteModal = false;
            $this->logoToDelete = null;

            // Redirect back to logo generations list
            return $this->redirect(route('logo.generations'), navigate: true);
        }

        $this->dispatch('show-toast', [
            'message' => 'Logo deleted successfully',
            'type' => 'success',
        ]);

        $this->showDeleteModal = false;
        $this->logoToDelete = null;

        // Refresh the component
        $this->dispatch('$refresh');
    }

    /**
     * Open delete all confirmation modal.
     */
    public function confirmDeleteAll(): void
    {
        $this->showDeleteAllModal = true;
    }

    /**
     * Cancel delete all operation.
     */
    public function cancelDeleteAll(): void
    {
        $this->showDeleteAllModal = false;
    }

    /**
     * Delete all logos for this generation.
     */
    public function deleteAll(): void
    {
        $logos = $this->logos;

        if ($logos->isEmpty()) {
            $this->dispatch('show-toast', [
                'message' => 'No logos to delete',
                'type' => 'error',
            ]);

            return;
        }

        $count = $logos->count();
        $businessName = $this->logoGeneration->business_name;

        // Delete all logo files and records
        foreach ($logos as $logo) {
            $logo->deleteFile();
            $logo->delete();
        }

        // Delete the parent logo generation record
        $this->logoGeneration->delete();

        $this->dispatch('show-toast', [
            'message' => "Successfully deleted {$count} logo".(($count !== 1) ? 's' : '')." for \"{$businessName}\"",
            'type' => 'success',
        ]);

        $this->showDeleteAllModal = false;

        // Redirect back to logo generations list
        return $this->redirect(route('logo.generations'), navigate: true);
    }

    /**
     * Render the logo gallery component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.logo-gallery', [
            'logos' => $this->getLogosProperty(),
        ])->layout('components.layouts.app');
    }
}
