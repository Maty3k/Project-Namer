<?php

declare(strict_types=1);

use App\Services\OpenAINameService;
use App\Services\DomainCheckService;
use App\Models\LogoGeneration;
use App\Jobs\GenerateLogosJob;
use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public string $businessDescription = '';
    public string $mode = 'creative';
    public bool $deepThinking = false;
    public bool $isLoading = false;
    public bool $isCheckingDomains = false;
    public array $generatedNames = [];
    public array $domainResults = [];
    public string $errorMessage = '';
    public int $checkingProgress = 0;
    public array $searchHistory = [];
    public bool $showHistory = false;
    public ?int $lastApiCallTime = null;
    public int $rateLimitCooldown = 30; // seconds
    public string $sessionId = '';
    public bool $isGeneratingLogos = false;

    public array $modes = [
        'creative' => 'Creative',
        'professional' => 'Professional',
        'brandable' => 'Brandable',
        'tech-focused' => 'Tech-focused',
    ];

    // Table sorting and filtering properties
    public array $currentSort = ['column' => null, 'direction' => null];
    public array $activeFilters = [];
    public array $sortedDomainResults = [];
    public array $filteredDomainResults = [];
    public array $processedDomainResults = [];

    public function updatedBusinessDescription(): void
    {
        $this->errorMessage = '';
        $this->businessDescription = $this->sanitizeInput($this->businessDescription);
    }

    /**
     * Sanitize user input to prevent XSS and other security issues.
     */
    private function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Strip HTML tags and encode special characters
        $input = strip_tags($input);
        
        // Remove javascript: protocol
        $input = preg_replace('/javascript:/i', '', $input);
        
        // Remove potentially dangerous JavaScript patterns (functions with parentheses first)
        $input = preg_replace('/alert\s*\([^)]*\)/i', '', (string) $input);
        $input = preg_replace('/eval\s*\([^)]*\)/i', '', (string) $input);
        
        // Then remove any remaining dangerous function names
        $input = preg_replace('/\b(alert|eval|document|window|console|setTimeout|setInterval)\b/i', '', (string) $input);
        
        // Remove any remaining potentially dangerous patterns
        $input = preg_replace('/on\w+\s*=/i', '', (string) $input);
        
        // Remove data: URIs which could contain JavaScript
        $input = preg_replace('/data:\s*[^;]*;base64/i', '', (string) $input);
        
        // Remove potential PII patterns for privacy protection
        $input = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[email]', (string) $input);
        $input = preg_replace('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', '[phone]', (string) $input);
        $input = preg_replace('/\b\d{3}[-.]?\d{2}[-.]?\d{4}\b/', '[ssn]', (string) $input);
        
        return trim((string) $input);
    }

    public function updatedMode(): void
    {
        $this->generatedNames = [];
        $this->domainResults = [];
    }

    public function generateNames(): void
    {
        // Check rate limiting
        if ($this->isRateLimited()) {
            $remainingTime = $this->getRemainingCooldownTime();
            $this->errorMessage = "Please wait {$remainingTime} seconds before generating more names.";
            return;
        }

        $this->validate([
            'businessDescription' => 'required|string|max:2000',
            'mode' => 'required|in:creative,professional,brandable,tech-focused',
        ]);

        $this->isLoading = true;
        $this->errorMessage = '';
        $this->generatedNames = [];
        $this->domainResults = [];
        $this->lastApiCallTime = time();

        try {
            $service = app(OpenAINameService::class);
            $this->generatedNames = $service->generateNames(
                $this->businessDescription,
                $this->mode,
                $this->deepThinking
            );
            
            // Initialize domain results with checking status
            $this->domainResults = array_map(fn($name) => [
                'name' => $name,
                'domains' => [
                    $name . '.com' => ['status' => 'checking', 'available' => null],
                    $name . '.net' => ['status' => 'checking', 'available' => null],
                    $name . '.org' => ['status' => 'checking', 'available' => null],
                ]
            ], $this->generatedNames);

            // Save to search history
            $this->saveToHistory();

            // Automatically start domain checking
            $this->checkDomains();
        } catch (\Exception $e) {
            $this->errorMessage = $this->getErrorMessage($e);
        } finally {
            $this->isLoading = false;
        }
    }

    public function checkDomains(): void
    {
        if (empty($this->generatedNames)) {
            return;
        }

        $this->isCheckingDomains = true;
        $this->checkingProgress = 0;
        $retryAttempts = 0;
        $maxRetries = 3;

        try {
            $domainService = app(DomainCheckService::class);
            $totalDomains = count($this->generatedNames) * 3; // 3 domains per name (.com, .net, .org)
            $checkedCount = 0;

            foreach ($this->domainResults as $index => $result) {
                foreach ($result['domains'] as $domain => $domainData) {
                    $success = false;
                    $currentRetries = 0;

                    while (!$success && $currentRetries <= $maxRetries) {
                        try {
                            $availability = $domainService->checkDomain($domain);
                            
                            // Debug: Log what we're getting from domain service
                            Log::info("NameGenerator domain check result for {$domain}", [
                                'availability_structure' => is_array($availability) ? array_keys($availability) : gettype($availability),
                                'checked_at_type' => isset($availability['checked_at']) ? gettype($availability['checked_at']) : 'not_set',
                                'checked_at_class' => isset($availability['checked_at']) && is_object($availability['checked_at']) ? $availability['checked_at']::class : 'not_object'
                            ]);
                            
                            $this->domainResults[$index]['domains'][$domain] = [
                                'status' => 'checked',
                                'available' => $availability['available']
                            ];
                            
                            $success = true;
                        } catch (\Exception $e) {
                            $currentRetries++;
                            
                            if ($currentRetries > $maxRetries) {
                                $this->domainResults[$index]['domains'][$domain] = [
                                    'status' => 'error',
                                    'available' => null,
                                    'error' => $this->getDomainErrorMessage($e)
                                ];
                            } else {
                                // Exponential backoff: wait 1s, then 2s, then 4s
                                sleep(2 ** ($currentRetries - 1));
                            }
                        }
                    }
                    
                    $checkedCount++;
                    $this->checkingProgress = intval(($checkedCount / $totalDomains) * 100);
                    
                    // Dispatch progress update
                    $this->dispatch('domainCheckProgress', $this->checkingProgress);
                }
            }
        } catch (\Exception) {
            // Handle general domain checking errors
            foreach ($this->domainResults as $index => $result) {
                foreach ($result['domains'] as $domain => $domainData) {
                    $this->domainResults[$index]['domains'][$domain] = [
                        'status' => 'error',
                        'available' => null,
                        'error' => 'Unable to check domain availability'
                    ];
                }
            }
        } finally {
            $this->isCheckingDomains = false;
            $this->checkingProgress = 100;
        }
    }

    public function getDomainStatusIcon(string $status, ?bool $available): string
    {
        return match($status) {
            'checking' => '🔄',
            'checked' => $available ? '✅' : '❌',
            'error' => '⚠️',
            default => '❓'
        };
    }

    public function getDomainStatusText(string $status, ?bool $available): string
    {
        return match($status) {
            'checking' => 'Checking...',
            'checked' => $available ? 'Available' : 'Taken',
            'error' => 'Error checking',
            default => 'Unknown'
        };
    }

    public function getDomainStatusClass(string $status, ?bool $available): string
    {
        return match ($status) {
            'checking' => 'text-blue-600 dark:text-blue-400',
            'checked' => $available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
            'error' => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    public function mount(): void
    {
        $this->loadSearchHistory();
        $this->sessionId = session()->getId();
    }

    public function loadSearchHistory(): void
    {
        $this->dispatch('loadSearchHistory');
    }

    public function saveToHistory(): void
    {
        if (empty($this->generatedNames)) {
            return;
        }

        $historyEntry = [
            'id' => uniqid(),
            'timestamp' => now()->toISOString(),
            'businessDescription' => $this->businessDescription,
            'mode' => $this->mode,
            'deepThinking' => $this->deepThinking,
            'generatedNames' => $this->generatedNames,
            'domainResults' => $this->domainResults,
        ];

        $this->dispatch('saveToHistory', $historyEntry);
    }

    public function reloadSearch(string $historyId): void
    {
        $this->dispatch('reloadSearch', $historyId);
    }

    public function clearHistory(): void
    {
        $this->dispatch('confirmClearHistory');
    }

    #[On('update-search-history')]
    public function updateSearchHistory(array $history): void
    {
        $this->searchHistory = $history;
    }

    #[On('reload-search-entry')]
    public function reloadSearchEntry(array $entry): void
    {
        $this->businessDescription = $entry['businessDescription'] ?? '';
        $this->mode = $entry['mode'] ?? 'creative';
        $this->deepThinking = $entry['deepThinking'] ?? false;
        $this->generatedNames = $entry['generatedNames'] ?? [];
        $this->domainResults = $entry['domainResults'] ?? [];
    }

    public function toggleHistory(): void
    {
        $this->showHistory = !$this->showHistory;
        
        if ($this->showHistory) {
            $this->loadSearchHistory();
        }
    }

    private function isRateLimited(): bool
    {
        if ($this->lastApiCallTime === null) {
            return false;
        }

        $timeSinceLastCall = time() - $this->lastApiCallTime;
        return $timeSinceLastCall < $this->rateLimitCooldown;
    }

    private function getRemainingCooldownTime(): int
    {
        if ($this->lastApiCallTime === null) {
            return 0;
        }

        $timeSinceLastCall = time() - $this->lastApiCallTime;
        return max(0, $this->rateLimitCooldown - $timeSinceLastCall);
    }

    private function getErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        // Network/connection errors
        if (str_contains($message, 'network') || str_contains($message, 'connection') || str_contains($message, 'timeout')) {
            return 'Unable to connect to our servers. Please check your internet connection and try again.';
        }
        
        // Rate limiting errors
        if (str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
            return 'Too many requests. Please wait a moment and try again.';
        }
        
        // Quota/billing errors
        if (str_contains($message, 'quota') || str_contains($message, 'billing') || str_contains($message, 'limit exceeded')) {
            return 'Daily usage limit reached. Please try again tomorrow or upgrade your plan.';
        }
        
        // Authentication errors
        if (str_contains($message, 'authentication') || str_contains($message, 'api key') || str_contains($message, 'unauthorized')) {
            return 'Service configuration issue. Please contact support.';
        }
        
        // Service unavailable
        if (str_contains($message, 'service unavailable') || str_contains($message, 'maintenance')) {
            return 'Service temporarily unavailable. Please try again in a few minutes.';
        }
        
        // Input validation errors
        if (str_contains($message, 'invalid') && str_contains($message, 'description')) {
            return 'Invalid business description. Please provide a clear description of your business idea.';
        }
        
        // Generic fallback
        return 'Failed to generate names. Please try again.';
    }

    private function getDomainErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        if (str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return 'Connection timeout';
        }
        
        if (str_contains($message, 'rate limit')) {
            return 'Rate limited';
        }
        
        return 'Check failed';
    }

    public function retryGeneration(): void
    {
        $this->generateNames();
    }

    /**
     * Generate logos for a selected business name.
     */
    public function generateLogos(string $selectedName): void
    {
        $this->validate([
            'businessDescription' => 'required|string|min:10|max:2000',
        ], [
            'businessDescription.required' => 'Business description is required to generate logos.',
            'businessDescription.min' => 'Please provide a more detailed business description (at least 10 characters).',
        ]);

        // Validate that the selected name is in the generated names
        if (!in_array($selectedName, $this->generatedNames)) {
            $this->errorMessage = 'Invalid business name selected.';
            return;
        }

        $this->isGeneratingLogos = true;
        $this->errorMessage = '';

        try {
            // Create logo generation request
            $logoGeneration = LogoGeneration::create([
                'session_id' => $this->sessionId,
                'business_name' => $selectedName,
                'business_description' => $this->businessDescription,
                'status' => 'pending',
                'total_logos_requested' => 12,
                'logos_completed' => 0,
                'api_provider' => 'openai',
                'cost_cents' => 0,
            ]);

            // Dispatch the logo generation job
            GenerateLogosJob::dispatch($logoGeneration);

            // Redirect to logo gallery to show progress
            if (!app()->environment('testing')) {
                redirect()->to(route('logo-gallery', $logoGeneration->id));
            }
        } catch (\Exception) {
            $this->errorMessage = 'Failed to start logo generation. Please try again.';
        } finally {
            $this->isGeneratingLogos = false;
        }
    }
    
    /**
     * Debug serialization issues using Livewire v3 dehydration hooks
     */
    public function dehydrateDomainResults($value)
    {
        try {
            json_encode($value);
        } catch (\Exception $e) {
            Log::error("NameGenerator serialization error for domainResults", [
                'type' => gettype($value),
                'error' => $e->getMessage(),
                'value_preview' => is_array($value) ? 'Array[' . count($value) . ']' : substr((string)$value, 0, 100)
            ]);
        }
        return $value;
    }

    public function dehydrateSearchHistory($value)
    {
        try {
            json_encode($value);
        } catch (\Exception $e) {
            Log::error("NameGenerator serialization error for searchHistory", [
                'type' => gettype($value),
                'error' => $e->getMessage(),
                'value_preview' => is_array($value) ? 'Array[' . count($value) . ']' : substr((string)$value, 0, 100)
            ]);
        }
        return $value;
    }

    /**
     * Sort table by specified column and direction
     */
    public function sortTable(string $column, string $direction): void
    {
        $this->currentSort = ['column' => $column, 'direction' => $direction];
        $this->applySorting();
    }

    /**
     * Apply current sorting to domain results
     */
    public function applySorting(): void
    {
        $this->processFiltersAndSort();
    }

    /**
     * Get sort value for a result based on column
     */
    private function getSortValue(array $result, string $column): mixed
    {
        return match ($column) {
            'name' => strtolower($result['name']),
            'length' => strlen($result['name']),
            'availability' => $this->getDomainAvailabilityScore($result),
            default => $result['name']
        };
    }

    /**
     * Compare two sort values
     */
    private function compareSortValues(mixed $a, mixed $b, string $column): int
    {
        if ($column === 'name') {
            return strcmp((string)$a, (string)$b);
        }
        
        if (is_numeric($a) && is_numeric($b)) {
            return $a <=> $b;
        }
        
        return strcmp((string)$a, (string)$b);
    }

    /**
     * Get domain availability score for sorting
     */
    private function getDomainAvailabilityScore(array $result): int
    {
        $domains = $result['domains'] ?? [];
        $availableCount = 0;
        $totalCount = count($domains);
        
        foreach ($domains as $domain) {
            if (($domain['status'] ?? '') === 'available' && ($domain['available'] ?? false)) {
                $availableCount++;
            }
        }
        
        // Higher score = more available domains
        return $totalCount > 0 ? (int)round(($availableCount / $totalCount) * 100) : 0;
    }

    /**
     * Filter table by specified criteria
     */
    public function filterTable(string $filterType, string $filterValue): void
    {
        $this->activeFilters[$filterType] = $filterValue;
        $this->applyFilters();
    }

    /**
     * Apply current filters to domain results
     */
    public function applyFilters(): void
    {
        $results = $this->domainResults;
        
        foreach ($this->activeFilters as $filterType => $filterValue) {
            $results = array_filter($results, function ($result) use ($filterType, $filterValue) {
                return $this->passesFilter($result, $filterType, $filterValue);
            });
        }
        
        $this->filteredDomainResults = array_values($results);
        $this->processFiltersAndSort();
    }

    /**
     * Check if result passes a specific filter
     */
    private function passesFilter(array $result, string $filterType, string $filterValue): bool
    {
        return match ($filterType) {
            'domain_status' => $this->passesStatusFilter($result, $filterValue),
            'name_length' => $this->passesLengthFilter($result, $filterValue),
            default => true
        };
    }

    /**
     * Check if result passes domain status filter
     */
    private function passesStatusFilter(array $result, string $filterValue): bool
    {
        $domains = $result['domains'] ?? [];
        
        return match ($filterValue) {
            'com_available' => $this->isDomainAvailable($domains, 'com'),
            'net_available' => $this->isDomainAvailable($domains, 'net'), 
            'org_available' => $this->isDomainAvailable($domains, 'org'),
            'all_available' => $this->areAllDomainsAvailable($domains),
            'any_available' => $this->isAnyDomainAvailable($domains),
            default => true
        };
    }

    /**
     * Check if specific TLD domain is available
     */
    private function isDomainAvailable(array $domains, string $tld): bool
    {
        foreach ($domains as $domain => $data) {
            if (str_ends_with($domain, ".{$tld}")) {
                return ($data['status'] ?? '') === 'available' && ($data['available'] ?? false);
            }
        }
        return false;
    }

    /**
     * Check if all domains are available
     */
    private function areAllDomainsAvailable(array $domains): bool
    {
        if (empty($domains)) {
            return false;
        }
        
        foreach ($domains as $data) {
            if (($data['status'] ?? '') !== 'available' || !($data['available'] ?? false)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if any domain is available
     */
    private function isAnyDomainAvailable(array $domains): bool
    {
        foreach ($domains as $data) {
            if (($data['status'] ?? '') === 'available' && ($data['available'] ?? false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if result passes name length filter
     */
    private function passesLengthFilter(array $result, string $filterValue): bool
    {
        $length = strlen($result['name']);
        
        return match ($filterValue) {
            'short' => $length <= 10,
            'medium' => $length > 10 && $length <= 20,
            'long' => $length > 20,
            default => true
        };
    }

    /**
     * Clear all active filters
     */
    public function clearFilters(): void
    {
        $this->activeFilters = [];
        $this->filteredDomainResults = [];
        $this->currentFilter = '';
        $this->processFiltersAndSort();
    }

    /**
     * Process filters and sorting to get final results
     */
    private function processFiltersAndSort(): void
    {
        // Start with the right base dataset
        if (!empty($this->activeFilters)) {
            $results = $this->filteredDomainResults;
        } else {
            $results = $this->domainResults;
        }

        // Apply sorting to the filtered (or unfiltered) results
        if ($this->currentSort['column']) {
            $column = $this->currentSort['column'];
            $direction = $this->currentSort['direction'];

            usort($results, function ($a, $b) use ($column, $direction) {
                $valueA = $this->getSortValue($a, $column);
                $valueB = $this->getSortValue($b, $column);

                $comparison = $this->compareSortValues($valueA, $valueB, $column);
                
                return $direction === 'asc' ? $comparison : -$comparison;
            });
        }

        $this->processedDomainResults = $results;
    }

    /**
     * Update processed results when domain results change
     */
    public function updatedDomainResults(): void
    {
        $this->applySorting();
    }

    // UI Control properties
    public string $currentSortColumn = '';
    public string $currentFilter = '';

    /**
     * Handle sort change from UI dropdown
     */
    public function handleSortChange(): void
    {
        if (!$this->currentSortColumn) {
            $this->currentSort = ['column' => null, 'direction' => null];
            $this->applySorting();
            return;
        }

        $parts = explode('_', $this->currentSortColumn);
        $column = $parts[0];
        $direction = isset($parts[1]) && $parts[1] === 'desc' ? 'desc' : 'asc';
        
        $this->sortTable($column, $direction);
    }

    /**
     * Handle filter change from UI dropdown
     */
    public function handleFilterChange(): void
    {
        if (!$this->currentFilter) {
            $this->clearFilters();
            return;
        }

        // Determine filter type based on filter value
        if (in_array($this->currentFilter, ['com_available', 'net_available', 'org_available', 'all_available', 'any_available'])) {
            $this->filterTable('domain_status', $this->currentFilter);
        } elseif (in_array($this->currentFilter, ['short', 'medium', 'long'])) {
            $this->filterTable('name_length', $this->currentFilter);
        }
    }

    /**
     * Remove specific filter
     */
    public function removeFilter(string $filterType): void
    {
        unset($this->activeFilters[$filterType]);
        
        // Update UI dropdown
        if ($filterType === 'domain_status' && in_array($this->currentFilter, ['com_available', 'net_available', 'org_available', 'all_available', 'any_available'])) {
            $this->currentFilter = '';
        } elseif ($filterType === 'name_length' && in_array($this->currentFilter, ['short', 'medium', 'long'])) {
            $this->currentFilter = '';
        }
        
        $this->applyFilters();
    }

    /**
     * Get display name for filter
     */
    public function getFilterDisplayName(string $filterType, string $filterValue): string
    {
        return match ($filterValue) {
            'com_available' => '.com Available',
            'net_available' => '.net Available',
            'org_available' => '.org Available',
            'all_available' => 'All TLDs Available',
            'any_available' => 'Any Available',
            'short' => 'Short Names (≤10 chars)',
            'medium' => 'Medium Names (11-20 chars)',
            'long' => 'Long Names (>20 chars)',
            default => ucfirst(str_replace('_', ' ', $filterValue))
        };
    }

} ?>

<div class="mx-auto max-w-4xl p-6">
    <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Business Name Generator
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Generate creative business names powered by AI
            </p>
        </div>

        <form wire:submit="generateNames" class="space-y-6">
            {{-- Business Description Field --}}
            <div>
                <flux:field>
                    <flux:label>Business Description</flux:label>
                    <flux:textarea
                        wire:model.live="businessDescription"
                        placeholder="Describe your business idea or concept..."
                        rows="4"
                        class="w-full" />
                    <flux:error name="businessDescription" />
                </flux:field>
            </div>

            {{-- Generation Mode Selection --}}
            <div>
                <flux:field>
                    <flux:label>Generation Mode</flux:label>
                    <flux:select wire:model.live="mode" class="w-full">
                        @foreach($modes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="mode" />
                </flux:field>
            </div>

            {{-- Deep Thinking Toggle --}}
            <div>
                <flux:field>
                    <flux:checkbox wire:model="deepThinking" label="Enable Deep Thinking Mode (slower but more thoughtful results)" />
                </flux:field>
            </div>

            {{-- Error Message --}}
            @if($errorMessage)
                <flux:callout variant="danger">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="font-medium text-red-800 dark:text-red-200 mb-1">
                                @if(str_contains($errorMessage, 'wait') && str_contains($errorMessage, 'seconds'))
                                    Rate Limit Reached
                                @elseif(str_contains($errorMessage, 'connection') || str_contains($errorMessage, 'internet'))
                                    Connection Error
                                @elseif(str_contains($errorMessage, 'quota') || str_contains($errorMessage, 'limit'))
                                    Usage Limit Reached
                                @elseif(str_contains($errorMessage, 'temporarily unavailable'))
                                    Service Maintenance
                                @else
                                    Generation Failed
                                @endif
                            </div>
                            <p class="text-red-700 dark:text-red-300">
                                {{ $errorMessage }}
                            </p>
                        </div>
                        
                        @if(!str_contains($errorMessage, 'wait') || !str_contains($errorMessage, 'seconds'))
                            <flux:button 
                                wire:click="retryGeneration" 
                                variant="outline"
                                size="sm"
                                class="ml-4 flex-shrink-0">
                                Try Again
                            </flux:button>
                        @endif
                    </div>
                </flux:callout>
            @endif

            {{-- Generate Button --}}
            <div>
                <flux:button 
                    type="submit" 
                    variant="primary" 
                    :disabled="$isLoading"
                    class="w-full sm:w-auto">
                    
                    <span wire:loading.remove>
                        Generate Names
                    </span>
                    
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generating...
                    </span>
                </flux:button>
            </div>
        </form>

        {{-- Search History Section --}}
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <flux:button 
                    wire:click="toggleHistory" 
                    variant="outline"
                    size="sm"
                    class="flex items-center space-x-2">
                    
                    <span>
                        {{ $showHistory ? 'Hide' : 'Show' }} Search History
                    </span>
                    
                    <svg 
                        class="w-4 h-4 transform transition-transform {{ $showHistory ? 'rotate-180' : '' }}"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </flux:button>

                @if(!empty($searchHistory) && $showHistory)
                    <flux:button 
                        wire:click="clearHistory" 
                        variant="danger"
                        size="sm">
                        Clear History
                    </flux:button>
                @endif
            </div>

            @if($showHistory)
                <div 
                    class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3 max-h-96 overflow-y-auto"
                    x-data="{ searchHistory: [] }"
                    x-init="
                        // Load search history from localStorage
                        searchHistory = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        $dispatch('update-search-history', searchHistory);
                    "
                    @load-search-history.window="
                        searchHistory = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        $dispatch('update-search-history', searchHistory);
                    "
                    @save-to-history.window="
                        let history = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        history.unshift($event.detail);
                        history = history.slice(0, 50); // Keep only last 50 entries
                        localStorage.setItem('nameGeneratorHistory', JSON.stringify(history));
                        searchHistory = history;
                        $dispatch('update-search-history', history);
                    "
                    @reload-search.window="
                        let history = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        let entry = history.find(h => h.id === $event.detail);
                        if (entry) {
                            $dispatch('reload-search-entry', entry);
                        }
                    "
                    @confirm-clear-history.window="
                        if (confirm('Are you sure you want to clear your search history? This action cannot be undone.')) {
                            localStorage.removeItem('nameGeneratorHistory');
                            searchHistory = [];
                            $dispatch('update-search-history', []);
                        }
                    ">

                    @if(empty($searchHistory))
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>No search history yet</p>
                            <p class="text-sm mt-1">Generated names will appear here for easy access</p>
                        </div>
                    @else
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Recent Searches ({{ count($searchHistory) }})
                        </h3>
                        
                        @foreach($searchHistory as $entry)
                            <div class="bg-white dark:bg-gray-700 rounded-md p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ date('M j, Y \a\t g:i A', strtotime($entry['timestamp'])) }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ ucfirst($entry['mode']) }}
                                            </span>
                                            @if($entry['deepThinking'])
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                    Deep Thinking
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                            "{{ $entry['businessDescription'] }}"
                                        </p>
                                        
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($entry['generatedNames'] as $name)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                                    {{ $name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <flux:button 
                                        wire:click="reloadSearch('{{ $entry['id'] }}')"
                                        variant="outline"
                                        size="sm"
                                        class="ml-4">
                                        Reload
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif
        </div>

        {{-- Generated Names & Domain Status Display --}}
        @if(!empty($domainResults))
            <div class="mt-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Generated Names & Domain Availability
                    </h2>
                    
                    {{-- Domain Checking Progress --}}
                    @if($isCheckingDomains)
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Checking domains... {{ $checkingProgress }}%
                        </div>
                    @endif
                </div>

                {{-- Table Controls --}}
                <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                    {{-- Sorting Controls --}}
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort by:</span>
                        <flux:select 
                            wire:model.live="currentSortColumn" 
                            wire:change="handleSortChange"
                            size="sm"
                            class="w-32">
                            <option value="">Default</option>
                            <option value="name">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="length">Length ↑</option>
                            <option value="length_desc">Length ↓</option>
                            <option value="availability">Availability ↑</option>
                            <option value="availability_desc">Availability ↓</option>
                        </flux:select>
                    </div>

                    {{-- Filtering Controls --}}
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</span>
                        <flux:select 
                            wire:model.live="currentFilter" 
                            wire:change="handleFilterChange"
                            size="sm"
                            class="w-40">
                            <option value="">All Results</option>
                            <option value="com_available">.com Available</option>
                            <option value="net_available">.net Available</option>
                            <option value="org_available">.org Available</option>
                            <option value="all_available">All TLDs Available</option>
                            <option value="any_available">Any Available</option>
                            <option value="short">Short Names (≤10 chars)</option>
                            <option value="medium">Medium Names (11-20 chars)</option>
                            <option value="long">Long Names (>20 chars)</option>
                        </flux:select>
                        
                        @if(!empty($activeFilters))
                            <flux:button 
                                wire:click="clearFilters" 
                                variant="outline"
                                size="sm"
                                class="text-red-600 hover:text-red-700">
                                Clear Filters
                            </flux:button>
                        @endif
                    </div>
                </div>

                {{-- Active Filters Display --}}
                @if(!empty($activeFilters))
                    <div class="mb-4 flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Active filters:</span>
                        @foreach($activeFilters as $filterType => $filterValue)
                            <flux:badge 
                                variant="outline" 
                                class="active-filter bg-blue-50 text-blue-700 border-blue-200">
                                {{ $this->getFilterDisplayName($filterType, $filterValue) }}
                                <button 
                                    wire:click="removeFilter('{{ $filterType }}')"
                                    class="ml-1 text-blue-500 hover:text-blue-700"
                                    aria-label="Remove filter">
                                    ×
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                {{-- Results Count --}}
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ count($processedDomainResults ?: $domainResults) }} of {{ count($domainResults) }} results
                    @if($currentSort['column'])
                        <span class="sort-indicator sort-{{ $currentSort['direction'] }} ml-2">
                            (sorted by {{ ucfirst($currentSort['column']) }} 
                            {{ $currentSort['direction'] === 'asc' ? '↑' : '↓' }})
                        </span>
                    @endif
                </div>

                {{-- Domain Results Table --}}
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column class="w-2/5">Business Name</flux:table.column>
                        <flux:table.column class="w-1/5">.com</flux:table.column>
                        <flux:table.column class="w-1/5">.net</flux:table.column>
                        <flux:table.column class="w-1/5">.org</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse(($processedDomainResults ?: $domainResults) as $result)
                            <flux:table.row>
                                <flux:table.cell class="font-semibold">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $result['name'] }}</span>
                                        
                                        {{-- Generate Logos Button --}}
                                        <flux:button
                                            wire:click="generateLogos('{{ $result['name'] }}')" 
                                            variant="outline"
                                            size="sm"
                                            :disabled="$isGeneratingLogos"
                                            class="ml-2">
                                            
                                            <span wire:loading.remove wire:target="generateLogos('{{ $result['name'] }}')">
                                                🎨 Generate Logos
                                            </span>
                                            
                                            <span wire:loading wire:target="generateLogos('{{ $result['name'] }}')"
                                                  class="flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Generating...
                                            </span>
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                                
                                @foreach(['com', 'net', 'org'] as $tld)
                                    @php
                                        $domainKey = $result['name'] . '.' . $tld;
                                        $domainData = $result['domains'][$domainKey] ?? ['status' => 'checking', 'available' => null];
                                    @endphp
                                    <flux:table.cell>
                                        <div class="flex items-center space-x-2">
                                            <flux:tooltip 
                                                text="{{ $domainData['status'] === 'error' && isset($domainData['error']) ? $domainData['error'] : 'Domain availability status' }}"
                                                position="top"
                                            >
                                                <span class="{{ $this->getDomainStatusClass($domainData['status'], $domainData['available'] ?? null) }}">
                                                    {{ $this->getDomainStatusIcon($domainData['status'], $domainData['available'] ?? null) }}
                                                </span>
                                            </flux:tooltip>
                                            
                                            <span class="text-sm {{ $this->getDomainStatusClass($domainData['status'], $domainData['available'] ?? null) }}">
                                                {{ $this->getDomainStatusText($domainData['status'], $domainData['available'] ?? null) }}
                                            </span>
                                        </div>
                                    </flux:table.cell>
                                @endforeach
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <div>
                                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p>No results found</p>
                                        <p class="text-sm mt-1">Try adjusting your filters or generate new names</p>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                {{-- Domain Status Legend --}}
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Domain Status Legend:</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <span class="text-green-600 dark:text-green-400">✅</span>
                            <span>Available</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-red-600 dark:text-red-400">❌</span>
                            <span>Taken</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-blue-600 dark:text-blue-400">🔄</span>
                            <span>Checking...</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-yellow-600 dark:text-yellow-400">⚠️</span>
                            <span>Error</span>
                        </div>
                    </div>
                </div>

                {{-- Manual Recheck Button --}}
                @if(!$isCheckingDomains && !empty($domainResults))
                    <div class="mt-4 text-center">
                        <flux:button 
                            wire:click="checkDomains" 
                            variant="outline"
                            size="sm">
                            Recheck Domains
                        </flux:button>
                    </div>
                @endif

                {{-- Bulk Logo Generation Section --}}
                @if(!empty($generatedNames) && !$isCheckingDomains)
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100">
                                    🎨 Generate Logos
                                </h3>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    Create AI-powered logo designs for your selected business name
                                </p>
                            </div>
                            <div class="text-right text-xs text-blue-600 dark:text-blue-400">
                                12 unique designs<br>
                                4 styles × 3 variations each
                            </div>
                        </div>
                        
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-3">
                            Click "Generate Logos" next to any business name above, or use the bulk generation below:
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            @foreach($generatedNames as $name)
                                <flux:button
                                    wire:click="generateLogos('{{ $name }}')" 
                                    variant="filled"
                                    size="sm"
                                    :disabled="$isGeneratingLogos"
                                    class="bg-blue-600 hover:bg-blue-700 text-white">
                                    
                                    <span wire:loading.remove wire:target="generateLogos('{{ $name }}')">
                                        Generate for {{ $name }}
                                    </span>
                                    
                                    <span wire:loading wire:target="generateLogos('{{ $name }}')" 
                                          class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Generating...
                                    </span>
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
