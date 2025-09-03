<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Logo Gallery Index component for displaying all user logo generations.
 */
class LogoGalleryIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    /**
     * Get the user's logo generations.
     */
    public function getLogoGenerationsProperty(): mixed
    {
        $query = LogoGeneration::where('user_id', Auth::id())
            ->with(['generatedLogos'])
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q): void {
                $q->where('business_name', 'like', "%{$this->search}%")
                    ->orWhere('business_description', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate(12);
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        return view('livewire.logo-gallery-index', [
            'logoGenerations' => $this->getLogoGenerationsProperty(),
        ]);
    }
}
