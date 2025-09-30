<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AIGeneration;
use App\Models\NameSuggestion;
use App\Models\Project;
use App\Models\UserAIPreferences;
use App\Services\AIGenerationService;
use App\Services\DomainCheckService;
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

    /** @var \Illuminate\Database\Eloquent\Collection<int, AIGeneration> */
    public ?\Illuminate\Database\Eloquent\Collection $aiGenerationHistory = null;

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
        'refresh-suggestions' => 'handleSuggestionsRefresh',
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
     * Boot the component - called before mount.
     */
    public function boot(): void
    {
        // Initialize default values to prevent serialization issues
        $this->realTimeProgress = [];
        $this->modelStatuses = [];
        $this->aiGenerationResults = [];
    }

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

        // Check for auto-generation parameter - only trigger once per session
        if (request()->get('auto_generate') === '1' && ! session()->has('auto_generated_'.$this->project->id)) {
            $this->showAIControls = true;
            $this->useAIGeneration = true;

            // Mark as auto-generated to prevent repeated triggers
            session()->put('auto_generated_'.$this->project->id, true);

            // Auto-trigger generation if models are selected and no names exist yet
            if (! empty($this->selectedAIModels) && $this->getFilteredSuggestionsProperty()->isEmpty()) {
                // Use a deferred method to trigger generation after mount completes
                $this->dispatch('trigger-auto-generation');
            }
        }
    }

    /**
     * Handle auto-generation trigger after mount.
     * Shows confirmation instead of auto-generating.
     */
    public function handleAutoGeneration(): void
    {
        // Additional safeguards to prevent unwanted generation
        if ($this->useAIGeneration
            && ! empty($this->selectedAIModels)
            && ! $this->isGeneratingNames
            && $this->getFilteredSuggestionsProperty()->isEmpty()
            && session()->has('auto_generated_'.$this->project->id)) {

            // Remove the session flag to prevent future auto-generation
            session()->forget('auto_generated_'.$this->project->id);

            // Show confirmation instead of auto-generating
            if (! empty($this->generationMode)) {
                $this->showGenerationConfirmation($this->generationMode);
            } else {
                // If no mode selected, still show a generic confirmation
                $this->dispatch('show-generation-confirmation', [
                    'mode' => 'default',
                    'message' => 'Generate business names now?',
                ]);
            }
        }
    }

    /**
     * Reset auto-generation flag for this project.
     * This allows the auto_generate parameter to work again if needed.
     */
    public function resetAutoGeneration(): void
    {
        session()->forget('auto_generated_'.$this->project->id);
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

        try {
            $this->project->update(['name' => $this->editableName]);
            $this->editingName = false;

            // Dispatch event to update sidebar
            $this->dispatch('project-updated', $this->project->uuid);
            $this->dispatch('show-toast', [
                'message' => 'Project name updated successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving project name: '.$e->getMessage());
            $this->dispatch('show-toast', [
                'message' => 'Error saving project name. Please try again.',
                'type' => 'error',
            ]);
        }
    }

    /**
     * Cancel name editing and revert changes.
     */
    public function cancelNameEdit(): void
    {
        try {
            $this->editingName = false;
            $this->editableName = $this->project->name;
            $this->resetErrorBag('editableName');
        } catch (\Exception $e) {
            \Log::error('Error canceling name edit: '.$e->getMessage());
            // Force reset to safe state
            $this->editingName = false;
            $this->editableName = $this->project->fresh()->name ?? '';
            $this->resetErrorBag();
        }
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
        $suggestions = $this->project->nameSuggestions;

        return match ($this->resultsFilter) {
            'visible' => $suggestions->where('is_hidden', false)->values(),
            'hidden' => $suggestions->where('is_hidden', true)->values(),
            'all' => $suggestions->values(),
            default => $suggestions->where('is_hidden', false)->values(),
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
        if (! in_array($filter, ['visible', 'hidden', 'all'])) {
            $this->addError('resultsFilter', 'Invalid filter value. Must be one of: visible, hidden, all');

            return;
        }

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

    /**
     * Handle suggestions refresh after AI generation.
     */
    public function handleSuggestionsRefresh(): void
    {
        // Force refresh the project data and re-render
        $this->project = $this->project->fresh(['nameSuggestions']);

        // Use JavaScript to trigger a Livewire component refresh
        $this->js('setTimeout(() => { $wire.$refresh(); }, 100);');
    }

    /**
     * Select a name suggestion.
     */
    public function selectName(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        $suggestion = NameSuggestion::find($suggestionId);

        if (! $suggestion || $suggestion->project_id !== $this->project->id) {
            $this->dispatch('show-toast', [
                'message' => 'Name suggestion not found',
                'type' => 'error',
            ]);

            return;
        }

        $this->project->update(['selected_name_id' => $suggestionId]);
        $this->project = $this->project->fresh(['selectedName']);

        $this->dispatch('show-toast', [
            'message' => "Selected '{$suggestion->name}' as your project name!",
            'type' => 'success',
        ]);

        $this->dispatch('name-selected', $suggestionId);
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Deselect the current name suggestion.
     */
    public function deselectName(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        if ($this->project->selected_name_id === $suggestionId) {
            $this->project->update(['selected_name_id' => null]);
            $this->project = $this->project->fresh(['selectedName']);

            $this->dispatch('show-toast', [
                'message' => 'Name deselected',
                'type' => 'info',
            ]);

            $this->dispatch('name-deselected', $suggestionId);
            $this->dispatch('project-updated', $this->project->uuid);
        }
    }

    /**
     * Hide a name suggestion.
     */
    public function hideSuggestion(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        $suggestion = NameSuggestion::find($suggestionId);

        if (! $suggestion || $suggestion->project_id !== $this->project->id) {
            return;
        }

        $suggestion->update(['is_hidden' => true]);

        $this->dispatch('show-toast', [
            'message' => "Hidden '{$suggestion->name}'",
            'type' => 'info',
        ]);

        $this->dispatch('suggestion-hidden', $suggestionId);
    }

    /**
     * Show a hidden name suggestion.
     */
    public function showSuggestion(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        $suggestion = NameSuggestion::find($suggestionId);

        if (! $suggestion || $suggestion->project_id !== $this->project->id) {
            return;
        }

        $suggestion->update(['is_hidden' => false]);

        $this->dispatch('show-toast', [
            'message' => "Restored '{$suggestion->name}'",
            'type' => 'success',
        ]);

        $this->dispatch('suggestion-shown', $suggestionId);
    }

    /**
     * Delete a name suggestion.
     */
    public function deleteSuggestion(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        $suggestion = NameSuggestion::find($suggestionId);

        if (! $suggestion || $suggestion->project_id !== $this->project->id) {
            return;
        }

        $suggestionName = $suggestion->name;

        // If this was the selected name, deselect it
        if ($this->project->selected_name_id === $suggestionId) {
            $this->project->update(['selected_name_id' => null]);
        }

        $suggestion->delete();

        // Refresh the project to update the suggestions
        $this->project = $this->project->fresh(['nameSuggestions', 'selectedName']);

        $this->dispatch('show-toast', [
            'message' => "Deleted '{$suggestionName}'",
            'type' => 'success',
        ]);
    }

    // AI Generation Methods

    /**
     * Load user AI preferences.
     */
    protected function loadUserAIPreferences(): void
    {
        $preferences = UserAIPreferences::where('user_id', auth()->id())->first();

        if ($preferences) {
            // Never pre-select models - let user choose from scratch
            $this->selectedAIModels = [];
            $this->deepThinking = $preferences->default_deep_thinking ?? false;
            $this->enableModelComparison = $preferences->enable_model_comparison ?? false;
        } else {
            // Default settings for users without saved preferences - no models pre-selected
            $this->selectedAIModels = [];
            $this->enableModelComparison = false;
            $this->deepThinking = false;
        }

        // Always start with no generation mode selected
        $this->generationMode = '';
    }

    /**
     * Load AI generation history for this project.
     */
    public function loadAIGenerationHistory(): void
    {
        $this->aiGenerationHistory = AIGeneration::where('project_id', $this->project->id)
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();
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

            return;
        }

        // Otherwise, select the new mode
        $this->generationMode = $mode;

        // Show confirmation dialog before auto-generating
        $this->showGenerationConfirmation($mode);
    }

    /**
     * Show confirmation dialog for generation.
     */
    public function showGenerationConfirmation(string $mode): void
    {
        // Dispatch event to show confirmation modal in the frontend
        $this->dispatch('show-generation-confirmation', [
            'mode' => $mode,
            'message' => "Generate names using {$mode} style?",
        ]);
    }

    /**
     * Confirm and start generation with the selected mode.
     */
    public function confirmGeneration(): void
    {
        // Only generate if AI generation is enabled and mode is selected
        if ($this->useAIGeneration && ! empty($this->generationMode)) {
            $this->generateMoreNames();
        }
    }

    /**
     * Cancel generation and reset mode.
     */
    public function cancelGeneration(): void
    {
        $this->generationMode = '';
    }

    /**
     * Generate more names using AI with project context.
     */
    public function generateMoreNames(): void
    {
        $this->authorize('update', $this->project);

        // Check if generation mode is selected
        if ($this->useAIGeneration && empty($this->generationMode)) {
            // Show AI controls and display helpful message
            $this->showAIControls = true;
            $this->dispatch('show-toast', [
                'message' => 'Please select a generation style first',
                'type' => 'info',
            ]);

            return;
        }

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
            $response = $aiService->generateNamesParallel(
                $contextualPrompt,
                $this->selectedAIModels,
                $this->generationMode,
                $this->deepThinking
            );

            // Extract just the results (model => names mapping)
            $this->aiGenerationResults = $response['results'] ?? [];

            // Create NameSuggestion records from AI results
            $this->createNameSuggestionsFromAI($this->aiGenerationResults, $aiGeneration);

            // Update generation status
            $aiGeneration->update([
                'status' => 'completed',
                'results_data' => $this->aiGenerationResults,
                'total_names_generated' => count(collect($this->aiGenerationResults)->flatten()),
                'completed_at' => now(),
            ]);

            // Refresh the project relationship to get updated suggestions
            $this->project->load('nameSuggestions');

            // Force refresh of the project from database to ensure we have the latest data
            $this->project = $this->project->fresh(['nameSuggestions']);

            // Trigger a client-side refresh after a short delay to ensure proper rendering
            $this->dispatch('refresh-suggestions');

            // Initialize active model tab for comparison
            $this->initializeActiveModelTab();

            // Dispatch generation completed event
            $totalNamesGenerated = count(collect($this->aiGenerationResults)->flatten());
            $this->dispatch('ai-generation-completed', [
                'generation_id' => $aiGeneration->id,
                'session_id' => $aiGeneration->generation_session_id,
                'project_uuid' => $this->project->uuid,
                'results' => $this->aiGenerationResults,
                'totalNames' => $totalNamesGenerated,
                'modelsUsed' => count($this->selectedAIModels),
                'elapsed_time_seconds' => $aiGeneration->getDurationInSeconds(),
            ]);

            $this->dispatch('show-toast', [
                'message' => 'Generated '.count(collect($this->aiGenerationResults)->flatten()).' new names!',
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
     * @param  array<string, array<string, mixed>>  $results
     */
    protected function createNameSuggestionsFromAI(array $results, AIGeneration $aiGeneration): void
    {
        // First pass: collect all valid names from all models with their metadata
        $allNamesWithMetadata = [];

        foreach ($results as $modelName => $modelResult) {
            // Extract names from the model result structure
            $names = $modelResult['names'] ?? [];

            // Filter out any non-string entries (like explanatory text)
            $validNames = array_filter($names, function ($name) {
                return is_string($name) &&
                       strlen(trim($name)) > 0 &&
                       strlen(trim($name)) <= 100 && // Reasonable name length limit
                       ! str_contains(strtolower($name), 'here are') && // Filter out explanatory text
                       ! str_contains(strtolower($name), 'business names') &&
                       ! str_contains(strtolower($name), 'information about') &&
                       ! str_contains(strtolower($name), 'need more') &&
                       preg_match('/^[a-zA-Z0-9\s\-\.&]+$/', $name); // Only allow reasonable characters
            });

            foreach ($validNames as $name) {
                $trimmedName = trim((string) $name);

                // Skip if empty after trimming
                if (empty($trimmedName)) {
                    continue;
                }

                // Store with normalized key for deduplication
                $normalizedKey = strtolower(str_replace([' ', '-', '.', '&'], '', $trimmedName));

                // Only keep the first occurrence of each name (first model gets priority)
                if (! isset($allNamesWithMetadata[$normalizedKey])) {
                    $allNamesWithMetadata[$normalizedKey] = [
                        'name' => $trimmedName,
                        'model' => $modelName,
                        'metadata' => $modelResult,
                    ];
                }
            }
        }

        // Second pass: create NameSuggestion records for unique names only
        foreach ($allNamesWithMetadata as $nameData) {
            // Generate domain list for this name
            $domains = $this->generateDomainsForName($nameData['name']);

            NameSuggestion::create([
                'project_id' => $this->project->id,
                'name' => $nameData['name'],
                'domains' => $domains,
                'generation_metadata' => [
                    'ai_model' => $nameData['model'],
                    'generation_mode' => $this->generationMode,
                    'deep_thinking' => $this->deepThinking,
                    'ai_generation_id' => $aiGeneration->id,
                    'generated_at' => now()->toISOString(),
                    'model_metadata' => [
                        'status' => $nameData['metadata']['status'] ?? 'unknown',
                        'response_time_ms' => $nameData['metadata']['response_time_ms'] ?? 0,
                        'cached' => $nameData['metadata']['cached'] ?? false,
                        'fallback_used' => $nameData['metadata']['fallback_used'] ?? false,
                    ],
                    'deduplication_info' => [
                        'unique_across_models' => true,
                        'first_generated_by' => $nameData['model'],
                    ],
                ],
            ]);
        }
    }

    /**
     * Generate domain list for a given name.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function generateDomainsForName(string $name): array
    {
        $tlds = ['com', 'net', 'org', 'io', 'co', 'app', 'dev', 'ai', 'tech', 'studio'];
        $domains = [];

        // Sanitize the name for domain use - remove spaces and special characters
        $sanitizedName = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $name));

        // Create kebab-case version by adding hyphens between words
        $kebabCaseName = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
        $kebabCaseName = preg_replace('/[^a-zA-Z0-9\-]/', '-', $kebabCaseName);
        $kebabCaseName = preg_replace('/\-+/', '-', (string) $kebabCaseName);
        $kebabCaseName = trim((string) $kebabCaseName, '-');

        foreach ($tlds as $tld) {
            // Add concatenated version (original behavior)
            $concatenatedDomain = $sanitizedName.'.'.$tld;
            $domains[$concatenatedDomain] = [
                'extension' => '.'.$tld,
                'available' => null, // Will be checked later
                'status' => 'pending',
            ];

            // Add kebab-case version if different from concatenated
            if ($kebabCaseName !== $sanitizedName && ! empty($kebabCaseName)) {
                $kebabDomain = $kebabCaseName.'.'.$tld;
                $domains[$kebabDomain] = [
                    'extension' => '.'.$tld,
                    'available' => null, // Will be checked later
                    'status' => 'pending',
                ];
            }
        }

        return $domains;
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

                    // Refresh the project relationship to get updated suggestions
                    $this->project->load('nameSuggestions');

                    // Force refresh of the project from database to ensure we have the latest data
                    $this->project = $this->project->fresh(['nameSuggestions']);

                    // Trigger a client-side refresh after a short delay to ensure proper rendering
                    $this->dispatch('refresh-suggestions');
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
     * Delete a single AI generation with confirmation.
     */
    public function deleteAIGeneration(int $generationId): void
    {
        $this->authorize('update', $this->project);

        $generation = AIGeneration::find($generationId);

        if (! $generation) {
            $this->dispatch('show-toast', [
                'message' => 'AI generation not found',
                'type' => 'error',
            ]);

            return;
        }

        if (! $generation->canBeDeletedBy(auth()->user())) {
            $this->dispatch('show-toast', [
                'message' => 'You cannot delete this AI generation',
                'type' => 'error',
            ]);

            return;
        }

        if ($generation->deleteWithCleanup()) {
            // Refresh the AI generation history
            $this->loadAIGenerationHistory();

            $this->dispatch('show-toast', [
                'message' => 'AI generation deleted successfully',
                'type' => 'success',
            ]);

            $this->dispatch('ai-generation-deleted', [
                'generation_id' => $generationId,
                'project_uuid' => $this->project->uuid,
            ]);
        } else {
            $this->dispatch('show-toast', [
                'message' => 'Failed to delete AI generation',
                'type' => 'error',
            ]);
        }
    }

    /**
     * Delete multiple AI generations in bulk.
     *
     * @param  array<int>  $generationIds
     */
    public function bulkDeleteAIGenerations(array $generationIds): void
    {
        $this->authorize('update', $this->project);

        if (empty($generationIds)) {
            $this->dispatch('show-toast', [
                'message' => 'No AI generations selected for deletion',
                'type' => 'error',
            ]);

            return;
        }

        $deletedCount = AIGeneration::bulkDeleteWithCleanup($generationIds, auth()->user());

        if ($deletedCount > 0) {
            // Refresh the AI generation history
            $this->loadAIGenerationHistory();

            $this->dispatch('show-toast', [
                'message' => "Successfully deleted {$deletedCount} AI generation(s)",
                'type' => 'success',
            ]);

            $this->dispatch('ai-generations-bulk-deleted', [
                'deleted_count' => $deletedCount,
                'project_uuid' => $this->project->uuid,
            ]);
        } else {
            $this->dispatch('show-toast', [
                'message' => 'No AI generations were deleted',
                'type' => 'warning',
            ]);
        }
    }

    /**
     * Delete all completed AI generations for this project.
     */
    public function deleteAllCompletedGenerations(): void
    {
        $this->authorize('update', $this->project);

        $generationIds = AIGeneration::forProject($this->project->id)
            ->forUser(auth()->id())
            ->whereNotIn('status', ['pending', 'running'])
            ->pluck('id')
            ->toArray();

        if (empty($generationIds)) {
            $this->dispatch('show-toast', [
                'message' => 'No completed AI generations to delete',
                'type' => 'info',
            ]);

            return;
        }

        $this->bulkDeleteAIGenerations($generationIds);
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

        // Skip serializing computed properties to prevent circular serialization issues
        if (in_array($property, [
            'filteredSuggestions',
            'suggestionCounts',
            'descriptionCharacterCount',
            'modelRecommendations',
        ])) {
            return null;
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

        if ($property === 'aiGenerationHistory' && is_array($value)) {
            $this->loadAIGenerationHistory();

            return $this->aiGenerationHistory;
        }

        // Don't hydrate computed properties - let them be computed fresh
        if (in_array($property, [
            'filteredSuggestions',
            'suggestionCounts',
            'descriptionCharacterCount',
            'modelRecommendations',
        ])) {
            return null;
        }

        return $value;
    }

    /**
     * Livewire dehydrate hook to clean up before serialization.
     */
    public function dehydrate(): void
    {
        // Ensure collections are properly cleaned before serialization
        if (isset($this->aiGenerationHistory)) {
            // Force collection to be fresh for next hydration
            $this->aiGenerationHistory = null;
        }
    }

    /**
     * Livewire hydrate hook to restore state after deserialization.
     */
    public function hydrate(): void
    {
        // Ensure arrays are properly initialized
        $this->realTimeProgress ??= [];
        $this->modelStatuses ??= [];
        $this->aiGenerationResults ??= [];

        // Reload AI generation history if not present
        if ($this->aiGenerationHistory === null) {
            $this->loadAIGenerationHistory();
        }

        // Ensure editing properties are properly initialized (only if truly unset, not empty)
        if (! isset($this->editableName)) {
            $this->editableName = $this->project->name ?? '';
        }
        if (! isset($this->editableDescription)) {
            $this->editableDescription = $this->project->description ?? '';
        }
    }

    /**
     * Get model recommendations based on user's historical AI generation performance.
     *
     * @return array{recommended_models: array<string>, model_scores: array<string, float>, based_on_generations: int}
     */
    public function getModelRecommendations(): array
    {
        // Get user's recent AI generations for analysis
        $recentGenerations = AIGeneration::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'partially_completed'])
            ->where('created_at', '>', now()->subDays(30)) // Last 30 days
            ->get();

        if ($recentGenerations->isEmpty()) {
            // Return default recommendations if no history
            return [
                'recommended_models' => ['gpt-4'],
                'model_scores' => ['gpt-4' => 100.0],
                'based_on_generations' => 0,
            ];
        }

        $modelPerformance = [];

        foreach ($recentGenerations as $generation) {
            $modelsRequested = $generation->models_requested ?? [];

            foreach ($modelsRequested as $modelId) {
                if (! isset($modelPerformance[$modelId])) {
                    $modelPerformance[$modelId] = [
                        'total_names' => 0,
                        'total_suggestions' => 0,
                        'total_response_time' => 0,
                        'total_generations' => 0,
                        'success_rate' => 0,
                    ];
                }

                $modelPerformance[$modelId]['total_generations']++;
                $modelPerformance[$modelId]['total_names'] += $generation->total_names_generated ?? 0;
                $modelPerformance[$modelId]['total_response_time'] += $generation->total_response_time_ms ?? 0;

                // Count actual suggestions created from this generation
                $suggestionsCount = NameSuggestion::where('ai_generation_session_id', $generation->generation_session_id)
                    ->where('ai_model_used', $modelId)
                    ->count();

                $modelPerformance[$modelId]['total_suggestions'] += $suggestionsCount;

                // Update success rate
                if ($generation->status === 'completed') {
                    $modelPerformance[$modelId]['success_rate']++;
                }
            }
        }

        // Calculate scores for each model
        $modelScores = [];
        foreach ($modelPerformance as $modelId => $performance) {
            // Note: total_generations will always be >= 1 since we only create entries when incrementing
            $totalGenerations = $performance['total_generations'];

            $avgNamesPerGeneration = $performance['total_names'] / $totalGenerations;
            $avgSuggestionsPerGeneration = $performance['total_suggestions'] / $totalGenerations;
            $avgResponseTime = $performance['total_response_time'] / $totalGenerations;
            $successRate = $performance['success_rate'] / $totalGenerations;

            // Calculate composite score (weighted by different factors)
            $score = 0;
            $score += $avgNamesPerGeneration * 20; // Names generated weight
            $score += $avgSuggestionsPerGeneration * 30; // Suggestions created weight
            $score += $successRate * 40; // Success rate weight
            $score += max(0, (5000 - $avgResponseTime) / 50); // Response time weight (lower is better)

            $modelScores[$modelId] = round($score, 2);
        }

        // Sort models by score (highest first)
        arsort($modelScores);

        // Get top recommended models
        $recommendedModels = array_keys(array_slice($modelScores, 0, 3, true));

        return [
            'recommended_models' => $recommendedModels,
            'model_scores' => $modelScores,
            'based_on_generations' => $recentGenerations->count(),
        ];
    }

    /**
     * Check domains for a specific suggestion when user expands the dropdown.
     */
    public function checkDomainsForSuggestion(int $suggestionId): void
    {
        $this->authorize('update', $this->project);

        $suggestion = NameSuggestion::where('project_id', $this->project->id)
            ->where('id', $suggestionId)
            ->first();

        if (! $suggestion) {
            $this->dispatch('show-toast', [
                'message' => 'Name suggestion not found',
                'type' => 'error',
            ]);

            return;
        }

        // Check if domains are already populated and checked
        if ($suggestion->domains && ! empty($suggestion->domains)) {
            $hasUncheckedDomains = false;
            foreach ($suggestion->domains as $domainData) {
                if (! isset($domainData['available'])) {
                    $hasUncheckedDomains = true;
                    break;
                }
            }

            // If all domains are already checked, don't check again
            if (! $hasUncheckedDomains) {
                return;
            }
        }

        try {
            $domainService = app(DomainCheckService::class);
            $checkedDomains = $domainService->checkBusinessName($suggestion->name);

            // Update the suggestion with checked domains
            $suggestion->domains = $checkedDomains;
            $suggestion->save();

            // Refresh the project relationship to show updated data
            $this->project->load('nameSuggestions');

            $this->dispatch('show-toast', [
                'message' => 'Domain availability updated',
                'type' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::warning('Domain checking failed for suggestion', [
                'suggestion_id' => $suggestionId,
                'name' => $suggestion->name,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'message' => 'Domain checking temporarily unavailable',
                'type' => 'warning',
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.project-page')
            ->layout('components.layouts.project-workflow');
    }
}
