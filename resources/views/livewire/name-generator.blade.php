<?php

declare(strict_types=1);

use App\Services\OpenAINameService;
use App\Services\DomainCheckService;
use Livewire\Volt\Component;

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

    public array $modes = [
        'creative' => 'Creative',
        'professional' => 'Professional',
        'brandable' => 'Brandable',
        'tech-focused' => 'Tech-focused',
    ];

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
                    <flux:checkbox wire:model="deepThinking">
                        Enable Deep Thinking Mode (slower but more thoughtful results)
                    </flux:checkbox>
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
                        $wire.set('searchHistory', searchHistory);
                    "
                    @load-search-history.window="
                        searchHistory = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        $wire.set('searchHistory', searchHistory);
                    "
                    @save-to-history.window="
                        let history = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        history.unshift($event.detail);
                        history = history.slice(0, 50); // Keep only last 50 entries
                        localStorage.setItem('nameGeneratorHistory', JSON.stringify(history));
                        searchHistory = history;
                        $wire.set('searchHistory', history);
                    "
                    @reload-search.window="
                        let history = JSON.parse(localStorage.getItem('nameGeneratorHistory') || '[]');
                        let entry = history.find(h => h.id === $event.detail);
                        if (entry) {
                            $wire.set('businessDescription', entry.businessDescription);
                            $wire.set('mode', entry.mode);
                            $wire.set('deepThinking', entry.deepThinking);
                            $wire.set('generatedNames', entry.generatedNames);
                            $wire.set('domainResults', entry.domainResults);
                        }
                    "
                    @confirm-clear-history.window="
                        if (confirm('Are you sure you want to clear your search history? This action cannot be undone.')) {
                            localStorage.removeItem('nameGeneratorHistory');
                            searchHistory = [];
                            $wire.set('searchHistory', []);
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

                {{-- Domain Results Table --}}
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column class="w-1/4">Business Name</flux:table.column>
                        <flux:table.column class="w-1/4">.com</flux:table.column>
                        <flux:table.column class="w-1/4">.net</flux:table.column>
                        <flux:table.column class="w-1/4">.org</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($domainResults as $result)
                            <flux:table.row>
                                <flux:table.cell class="font-semibold">
                                    {{ $result['name'] }}
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
                        @endforeach
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
            </div>
        @endif
    </div>
</div>
