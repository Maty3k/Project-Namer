<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ShareManagement component for managing user shares.
 *
 * Provides functionality for viewing, creating, and managing shares
 * with pagination and filtering capabilities.
 */
final class ShareManagement extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public string $shareableType = '';

    public int $shareableId = 0;

    public string $title = '';

    public string $description = '';

    public string $shareType = 'public';

    public string $password = '';

    public string $expiresAt = '';

    /** @var array<string, mixed> */
    public array $settings = [];

    public string $search = '';

    public string $filterType = '';

    public string $filterActive = '';

    /** @var array<string, string> */
    protected array $queryString = ['search' => ''];

    public function mount(): void
    {
        $this->settings = [
            'show_title' => true,
            'show_description' => true,
            'allow_downloads' => false,
        ];
    }

    public function openCreateModal(int $logoGenerationId): void
    {
        $this->resetForm();
        $this->shareableType = LogoGeneration::class;
        $this->shareableId = $logoGenerationId;
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function createShare(ShareService $shareService): void
    {
        $this->validate([
            'shareableType' => 'required|string',
            'shareableId' => 'required|integer|exists:logo_generations,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'shareType' => 'required|in:public,password_protected',
            'password' => 'required_if:shareType,password_protected|min:6',
            'expiresAt' => 'nullable|date|after:now',
            'settings' => 'array',
        ]);

        try {
            $user = Auth::user();
            if (! $user) {
                $this->addError('general', 'You must be logged in to create shares.');

                return;
            }

            $shareData = [
                'shareable_type' => $this->shareableType,
                'shareable_id' => $this->shareableId,
                'title' => $this->title,
                'description' => $this->description,
                'share_type' => $this->shareType,
                'settings' => $this->settings,
            ];

            if ($this->shareType === 'password_protected') {
                $shareData['password'] = $this->password;
            }

            if ($this->expiresAt) {
                $shareData['expires_at'] = $this->expiresAt;
            }

            $share = $shareService->createShare($user, $shareData);

            $this->closeCreateModal();
            $this->dispatch('share-created', [
                'shareUrl' => $share->getShareUrl(),
                'uuid' => $share->uuid,
            ]);

            session()->flash('success', 'Share created successfully!');
        } catch (ValidationException $e) {
            $this->addError('validation', $e->getMessage());
        } catch (\Exception) {
            $this->addError('general', 'An error occurred while creating the share. Please try again.');
        }
    }

    public function deactivateShare(int $shareId, ShareService $shareService): void
    {
        $share = Share::where('id', $shareId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $share) {
            $this->addError('general', 'Share not found.');

            return;
        }

        $shareService->deactivateShare($share);
        session()->flash('success', 'Share deactivated successfully!');
    }

    public function copyShareUrl(string $uuid): void
    {
        $share = Share::where('uuid', $uuid)->where('user_id', Auth::id())->first();
        if ($share) {
            $this->dispatch('copy-to-clipboard', shareUrl: $share->getShareUrl());
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterActive(): void
    {
        $this->resetPage();
    }

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cachedShares = null;

    /**
     * @return array<string, mixed>
     */
    public function getShares(): array
    {
        if ($this->cachedShares !== null) {
            return $this->cachedShares;
        }

        $user = Auth::user();
        if (! $user) {
            return $this->cachedShares = [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 0,
                ],
            ];
        }

        $shares = Share::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->cachedShares = [
            'data' => $shares->toArray(),
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => $shares->count(),
                'per_page' => $shares->count(),
            ],
        ];
    }

    private function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->shareType = 'public';
        $this->password = '';
        $this->expiresAt = '';
        $this->settings = [
            'show_title' => true,
            'show_description' => true,
            'allow_downloads' => false,
        ];
        $this->resetErrorBag();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.share-management');
    }
}
