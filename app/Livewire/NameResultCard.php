<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NameSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    public int $suggestionId;

    public bool $expanded = false;

    public int $domainsCheckedAt = 0;

    /**
     * Mount the component with a name suggestion.
     */
    public function mount(int $suggestionId): void
    {
        $this->suggestionId = $suggestionId;
    }

    /**
     * Get the suggestion model (always fresh from database).
     */
    #[Computed]
    public function suggestion(): NameSuggestion
    {
        return NameSuggestion::findOrFail($this->suggestionId);
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
     * Check domain availability for this name suggestion.
     *
     * Only performs checks if domains haven't been checked yet.
     * This is called when the user expands the card to view domains.
     */
    public function checkDomains(): void
    {
        $suggestion = $this->suggestion;
        $this->authorize('update', $suggestion->project);

        // Check if domains have already been checked
        if ($this->domainsAlreadyChecked()) {
            return;
        }

        $domainCheckService = app(\App\Services\DomainCheckService::class);
        $checkedDomains = [];

        // Check all domains
        foreach ($suggestion->domains as $domainName => $domainData) {
            try {
                $result = $domainCheckService->checkDomain($domainName);
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => $result['available'] ?? null,
                    'status' => $result['status'] ?? 'unknown',
                    'has_dns_records' => $result['has_dns_records'] ?? null,
                    'check_method' => $result['check_method'] ?? 'dns',
                ];
            } catch (\Exception $e) {
                // If check fails, mark as error
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => null,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Save all checked domains to database
        $suggestion->update(['domains' => $checkedDomains]);

        // CRITICAL: Force Livewire to completely refresh this component
        unset($this->suggestion);

        // Update timestamp to trigger change detection
        $this->domainsCheckedAt = time();

        // Tell parent to refresh all cards
        $this->dispatch('refresh-suggestions')->to('project-page');

        $this->dispatch('show-toast', [
            'message' => 'Domain availability checked!',
            'type' => 'success',
        ]);
    }

    /**
     * Check if domains have already been checked.
     */
    protected function domainsAlreadyChecked(): bool
    {
        if (! $this->suggestion->domains || empty($this->suggestion->domains)) {
            return false;
        }

        // Check if at least one domain has availability info (not null and not 'pending')
        foreach ($this->suggestion->domains as $domainData) {
            if (isset($domainData['available']) && $domainData['available'] !== null) {
                return true;
            }
            if (isset($domainData['status']) && ! in_array($domainData['status'], ['pending', 'unknown'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if domains have been checked (public property for blade).
     */
    public function getDomainsCheckedProperty(): bool
    {
        return $this->domainsAlreadyChecked();
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
     * Check if all domains are unavailable (none are available).
     */
    public function getAllDomainsUnavailableProperty(): bool
    {
        if (! $this->hasDomains) {
            return false;
        }

        $domains = collect($this->suggestion->domains);

        // Check if we have domain data with availability info
        $domainsWithAvailability = $domains->filter(function ($domain) {
            return isset($domain['available']);
        });

        // If no domains have availability info yet, return false
        if ($domainsWithAvailability->isEmpty()) {
            return false;
        }

        // Check if ALL domains with availability info are unavailable
        return $domainsWithAvailability->every(function ($domain) {
            return $domain['available'] === false;
        });
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
