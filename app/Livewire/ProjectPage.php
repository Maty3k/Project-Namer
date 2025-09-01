<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
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
        }
    }

    /**
     * Get the character count for description.
     */
    public function getDescriptionCharacterCountProperty(): string
    {
        return strlen($this->editableDescription).' / 2000';
    }

    public function render(): View
    {
        return view('livewire.project-page')
            ->layout('components.layouts.project-workflow');
    }
}
