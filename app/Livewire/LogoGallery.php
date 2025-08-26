<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ColorScheme;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Logo Gallery component for displaying and managing generated logos.
 *
 * Handles logo display, color customization, and download functionality
 * with real-time updates during generation process.
 */
class LogoGallery extends Component
{
    public int $logoGenerationId;

    protected ?LogoGeneration $logoGeneration = null;

    // Color customization
    public string $selectedColorScheme = '';

    /** @var array<int, int> */
    public array $selectedLogos = [];

    public bool $isCustomizingColors = false;

    // UI state
    public string $viewMode = 'grid'; // 'grid' or 'list'

    public bool $showColorPicker = false;

    // Error handling
    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    /** @var array<string, string> */
    protected array $rules = [
        'selectedColorScheme' => 'required|string',
        'selectedLogos' => 'required|array|min:1',
        'selectedLogos.*' => 'exists:generated_logos,id',
    ];

    /** @var array<string, string> */
    protected array $messages = [
        'selectedColorScheme.required' => 'Please select a color scheme',
        'selectedLogos.required' => 'Please select at least one logo',
        'selectedLogos.min' => 'Please select at least one logo',
    ];

    public function mount(int $logoGenerationId): void
    {
        $this->logoGenerationId = $logoGenerationId;
        $this->loadLogoGeneration();
    }

    /**
     * Load logo generation data.
     */
    public function loadLogoGeneration(): void
    {
        $this->logoGeneration = LogoGeneration::with(['generatedLogos.colorVariants'])
            ->find($this->logoGenerationId);

        if (! $this->logoGeneration) {
            $this->errorMessage = 'Logo generation not found.';
        }
    }

    /**
     * Refresh generation status.
     */
    #[On('refresh-logo-status')]
    public function refreshStatus(): void
    {
        $this->loadLogoGeneration();

        if ($this->logoGeneration && $this->logoGeneration->status === 'completed') {
            $this->dispatch('toast', message: 'Logo generation completed!', type: 'success');
        }
    }

    /**
     * Apply color scheme to selected logos.
     */
    public function applyColorScheme(): void
    {
        $this->validate();

        if (! $this->logoGeneration) {
            $this->dispatch('toast', message: 'Logo generation not found', type: 'error');

            return;
        }

        $this->isCustomizingColors = true;
        $this->errorMessage = null;
        $successCount = 0;

        try {
            $colorPaletteService = app(ColorPaletteService::class);
            $svgProcessor = app(SvgColorProcessor::class);

            // Validate color scheme exists
            if (! $colorPaletteService->colorSchemeExists($this->selectedColorScheme)) {
                $this->addError('selectedColorScheme', 'Invalid color scheme selected');

                return;
            }

            $colorPalette = $colorPaletteService->getColorPalette(ColorScheme::from($this->selectedColorScheme));

            foreach ($this->selectedLogos as $logoId) {
                $logo = GeneratedLogo::find($logoId);

                if (! $logo || $logo->logo_generation_id !== $this->logoGenerationId) {
                    continue;
                }

                // Check if this color variant already exists
                $existingVariant = $logo->colorVariants()
                    ->where('color_scheme', $this->selectedColorScheme)
                    ->first();

                if ($existingVariant) {
                    continue; // Skip if already exists
                }

                // Read original SVG content
                if (! Storage::disk('public')->exists($logo->original_file_path)) {
                    Log::warning('Original logo file not found', ['path' => $logo->original_file_path]);

                    continue;
                }

                $originalSvg = Storage::disk('public')->get($logo->original_file_path);

                if (empty($originalSvg)) {
                    Log::warning('Empty SVG content', ['path' => $logo->original_file_path]);

                    continue;
                }

                // Process SVG with new colors
                $result = $svgProcessor->processSvg($originalSvg, $colorPalette);

                if (! $result['success']) {
                    Log::warning('SVG processing failed', [
                        'logo_id' => $logo->id,
                        'errors' => $result['errors'] ?? [],
                    ]);

                    continue;
                }

                // Generate file path for customized version
                $fileName = pathinfo((string) $logo->original_file_path, PATHINFO_FILENAME);
                $customizedPath = "logos/{$this->logoGenerationId}/customized/{$fileName}-{$this->selectedColorScheme}.svg";

                // Save customized SVG
                Storage::disk('public')->put($customizedPath, $result['svg']);

                // Create color variant record
                LogoColorVariant::create([
                    'generated_logo_id' => $logo->id,
                    'color_scheme' => $this->selectedColorScheme,
                    'file_path' => $customizedPath,
                ]);

                $successCount++;
            }

            $this->successMessage = "{$successCount} logos customized successfully";
            $this->dispatch('toast', message: $this->successMessage, type: 'success');

            // Reset selections
            $this->selectedLogos = [];
            $this->selectedColorScheme = '';
            $this->showColorPicker = false;

            // Reload data to show new variants
            $this->loadLogoGeneration();

        } catch (Exception $e) {
            $this->errorMessage = 'Failed to customize logos: '.$e->getMessage();
            $this->dispatch('toast', message: $this->errorMessage, type: 'error');
            Log::error('Logo customization failed', [
                'logo_generation_id' => $this->logoGenerationId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isCustomizingColors = false;
        }
    }

    /**
     * Download individual logo.
     */
    public function downloadLogo(int $logoId, string $format = 'svg', ?string $colorScheme = null): void
    {
        $logo = GeneratedLogo::find($logoId);

        if (! $logo || $logo->logo_generation_id !== $this->logoGenerationId) {
            $this->dispatch('toast', message: 'Logo not found', type: 'error');

            return;
        }

        $downloadUrl = route('api.logos.download', [
            'logoGeneration' => $this->logoGenerationId,
            'generatedLogo' => $logoId,
        ]);

        if ($colorScheme) {
            $downloadUrl .= "?color_scheme={$colorScheme}&format={$format}";
        } else {
            $downloadUrl .= "?format={$format}";
        }

        $this->dispatch('download-file', url: $downloadUrl);
    }

    /**
     * Download all logos as ZIP.
     */
    public function downloadBatch(?string $colorScheme = null): void
    {
        $logoGeneration = $this->getLogoGenerationProperty();
        if (! $logoGeneration || $logoGeneration->generatedLogos->isEmpty()) {
            $this->dispatch('toast', message: 'No logos available for download', type: 'error');

            return;
        }

        $downloadUrl = route('api.logos.download-batch', $this->logoGenerationId);

        if ($colorScheme) {
            $downloadUrl .= "?color_scheme={$colorScheme}";
        }

        $this->dispatch('download-file', url: $downloadUrl);
        $this->dispatch('toast', message: 'Download started!', type: 'success');
    }

    /**
     * Toggle logo selection for customization.
     */
    public function toggleLogoSelection(int $logoId): void
    {
        if (in_array($logoId, $this->selectedLogos)) {
            $this->selectedLogos = array_values(array_diff($this->selectedLogos, [$logoId]));
        } else {
            $this->selectedLogos[] = $logoId;
        }
    }

    /**
     * Clear logo selection.
     */
    public function clearSelection(): void
    {
        $this->selectedLogos = [];
        $this->selectedColorScheme = '';
        $this->showColorPicker = false;
    }

    /**
     * Toggle view mode between grid and list.
     */
    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    /**
     * Get available color schemes.
     */
    /**
     * @return array<string, mixed>
     */
    public function getColorSchemesProperty(): array
    {
        return app(ColorPaletteService::class)->getAllColorSchemesWithMetadata();
    }

    /**
     * Get logos grouped by style.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLogosByStyleProperty(): array
    {
        if (! $this->logoGeneration) {
            return [];
        }

        return $this->logoGeneration->generatedLogos
            ->groupBy('style')
            ->map(fn ($logos, $style) => [
                'style' => $style,
                'display_name' => ucfirst((string) $style),
                'logos' => $logos,
            ])->values()->toArray();
    }

    /**
     * Get the logo generation model.
     */
    public function getLogoGenerationProperty(): ?LogoGeneration
    {
        return $this->logoGeneration;
    }

    /**
     * Get generation progress data.
     */
    /**
     * @return array<string, int>
     */
    public function getProgressProperty(): array
    {
        if (! $this->logoGeneration) {
            return ['percentage' => 0, 'completed' => 0, 'total' => 0];
        }

        $total = $this->logoGeneration->total_logos_requested;
        $completed = $this->logoGeneration->logos_completed;

        return [
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    public function render(): View
    {
        return view('livewire.logo-gallery');
    }
}
