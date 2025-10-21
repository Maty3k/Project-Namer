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

class LogoGallery extends Component
{
    public LogoGeneration $logoGeneration;

    public bool $showDeleteModal = false;

    public ?GeneratedLogo $logoToDelete = null;

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
     */
    public function getLogosProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->logoGeneration->generatedLogos()
            ->orderBy('created_at', 'asc')
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

        $filename = basename($filePath);

        return Storage::disk('public')->download($filePath, $filename);
    }

    /**
     * Download all logos as a zip file.
     */
    public function downloadAll(): BinaryFileResponse
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
                    $zip->addFromString(basename($logo->file_path), $fileContents);
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
     * Toggle saved status for this logo generation.
     */
    public function toggleSaved(): void
    {
        $this->logoGeneration->toggleSaved();

        $this->dispatch('show-toast', [
            'message' => $this->logoGeneration->is_saved
                ? 'Saved to gallery successfully'
                : 'Removed from saved gallery',
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
     * Render the logo gallery component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.logo-gallery', [
            'logos' => $this->getLogosProperty(),
        ])->layout('components.layouts.app');
    }
}
