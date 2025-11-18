<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Share;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ShareManagement component for managing user shares.
 *
 * Provides a dashboard for viewing, filtering, sorting, and managing shares
 * with pagination, search, and analytics display.
 */
final class ShareManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'all';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    /**
     * Delete a share.
     */
    public function deleteShare(int $shareId): void
    {
        $share = Share::where('id', $shareId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $share) {
            abort(403);
        }

        $share->delete();

        $this->dispatch('share-deleted');
        $this->dispatch('show-toast', [
            'message' => 'Share deleted successfully!',
            'type' => 'success',
        ]);
    }

    /**
     * Toggle share active status.
     */
    public function toggleShareStatus(int $shareId): void
    {
        $share = Share::where('id', $shareId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $share) {
            abort(403);
        }

        $share->is_active = ! $share->is_active;
        $share->save();

        $this->dispatch('status-toggled');
        $this->dispatch('show-toast', [
            'message' => 'Share status updated!',
            'type' => 'success',
        ]);
    }

    /**
     * Copy share URL to clipboard.
     */
    public function copyShareUrl(int $shareId): void
    {
        $share = Share::where('id', $shareId)
            ->where('user_id', auth()->id())
            ->first();

        if ($share) {
            $this->dispatch('url-copied', url: $share->getShareUrl());
        }
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    /**
     * Reset page when search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset page when filter changes.
     */
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Get shares with filtering, sorting, and pagination.
     */
    public function getSharesProperty(): LengthAwarePaginator
    {
        return Share::query()
            ->where('user_id', auth()->id())
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus === 'active', function (Builder $query): void {
                $query->where('is_active', true);
            })
            ->when($this->filterStatus === 'inactive', function (Builder $query): void {
                $query->where('is_active', false);
            })
            ->when($this->filterStatus === 'expired', function (Builder $query): void {
                $query->where('expires_at', '<', now())
                    ->whereNotNull('expires_at');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.share-management', [
            'shares' => $this->shares,
        ]);
    }
}
