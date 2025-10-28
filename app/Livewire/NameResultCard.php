<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NameSuggestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * NameResultCard component for displaying individual name suggestions.
 *
 * Handles name suggestion display, expansion, and selection functionality
 * with real-time updates and visual feedback.
 *
 * @property-read array<string, mixed>|null $freshDomains
 * @property-read bool $domainsChecked
 * @property-read bool $isSelected
 * @property-read int $availableDomainsCount
 * @property-read int $totalDomainsCount
 * @property-read int $logoCount
 * @property-read bool $hasDomains
 * @property-read bool $hasLogos
 * @property-read bool $allDomainsUnavailable
 * @property-read string|null $aiModel
 * @property-read int $totalGeneratedLogosCount
 * @property-read bool $hasGeneratedLogos
 */
class NameResultCard extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public NameSuggestion $suggestion;

    public bool $expanded = false;

    public bool $isCheckingDomains = false;

    public bool $isGeneratingLogos = false;

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
     * Toggle the favorite status of this name suggestion.
     */
    public function toggleFavorite(): void
    {
        $this->authorize('update', $this->suggestion->project);

        $this->suggestion->update(['is_favorited' => ! $this->suggestion->is_favorited]);

        // Refresh the suggestion to get the updated state
        $this->suggestion->refresh();

        // Dispatch event to refresh the sidebar
        $this->dispatch('project-updated');

        $this->dispatch('show-toast', [
            'message' => $this->suggestion->is_favorited
                ? "Added '{$this->suggestion->name}' to favorites!"
                : "Removed '{$this->suggestion->name}' from favorites.",
            'type' => 'success',
        ]);
    }

    /**
     * Request logo generation for this name suggestion.
     */
    public function generateLogos(): void
    {
        $this->authorize('update', $this->suggestion->project);

        // Start polling for logo updates
        $this->isGeneratingLogos = true;

        $this->dispatch('logos-requested', $this->suggestion->id);
    }

    /**
     * Listen for logo generation start event from dashboard.
     */
    #[On('logo-generation-started')]
    public function onLogoGenerationStarted(int $logoGenerationId): void
    {
        // Check if this logo generation is for our suggestion
        $logoGeneration = \App\Models\LogoGeneration::find($logoGenerationId);

        if ($logoGeneration && $logoGeneration->business_name === $this->suggestion->name) {
            $this->isGeneratingLogos = true;
        }
    }

    /**
     * Check domain availability for this name suggestion.
     *
     * Only performs checks if domains haven't been checked yet.
     * This is called when the user expands the card to view domains.
     */
    /**
     * Get fresh domain data directly from the database.
     * This bypasses the in-memory model to avoid triggering Livewire updates.
     *
     * @return array<string, mixed>|null
     */
    public function getFreshDomainsProperty(): ?array
    {
        // Reload from database to get latest domains without triggering Livewire updates
        $fresh = NameSuggestion::where('id', $this->suggestion->id)->first();

        return $fresh?->domains;
    }

    #[On('check-domains-{suggestion.id}')]
    public function checkDomains(): void
    {
        $this->authorize('update', $this->suggestion->project);

        // Check if domains have already been checked
        if ($this->domainsAlreadyChecked()) {
            return;
        }

        // Get domains from fresh database data to avoid triggering Livewire updates
        $domains = $this->freshDomains;
        if (! is_array($domains)) {
            return;
        }

        // OPTIMIZATION: Extract domain names for batch processing
        $domainNames = array_keys($domains);

        // OPTIMIZATION: Use DomainCheckService for batch processing with cache
        $domainCheckService = app(\App\Services\DomainCheckService::class);
        $checkResults = $domainCheckService->checkMultipleDomains($domainNames);

        // Build checked domains array with results
        $checkedDomains = [];
        foreach ($domains as $domainName => $domainData) {
            if (! is_array($domainData)) {
                continue;
            }

            $result = $checkResults[$domainName] ?? null;

            if ($result) {
                $checkedDomains[$domainName] = [
                    'extension' => $domainData['extension'] ?? '',
                    'available' => $result['available'],
                    'status' => $result['status'] ?? ($result['available'] ? 'available' : 'taken'),
                    'has_dns_records' => $result['has_dns_records'] ?? null,
                    'check_method' => $result['check_method'] ?? null,
                    'dns_records' => $result['dns_records'] ?? null,
                ];
            } else {
                // Fallback if check failed
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

        // DO NOT update $this->suggestion->domains - it triggers Livewire DOM morphing
        // Alpine.js will handle ALL UI updates via the domains-updated event

        // Dispatch browser event to Alpine.js with domain data
        // Alpine will update the UI without Livewire touching the DOM
        $this->dispatch('domains-updated', [
            'suggestionId' => $this->suggestion->id,
            'domains' => $checkedDomains,
        ]);

        // Dispatch completion event for tests and other listeners
        $this->dispatch('domain-check-complete', id: $this->suggestion->id);
    }

    /**
     * Refresh domain data when polling.
     * This method is called periodically while isCheckingDomains is true.
     * Checks DomainCache for updated results and syncs them to the suggestion.
     */
    public function refreshDomains(): void
    {
        // Get fresh domain data from database
        $domains = $this->freshDomains;

        if (! $domains) {
            return;
        }

        $updated = false;

        // Check each domain in DomainCache for updated results
        foreach ($domains as $domainName => $domainData) {
            if (! is_array($domainData)) {
                continue;
            }

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

            // DO NOT update $this->suggestion->domains - it triggers Livewire DOM morphing
        }

        // Check if all domains are done and update polling status
        if ($this->allDomainsChecked($domains)) {
            $this->isCheckingDomains = false;
            $this->dispatch('domain-check-complete', id: $this->suggestion->id);
        }
    }

    /**
     * Check if all domains have completed checking.
     *
     * @param  array<string, mixed>  $domains
     */
    protected function allDomainsChecked(array $domains): bool
    {
        foreach ($domains as $domainData) {
            if (! is_array($domainData)) {
                continue;
            }

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
        // Get fresh data from database to avoid triggering Livewire updates
        $domains = $this->freshDomains;

        if ($domains === null || $domains === []) {
            return false;
        }

        // Check if at least one domain has availability info (not null and not 'pending' or 'checking')
        foreach ($domains as $domainData) {
            if (! is_array($domainData)) {
                continue;
            }

            if (array_key_exists('available', $domainData) && $domainData['available'] !== null) {
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
        $domains = $this->freshDomains;

        if (! $domains) {
            return 0;
        }

        /** @var array<string, mixed> $domains */
        return collect($domains)
            ->where('available', true)
            ->count();
    }

    /**
     * Get the total count of domains checked.
     */
    public function getTotalDomainsCountProperty(): int
    {
        $domains = $this->freshDomains;

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
        $domains = $this->freshDomains;

        return $domains !== null && ! empty($domains);
    }

    /**
     * Check if logos have been generated.
     */
    public function getHasLogosProperty(): bool
    {
        // Refresh suggestion from database to get latest logos
        $fresh = NameSuggestion::find($this->suggestion->id);

        return $fresh && $fresh->logos !== null && ! empty($fresh->logos);
    }

    /**
     * Check if all domains are unavailable (none are available).
     */
    public function getAllDomainsUnavailableProperty(): bool
    {
        if (! $this->getHasDomainsProperty()) {
            return false;
        }

        /** @var array<string, mixed> $freshDomains */
        $freshDomains = $this->freshDomains;
        $domains = collect($freshDomains);

        // Check if we have domain data with availability info
        $domainsWithAvailability = $domains->filter(fn ($domain) => is_array($domain) && isset($domain['available']));

        // If no domains have availability info yet, return false
        if ($domainsWithAvailability->isEmpty()) {
            return false;
        }

        // Check if ALL domains with availability info are unavailable
        return $domainsWithAvailability->every(fn ($domain) => $domain['available'] === false);
    }

    /**
     * Get the AI model used for generation.
     */
    public function getAiModelProperty(): ?string
    {
        if (! $this->suggestion->generation_metadata) {
            return null;
        }

        $aiModel = $this->suggestion->generation_metadata['ai_model'] ?? null;

        return is_string($aiModel) ? $aiModel : null;
    }

    /**
     * Get the LogoGeneration ID associated with this name suggestion.
     */
    public function getLogoGenerationIdProperty(): ?int
    {
        if (! $this->hasLogos) {
            return null;
        }

        // Find the most recent LogoGeneration for this name
        $logoGeneration = \App\Models\LogoGeneration::where('business_name', $this->suggestion->name)
            ->where('status', 'completed')
            ->latest()
            ->first();

        return $logoGeneration?->id;
    }

    /**
     * Get the total count of logos generated for this business name.
     */
    public function getTotalGeneratedLogosCountProperty(): int
    {
        return \App\Models\LogoGeneration::where('business_name', $this->suggestion->name)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->sum('logos_completed');
    }

    /**
     * Check if logos have been generated for this business name.
     */
    public function getHasGeneratedLogosProperty(): bool
    {
        return $this->totalGeneratedLogosCount > 0;
    }

    public function render(): View
    {
        return view('livewire.name-result-card');
    }
}
