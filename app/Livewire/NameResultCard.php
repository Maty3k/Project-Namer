<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NameSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    public NameSuggestion $suggestion;

    public bool $expanded = false;

    public bool $isCheckingDomains = false;

    /**
     * Mount the component with a name suggestion.
     */
    public function mount(NameSuggestion $suggestion): void
    {
        $this->suggestion = $suggestion;
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
    /**
     * Get fresh domain data directly from database.
     * This method ensures we always have the latest domain data
     * without mutating the $suggestion model property.
     */
    protected function getFreshDomains(): ?array
    {
        $fresh = NameSuggestion::find($this->suggestion->id);

        return $fresh?->domains;
    }

    /**
     * Computed property wrapper for blade templates.
     */
    #[Computed]
    public function freshDomains(): ?array
    {
        return $this->getFreshDomains();
    }

    #[On('check-domains-{suggestion.id}')]
    public function checkDomains(): void
    {
        $this->authorize('update', $this->suggestion->project);

        // Check if domains have already been checked
        if ($this->domainsAlreadyChecked()) {
            return;
        }

        $checkedDomains = [];

        // Check each domain synchronously for instant results
        foreach ($this->suggestion->domains as $domainName => $domainData) {
            // Use synchronous dispatch for instant results (no queue worker needed)
            \App\Jobs\CheckDomainDNSJob::dispatchSync($domainName);

            // Get the cached result immediately after sync processing
            $cached = \App\Models\DomainCache::findByDomain($domainName);

            if ($cached) {
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => $cached->available,
                    'status' => $cached->available ? 'available' : 'taken',
                    'has_dns_records' => $cached->has_dns_records,
                    'check_method' => $cached->check_method,
                    'dns_records' => $cached->dns_records,
                ];
            } else {
                // Fallback if caching failed
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => null,
                    'status' => 'error',
                    'has_dns_records' => null,
                    'check_method' => null,
                ];
            }
        }

        // Update the database with checked results
        NameSuggestion::where('id', $this->suggestion->id)
            ->update(['domains' => $checkedDomains]);

        // Reload the suggestion to get fresh data
        $this->suggestion = NameSuggestion::find($this->suggestion->id);

        // Dispatch completion event for Alpine.js to hide checking indicator
        $this->dispatch('domain-check-complete', id: $this->suggestion->id);

        $this->dispatch('show-toast', [
            'message' => 'Domain availability checked!',
            'type' => 'success',
        ]);
    }

    /**
     * Refresh domain data when polling.
     * This method is called periodically while isCheckingDomains is true.
     * Checks DomainCache for updated results and syncs them to the suggestion.
     */
    public function refreshDomains(): void
    {
        // Get fresh domain data from database
        $domains = $this->getFreshDomains();

        if (! $domains) {
            return;
        }

        $updated = false;

        // Check each domain in DomainCache for updated results
        foreach ($domains as $domainName => $domainData) {
            // Skip if already checked
            if (($domainData['status'] ?? 'checking') !== 'checking') {
                continue;
            }

            // Look for cached result
            $cachedDomain = \App\Models\DomainCache::findByDomain($domainName);

            if ($cachedDomain && ! $cachedDomain->isExpired()) {
                // Update the domain with cached results
                $domains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => $cachedDomain->available,
                    'status' => $cachedDomain->available ? 'available' : 'taken',
                    'has_dns_records' => $cachedDomain->has_dns_records,
                    'check_method' => $cachedDomain->check_method,
                    'dns_records' => $cachedDomain->dns_records,
                ];
                $updated = true;
            }
        }

        // Update database if any domains were updated
        if ($updated) {
            NameSuggestion::where('id', $this->suggestion->id)
                ->update(['domains' => $domains]);
        }

        // Check if all domains are done and update polling status
        if ($this->allDomainsChecked($domains)) {
            $this->isCheckingDomains = false;
            $this->dispatch('domain-check-complete', id: $this->suggestion->id);
        }
    }

    /**
     * Check if all domains have completed checking.
     */
    protected function allDomainsChecked(array $domains): bool
    {
        foreach ($domains as $domainData) {
            if (($domainData['status'] ?? 'checking') === 'checking') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if domains have already been checked.
     */
    protected function domainsAlreadyChecked(): bool
    {
        $domains = $this->getFreshDomains();

        if (! $domains || empty($domains)) {
            return false;
        }

        // Check if at least one domain has availability info (not null and not 'pending' or 'checking')
        foreach ($domains as $domainData) {
            if (isset($domainData['available']) && $domainData['available'] !== null) {
                return true;
            }
            if (isset($domainData['status']) && ! in_array($domainData['status'], ['pending', 'unknown', 'checking'], true)) {
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
        $domains = $this->getFreshDomains();

        if (! $domains) {
            return 0;
        }

        return collect($domains)
            ->where('available', true)
            ->count();
    }

    /**
     * Get the total count of domains checked.
     */
    public function getTotalDomainsCountProperty(): int
    {
        $domains = $this->getFreshDomains();

        if (! $domains) {
            return 0;
        }

        return count($domains);
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
        $domains = $this->getFreshDomains();

        return $domains !== null && ! empty($domains);
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

        $domains = collect($this->getFreshDomains());

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
