<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\ThemeHelper;
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

    public ?string $projectToDelete = null;

    public bool $showDeleteConfirmation = false;

    public mixed $userTheme = null;

    /** @var array<string, string> */
    protected $listeners = [
        'project-created' => 'refreshProjects',
        'project-updated' => 'refreshProjects',
        'project-deleted' => 'refreshProjects',
        'name-selected' => 'refreshProjects',
        'name-deselected' => 'refreshProjects',
        'theme-updated' => 'onThemeUpdated',
        'theme-applied' => 'onThemeUpdated',
        'theme-saved' => 'onThemeUpdated',
    ];

    /**
     * Mount the component with optional active project UUID.
     */
    public function mount(?string $activeProjectUuid = null): void
    {
        $this->activeProjectUuid = $activeProjectUuid;
        $this->userTheme = ThemeHelper::getCurrentUserTheme();

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

        // Add JavaScript to enhance animations with GPU acceleration
        $this->js('
            const sidebar = document.querySelector(".themed-sidebar");
            if (sidebar) {
                // Enable GPU acceleration for smoother animations
                sidebar.style.transform = "translateZ(0)";
                sidebar.style.willChange = "transform, width, opacity";

                // Add a temporary class for enhanced animation
                sidebar.classList.add("transitioning");

                // Remove the transitioning class after animation completes
                setTimeout(() => {
                    sidebar.classList.remove("transitioning");
                    sidebar.style.willChange = "auto";
                }, 600);
            }
        ');
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

        return $this->$property;
    }

    /**
     * Handle Livewire hydration to restore objects from serialized data.
     */
    protected function hydrateProperty(string $property, mixed $value): mixed
    {
        if ($property === 'selectedProject' && is_int($value)) {
            return Project::find($value);
        }

        // Don't hydrate computed properties - let them be computed fresh
        if (in_array($property, ['projects', 'projectCount'])) {
            return null;
        }

        return $value;
    }

    /**
     * Show delete confirmation modal for a project.
     */
    public function confirmDeleteProject(string $uuid): void
    {
        $project = Project::where('uuid', $uuid)
            ->where('user_id', Auth::id())
            ->first();

        if (! $project) {
            $this->dispatch('show-toast', [
                'message' => 'Project not found',
                'type' => 'error',
            ]);

            return;
        }

        $this->projectToDelete = $uuid;
        $this->showDeleteConfirmation = true;
    }

    /**
     * Cancel the delete operation.
     */
    public function cancelDeleteProject(): void
    {
        $this->projectToDelete = null;
        $this->showDeleteConfirmation = false;
    }

    /**
     * Delete the confirmed project.
     */
    public function deleteProject(): void
    {
        if (! $this->projectToDelete) {
            return;
        }

        $project = Project::where('uuid', $this->projectToDelete)
            ->where('user_id', Auth::id())
            ->first();

        if (! $project) {
            $this->dispatch('show-toast', [
                'message' => 'Project not found',
                'type' => 'error',
            ]);
            $this->cancelDeleteProject();

            return;
        }

        $projectName = $project->name;
        $wasActiveProject = $this->isActiveProject($project);

        try {
            $project->delete();

            // If we deleted the active project, redirect to dashboard
            if ($wasActiveProject) {
                $this->activeProjectUuid = null;
                $this->selectedProject = null;
                $this->dispatch('project-deleted', $this->projectToDelete);
                $this->redirect(route('dashboard'));

                return;
            }

            $this->dispatch('show-toast', [
                'message' => "Project '{$projectName}' deleted successfully",
                'type' => 'success',
            ]);
            $this->dispatch('project-deleted', $this->projectToDelete);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to delete project: '.$e->getMessage(),
                'type' => 'error',
            ]);
        } finally {
            $this->cancelDeleteProject();
        }
    }

    /**
     * Handle theme updates by clearing cache and refreshing component.
     */
    public function onThemeUpdated(): void
    {
        // Clear theme cache to ensure fresh theme data
        ThemeHelper::clearUserThemeCache();

        // Update the userTheme property with fresh data
        $this->userTheme = ThemeHelper::getCurrentUserTheme();

        // Refresh the component to apply new theme
        $this->dispatch('$refresh');
    }

    public function render(): View
    {
        return view('livewire.sidebar');
    }
}
