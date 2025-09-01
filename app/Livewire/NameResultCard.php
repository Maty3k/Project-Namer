<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NameSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * NameResultCard component for displaying individual name suggestions.
 *
 * Handles name suggestion display, expansion, hiding/showing, and selection functionality
 * with real-time updates and visual feedback.
 */
class NameResultCard extends Component
{
    use AuthorizesRequests;

    public NameSuggestion $suggestion;

    public bool $expanded = false;

    /**
     * Mount the component with a name suggestion.
     */
    public function mount(NameSuggestion $suggestion): void
    {
        $this->suggestion = $suggestion;
    }

    /**
     * Toggle the expanded state of the card.
     */
    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /**
     * Hide this suggestion from the results.
     */
    public function hideSuggestion(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->update(['is_hidden' => true]);

        $this->dispatch('suggestion-hidden', $this->suggestion->id);
    }

    /**
     * Show this suggestion in the results.
     */
    public function showSuggestion(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->update(['is_hidden' => false]);

        $this->dispatch('suggestion-shown', $this->suggestion->id);
    }

    /**
     * Select this name suggestion for the project.
     */
    public function selectName(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->project->update(['selected_name_id' => $this->suggestion->id]);

        $this->dispatch('name-selected', $this->suggestion->id);
    }

    /**
     * Deselect the currently selected name.
     */
    public function deselectName(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->project->update(['selected_name_id' => null]);

        $this->dispatch('name-deselected', $this->suggestion->id);
    }

    /**
     * Request logo generation for this name suggestion.
     */
    public function generateLogos(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->dispatch('logos-requested', $this->suggestion->id);
    }

    /**
     * Check if this suggestion is currently selected.
     */
    public function getIsSelectedProperty(): bool
    {
        return $this->suggestion->project->selected_name_id === $this->suggestion->id;
    }

    /**
     * Get the count of available domains.
     */
    public function getAvailableDomainsCountProperty(): int
    {
        if (! $this->suggestion->domains) {
            return 0;
        }

        return collect($this->suggestion->domains)
            ->where('available', true)
            ->count();
    }

    /**
     * Get the total count of domains checked.
     */
    public function getTotalDomainsCountProperty(): int
    {
        if (! $this->suggestion->domains) {
            return 0;
        }

        return count($this->suggestion->domains);
    }

    /**
     * Get the count of generated logos.
     */
    public function getLogoCountProperty(): int
    {
        if (! $this->suggestion->logos) {
            return 0;
        }

        return count($this->suggestion->logos);
    }

    /**
     * Check if domain checking has been performed.
     */
    public function getHasDomainsProperty(): bool
    {
        return $this->suggestion->domains !== null && ! empty($this->suggestion->domains);
    }

    /**
     * Check if logos have been generated.
     */
    public function getHasLogosProperty(): bool
    {
        return $this->suggestion->logos !== null && ! empty($this->suggestion->logos);
    }

    /**
     * Get the AI model used for generation.
     */
    public function getAiModelProperty(): ?string
    {
        if (! $this->suggestion->generation_metadata) {
            return null;
        }

        return $this->suggestion->generation_metadata['ai_model'] ?? null;
    }

    public function render(): View
    {
        return view('livewire.name-result-card');
    }
}
