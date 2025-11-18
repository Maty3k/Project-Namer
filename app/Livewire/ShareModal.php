<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Services\ShareService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ShareModal extends Component
{
    use AuthorizesRequests;

    public LogoGeneration $logoGeneration;

    public bool $showModal = false;

    #[Validate]
    public string $title = '';

    #[Validate]
    public string $description = '';

    #[Validate]
    public string $shareType = 'public';

    #[Validate]
    public string $password = '';

    #[Validate]
    public ?int $expiresInDays = null;

    public ?string $shareUrl = null;

    public array $socialMediaUrls = [];

    public bool $isLoading = false;

    /**
     * Validation rules for the share form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'shareType' => ['required', 'in:public,password_protected'],
            'password' => ['required_if:shareType,password_protected', 'string', 'min:6'],
            'expiresInDays' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Mount the component with a logo generation.
     */
    public function mount(LogoGeneration $logoGeneration): void
    {
        $this->logoGeneration = $logoGeneration;
    }

    /**
     * Create a new share for the logo generation.
     */
    public function createShare(): void
    {
        // Authorize that the user owns this logo generation
        if ($this->logoGeneration->user_id !== auth()->id()) {
            abort(403);
        }

        $this->isLoading = true;

        $this->validate();

        try {
            $shareService = app(ShareService::class);

            $shareData = [
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'title' => $this->title ?: $this->logoGeneration->business_name,
                'description' => $this->description,
                'share_type' => $this->shareType,
            ];

            if ($this->shareType === 'password_protected') {
                $shareData['password'] = $this->password;
            }

            if ($this->expiresInDays) {
                $shareData['expires_at'] = now()->addDays($this->expiresInDays);
            }

            $share = $shareService->createShare(auth()->user(), $shareData);

            $this->shareUrl = $share->getShareUrl();
            $this->socialMediaUrls = $shareService->generateAllSocialMediaUrls($share);

            $this->dispatch('share-created', shareUrl: $this->shareUrl);
            $this->dispatch('show-toast', [
                'message' => 'Share created successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to create share. Please try again.',
                'type' => 'error',
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get social media URLs for a created share.
     */
    public function getSocialMediaUrls(): void
    {
        if (! $this->shareUrl) {
            return;
        }

        // Find the share by URL to get social media URLs
        $uuid = basename($this->shareUrl);
        $share = Share::where('uuid', $uuid)->first();

        if ($share) {
            $shareService = app(ShareService::class);
            $this->socialMediaUrls = $shareService->generateAllSocialMediaUrls($share);
        }
    }

    /**
     * Copy the share URL to clipboard.
     */
    public function copyToClipboard(): void
    {
        $this->dispatch('url-copied');
        $this->dispatch('show-toast', [
            'message' => 'Link copied to clipboard!',
            'type' => 'success',
        ]);
    }

    /**
     * Reset the form to its initial state.
     */
    public function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->shareType = 'public';
        $this->password = '';
        $this->expiresInDays = null;
        $this->shareUrl = null;
        $this->socialMediaUrls = [];
        $this->resetValidation();
    }

    /**
     * Open the share modal.
     */
    public function openModal(): void
    {
        $this->showModal = true;
    }

    /**
     * Close the share modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.share-modal');
    }
}
