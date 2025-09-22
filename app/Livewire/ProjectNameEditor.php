<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Simple, focused component for editing project names.
 */
class ProjectNameEditor extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $name = '';

    public bool $editing = false;

    /** @var array<string, string> */
    protected array $rules = [
        'name' => 'required|string|min:2|max:255',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'name.required' => 'Project name is required',
        'name.min' => 'Project name must be at least 2 characters',
        'name.max' => 'Project name must be less than 255 characters',
    ];

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
        $this->name = $project->name;
    }

    public function startEdit(): void
    {
        $this->authorize('update', $this->project);
        $this->editing = true;
        $this->name = $this->project->name;
    }

    public function save(): void
    {
        $this->authorize('update', $this->project);

        $this->validate();

        $this->project->update(['name' => $this->name]);

        $this->editing = false;

        $this->dispatch('project-updated', $this->project->uuid);
        $this->dispatch('show-toast', [
            'message' => 'Project name updated successfully!',
            'type' => 'success',
        ]);
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->name = $this->project->name;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.project-name-editor');
    }
}
