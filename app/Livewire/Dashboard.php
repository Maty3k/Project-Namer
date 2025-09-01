<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Dashboard component for project workflow UI.
 *
 * Handles project creation with validation and redirects to project page.
 */
class Dashboard extends Component
{
    public string $description = '';

    /** @var array<string, string> */
    protected array $rules = [
        'description' => 'required|string|min:10|max:2000',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'description.required' => 'Please describe your project',
        'description.min' => 'Project description must be at least 10 characters',
        'description.max' => 'Project description must be less than 2000 characters',
    ];

    /**
     * Create a new project with the provided description.
     */
    public function createProject(): void
    {
        $this->validate();

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $project = Project::create([
            'description' => $this->description,
            'user_id' => $user->id,
        ]);

        // Clear the description field
        $this->description = '';

        // Dispatch event to update sidebar
        $this->dispatch('project-created', $project->uuid);

        // Redirect to the project page
        $this->redirect("/project/{$project->uuid}");
    }

    public function render(): View
    {
        return view('livewire.dashboard')
            ->layout('components.layouts.project-workflow');
    }
}
