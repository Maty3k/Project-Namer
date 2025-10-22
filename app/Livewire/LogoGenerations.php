<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use Livewire\Component;

class LogoGenerations extends Component
{
    public bool $showDeleteModal = false;

    public ?LogoGeneration $generationToDelete = null;

    /**
     * Get all logo generations for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, LogoGeneration>
     */
    public function getLogoGenerationsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->logoGenerations()
            ->with('generatedLogos')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $generationId): void
    {
        $this->generationToDelete = LogoGeneration::findOrFail($generationId);

        // Verify this generation belongs to the current user
        if ($this->generationToDelete->user_id !== auth()->id()) {
            abort(403);
        }

        $this->showDeleteModal = true;
    }

    /**
     * Cancel delete operation.
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->generationToDelete = null;
    }

    /**
     * Delete all logos for a specific generation.
     */
    public function deleteAllLogos(): void
    {
        if (! $this->generationToDelete) {
            return;
        }

        $logos = $this->generationToDelete->generatedLogos;
        $count = $logos->count();

        // Delete all files and database records
        foreach ($logos as $logo) {
            $logo->deleteFile();
            $logo->delete();
        }

        // Reset the completed logos count to 0
        $this->generationToDelete->update(['logos_completed' => 0]);

        $this->dispatch('show-toast', [
            'message' => "All {$count} logos deleted successfully",
            'type' => 'success',
        ]);

        $this->showDeleteModal = false;
        $this->generationToDelete = null;

        // Refresh the component
        $this->dispatch('$refresh');
    }

    /**
     * Render the logo generations list.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.logo-generations', [
            'logoGenerations' => $this->getLogoGenerationsProperty(),
        ])->layout('components.layouts.app');
    }
}
