<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\GenerateLogosJob;
use App\Models\AIGeneration;
use App\Models\AIModelPerformance;
use App\Models\GenerationCache;
use App\Models\LogoGeneration;
use App\Models\NamingSession;
use App\Models\Share;
use App\Models\UserAIPreferences;
use App\Services\AI\AIGenerationService;
use App\Services\AI\PrismAIService;
use App\Services\DomainCheckService;
use App\Services\ExportService;
use App\Services\OpenAINameService;
use App\Services\SessionService;
use App\Services\ShareService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Name generator dashboard component for the Project Namer application.
 *
 * Handles the complete workflow from business idea input to name generation,
 * domain checking, logo creation, and result sharing.
 */
class NameGeneratorDashboard extends Component
{
    // Business idea input
    public string $businessIdea = '';

    public string $generationMode = 'creative';

    public bool $deepThinking = false;

    // AI Generation Properties
    public bool $useAIGeneration = false;

    public bool $enableModelComparison = false;

    /** @var array<int, string> */
    public array $selectedAIModels = [];

    /** @var array<int, array<string, string>> */
    public array $availableAIModels = [
        ['id' => 'gpt-4', 'name' => 'GPT-4', 'provider' => 'OpenAI'],
        ['id' => 'claude-3.5-sonnet', 'name' => 'Claude 3.5', 'provider' => 'Anthropic'],
        ['id' => 'gemini-1.5-pro', 'name' => 'Gemini Pro', 'provider' => 'Google'],
        ['id' => 'grok-beta', 'name' => 'Grok', 'provider' => 'xAI'],
    ];

    /** @var array<string, bool> */
    public array $modelAvailability = [];

    public ?int $currentAIGenerationId = null;

    public string $aiGenerationStatus = '';

    /** @var array<string, array<int, string>> */
    public array $aiModelResults = [];

    public string $activeModelTab = '';

    // Generated names and domain results
    /** @var array<int, string> */
    public array $generatedNames = [];

    /** @var array<string, array<string, mixed>> */
    public array $domainResults = [];

    // Logo generation
    protected ?LogoGeneration $currentLogoGeneration = null;

    /** @var array<int, string> */
    public array $selectedNamesForLogos = [];

    public bool $selectAll = false;

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

    // Session management
    public ?string $currentSessionId = null;

    public bool $hasUnsavedChanges = false;

    protected ?NamingSession $currentSession = null;

    /** @var array<string, string> */
    protected array $rules = [
        'businessIdea' => 'required|string|max:2000',
        'generationMode' => 'required|in:creative,professional,brandable,tech-focused',
        'deepThinking' => 'boolean',
        'selectedNamesForLogos' => 'array|max:5',
        'useAIGeneration' => 'boolean',
        'enableModelComparison' => 'boolean',
        'selectedAIModels' => 'array|min:1|max:4',
        'selectedAIModels.*' => 'string|in:gpt-4,claude-3.5-sonnet,gemini-1.5-pro,grok-beta',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'businessIdea.required' => 'Please describe your business idea',
        'businessIdea.max' => 'Business idea must be less than 2000 characters',
        'selectedNamesForLogos.max' => 'You can select up to 5 names for logo generation',
        'selectedAIModels.required' => 'Please select at least one AI model',
        'selectedAIModels.min' => 'Please select at least one AI model',
        'selectedAIModels.max' => 'You can select up to 4 AI models for comparison',
    ];

    public function mount(): void
    {
        $this->loadSearchHistory();
        $this->checkForActiveLogoGeneration();
        $this->loadUserAIPreferences();
        $this->checkModelAvailability();
    }

    /**
     * Generate business names using OpenAI API.
     */
    public function generateNames(): void
    {
        $this->validate([
            'businessIdea' => 'required|string|max:2000',
            'generationMode' => 'required|in:creative,professional,brandable,tech-focused',
            'deepThinking' => 'boolean',
        ]);

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

            // Auto-save session after successful generation
            $this->autoSaveSession();

            $this->successMessage = 'Generated '.count($this->generatedNames).' business names!';

            // Update search history
            $this->addToSearchHistory();

            // Dispatch success event for toast notification
            $this->dispatch('toast', message: $this->successMessage, type: 'success');

        } catch (Exception) {
            // Try fallback generation when OpenAI fails
            try {
                $fallbackService = app(\App\Services\FallbackNameService::class);
                $this->generatedNames = $fallbackService->generateNames(
                    $this->businessIdea,
                    $this->generationMode,
                    10
                );

                $this->checkDomainAvailability();
                $this->showResults = true;
                $this->activeTab = 'results';
                $this->autoSaveSession();

                $this->successMessage = 'Generated '.count($this->generatedNames).' business names using creative generation!';
                $this->dispatch('toast', message: 'API unavailable - used creative generation instead', type: 'warning');

            } catch (Exception $fallbackException) {
                $this->errorMessage = $this->getFriendlyErrorMessage($fallbackException->getMessage());
                $this->dispatch('toast', message: $this->errorMessage, type: 'error');
            }
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
    public function dehydrateBusinessIdea(mixed $value): mixed
    {
        $this->debugProperty('businessIdea', $value);

        return $value;
    }

    public function dehydrateDomainResults(mixed $value): mixed
    {
        $this->debugProperty('domainResults', $value);

        return $value;
    }

    public function dehydrateSearchHistory(mixed $value): mixed
    {
        $this->debugProperty('searchHistory', $value);

        return $value;
    }

    private function debugProperty(string $key, mixed $value): void
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error("Dashboard serialization error for property: {$key}", [
                'property' => $key,
                'type' => gettype($value),
                'error' => $e->getMessage(),
                'value_preview' => is_object($value) ? $value::class : (is_array($value) ? 'Array['.count($value).']' : substr((string) $value, 0, 100)),
            ]);
        }
    }

    // Session Management Methods

    /**
     * Confirm session switch with save option.
     */
    public function confirmSessionSwitch(string $newSessionId, bool $saveChanges): void
    {
        if ($saveChanges && $this->currentSessionId) {
            $this->autoSaveSession();
        }

        $this->hasUnsavedChanges = false;
        $this->loadSession($newSessionId);
    }

    /**
     * Auto-save current session.
     */
    public function autoSave(): void
    {
        if (empty(trim($this->businessIdea))) {
            return;
        }

        $this->autoSaveSession();
    }

    /**
     * Create new session.
     */
    public function newSession(): void
    {
        if ($this->hasUnsavedChanges) {
            $this->dispatch('confirm-new-session');

            return;
        }

        $this->currentSession = null;
        $this->currentSessionId = null;
        $this->resetState();
        $this->businessIdea = '';
        $this->generationMode = 'creative';
        $this->deepThinking = false;
        $this->activeTab = 'generate';
        $this->hasUnsavedChanges = false;
    }

    /**
     * Auto-save session data.
     */
    private function autoSaveSession(): void
    {
        $user = Auth::user();
        if (! $user || empty(trim($this->businessIdea))) {
            return;
        }

        $sessionService = app(SessionService::class);

        if ($this->currentSessionId) {
            // Update existing session
            $sessionService->saveSession($user, $this->currentSessionId, [
                'business_description' => $this->businessIdea,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
                'title' => $this->generateSessionTitle(),
            ]);
        } else {
            // Create new session
            $this->currentSession = $sessionService->createSession($user, [
                'title' => $this->generateSessionTitle(),
                'business_description' => $this->businessIdea,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
            ]);
            $this->currentSessionId = $this->currentSession->id;
        }

        // Save results if available
        if (! empty($this->generatedNames)) {
            $this->currentSession->results()->create([
                'generated_names' => $this->generatedNames,
                'domain_results' => $this->domainResults,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
            ]);
        }

        $this->hasUnsavedChanges = false;
    }

    /**
     * Generate a session title from business description.
     */
    private function generateSessionTitle(): string
    {
        $title = trim($this->businessIdea);
        if (empty($title)) {
            return 'New Session '.now()->format('M j, g:i A');
        }

        // Take first 50 characters and clean up
        $title = substr($title, 0, 50);
        $title = trim($title);

        // Add ellipsis if truncated
        if (strlen($this->businessIdea) > 50) {
            $title .= '...';
        }

        return $title;
    }

    /**
     * Load a session by ID.
     */
    public function loadSession(string $sessionId): void
    {
        // Check if there are unsaved changes and this is not the same session
        if ($this->hasUnsavedChanges && $this->currentSessionId !== $sessionId) {
            $this->dispatch('confirm-session-switch', newSessionId: $sessionId);

            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $sessionService = app(SessionService::class);
        $session = $sessionService->loadSession($user, $sessionId);

        if ($session) {
            $this->currentSession = $session;
            $this->currentSessionId = $session->id;
            $this->businessIdea = $session->business_description ?? '';
            $this->generationMode = $session->generation_mode ?? 'creative';
            $this->deepThinking = $session->deep_thinking ?? false;

            // Load results if available
            $latestResult = $session->results()->latest()->first();
            if ($latestResult) {
                $this->generatedNames = $latestResult->generated_names ?? [];
                $this->domainResults = $latestResult->domain_results ?? [];
                $this->showResults = ! empty($this->generatedNames);
                if ($this->showResults) {
                    $this->activeTab = 'results';
                }
            }

            $this->hasUnsavedChanges = false;
        } else {
            $this->dispatch('toast',
                message: 'Session not found',
                type: 'error'
            );
        }
    }

    /**
     * Listen for sidebar events.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    #[On('sessionLoaded')]
    public function onSessionLoaded(array $data): void
    {
        $this->loadSession($data['sessionId']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[On('sessionCreated')]
    public function onSessionCreated(array $data): void
    {
        $this->loadSession($data['sessionId']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[On('sessionDeleted')]
    public function onSessionDeleted(array $data): void
    {
        if ($this->currentSessionId === $data['sessionId']) {
            $this->newSession();
        }
    }

    /**
     * Mark changes as unsaved when form data changes.
     */
    public function updatedBusinessIdea(): void
    {
        $this->hasUnsavedChanges = true;
    }

    public function updatedGenerationMode(): void
    {
        $this->hasUnsavedChanges = true;
    }

    public function updatedDeepThinking(): void
    {
        $this->hasUnsavedChanges = true;
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedNamesForLogos = $this->generatedNames;
        } else {
            $this->selectedNamesForLogos = [];
        }
    }

    /**
     * Load user AI preferences.
     */
    protected function loadUserAIPreferences(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $preferences = UserAIPreferences::findOrCreateForUser($user->id);

        $this->selectedAIModels = $preferences->preferred_models;
        $this->generationMode = $preferences->default_generation_mode;
        $this->deepThinking = $preferences->default_deep_thinking;
        $this->enableModelComparison = $preferences->enable_model_comparison;
    }

    /**
     * Check AI model availability.
     */
    public function checkModelAvailability(): void
    {
        try {
            $aiService = app(PrismAIService::class);

            foreach ($this->availableAIModels as $model) {
                $this->modelAvailability[$model['id']] = $aiService->isModelAvailable($model['id']);
            }
        } catch (Exception $e) {
            Log::warning('Failed to check model availability', ['error' => $e->getMessage()]);
            // Default all to available if check fails
            foreach ($this->availableAIModels as $model) {
                $this->modelAvailability[$model['id']] = true;
            }
        }
    }

    /**
     * Initialize the active model tab when AI results are available.
     */
    public function initializeActiveModelTab(): void
    {
        if (empty($this->activeModelTab) && ! empty($this->aiModelResults) && $this->enableModelComparison) {
            $this->activeModelTab = array_key_first($this->aiModelResults);
        }
    }

    /**
     * Generate names with AI.
     */
    public function generateNamesWithAI(): void
    {
        $this->validate([
            'businessIdea' => 'required|string|max:2000',
            'selectedAIModels' => 'required|array|min:1',
        ]);

        // Check rate limits
        if (! $this->checkAIRateLimits()) {
            $this->errorMessage = 'AI generation rate limit exceeded. Please try again later.';
            $this->dispatch('toast', type: 'error');

            return;
        }

        $this->resetState();
        $this->isGeneratingNames = true;
        $this->errorMessage = null;

        /** @var AIGeneration|null $aiGeneration */
        $aiGeneration = null;

        try {
            $user = Auth::user();
            if (! $user) {
                throw new Exception('User not authenticated');
            }

            // Create AI generation record
            $aiGeneration = AIGeneration::create([
                'user_id' => $user->id,
                'project_id' => null, // Will be set if project is created
                'generation_session_id' => uniqid('gen_'),
                'models_requested' => $this->selectedAIModels,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
                'status' => 'pending',
                'prompt_used' => $this->businessIdea,
            ]);

            $this->currentAIGenerationId = $aiGeneration->id;
            $this->aiGenerationStatus = 'Initializing AI models...';

            // Mark as started
            $aiGeneration->markAsStarted();

            // Dispatch AI generation started event
            $this->dispatch('ai-generation-started', [
                'generationId' => $aiGeneration->id,
                'models' => $this->selectedAIModels,
                'deepThinking' => $this->deepThinking,
            ]);

            // Dispatch deep thinking activation if enabled
            if ($this->deepThinking) {
                $this->dispatch('ai-deep-thinking-activated', [
                    'generationId' => $aiGeneration->id,
                    'message' => 'Enhanced processing activated for higher quality results',
                ]);
            }

            // Generate names using AI service
            $aiService = app(AIGenerationService::class);

            $startTime = microtime(true);
            $results = $aiService->generateWithModels(
                $aiGeneration,
                $this->selectedAIModels,
                $this->businessIdea,
                [
                    'mode' => $this->generationMode,
                    'deep_thinking' => $this->deepThinking,
                ]
            );
            $endTime = microtime(true);

            // Process results
            $this->aiModelResults = $results;
            $allNames = [];

            foreach ($results as $model => $names) {
                $allNames = array_merge($allNames, $names);

                // Update model performance metrics
                $this->updateModelPerformance($model, true, (int) (($endTime - $startTime) * 1000));
            }

            // Remove duplicates and set generated names
            $this->generatedNames = array_unique($allNames);

            // Initialize active model tab for comparison
            $this->initializeActiveModelTab();

            // Mark AI generation as completed
            $aiGeneration->markAsCompleted([
                'names' => $this->generatedNames,
                'model_results' => $results,
            ], [
                'total_time_ms' => (int) (($endTime - $startTime) * 1000),
                'models_used' => array_keys($results),
            ]);

            // Check domain availability
            $this->checkDomainAvailability();
            $this->showResults = true;
            $this->activeTab = 'results';

            // Auto-save session
            $this->autoSaveSession();

            $this->successMessage = 'AI generated '.count($this->generatedNames).' business names!';
            $this->addToSearchHistory();

            // Dispatch completion event
            $this->dispatch('ai-generation-completed', [
                'generationId' => $aiGeneration->id,
                'totalNames' => count($this->generatedNames),
                'modelsUsed' => count(array_keys($results)),
                'processingTime' => (int) (($endTime - $startTime) * 1000),
            ]);

        } catch (Exception $e) {
            Log::error('AI generation failed', ['error' => $e->getMessage()]);

            // Check for specific error types
            if (str_contains($e->getMessage(), 'rate limit') || str_contains($e->getMessage(), 'Rate limit')) {
                $this->errorMessage = 'OpenAI API rate limit reached. Falling back to creative generation...';
                $this->dispatch('show-toast', [
                    'message' => 'API rate limit reached. Using creative generation instead.',
                    'type' => 'warning',
                ]);
            } elseif (str_contains($e->getMessage(), 'insufficient_quota') || str_contains($e->getMessage(), 'quota')) {
                $this->errorMessage = 'OpenAI API quota exceeded. Falling back to creative generation...';
                $this->dispatch('show-toast', [
                    'message' => 'API quota exceeded. Using creative generation instead.',
                    'type' => 'warning',
                ]);
            } else {
                $this->errorMessage = 'AI generation failed. Falling back to creative generation...';
                $this->dispatch('show-toast', [
                    'message' => 'AI generation failed. Using creative generation instead.',
                    'type' => 'info',
                ]);
            }

            // Only try to mark as failed if aiGeneration was created
            if ($aiGeneration !== null) {
                $aiGeneration->markAsFailed($e->getMessage());
            }

            // Dispatch error event
            $this->dispatch('ai-generation-error', [
                'message' => $this->errorMessage,
                'originalError' => $e->getMessage(),
                'generationId' => $aiGeneration?->id,
            ]);

            // Fall back to creative generation using fallback service
            try {
                $fallbackService = app(\App\Services\FallbackNameService::class);
                $this->generatedNames = $fallbackService->generateNames(
                    $this->businessIdea,
                    $this->generationMode,
                    10
                );

                $this->checkDomainAvailability();
                $this->showResults = true;
                $this->activeTab = 'results';
                $this->autoSaveSession();

                $this->dispatch('show-toast', [
                    'message' => 'Generated '.count($this->generatedNames).' creative names using fallback generation!',
                    'type' => 'success',
                ]);

            } catch (Exception $fallbackException) {
                $this->errorMessage = 'All generation methods failed. Please try again later.';
                Log::error('Fallback generation also failed', ['error' => $fallbackException->getMessage()]);
            }
        } finally {
            $this->isGeneratingNames = false;
            $this->currentAIGenerationId = null;
            $this->aiGenerationStatus = '';
        }
    }

    /**
     * Cancel AI generation.
     */
    public function cancelAIGeneration(): void
    {
        if ($this->currentAIGenerationId) {
            $generation = AIGeneration::find($this->currentAIGenerationId);
            if ($generation && $generation->isInProgress()) {
                $generation->update(['status' => 'cancelled']);
            }
        }

        $this->isGeneratingNames = false;
        $this->currentAIGenerationId = null;
        $this->aiGenerationStatus = '';

        $this->dispatch('toast', message: 'AI generation cancelled', type: 'info');
    }

    /**
     * Save AI preferences.
     */
    public function saveAIPreferences(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $preferences = UserAIPreferences::findOrCreateForUser($user->id);

        $preferences->updatePreferredModels($this->selectedAIModels);
        $preferences->update([
            'default_generation_mode' => $this->generationMode,
            'default_deep_thinking' => $this->deepThinking,
            'enable_model_comparison' => $this->enableModelComparison,
        ]);

        $this->dispatch('toast', message: 'AI preferences saved');
    }

    /**
     * Check AI rate limits.
     */
    protected function checkAIRateLimits(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Check if user has exceeded rate limits (10 generations per hour)
        $recentGenerations = AIGeneration::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentGenerations < 10;
    }

    /**
     * Update AI model performance metrics.
     */
    protected function updateModelPerformance(string $modelName, bool $success, int $responseTime): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $performance = AIModelPerformance::findOrCreateForUser($user->id, $modelName);

        // Estimate tokens and cost (simplified - would need actual API response data)
        $estimatedTokens = strlen($this->businessIdea) * 2; // Rough estimate
        $estimatedCost = (int) ($estimatedTokens * 0.01); // Rough cost estimate in cents

        $performance->updateMetrics($responseTime, $estimatedTokens, $estimatedCost, $success);
    }

    /**
     * Check if current session has image context.
     */
    public function hasImageContext(): bool
    {
        if (! $this->currentSession) {
            return false;
        }

        $imageIds = $this->currentSession->image_context_ids ?? [];

        return ! empty($imageIds);
    }

    /**
     * Get count of images used as context.
     */
    public function getImageContextCount(): int
    {
        if (! $this->currentSession) {
            return 0;
        }

        $imageIds = $this->currentSession->image_context_ids ?? [];

        return count($imageIds);
    }

    /**
     * Clear image context from current session.
     */
    public function clearImageContext(): void
    {
        if (! $this->currentSession) {
            return;
        }

        $this->currentSession->update(['image_context_ids' => null]);
    }

    /**
     * Add image to context for current session.
     */
    public function addImageToContext(int $imageId): void
    {
        if (! $this->currentSession) {
            return;
        }

        $currentIds = $this->currentSession->image_context_ids ?? [];

        if (! in_array($imageId, $currentIds)) {
            $currentIds[] = $imageId;
            $this->currentSession->update(['image_context_ids' => $currentIds]);
        }
    }

    /**
     * Remove image from context for current session.
     */
    public function removeImageFromContext(int $imageId): void
    {
        if (! $this->currentSession) {
            return;
        }

        $currentIds = $this->currentSession->image_context_ids ?? [];
        $updatedIds = array_filter($currentIds, fn ($id) => $id !== $imageId);

        $this->currentSession->update(['image_context_ids' => array_values($updatedIds)]);
    }

    public function render(): View
    {
        return view('livewire.name-generator-dashboard');
    }
}
