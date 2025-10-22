<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use Livewire\Component;

class LogoGenerations extends Component
{
    public bool $showDeleteModal = false;

    public ?LogoGeneration $generationToDelete = null;

    public string $search = '';

    public string $filterBy = 'newest';

    /**
     * Get all logo generations for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, LogoGeneration>
     */
    public function getLogoGenerationsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        $query = auth()->user()
            ->logoGenerations()
            ->with('generatedLogos');

        // Apply search filter
        if ($this->search) {
            $query->where('business_name', 'like', '%'.$this->search.'%');
        }

        // Apply sorting based on filter
        match ($this->filterBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'alphabetical' => $query->orderBy('business_name', 'asc'),
            'favorited' => $query->orderBy('is_saved', 'desc')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'), // newest
        };

        return $query->get();
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

        $businessName = $this->generationToDelete->business_name;
        $logos = $this->generationToDelete->generatedLogos;
        $count = $logos->count();

        // Delete all files and database records
        foreach ($logos as $logo) {
            $logo->deleteFile();
            $logo->delete();
        }

        // Delete the logo generation record itself
        $this->generationToDelete->delete();

        $this->dispatch('show-toast', [
            'message' => "All {$count} logos for \"{$businessName}\" deleted successfully",
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
