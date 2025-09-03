<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\UserAIPreferences;
use App\Services\AI\AIGenerationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

/**
 * ProjectPage component for viewing and editing individual projects.
 *
 * Handles project display, inline name editing, description auto-save functionality,
 * AI generation integration, and real-time progress tracking.
 *
 * Dispatched Livewire Events:
 * - ai-generation-started: When AI generation begins
 * - ai-generation-created: When AI generation record is created
 * - ai-generation-completed: When AI generation finishes successfully
 * - ai-generation-failed: When AI generation encounters an error
 * - ai-generation-cancelled: When AI generation is cancelled by user
 * - ai-progress-updated: Real-time progress updates during generation
 * - ai-name-selected: When user selects an AI-generated name
 * - ai-name-deselected: When user deselects a name
 * - ai-model-tab-changed: When user switches between model comparison tabs
 * - ai-preferences-saved: When user saves their AI generation preferences
 * - project-updated: When project data changes requiring sidebar refresh
 * - show-toast: For user notifications and feedback
 */
class ProjectPage extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $editableName = '';

    public string $editableDescription = '';

    public bool $editingName = false;

    public string $resultsFilter = 'visible'; // 'visible', 'hidden', 'all'

    // AI Generation Properties
    public bool $showAIControls = false;

    public bool $useAIGeneration = false;

    /** @var array<int, string> */
    public array $selectedAIModels = [];

    public string $generationMode = '';

    public bool $deepThinking = false;

    public bool $enableModelComparison = false;

    public bool $isGeneratingNames = false;

    public string $errorMessage = '';

    /** @var array<string, array<int, string>> */
    public array $aiGenerationResults = [];

    /** @var array<int, int> */
    public array $selectedSuggestions = [];

    /** @var array<int, AIGeneration> */
    public array $aiGenerationHistory = [];

    public ?int $currentAIGenerationId = null;

    public string $activeModelTab = '';

    /** @var array<string, mixed> */
    public array $realTimeProgress = [];

    /** @var array<string, string> */
    public array $modelStatuses = [];

    /** @var array<string, string> */
    protected $listeners = [
        'name-selected' => 'handleNameSelected',
        'name-deselected' => 'handleNameDeselected',
        'suggestion-hidden' => 'handleSuggestionVisibilityChanged',
        'suggestion-shown' => 'handleSuggestionVisibilityChanged',
        'trigger-auto-generation' => 'handleAutoGeneration',
    ];

    /** @var array<string, string> */
    protected array $rules = [
        'editableName' => 'required|string|min:2|max:255',
        'editableDescription' => 'required|string|min:10|max:2000',
        'selectedAIModels' => 'required_if:useAIGeneration,true|array|min:1',
        'selectedAIModels.*' => 'string|in:gpt-4,claude-3.5-sonnet,gemini-1.5-pro,grok-beta',
        'generationMode' => 'nullable|string|in:creative,professional,brandable,tech-focused',
        'deepThinking' => 'boolean',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'editableName.required' => 'Project name is required',
        'editableName.min' => 'Project name must be at least 2 characters',
        'editableName.max' => 'Project name must be less than 255 characters',
        'editableDescription.required' => 'Project description is required',
        'editableDescription.min' => 'Project description must be at least 10 characters',
        'editableDescription.max' => 'Project description must be less than 2000 characters',
        'selectedAIModels.required_if' => 'Please select at least one AI model when using AI generation',
        'selectedAIModels.min' => 'Please select at least one AI model',
        'selectedAIModels.*.in' => 'Selected AI model is not supported',
        'generationMode.required' => 'Please select a generation style',
        'generationMode.in' => 'Invalid generation mode selected',
    ];

    /**
     * Mount the component with project UUID.
     */
    public function mount(string $uuid): void
    {
        $this->project = Project::where('uuid', $uuid)->firstOrFail();

        // Check if user can view this project
        $this->authorize('view', $this->project);

        $this->editableName = $this->project->name;
        $this->editableDescription = $this->project->description;

        // Load user AI preferences
        $this->loadUserAIPreferences();

        // Load AI generation history for this project
        $this->loadAIGenerationHistory();

        // Check for auto-generation parameter
        if (request()->get('auto_generate') === '1') {
            $this->showAIControls = true;
            $this->useAIGeneration = true;

            // Auto-trigger generation if models are selected
            if (! empty($this->selectedAIModels)) {
                // Use a deferred method to trigger generation after mount completes
                $this->dispatch('trigger-auto-generation');
            }
        }
    }

    /**
     * Handle auto-generation trigger after mount.
     */
    public function handleAutoGeneration(): void
    {
        if ($this->useAIGeneration && ! empty($this->selectedAIModels) && ! $this->isGeneratingNames) {
            $this->generateMoreNames();
        }
    }

    /**
     * Start editing the project name.
     */
    public function editName(): void
    {
        $this->authorize('update', $this->project);
        $this->editingName = true;
        $this->editableName = $this->project->name;
    }

    /**
     * Save the edited project name.
     */
    public function saveName(): void
    {
        $this->authorize('update', $this->project);

        $this->validate(['editableName' => $this->rules['editableName']]);

        $this->project->update(['name' => $this->editableName]);
        $this->editingName = false;

        // Dispatch event to update sidebar
        $this->dispatch('project-updated', $this->project->uuid);
        $this->dispatch('show-toast', [
            'message' => 'Project name updated successfully!',
            'type' => 'success',
        ]);
    }

    /**
     * Cancel name editing and revert changes.
     */
    public function cancelNameEdit(): void
    {
        $this->editingName = false;
        $this->editableName = $this->project->name;
        $this->resetErrorBag('editableName');
    }

    /**
     * Save the project description.
     */
    public function saveDescription(): void
    {
        $this->authorize('update', $this->project);

        $this->validate(['editableDescription' => $this->rules['editableDescription']]);

        $this->project->update(['description' => $this->editableDescription]);

        // Dispatch event to update sidebar
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Auto-save description after typing delay.
     */
    public function autoSaveDescription(): void
    {
        $this->authorize('update', $this->project);

        if (strlen(trim($this->editableDescription)) >= 10) {
            $this->project->update(['description' => $this->editableDescription]);

            // Only show toast for manual save, not auto-save to avoid spam
            // Auto-save feedback is handled via the UI "Auto-saving..." indicator
        }
    }

    /**
     * Get the character count for description.
     */
    public function getDescriptionCharacterCountProperty(): string
    {
        return strlen($this->editableDescription).' / 2000';
    }

    /**
     * Get filtered name suggestions based on current filter.
     *
     * @return Collection<int, \App\Models\NameSuggestion>
     */
    public function getFilteredSuggestionsProperty(): Collection
    {
        $query = $this->project->nameSuggestions();

        return match ($this->resultsFilter) {
            'visible' => $query->where('is_hidden', false)->get(),
            'hidden' => $query->where('is_hidden', true)->get(),
            'all' => $query->get(),
            default => $query->where('is_hidden', false)->get(),
        };
    }

    /**
     * Get the count of suggestions by type.
     *
     * @return array<string, int>
     */
    public function getSuggestionCountsProperty(): array
    {
        $suggestions = $this->project->nameSuggestions;

        return [
            'visible' => $suggestions->where('is_hidden', false)->count(),
            'hidden' => $suggestions->where('is_hidden', true)->count(),
            'total' => $suggestions->count(),
        ];
    }

    /**
     * Set the results filter.
     */
    public function setResultsFilter(string $filter): void
    {
        $this->resultsFilter = $filter;
    }

    /**
     * Handle when a name is selected.
     */
    public function handleNameSelected(int $suggestionId): void
    {
        $suggestion = NameSuggestion::find($suggestionId);

        // Refresh project to get updated selected_name_id
        $this->project = $this->project->fresh();

        // Dispatch name selected event
        if ($suggestion) {
            $this->dispatch('ai-name-selected', [
                'suggestion_id' => $suggestionId,
                'name' => $suggestion->name,
                'project_uuid' => $this->project->uuid,
                'generation_session_id' => $suggestion->ai_generation_session_id,
                'model_used' => $suggestion->ai_model_used,
                'is_ai_generated' => $suggestion->isAiGenerated(),
            ]);
        }

        // Dispatch event to update sidebar if name changed
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Handle when a name is deselected.
     */
    public function handleNameDeselected(int $suggestionId): void
    {
        $suggestion = NameSuggestion::find($suggestionId);

        // Refresh project to get updated selected_name_id
        $this->project = $this->project->fresh();

        // Dispatch name deselected event
        if ($suggestion) {
            $this->dispatch('ai-name-deselected', [
                'suggestion_id' => $suggestionId,
                'name' => $suggestion->name,
                'project_uuid' => $this->project->uuid,
                'generation_session_id' => $suggestion->ai_generation_session_id,
                'model_used' => $suggestion->ai_model_used,
                'is_ai_generated' => $suggestion->isAiGenerated(),
            ]);
        }

        // Dispatch event to update sidebar
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Called when active model tab is updated.
     */
    public function updatedActiveModelTab(): void
    {
        // Dispatch model tab changed event
        $this->dispatch('ai-model-tab-changed', [
            'model_id' => $this->activeModelTab,
            'project_uuid' => $this->project->uuid,
            'generation_id' => $this->currentAIGenerationId,
            'available_models' => array_keys($this->aiGenerationResults),
        ]);
    }

    /**
     * Handle when suggestion visibility changes.
     */
    public function handleSuggestionVisibilityChanged(int $suggestionId): void
    {
        // Force refresh of computed properties by re-rendering
        $this->render();
    }

    // AI Generation Methods

    /**
     * Load user AI preferences.
     */
    protected function loadUserAIPreferences(): void
    {
        $preferences = UserAIPreferences::where('user_id', auth()->id())->first();

        if ($preferences) {
            $this->selectedAIModels = $preferences->preferred_models ?? [];
            $this->generationMode = $preferences->default_generation_mode ?? '';
            $this->deepThinking = $preferences->default_deep_thinking ?? false;
            $this->enableModelComparison = $preferences->enable_model_comparison ?? false;
        } else {
            // Apply smart recommendations for users without saved preferences
            $recommendations = $this->getModelRecommendations();

            if ($recommendations['based_on_generations'] > 0) {
                // User has history, use smart recommendations
                $this->selectedAIModels = array_slice($recommendations['recommended_models'], 0, 2);
                $this->enableModelComparison = $recommendations['based_on_generations'] > 3;

                // Dispatch event for analytics
                $this->dispatch('smart-recommendations-auto-applied', [
                    'user_id' => auth()->id(),
                    'project_uuid' => $this->project->uuid,
                    'recommendations' => $recommendations,
                    'auto_selected_models' => $this->selectedAIModels,
                ]);
            } else {
                // New user, use default recommendations
                $this->selectedAIModels = ['gpt-4'];
                $this->enableModelComparison = false;
            }

            // Set other defaults
            $this->generationMode = '';
            $this->deepThinking = false;
        }
    }

    /**
     * Load AI generation history for this project.
     */
    protected function loadAIGenerationHistory(): void
    {
        $this->aiGenerationHistory = AIGeneration::where('project_id', $this->project->id)
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Initialize the active model tab when AI results are available.
     */
    public function initializeActiveModelTab(): void
    {
        if (empty($this->activeModelTab) && ! empty($this->aiGenerationResults) && $this->enableModelComparison) {
            $this->activeModelTab = array_key_first($this->aiGenerationResults);
        }
    }

    /**
     * Update real-time progress for AI generation.
     */
    public function updateProgress(): void
    {
        if (! $this->currentAIGenerationId) {
            return;
        }

        $aiGeneration = AIGeneration::find($this->currentAIGenerationId);

        if (! $aiGeneration || ! $aiGeneration->isInProgress()) {
            $this->currentAIGenerationId = null;
            $this->realTimeProgress = [];
            $this->modelStatuses = [];

            return;
        }

        // Get current progress from generation metadata
        $metadata = $aiGeneration->execution_metadata ?? [];
        $this->modelStatuses = $metadata['model_status'] ?? [];

        // Calculate overall progress
        $totalModels = count($aiGeneration->models_requested ?? []);
        $completedModels = collect($this->modelStatuses)->filter(fn ($status) => in_array($status, ['completed', 'failed']))->count();

        $this->realTimeProgress = [
            'overall_progress' => $totalModels > 0 ? round(($completedModels / $totalModels) * 100) : 0,
            'active_models' => collect($this->modelStatuses)->filter(fn ($status) => $status === 'running')->count(),
            'completed_models' => collect($this->modelStatuses)->filter(fn ($status) => $status === 'completed')->count(),
            'failed_models' => collect($this->modelStatuses)->filter(fn ($status) => $status === 'failed')->count(),
            'elapsed_time' => $aiGeneration->started_at ? now()->diffInSeconds($aiGeneration->started_at) : 0,
        ];

        // Dispatch detailed progress event
        $this->dispatch('ai-progress-updated', [
            'generation_id' => $this->currentAIGenerationId,
            'project_uuid' => $this->project->uuid,
            'progress' => $this->realTimeProgress,
            'model_statuses' => $this->modelStatuses,
            'models_requested' => $aiGeneration->models_requested ?? [],
        ]);
    }

    /**
     * Get progress status for a specific model.
     *
     * @return array<string, mixed>
     */
    public function getModelProgress(string $modelId): array
    {
        $status = $this->modelStatuses[$modelId] ?? 'pending';
        $progressPercent = match ($status) {
            'pending' => 0,
            'running' => 50,
            'completed' => 100,
            'failed' => 100,
            'cancelled' => 50,
            default => 0,
        };

        return [
            'status' => $status,
            'progress' => $progressPercent,
            'color' => match ($status) {
                'pending' => 'gray',
                'running' => 'blue',
                'completed' => 'green',
                'failed' => 'red',
                'cancelled' => 'orange',
                default => 'gray',
            },
        ];
    }

    /**
     * Get model progress data for all models.
     *
     * @return array<string, mixed>
     */
    public function getModelProgressData(): array
    {
        $progressData = [];

        foreach ($this->selectedAIModels as $modelId) {
            $progressData[$modelId] = $this->getModelProgress($modelId);
        }

        return $progressData;
    }

    /**
     * Toggle generation mode selection/deselection.
     */
    public function toggleGenerationMode(string $mode): void
    {
        // Validate that the mode is valid
        $validModes = ['creative', 'professional', 'brandable', 'tech-focused'];
        
        if (! in_array($mode, $validModes)) {
            // Invalid mode, do nothing
            return;
        }
        
        // If the same mode is already selected, deselect it
        if ($this->generationMode === $mode) {
            $this->generationMode = '';
        } else {
            // Otherwise, select the new mode
            $this->generationMode = $mode;
        }
    }

    /**
     * Generate more names using AI with project context.
     */
    public function generateMoreNames(): void
    {
        $this->authorize('update', $this->project);

        // Validate AI generation settings
        if ($this->useAIGeneration) {
            // Require generation mode when generating names
            $rules = $this->rules;
            $rules['generationMode'] = 'required|string|in:creative,professional,brandable,tech-focused';
            
            $this->validate([
                'selectedAIModels' => $rules['selectedAIModels'],
                'selectedAIModels.*' => $rules['selectedAIModels.*'],
                'generationMode' => $rules['generationMode'],
                'deepThinking' => $rules['deepThinking'],
            ]);
        }

        $this->isGeneratingNames = true;
        $this->errorMessage = '';
        $this->aiGenerationResults = [];

        // Dispatch generation started event
        $this->dispatch('ai-generation-started', [
            'project_uuid' => $this->project->uuid,
            'models' => $this->selectedAIModels,
            'mode' => $this->generationMode,
            'deep_thinking' => $this->deepThinking,
        ]);

        // Dispatch deep thinking activation if enabled
        if ($this->deepThinking) {
            $this->dispatch('ai-deep-thinking-activated', [
                'project_uuid' => $this->project->uuid,
                'message' => 'Enhanced processing activated for higher quality results',
            ]);
        }

        try {
            // Create contextual prompt using project data
            $contextualPrompt = $this->buildContextualPrompt();

            // Use AI generation service
            $aiService = app(AIGenerationService::class);

            // Create AI generation record
            $aiGeneration = AIGeneration::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'generation_session_id' => 'session_'.uniqid(),
                'models_requested' => $this->selectedAIModels,
                'generation_mode' => $this->generationMode,
                'deep_thinking' => $this->deepThinking,
                'prompt_used' => $contextualPrompt,
                'status' => 'running',
                'started_at' => now(),
            ]);

            $this->currentAIGenerationId = $aiGeneration->id;

            // Dispatch generation record created event
            $this->dispatch('ai-generation-created', [
                'generation_id' => $aiGeneration->id,
                'session_id' => $aiGeneration->generation_session_id,
                'project_uuid' => $this->project->uuid,
            ]);

            // Generate names using multiple models
            $results = $aiService->generateWithModels(
                $aiGeneration,
                $this->selectedAIModels,
                $contextualPrompt,
                [
                    'mode' => $this->generationMode,
                    'deep_thinking' => $this->deepThinking,
                ]
            );

            $this->aiGenerationResults = $results;

            // Create NameSuggestion records from AI results
            $this->createNameSuggestionsFromAI($results, $aiGeneration);

            // Update generation status
            $aiGeneration->update([
                'status' => 'completed',
                'results_data' => $results,
                'total_names_generated' => count(collect($results)->flatten()),
                'completed_at' => now(),
            ]);

            // Initialize active model tab for comparison
            $this->initializeActiveModelTab();

            // Dispatch generation completed event
            $totalNamesGenerated = count(collect($results)->flatten());
            $this->dispatch('ai-generation-completed', [
                'generation_id' => $aiGeneration->id,
                'session_id' => $aiGeneration->generation_session_id,
                'project_uuid' => $this->project->uuid,
                'results' => $results,
                'totalNames' => $totalNamesGenerated,
                'modelsUsed' => count($this->selectedAIModels),
                'elapsed_time_seconds' => $aiGeneration->getDurationInSeconds(),
            ]);

            $this->dispatch('show-toast', [
                'message' => 'Generated '.count(collect($results)->flatten()).' new names!',
                'type' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('AI generation failed in ProjectPage', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            // Check for specific error types
            if (str_contains($e->getMessage(), 'rate limit') || str_contains($e->getMessage(), 'Rate limit')) {
                $this->errorMessage = 'OpenAI API rate limit reached. Please wait a moment and try again, or consider upgrading your OpenAI plan for higher limits.';
                $this->dispatch('show-toast', [
                    'message' => 'API rate limit reached. Try again in a few minutes.',
                    'type' => 'warning',
                ]);
            } elseif (str_contains($e->getMessage(), 'insufficient_quota') || str_contains($e->getMessage(), 'quota')) {
                $this->errorMessage = 'OpenAI API quota exceeded. Please check your billing and usage limits in your OpenAI account.';
                $this->dispatch('show-toast', [
                    'message' => 'API quota exceeded. Check your OpenAI account billing.',
                    'type' => 'error',
                ]);
            } else {
                $this->errorMessage = 'AI generation failed: '.$e->getMessage();
                $this->dispatch('show-toast', [
                    'message' => 'Generation failed. Please try again.',
                    'type' => 'error',
                ]);
            }

            if (isset($aiGeneration)) {
                $aiGeneration->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                // Dispatch generation failed event
                $this->dispatch('ai-generation-error', [
                    'generation_id' => $aiGeneration->id,
                    'session_id' => $aiGeneration->generation_session_id,
                    'project_uuid' => $this->project->uuid,
                    'message' => $this->errorMessage,
                    'originalError' => $e->getMessage(),
                    'models_attempted' => $this->selectedAIModels,
                    'elapsed_time_seconds' => $aiGeneration->getDurationInSeconds(),
                ]);
            }

            $this->dispatch('show-toast', [
                'message' => 'AI generation failed. Please try again.',
                'type' => 'error',
            ]);
        } finally {
            $this->isGeneratingNames = false;
            $this->currentAIGenerationId = null;
        }
    }

    /**
     * Build contextual prompt using project data.
     */
    protected function buildContextualPrompt(): string
    {
        $prompt = "Generate business names for a project called '{$this->project->name}'. ";
        $prompt .= "Description: {$this->project->description}. ";

        // Add context from selected names
        if ($this->project->selectedName) {
            $prompt .= "The user has already selected '{$this->project->selectedName->name}' as their preferred name, so generate similar variations and alternatives. ";
        }

        // Add context from existing suggestions
        $existingNames = $this->project->nameSuggestions()
            ->where('is_hidden', false)
            ->limit(5)
            ->pluck('name')
            ->toArray();

        if (! empty($existingNames)) {
            $prompt .= 'Existing suggestions include: '.implode(', ', $existingNames).'. Generate different but complementary options. ';
        }

        return $prompt;
    }

    /**
     * Create NameSuggestion records from AI results.
     *
     * @param  array<string, array<int, string>>  $results
     */
    protected function createNameSuggestionsFromAI(array $results, AIGeneration $aiGeneration): void
    {
        foreach ($results as $modelName => $names) {
            foreach ($names as $name) {
                NameSuggestion::create([
                    'project_id' => $this->project->id,
                    'name' => $name,
                    'generation_metadata' => [
                        'ai_model' => $modelName,
                        'generation_mode' => $this->generationMode,
                        'deep_thinking' => $this->deepThinking,
                        'ai_generation_id' => $aiGeneration->id,
                        'generated_at' => now()->toISOString(),
                    ],
                ]);
            }
        }
    }

    /**
     * Cancel AI generation in progress with partial result preservation.
     */
    public function cancelAIGeneration(): void
    {
        $partialResults = [];

        if ($this->currentAIGenerationId) {
            $aiGeneration = AIGeneration::find($this->currentAIGenerationId);
            if ($aiGeneration && $aiGeneration->user_id === auth()->id()) {
                // Collect partial results from completed models
                $partialResults = $this->collectPartialResults($aiGeneration);

                // Create name suggestions from any completed results
                if (! empty($partialResults)) {
                    $this->createNameSuggestionsFromAI($partialResults, $aiGeneration);
                    $this->aiGenerationResults = $partialResults;
                }

                // Update generation status with preservation info
                $aiGeneration->update([
                    'status' => 'cancelled',
                    'results_data' => $partialResults,
                    'total_names_generated' => count(collect($partialResults)->flatten()),
                    'completed_at' => now(),
                    'execution_metadata' => array_merge(
                        $aiGeneration->execution_metadata ?? [],
                        ['cancelled_with_partial_results' => ! empty($partialResults)]
                    ),
                ]);

                // Cancel running jobs (clear cache to prevent further processing)
                $this->cancelRunningJobs($aiGeneration);

                // Dispatch generation cancelled event
                $this->dispatch('ai-generation-cancelled', [
                    'generation_id' => $aiGeneration->id,
                    'session_id' => $aiGeneration->generation_session_id,
                    'project_uuid' => $this->project->uuid,
                    'partial_results_preserved' => ! empty($partialResults),
                    'partial_results_count' => ! empty($partialResults) ? count(collect($partialResults)->flatten()) : 0,
                    'models_requested' => $aiGeneration->models_requested,
                    'elapsed_time_seconds' => $aiGeneration->getDurationInSeconds(),
                ]);
            }
        }

        $this->isGeneratingNames = false;
        $this->currentAIGenerationId = null;
        $this->realTimeProgress = [];
        $this->modelStatuses = [];

        $completedCount = ! empty($partialResults) ? count(collect($partialResults)->flatten()) : 0;
        $message = $completedCount > 0
            ? "Generation cancelled. Saved {$completedCount} names from completed models."
            : 'AI generation cancelled';

        $this->dispatch('show-toast', [
            'message' => $message,
            'type' => $completedCount > 0 ? 'success' : 'info',
        ]);
    }

    /**
     * Collect partial results from completed models.
     *
     * @return array<string, array<string>>
     */
    protected function collectPartialResults(AIGeneration $aiGeneration): array
    {
        $partialResults = [];
        $modelsRequested = $aiGeneration->models_requested ?? [];

        foreach ($modelsRequested as $modelId) {
            $cacheKey = "ai_generation_result_{$aiGeneration->id}_{$modelId}";
            $cachedResult = Cache::get($cacheKey);

            if ($cachedResult && $cachedResult['status'] === 'completed' && ! empty($cachedResult['results'])) {
                $partialResults[$modelId] = $cachedResult['results'];
            }
        }

        return $partialResults;
    }

    /**
     * Cancel running jobs for the generation.
     */
    protected function cancelRunningJobs(AIGeneration $aiGeneration): void
    {
        $modelsRequested = $aiGeneration->models_requested ?? [];

        foreach ($modelsRequested as $modelId) {
            $cacheKey = "ai_generation_result_{$aiGeneration->id}_{$modelId}";

            // Mark as cancelled in cache to prevent job completion
            Cache::put($cacheKey, [
                'model_id' => $modelId,
                'results' => [],
                'execution_time_ms' => 0,
                'names_generated' => 0,
                'status' => 'cancelled',
                'cancelled_at' => now()->toISOString(),
            ], 600);
        }

        // Update model statuses to cancelled for incomplete jobs
        $metadata = $aiGeneration->execution_metadata ?? [];
        $modelStatuses = $metadata['model_status'] ?? [];

        foreach ($modelStatuses as $modelId => $status) {
            if (in_array($status, ['pending', 'running'])) {
                $modelStatuses[$modelId] = 'cancelled';
            }
        }

        $metadata['model_status'] = $modelStatuses;
        $aiGeneration->update(['execution_metadata' => $metadata]);
    }

    /**
     * Bulk hide selected suggestions.
     */
    public function bulkHideSuggestions(): void
    {
        $this->authorize('update', $this->project);

        NameSuggestion::whereIn('id', $this->selectedSuggestions)
            ->where('project_id', $this->project->id)
            ->update(['is_hidden' => true]);

        $count = count($this->selectedSuggestions);
        $this->selectedSuggestions = [];

        $this->dispatch('show-toast', [
            'message' => "Hidden {$count} suggestions",
            'type' => 'info',
        ]);
    }

    /**
     * Bulk show selected suggestions.
     */
    public function bulkShowSuggestions(): void
    {
        $this->authorize('update', $this->project);

        NameSuggestion::whereIn('id', $this->selectedSuggestions)
            ->where('project_id', $this->project->id)
            ->update(['is_hidden' => false]);

        $count = count($this->selectedSuggestions);
        $this->selectedSuggestions = [];

        $this->dispatch('show-toast', [
            'message' => "Restored {$count} suggestions",
            'type' => 'success',
        ]);
    }

    /**
     * Regenerate names for selected suggestions.
     */
    public function regenerateSelectedNames(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedSuggestions)) {
            $this->dispatch('show-toast', [
                'message' => 'Please select suggestions to regenerate',
                'type' => 'warning',
            ]);

            return;
        }

        // Use the selected names as context for regeneration
        $selectedNames = NameSuggestion::whereIn('id', $this->selectedSuggestions)
            ->where('project_id', $this->project->id)
            ->pluck('name')
            ->toArray();

        // Generate new names based on selected ones
        $this->generateMoreNames();
    }

    /**
     * Save user AI preferences.
     */
    public function saveAIPreferences(): void
    {
        UserAIPreferences::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'preferred_models' => $this->selectedAIModels,
                'default_generation_mode' => $this->generationMode,
                'default_deep_thinking' => $this->deepThinking,
                'enable_model_comparison' => $this->enableModelComparison,
            ]
        );

        // Dispatch preferences saved event
        $this->dispatch('ai-preferences-saved', [
            'user_id' => auth()->id(),
            'project_uuid' => $this->project->uuid,
            'preferred_models' => $this->selectedAIModels,
            'generation_mode' => $this->generationMode,
            'deep_thinking' => $this->deepThinking,
            'model_comparison' => $this->enableModelComparison,
        ]);

        $this->dispatch('show-toast', [
            'message' => 'AI preferences saved',
            'type' => 'success',
        ]);
    }

    /**
     * Get model recommendations based on user preferences and performance.
     *
     * @return array<string, mixed>
     */
    public function getModelRecommendations(): array
    {
        $userId = auth()->id();

        // Get user's historical AI generations
        $userGenerations = AIGeneration::where('user_id', $userId)
            ->where('status', 'completed')
            ->with('user')
            ->get();

        $modelScores = [];
        $totalGenerations = $userGenerations->count();

        if ($totalGenerations === 0) {
            // Return default recommendations for new users
            return $this->getDefaultModelRecommendations();
        }

        // Analyze each model's performance
        foreach (['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro', 'grok-beta'] as $modelId) {
            $modelGenerations = $userGenerations->filter(fn ($generation) => in_array($modelId, $generation->models_requested ?? []));

            if ($modelGenerations->count() === 0) {
                $modelScores[$modelId] = 0;

                continue;
            }

            // Calculate multiple scoring factors
            $usageFrequency = $modelGenerations->count() / $totalGenerations;
            $averageResponseTime = $modelGenerations->avg('total_response_time_ms') ?? 3000;
            $averageNamesGenerated = $modelGenerations->avg('total_names_generated') ?? 5;
            $successRate = $modelGenerations->where('status', 'completed')->count() / $modelGenerations->count();

            // Calculate user satisfaction based on name selection patterns
            $satisfactionScore = $this->calculateModelSatisfactionScore($modelId, $modelGenerations);

            // Weighted scoring algorithm
            $score = (
                ($usageFrequency * 0.3) +
                ($satisfactionScore * 0.4) +
                ($successRate * 0.2) +
                (1 / max($averageResponseTime / 1000, 1) * 0.05) + // Faster is better
                (min($averageNamesGenerated / 10, 1) * 0.05) // More names is better (up to 10)
            ) * 100;

            $modelScores[$modelId] = round($score, 2);
        }

        // Sort by score descending
        arsort($modelScores);

        return [
            'recommended_models' => array_keys(array_slice($modelScores, 0, 3, true)),
            'model_scores' => $modelScores,
            'based_on_generations' => $totalGenerations,
        ];
    }

    /**
     * Calculate satisfaction score based on user's name selection patterns.
     * Uses generation session data and project final selections as satisfaction indicators.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, AIGeneration>  $modelGenerations
     */
    protected function calculateModelSatisfactionScore(string $modelId, $modelGenerations): float
    {
        $totalSessions = 0;
        $satisfactionPoints = 0;

        foreach ($modelGenerations as $generation) {
            $totalSessions++;

            // Check if this generation session resulted in project name selection
            $sessionSuggestions = NameSuggestion::where('ai_generation_session_id', $generation->generation_session_id)
                ->where('ai_model_used', $modelId)
                ->count();

            if ($sessionSuggestions > 0) {
                // Award satisfaction points based on names generated count (more = better satisfaction)
                $namesGenerated = $generation->total_names_generated ?? 0;
                $sessionSatisfaction = min($namesGenerated / 10, 1.0); // Normalize to 0-1

                // Bonus points for faster response times (under 2 seconds gets full bonus)
                $responseTime = $generation->total_response_time_ms ?? 3000;
                $speedBonus = max(0, (3000 - $responseTime) / 3000 * 0.2); // Up to 20% bonus

                $satisfactionPoints += $sessionSatisfaction + $speedBonus;
            }
        }

        return $totalSessions > 0 ? min($satisfactionPoints / $totalSessions, 1.0) : 0.5; // Default to neutral
    }

    /**
     * Get default model recommendations for new users.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultModelRecommendations(): array
    {
        return [
            'recommended_models' => ['gpt-4', 'claude-3.5-sonnet', 'gemini-1.5-pro'],
            'model_scores' => [
                'gpt-4' => 85,
                'claude-3.5-sonnet' => 80,
                'gemini-1.5-pro' => 75,
                'grok-beta' => 70,
            ],
            'based_on_generations' => 0,
        ];
    }

    /**
     * Apply smart model selection based on user preferences.
     */
    public function applySmartModelSelection(): void
    {
        $recommendations = $this->getModelRecommendations();

        // Auto-select top 2 recommended models for efficiency
        $this->selectedAIModels = array_slice($recommendations['recommended_models'], 0, 2);

        // Enable comparison if user historically uses multiple models
        if ($recommendations['based_on_generations'] > 3) {
            $this->enableModelComparison = true;
        }

        // Dispatch event for analytics
        $this->dispatch('smart-model-selection-applied', [
            'user_id' => auth()->id(),
            'project_uuid' => $this->project->uuid,
            'recommendations' => $recommendations,
            'selected_models' => $this->selectedAIModels,
        ]);

        $this->dispatch('show-toast', [
            'message' => 'Applied smart model selection based on your preferences',
            'type' => 'info',
        ]);
    }

    /**
     * Handle Livewire serialization to prevent toJSON errors.
     */
    protected function serializeProperty(string $property): mixed
    {
        if ($this->$property instanceof Project) {
            return $this->$property->id;
        }

        if ($this->$property instanceof \Illuminate\Database\Eloquent\Collection) {
            return $this->$property->pluck('id')->toArray();
        }

        if ($this->$property instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return [
                'items' => $this->$property->getCollection()->pluck('id')->toArray(),
                'current_page' => $this->$property->currentPage(),
                'total' => $this->$property->total(),
            ];
        }

        return $this->$property;
    }

    /**
     * Handle Livewire hydration to restore objects from serialized data.
     */
    protected function hydrateProperty(string $property, mixed $value): mixed
    {
        if ($property === 'project' && is_int($value)) {
            return Project::find($value);
        }

        // Don't hydrate computed properties - let them be computed fresh
        if (in_array($property, ['filteredSuggestions', 'suggestionCounts'])) {
            return null;
        }

        return $value;
    }

    public function render(): View
    {
        return view('livewire.project-page')
            ->layout('components.layouts.project-workflow');
    }
}
