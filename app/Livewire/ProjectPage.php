<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * ProjectPage component for viewing and editing individual projects.
 *
 * Handles project display, inline name editing, and description auto-save functionality.
 */
class ProjectPage extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $editableName = '';

    public string $editableDescription = '';

    public bool $editingName = false;

    public string $resultsFilter = 'visible'; // 'visible', 'hidden', 'all'

    /** @var array<string, string> */
    protected $listeners = [
        'name-selected' => 'handleNameSelected',
        'name-deselected' => 'handleNameDeselected',
        'suggestion-hidden' => 'handleSuggestionVisibilityChanged',
        'suggestion-shown' => 'handleSuggestionVisibilityChanged',
    ];

    /** @var array<string, string> */
    protected array $rules = [
        'editableName' => 'required|string|min:2|max:255',
        'editableDescription' => 'required|string|min:10|max:2000',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'editableName.required' => 'Project name is required',
        'editableName.min' => 'Project name must be at least 2 characters',
        'editableName.max' => 'Project name must be less than 255 characters',
        'editableDescription.required' => 'Project description is required',
        'editableDescription.min' => 'Project description must be at least 10 characters',
        'editableDescription.max' => 'Project description must be less than 2000 characters',
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
        // Refresh project to get updated selected_name_id
        $this->project = $this->project->fresh();

        // Dispatch event to update sidebar if name changed
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Handle when a name is deselected.
     */
    public function handleNameDeselected(int $suggestionId): void
    {
        // Refresh project to get updated selected_name_id
        $this->project = $this->project->fresh();

        // Dispatch event to update sidebar
        $this->dispatch('project-updated', $this->project->uuid);
    }

    /**
     * Handle when suggestion visibility changes.
     */
    public function handleSuggestionVisibilityChanged(int $suggestionId): void
    {
        // Force refresh of computed properties by re-rendering
        $this->render();
    }

    public function render(): View
    {
        return view('livewire.project-page')
            ->layout('components.layouts.project-workflow');
    }
}
