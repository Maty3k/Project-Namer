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

    public bool $bulkDeleteMode = false;

    /** @var array<string> */
    public array $selectedProjects = [];

    public bool $showBulkDeleteConfirmation = false;

    /** @var array<string, string> */
    protected $listeners = [
        'project-created' => 'refreshProjectsOnly',
        'project-updated' => 'refreshProjectsOnly',
        'project-deleted' => 'refreshProjects',
        'name-selected' => 'refreshProjectsOnly',
        'name-deselected' => 'refreshProjectsOnly',
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

        // Restore collapsed state from session
        $this->collapsed = session('sidebar.collapsed', false);

        // On mobile, default to collapsed if no session exists
        if (! session()->has('sidebar.collapsed')) {
            if (request()->header('User-Agent') && preg_match('/Mobile|Android|iPhone/i', request()->header('User-Agent'))) {
                $this->collapsed = true;
                session(['sidebar.collapsed' => true]);
            }
        }

        if ($this->activeProjectUuid) {
            $this->selectedProject = Project::where('uuid', $this->activeProjectUuid)
                ->where('user_id', Auth::id())
                ->first();
        }
    }

    /**
     * Hydrate the component and restore collapsed state from session.
     */
    public function hydrate(): void
    {
        // Always restore from session on every request
        $this->collapsed = session('sidebar.collapsed', false);
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
            ->withCount(['nameSuggestions as favorited_names_count' => function ($query): void {
                $query->where('is_favorited', true);
            }])
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

        // Save to session
        session(['sidebar.collapsed' => $this->collapsed]);
    }

    /**
     * Refresh projects list without affecting sidebar state.
     */
    public function refreshProjectsOnly(?string $projectUuid = null): void
    {
        // Trigger component refresh to update the projects list.
        // The collapsed state is now managed by Alpine + localStorage,
        // so it will persist across this refresh.
        $this->dispatch('$refresh');
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
            $this->dispatch('toast', message: 'Project not found', type: 'error');

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
            $this->dispatch('toast', message: 'Project not found', type: 'error');
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

            $this->dispatch('toast', message: "Project '{$projectName}' deleted successfully", type: 'success');
            $this->dispatch('project-deleted', $this->projectToDelete);

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete project: '.$e->getMessage(), type: 'error');
        } finally {
            $this->cancelDeleteProject();
        }
    }

    /**
     * Toggle bulk delete mode.
     */
    public function toggleBulkDeleteMode(): void
    {
        $this->bulkDeleteMode = ! $this->bulkDeleteMode;

        // Clear selections when exiting bulk delete mode
        if (! $this->bulkDeleteMode) {
            $this->selectedProjects = [];
        }
    }

    /**
     * Toggle project selection for bulk delete.
     */
    public function toggleProjectSelection(string $uuid): void
    {
        if (in_array($uuid, $this->selectedProjects)) {
            $this->selectedProjects = array_values(array_filter(
                $this->selectedProjects,
                fn ($id) => $id !== $uuid
            ));
        } else {
            $this->selectedProjects[] = $uuid;
        }
    }

    /**
     * Select all projects for bulk delete.
     */
    public function selectAllProjects(): void
    {
        $this->selectedProjects = $this->getProjectsProperty()
            ->pluck('uuid')
            ->toArray();
    }

    /**
     * Deselect all projects.
     */
    public function deselectAllProjects(): void
    {
        $this->selectedProjects = [];
    }

    /**
     * Show bulk delete confirmation modal.
     */
    public function confirmBulkDelete(): void
    {
        if (empty($this->selectedProjects)) {
            $this->dispatch('toast', message: 'No projects selected', type: 'error');

            return;
        }

        $this->showBulkDeleteConfirmation = true;
    }

    /**
     * Cancel bulk delete operation.
     */
    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteConfirmation = false;
    }

    /**
     * Execute bulk delete operation.
     */
    public function bulkDeleteProjects(): void
    {
        if (empty($this->selectedProjects)) {
            $this->dispatch('toast', message: 'No projects selected', type: 'error');
            $this->cancelBulkDelete();

            return;
        }

        $deletedCount = 0;
        $wasActiveProjectDeleted = false;

        try {
            $projects = Project::whereIn('uuid', $this->selectedProjects)
                ->where('user_id', Auth::id())
                ->get();

            foreach ($projects as $project) {
                if ($this->isActiveProject($project)) {
                    $wasActiveProjectDeleted = true;
                }

                $project->delete();
                $deletedCount++;
            }

            $message = $deletedCount === 1
                ? '1 project deleted successfully'
                : "{$deletedCount} projects deleted successfully";

            $this->dispatch('toast', message: $message, type: 'success');
            $this->dispatch('project-deleted');

            // Reset state
            $this->selectedProjects = [];
            $this->bulkDeleteMode = false;
            $this->showBulkDeleteConfirmation = false;

            // If active project was deleted, redirect to dashboard
            if ($wasActiveProjectDeleted) {
                $this->activeProjectUuid = null;
                $this->selectedProject = null;
                $this->redirect(route('dashboard'));
            }

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete projects: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Handle theme updates by clearing cache and refreshing component.
     */
    public function onThemeUpdated(): void
    {
        // Clear theme cache to ensure fresh theme data
        ThemeHelper::clearUserThemeCache();

        // Refresh the component to apply new theme
        $this->dispatch('$refresh');
    }

    public function render(): View
    {
        return view('livewire.sidebar');
    }
}
