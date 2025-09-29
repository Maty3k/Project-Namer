<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\CheckDomainDnsJob;
use App\Models\NameSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;
use Livewire\Attributes\On;
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

    public ?NameSuggestion $suggestion = null;

    public bool $expanded = false;

    public ?int $suggestionId = null;

    public bool $dnsCheckLoading = false;

    public bool $dnsFilteringEnabled = false;

    public ?string $dnsError = null;

    /**
     * Mount the component with a name suggestion.
     */
    public function mount(NameSuggestion $suggestion, bool $dnsFilteringEnabled = false): void
    {
        $this->suggestion = $suggestion;
        $this->suggestionId = $suggestion->id;
        $this->dnsFilteringEnabled = $dnsFilteringEnabled;
    }

    /**
     * Toggle the expanded state of the card.
     * Note: Now handled purely by Alpine.js for smooth animations without server roundtrips.
     */
    public function toggleExpanded(): void
    {
        // This method is kept for backwards compatibility but not used anymore
        // All expansion/collapse is handled client-side by Alpine.js
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
        $this->dispatch('show-toast', [
            'message' => "Hidden '{$this->suggestion->name}' from results.",
            'type' => 'info',
        ]);
    }

    /**
     * Show this suggestion in the results.
     */
    public function showSuggestion(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->update(['is_hidden' => false]);

        $this->dispatch('suggestion-shown', $this->suggestion->id);
        $this->dispatch('show-toast', [
            'message' => "Restored '{$this->suggestion->name}' to visible results.",
            'type' => 'success',
        ]);
    }

    /**
     * Select this name suggestion for the project.
     */
    public function selectName(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->project->update(['selected_name_id' => $this->suggestion->id]);

        $this->dispatch('name-selected', $this->suggestion->id);
        $this->dispatch('show-toast', [
            'message' => "Selected '{$this->suggestion->name}' as your project name!",
            'type' => 'success',
        ]);
    }

    /**
     * Deselect the currently selected name.
     */
    public function deselectName(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->project->update(['selected_name_id' => null]);

        $this->dispatch('name-deselected', $this->suggestion->id);
        $this->dispatch('show-toast', [
            'message' => "Deselected '{$this->suggestion->name}'. You can select another name anytime.",
            'type' => 'info',
        ]);
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
     * Trigger a manual DNS check for this suggestion.
     */
    public function triggerDnsCheck(): void
    {
        try {
            $this->authorize('update', $this->suggestion->project);

            if ($this->suggestion->isDnsChecked()) {
                return; // Already checked
            }

            $this->dnsCheckLoading = true;
            $this->dnsError = null;

            // Queue the DNS check job
            Queue::push(new CheckDomainDnsJob($this->suggestion->id));

            // Dispatch event to notify parent components
            $this->dispatch('dns-check-triggered', ['suggestionId' => $this->suggestion->id]);

            $this->dispatch('show-toast', [
                'message' => "DNS check started for '{$this->suggestion->name}'",
                'type' => 'info',
            ]);

        } catch (\Exception $e) {
            $this->dnsCheckLoading = false;
            $this->dnsError = 'Failed to start DNS check. Please try again.';

            $this->dispatch('show-toast', [
                'message' => 'Failed to start DNS check. Please try again.',
                'type' => 'error',
            ]);

            \Log::error('DNS check trigger failed', [
                'suggestion_id' => $this->suggestion->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Refresh the suggestion data from the database.
     */
    public function refreshSuggestion(): void
    {
        if ($this->suggestionId) {
            $this->suggestion = NameSuggestion::find($this->suggestionId);
            $this->dnsCheckLoading = false;
            $this->dnsError = null;
        }
    }

    /**
     * Handle DNS check in progress event.
     */
    #[On('dns-check-in-progress')]
    public function handleDnsCheckInProgress(array $data): void
    {
        $triggeredSuggestionId = $data['suggestionId'] ?? null;

        if ($triggeredSuggestionId && $triggeredSuggestionId === $this->suggestionId) {
            $this->dnsCheckLoading = true;
        }
    }

    /**
     * Handle suggestion DNS update event.
     */
    #[On('suggestion-dns-updated')]
    public function handleSuggestionDnsUpdated(array $data): void
    {
        $updatedSuggestionId = $data['suggestionId'] ?? null;

        if ($updatedSuggestionId && $updatedSuggestionId === $this->suggestionId) {
            // Refresh the suggestion data to get updated DNS status
            $this->refreshSuggestion();
        }
    }

    /**
     * Handle DNS check error event.
     */
    #[On('dns-check-error')]
    public function handleDnsCheckError(array $data): void
    {
        $errorSuggestionId = $data['suggestionId'] ?? null;
        $errorMessage = $data['error'] ?? 'DNS check failed';

        if ($errorSuggestionId && $errorSuggestionId === $this->suggestionId) {
            $this->dnsCheckLoading = false;
            $this->dnsError = $errorMessage;
        }
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
     * Get DNS status information.
     */
    public function getDnsStatusProperty(): array
    {
        return $this->suggestion->getDnsStatus();
    }

    /**
     * Get count of domains filtered by DNS availability.
     */
    public function getDnsFilteredCountProperty(): int
    {
        if (! $this->suggestion->domains) {
            return 0;
        }

        return collect($this->suggestion->domains)
            ->filter(function ($domainData, $key) {
                if (is_string($key) && ! is_numeric($key)) {
                    return ($domainData['available'] ?? null) === true;
                }
                return ($domainData['available'] ?? null) === true;
            })
            ->count();
    }

    /**
     * Check if this suggestion should be hidden due to DNS filtering.
     */
    public function getShouldHideForDnsProperty(): bool
    {
        if (! $this->dnsFilteringEnabled) {
            return false;
        }

        return $this->suggestion->hasDnsRecords();
    }

    /**
     * Get DNS error status for display.
     */
    public function getDnsErrorProperty(): ?string
    {
        return $this->dnsError;
    }

    /**
     * Clear DNS error state.
     */
    public function clearDnsError(): void
    {
        $this->dnsError = null;
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

    /**
     * Livewire boot method for component initialization.
     */
    public function boot(): void
    {
        // Ensure fresh suggestion data on each request
        if ($this->suggestionId && (! $this->suggestion || $this->suggestion->id !== $this->suggestionId)) {
            $this->suggestion = NameSuggestion::find($this->suggestionId);
        }
    }

    /**
     * Handle component dehydration for state persistence.
     */
    public function dehydrate(): void
    {
        // Ensure suggestion ID is preserved
        if ($this->suggestion instanceof NameSuggestion) {
            $this->suggestionId = $this->suggestion->id;
        }
    }

    /**
     * Handle component hydration for state restoration.
     */
    public function hydrate(): void
    {
        // Restore suggestion from ID if needed
        if ($this->suggestionId && (! $this->suggestion || $this->suggestion->id !== $this->suggestionId)) {
            $this->suggestion = NameSuggestion::find($this->suggestionId);
        }
    }

    /**
     * Serialize properties for Livewire state management.
     */
    protected function serializeProperty(string $property): mixed
    {
        if ($property === 'suggestion' && $this->$property instanceof NameSuggestion) {
            return $this->$property->id;
        }

        return $this->$property;
    }

    /**
     * Hydrate properties from Livewire state.
     */
    protected function hydrateProperty(string $property, mixed $value): mixed
    {
        if ($property === 'suggestion' && is_int($value)) {
            return NameSuggestion::find($value);
        }

        return $value;
    }

    public function render(): View
    {
        return view('livewire.name-result-card');
    }
}
