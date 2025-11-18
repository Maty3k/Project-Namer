<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Services\ExportService;
use App\Services\ShareService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
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

    // Export properties
    #[Validate]
    public string $exportType = 'pdf';

    #[Validate]
    public bool $includeMetadata = true;

    #[Validate]
    public bool $includeDomains = false;

    public ?string $exportUrl = null;

    public bool $isGenerating = false;

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
            'exportType' => ['required', 'in:pdf,csv,json'],
            'includeMetadata' => ['boolean'],
            'includeDomains' => ['boolean'],
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
        } catch (\Exception) {
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
     * Generate an export for the logo generation.
     */
    public function generateExport(): void
    {
        // Authorize that the user owns this logo generation
        if ($this->logoGeneration->user_id !== auth()->id()) {
            abort(403);
        }

        $this->isGenerating = true;

        $this->validate([
            'exportType' => ['required', 'in:pdf,csv,json'],
        ]);

        try {
            $exportService = app(ExportService::class);

            $exportData = [
                'exportable_type' => LogoGeneration::class,
                'exportable_id' => $this->logoGeneration->id,
                'export_type' => $this->exportType,
                'include_metadata' => $this->includeMetadata,
                'include_domains' => $this->includeDomains,
            ];

            $export = $exportService->createExport(auth()->user(), $exportData);

            $this->exportUrl = route('api.exports.download', $export->uuid);

            $this->dispatch('export-generated');
            $this->dispatch('show-toast', [
                'message' => 'Export generated successfully!',
                'type' => 'success',
            ]);
        } catch (\Exception) {
            $this->dispatch('show-toast', [
                'message' => 'Failed to generate export. Please try again.',
                'type' => 'error',
            ]);
        } finally {
            $this->isGenerating = false;
        }
    }

    /**
     * Download the generated export.
     */
    public function downloadExport(): void
    {
        if (! $this->exportUrl) {
            return;
        }

        // Extract UUID from URL
        $uuid = basename($this->exportUrl);

        // Find the export
        $export = Export::where('uuid', $uuid)->first();

        if (! $export) {
            return;
        }

        // Increment download count
        $export->increment('download_count');
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
        $this->exportType = 'pdf';
        $this->includeMetadata = true;
        $this->includeDomains = false;
        $this->exportUrl = null;
        $this->resetValidation();
    }

    /**
     * Open the share modal.
     */
    #[On('openShareModal')]
    public function openModal(?int $generationId = null): void
    {
        // If no generationId provided (e.g., in tests), open the modal
        // Otherwise, only open if the event is for this specific generation
        if ($generationId === null || $this->logoGeneration->id === $generationId) {
            $this->showModal = true;
        }
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
