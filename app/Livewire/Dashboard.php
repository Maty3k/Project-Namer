<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\GenerateLogosJob;
use App\Models\GenerationCache;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Services\DomainCheckService;
use App\Services\ExportService;
use App\Services\OpenAINameService;
use App\Services\ShareService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Main dashboard component for the Project Namer application.
 *
 * Handles the complete workflow from business idea input to name generation,
 * domain checking, logo creation, and result sharing.
 */
class Dashboard extends Component
{
    // Business idea input
    public string $businessIdea = '';

    public string $generationMode = 'creative';

    public bool $deepThinking = false;

    // Generated names and domain results
    /** @var array<int, string> */
    public array $generatedNames = [];

    /** @var array<string, array<string, mixed>> */
    public array $domainResults = [];

    // Logo generation
    protected ?LogoGeneration $currentLogoGeneration = null;

    /** @var array<int, string> */
    public array $selectedNamesForLogos = [];

    public bool $showLogoGeneration = false;

    // UI state
    public bool $isGeneratingNames = false;

    public bool $isCheckingDomains = false;

    public bool $showResults = false;

    public bool $showHistory = false;

    /** @var array<int, array<string, mixed>> */
    public array $searchHistory = [];

    // Error handling
    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    // Current active tab
    public string $activeTab = 'generate';

    /** @var array<string, string> */
    protected array $rules = [
        'businessIdea' => 'required|string|max:2000',
        'generationMode' => 'required|in:creative,professional,brandable,tech-focused',
        'deepThinking' => 'boolean',
        'selectedNamesForLogos' => 'array|max:5',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'businessIdea.required' => 'Please describe your business idea',
        'businessIdea.max' => 'Business idea must be less than 2000 characters',
        'selectedNamesForLogos.max' => 'You can select up to 5 names for logo generation',
    ];

    public function mount(): void
    {
        $this->loadSearchHistory();
        $this->checkForActiveLogoGeneration();
    }

    /**
     * Generate business names using OpenAI API.
     */
    public function generateNames(): void
    {
        $this->validate();

        $this->resetState();
        $this->isGeneratingNames = true;
        $this->errorMessage = null;

        try {
            $nameService = app(OpenAINameService::class);
            $this->generatedNames = $nameService->generateNames(
                $this->businessIdea,
                $this->generationMode,
                $this->deepThinking
            );

            $this->checkDomainAvailability();
            $this->showResults = true;
            $this->activeTab = 'results';

            $this->successMessage = 'Generated '.count($this->generatedNames).' business names!';

            // Update search history
            $this->addToSearchHistory();

            // Dispatch success event for toast notification
            $this->dispatch('toast', message: $this->successMessage, type: 'success');

        } catch (Exception $e) {
            $this->errorMessage = $this->getFriendlyErrorMessage($e->getMessage());
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isGeneratingNames = false;
        }
    }

    /**
     * Check domain availability for all generated names.
     */
    private function checkDomainAvailability(): void
    {
        if (empty($this->generatedNames)) {
            return;
        }

        $this->isCheckingDomains = true;

        try {
            $domainService = app(DomainCheckService::class);
            $this->domainResults = [];

            foreach ($this->generatedNames as $name) {
                $this->domainResults[$name] = $domainService->checkBusinessName($name);
            }
        } catch (Exception $e) {
            // Log error but don't fail the entire generation
            logger()->warning('Domain checking failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isCheckingDomains = false;
        }
    }

    /**
     * Generate logos for selected business names.
     */
    public function generateLogos(): void
    {
        $this->validate([
            'selectedNamesForLogos' => 'required|array|min:1|max:5',
        ], [
            'selectedNamesForLogos.required' => 'Please select at least one name for logo generation',
            'selectedNamesForLogos.min' => 'Please select at least one name',
            'selectedNamesForLogos.max' => 'You can select up to 5 names',
        ]);

        try {
            // Create logo generation record
            $this->currentLogoGeneration = LogoGeneration::create([
                'session_id' => session()->getId(),
                'business_name' => implode(', ', $this->selectedNamesForLogos),
                'business_description' => $this->businessIdea,
                'generation_mode' => $this->generationMode,
                'status' => 'processing',
                'total_logos_requested' => count($this->selectedNamesForLogos) * 4, // 4 styles per name
                'logos_completed' => 0,
            ]);

            // Dispatch logo generation job
            GenerateLogosJob::dispatch($this->currentLogoGeneration);

            $this->showLogoGeneration = true;
            $this->activeTab = 'logos';

            $this->successMessage = 'Logo generation started! This may take a few minutes.';
            $this->dispatch('toast', message: $this->successMessage, type: 'success');

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to start logo generation: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
        }
    }

    /**
     * Share current results.
     */
    public function shareResults(): void
    {
        if (empty($this->generatedNames)) {
            $this->dispatch('toast', message: 'No results to share', type: 'error');

            return;
        }

        try {
            $shareService = app(ShareService::class);

            $user = Auth::user();
            if (! $user) {
                throw new Exception('User not authenticated');
            }

            $share = $shareService->createShare($user, [
                'business_description' => $this->businessIdea,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
                'generated_names' => $this->generatedNames,
                'domain_results' => $this->domainResults,
            ]);

            $shareUrl = route('public-share.show', $share->uuid);

            // Copy to clipboard via JavaScript
            $this->dispatch('copy-to-clipboard', url: $shareUrl);
            $this->dispatch('toast', message: 'Share link copied to clipboard!', type: 'success');

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to create share link: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
        }
    }

    /**
     * Export results in various formats.
     */
    public function exportResults(string $format): void
    {
        if (empty($this->generatedNames)) {
            $this->dispatch('toast', message: 'No results to export', type: 'error');

            return;
        }

        try {
            $exportService = app(ExportService::class);

            $exportData = [
                'business_description' => $this->businessIdea,
                'generation_mode' => $this->generationMode,
                'generated_names' => $this->generatedNames,
                'domain_results' => $this->domainResults,
                'exported_at' => now()->format('Y-m-d H:i:s'),
            ];

            $user = Auth::user();
            if (! $user) {
                throw new Exception('User not authenticated');
            }

            $export = $exportService->createExport($user, $exportData);

            $this->dispatch('download-file', url: route('public-download', $export->uuid));
            $this->dispatch('toast', message: 'Export started! Download will begin shortly.', type: 'success');

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to export results: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
        }
    }

    /**
     * Load a previous search from history.
     */
    public function loadFromHistory(string $inputHash): void
    {
        $cached = GenerationCache::where('input_hash', $inputHash)->first();

        if ($cached) {
            $this->businessIdea = $cached->business_description;
            $this->generationMode = $cached->mode;
            $this->deepThinking = $cached->deep_thinking;
            $this->generatedNames = $cached->generated_names;

            $this->checkDomainAvailability();
            $this->showResults = true;
            $this->activeTab = 'results';

            $this->dispatch('toast', message: 'Loaded previous search', type: 'success');
        }
    }

    /**
     * Clear current results and reset form.
     */
    public function clearResults(): void
    {
        $this->resetState();
        $this->activeTab = 'generate';
        $this->dispatch('toast', message: 'Results cleared', type: 'info');
    }

    /**
     * Toggle name selection for logo generation.
     */
    public function toggleNameSelection(string $name): void
    {
        if (in_array($name, $this->selectedNamesForLogos)) {
            $this->selectedNamesForLogos = array_values(array_diff($this->selectedNamesForLogos, [$name]));
        } else {
            if (count($this->selectedNamesForLogos) < 5) {
                $this->selectedNamesForLogos[] = $name;
            } else {
                $this->dispatch('toast', message: 'You can select up to 5 names', type: 'warning');
            }
        }
    }

    /**
     * Refresh logo generation status.
     */
    #[On('refresh-logo-status')]
    public function refreshLogoStatus(): void
    {
        if ($this->currentLogoGeneration) {
            $this->currentLogoGeneration->refresh();
        }
    }

    /**
     * Reset component state.
     */
    private function resetState(): void
    {
        $this->generatedNames = [];
        $this->domainResults = [];
        $this->selectedNamesForLogos = [];
        $this->showResults = false;
        $this->showLogoGeneration = false;
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Add current search to history.
     */
    private function addToSearchHistory(): void
    {
        $inputHash = GenerationCache::generateHash($this->businessIdea, $this->generationMode, $this->deepThinking);

        $this->searchHistory = array_filter($this->searchHistory, fn ($item) => $item['hash'] !== $inputHash);

        array_unshift($this->searchHistory, [
            'hash' => $inputHash,
            'business_idea' => $this->businessIdea,
            'mode' => $this->generationMode,
            'deep_thinking' => $this->deepThinking,
            'timestamp' => now()->format('M j, Y g:i A'),
            'name_count' => count($this->generatedNames),
        ]);

        // Keep only last 10 searches
        $this->searchHistory = array_slice($this->searchHistory, 0, 10);
    }

    /**
     * Load search history from cache.
     */
    private function loadSearchHistory(): void
    {
        $recent = GenerationCache::orderBy('cached_at', 'desc')
            ->take(10)
            ->get();

        $this->searchHistory = $recent->map(fn ($cache) => [
            'hash' => $cache->input_hash,
            'business_idea' => $cache->business_description,
            'mode' => $cache->mode,
            'deep_thinking' => $cache->deep_thinking,
            'timestamp' => $cache->cached_at->format('M j, Y g:i A'),
            'name_count' => count($cache->generated_names),
        ])->toArray();
    }

    /**
     * Get the current logo generation model.
     */
    public function getCurrentLogoGenerationProperty(): ?LogoGeneration
    {
        return $this->currentLogoGeneration;
    }

    /**
     * Check for active logo generation.
     */
    private function checkForActiveLogoGeneration(): void
    {
        $this->currentLogoGeneration = LogoGeneration::whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($this->currentLogoGeneration) {
            $this->showLogoGeneration = true;
        }
    }

    /**
     * Get user-friendly error message.
     */
    private function getFriendlyErrorMessage(string $error): string
    {
        return match (true) {
            str_contains($error, 'timeout') => 'Request timed out. Please try again.',
            str_contains($error, 'rate limit') => 'Too many requests. Please wait a moment and try again.',
            str_contains($error, 'API key') => 'Service temporarily unavailable. Please try again later.',
            str_contains($error, 'network') => 'Network error. Please check your connection.',
            default => 'An error occurred. Please try again.',
        };
    }

    /**
     * Debug serialization issues using Livewire v3 lifecycle hooks
     */
    public function dehydrateBusinessIdea($value)
    {
        $this->debugProperty('businessIdea', $value);
        return $value;
    }

    public function dehydrateDomainResults($value)
    {
        $this->debugProperty('domainResults', $value);
        return $value;
    }

    public function dehydrateSearchHistory($value)
    {
        $this->debugProperty('searchHistory', $value);
        return $value;
    }

    private function debugProperty($key, $value)
    {
        try {
            json_encode($value);
        } catch (\Exception $e) {
            Log::error("Dashboard serialization error for property: {$key}", [
                'property' => $key,
                'type' => gettype($value),
                'error' => $e->getMessage(),
                'value_preview' => is_object($value) ? get_class($value) : (is_array($value) ? 'Array[' . count($value) . ']' : substr((string)$value, 0, 100))
            ]);
        }
    }

    /**
     * Add a toJSON method to prevent the toJSON error.
     */
    public function toJSON()
    {
        Log::error("Dashboard toJSON method called - this indicates a serialization issue", [
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
        
        // Return component state for JSON serialization
        $reflection = new ReflectionClass($this);
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic() && !$property->isStatic()) {
                $name = $property->getName();
                try {
                    $value = $this->{$name};
                    json_encode($value); // Test if serializable
                    $properties[$name] = $value;
                } catch (\Exception $e) {
                    Log::error("Dashboard property {$name} not serializable", [
                        'error' => $e->getMessage(),
                        'type' => gettype($this->{$name})
                    ]);
                    $properties[$name] = is_object($this->{$name}) ? get_class($this->{$name}) : 'NOT_SERIALIZABLE';
                }
            }
        }
        
        return json_encode($properties);
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
