<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Sidebar component for project navigation.
 *
 * Displays user's projects in chronological order with active project highlighting
 * and real-time updates via Livewire events.
 */
class Sidebar extends Component
{
    public ?string $activeProjectUuid = null;

    public bool $collapsed = false;

    public ?Project $selectedProject = null;

    /** @var array<string, string> */
    protected $listeners = [
        'project-created' => 'refreshProjects',
        'project-updated' => 'refreshProjects',
        'project-deleted' => 'refreshProjects',
        'name-selected' => 'refreshProjects',
        'name-deselected' => 'refreshProjects',
    ];

    /**
     * Mount the component with optional active project UUID.
     */
    public function mount(?string $activeProjectUuid = null): void
    {
        $this->activeProjectUuid = $activeProjectUuid;

        if ($this->activeProjectUuid) {
            $this->selectedProject = Project::where('uuid', $this->activeProjectUuid)
                ->where('user_id', Auth::id())
                ->first();
        }
    }

    /**
     * Get user's projects ordered chronologically (newest first).
     *
     * @return Collection<int, Project>
     */
    public function getProjectsProperty(): Collection
    {
        return Project::where('user_id', Auth::id())
            ->with('selectedName')
            ->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the count of user's projects.
     */
    public function getProjectCountProperty(): int
    {
        return $this->getProjectsProperty()->count();
    }

    /**
     * Navigate to create new project page.
     */
    public function createNewProject(): void
    {
        $this->redirect('/dashboard');
    }

    /**
     * Navigate to specific project page.
     */
    public function selectProject(string $uuid): void
    {
        $this->redirect("/project/{$uuid}");
    }

    /**
     * Toggle sidebar collapse state.
     */
    public function toggleCollapse(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    /**
     * Refresh projects list when events are received.
     */
    public function refreshProjects(?string $projectUuid = null): void
    {
        // Computed properties are automatically refreshed on the next access
        // No explicit action needed for Livewire computed properties

        // Update selected project if it was the affected one
        if ($projectUuid && $this->activeProjectUuid === $projectUuid) {
            $this->selectedProject = Project::where('uuid', $projectUuid)
                ->where('user_id', Auth::id())
                ->first();
        }
    }

    /**
     * Check if a project is the currently active one.
     */
    public function isActiveProject(Project $project): bool
    {
        return $this->activeProjectUuid === $project->uuid;
    }

    /**
     * Truncate project name for display.
     */
    public function truncateName(string $name, int $length = 25): string
    {
        return strlen($name) > $length ? substr($name, 0, $length).'...' : $name;
    }

    public function render(): View
    {
        return view('livewire.sidebar');
    }
}
