<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NamingSession;
use App\Services\SessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SessionSidebar extends Component
{
    public string $searchQuery = '';

    public bool $isCollapsed = false;

    public bool $showStarredOnly = false;

    public int $limit = 20;

    public int $offset = 0;

    public ?string $renamingSessionId = null;

    public string $renameText = '';

    protected function getSessionService(): SessionService
    {
        return app(SessionService::class);
    }

    public function mount(): void
    {
        $this->loadInitialState();
    }

    /**
     * @return Collection<int, NamingSession>
     *
     * @phpstan-return Collection<int, NamingSession>
     */
    #[Computed]
    public function sessions(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            /** @var Collection<int, NamingSession> */
            return collect();
        }

        $sessionService = $this->getSessionService();

        // If searching, return search results
        if (! empty($this->searchQuery)) {
            return $sessionService->searchSessions($user, $this->searchQuery);
        }

        // If filtering starred only
        if ($this->showStarredOnly) {
            return $sessionService->filterSessions($user, ['is_starred' => true]);
        }

        // Return paginated sessions
        return $sessionService->getUserSessions($user, $this->limit, 0);
    }

    /**
     * @return array<string, array<int, NamingSession>>
     *
     * @phpstan-return array<string, array<int, NamingSession>>
     */
    #[Computed]
    public function groupedSessions(): array
    {
        $sessions = $this->sessions();
        $grouped = [];

        foreach ($sessions as $session) {
            $dateGroup = $session->getDateGroup();
            if (! isset($grouped[$dateGroup])) {
                $grouped[$dateGroup] = [];
            }
            $grouped[$dateGroup][] = $session;
        }

        return $grouped;
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPagination();
    }

    public function createNewSession(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $session = $this->getSessionService()->createSession($user, [
            'title' => 'New Session '.now()->format('M j, g:i A'),
            'business_description' => null,
            'generation_mode' => 'creative',
        ]);

        $this->dispatch('sessionCreated', ['sessionId' => $session->id]);
    }

    public function loadSession(string $sessionId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $session = $this->getSessionService()->loadSession($user, $sessionId);

        if ($session) {
            $this->dispatch('sessionLoaded', ['sessionId' => $session->id]);
        } else {
            $this->dispatch('sessionLoadError');
        }
    }

    public function deleteSession(string $sessionId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $deleted = $this->getSessionService()->deleteSession($user, $sessionId);

        if ($deleted) {
            $this->dispatch('sessionDeleted', ['sessionId' => $sessionId]);
        }
    }

    public function duplicateSession(string $sessionId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $duplicatedSession = $this->getSessionService()->duplicateSession($user, $sessionId);

        if ($duplicatedSession) {
            $this->dispatch('sessionCreated', ['sessionId' => $duplicatedSession->id]);
        }
    }

    public function toggleStar(string $sessionId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $session = $user->namingSessions()->find($sessionId);

        if ($session) {
            $session->toggleStarred();
        }
    }

    public function startRename(string $sessionId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $session = $user->namingSessions()->find($sessionId);

        if ($session) {
            $this->renamingSessionId = $sessionId;
            $this->renameText = $session->title;
        }
    }

    public function saveRename(string $sessionId, string $newTitle): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->getSessionService()->saveSession($user, $sessionId, ['title' => $newTitle]);
        $this->cancelRename();
    }

    public function cancelRename(): void
    {
        $this->renamingSessionId = null;
        $this->renameText = '';
    }

    public function toggleStarredFilter(): void
    {
        $this->showStarredOnly = ! $this->showStarredOnly;
        $this->resetPagination();
    }

    public function loadMore(): void
    {
        $this->limit += 20;
    }

    /**
     * Clear the search query.
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
    }

    public function toggleSidebar(): void
    {
        $this->isCollapsed = ! $this->isCollapsed;
    }

    public function toggleFocusMode(): void
    {
        $this->isCollapsed = ! $this->isCollapsed;
        $this->dispatch('focusModeToggled', ['enabled' => $this->isCollapsed]);
    }

    protected function loadInitialState(): void
    {
        // Load collapsed state from localStorage via JavaScript
        $this->dispatch('loadSidebarState');
    }

    protected function resetPagination(): void
    {
        $this->limit = 20;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.session-sidebar', [
            'groupedSessions' => $this->groupedSessions(),
            'sessions' => $this->sessions(),
        ]);
    }
}
