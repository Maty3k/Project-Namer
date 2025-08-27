<?php

use App\Models\LogoGeneration;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;
use App\Services\LogoVariantCacheService;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use function Livewire\Volt\{state, computed, mount, on};

new class extends Component {
    public ?int $logoGenerationId = null;
    public array $selectedLogos = [];
    public string $selectedColorScheme = '';
    public string $viewMode = 'grid';
    public bool $isProcessing = false;

    public function mount(int $logoGenerationId): void
    {
        $this->logoGenerationId = $logoGenerationId;
        $this->selectedColorScheme = '';
        $this->selectedLogos = [];
    }

    public function getLogoGeneration()
    {
        $cacheService = app(LogoVariantCacheService::class);
        $cached = $cacheService->getCachedLogoGeneration($this->logoGenerationId);
        
        return $cached ?: LogoGeneration::with(['generatedLogos.colorVariants'])
            ->findOrFail($this->logoGenerationId);
    }

    public function getColorSchemes()
    {
        return app(ColorPaletteService::class)->getAllColorSchemesWithMetadata();
    }

    public function getLogosByStyle()
    {
        $cacheService = app(LogoVariantCacheService::class);
        return $cacheService->getLogosByStyle($this->logoGenerationId);
    }

    public function getColorSchemeDisplayName(string $colorScheme): string
    {
        return app(ColorPaletteService::class)->getDisplayName($colorScheme);
    }

    public function refreshStatus(): void
    {
        // This will trigger a re-render and refresh the status
        $this->dispatch('$refresh');
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function toggleLogoSelection(int $logoId): void
    {
        if (in_array($logoId, $this->selectedLogos)) {
            $this->selectedLogos = array_values(array_diff($this->selectedLogos, [$logoId]));
        } else {
            $this->selectedLogos[] = $logoId;
        }
    }

    public function selectAllLogos(): void
    {
        $logoGeneration = $this->getLogoGeneration();
        $this->selectedLogos = $logoGeneration->generatedLogos->pluck('id')->toArray();
    }

    public function clearSelection(): void
    {
        $this->selectedLogos = [];
    }

    public function applyColorScheme(): void
    {
        $this->validate([
            'selectedLogos' => ['required', 'array', 'min:1'],
            'selectedColorScheme' => ['required', 'string'],
        ], [
            'selectedLogos.required' => 'Please select at least one logo to customize.',
            'selectedLogos.min' => 'Please select at least one logo to customize.',
            'selectedColorScheme.required' => 'Please select a color scheme.',
        ]);

        // Validate color scheme exists
        $colorService = app(ColorPaletteService::class);
        if (!$colorService->colorSchemeExists($this->selectedColorScheme)) {
            $this->addError('selectedColorScheme', 'The selected color scheme is invalid.');
            return;
        }

        $this->isProcessing = true;

        try {
            $logoGeneration = $this->getLogoGeneration();
            $logos = $logoGeneration->generatedLogos()
                ->whereIn('id', $this->selectedLogos)
                ->get();

            $customizedCount = 0;
            $palette = $colorService->getColorPalette($this->selectedColorScheme);
            $svgProcessor = app(SvgColorProcessor::class);

            foreach ($logos as $logo) {
                try {
                    // Check if customization already exists using cache
                    $cacheService = app(LogoVariantCacheService::class);
                    $variantExists = $cacheService->variantExists($logo->id, $this->selectedColorScheme);

                    if ($variantExists) {
                        $customizedCount++;
                        continue;
                    }

                    // Read and process the original file
                    if (!$logo->fileExists()) {
                        continue;
                    }

                    $originalPath = storage_path('app/public/' . $logo->original_file_path);
                    $originalContent = file_get_contents($originalPath);
                    
                    $result = $svgProcessor->processSvg($originalContent, $palette);
                    
                    if (!$result['success']) {
                        continue;
                    }
                    
                    $customizedContent = $result['svg'];

                    // Save customized version
                    $customizedFileName = $logo->generateDownloadFilename($this->selectedColorScheme, 'svg');
                    $customizedPath = "logos/{$logoGeneration->id}/customized/{$customizedFileName}";
                    
                    Storage::disk('public')->put($customizedPath, $customizedContent);

                    // Create color variant record
                    $variant = $logo->colorVariants()->create([
                        'color_scheme' => $this->selectedColorScheme,
                        'file_path' => $customizedPath,
                        'file_size' => strlen((string) $customizedContent),
                    ]);

                    // Invalidate cache after creating new variant
                    $cacheService->invalidateLogoCache($logo->id);

                    $customizedCount++;

                } catch (\Exception $e) {
                    \Log::warning('Failed to customize logo in gallery', [
                        'logo_id' => $logo->id,
                        'color_scheme' => $this->selectedColorScheme,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->dispatch('toast', 
                message: "{$customizedCount} logos customized successfully",
                type: 'success',
                duration: 4000
            );

        } catch (\Exception $e) {
            $this->dispatch('toast', 
                message: 'Failed to customize logos. Please try again.',
                type: 'error',
                duration: 8000
            );
            \Log::error('Logo customization failed in gallery', [
                'error' => $e->getMessage(),
                'selected_logos' => $this->selectedLogos,
                'color_scheme' => $this->selectedColorScheme,
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function downloadLogo(int $logoId, string $format = 'svg', ?string $colorScheme = null): void
    {
        $logoGeneration = $this->getLogoGeneration();
        $logo = $logoGeneration->generatedLogos()->findOrFail($logoId);

        if ($colorScheme) {
            $colorVariant = $logo->colorVariants()
                ->where('color_scheme', $colorScheme)
                ->firstOrFail();
            
            $filePath = $colorVariant->file_path;
            $fileName = $logo->generateDownloadFilename($colorScheme, $format);
        } else {
            $filePath = $logo->original_file_path;
            $fileName = $logo->generateDownloadFilename('original', $format);
        }

        if (!Storage::disk('public')->exists($filePath)) {
            $this->dispatch('toast', 
                message: 'File not found. It may have been removed or is being regenerated.',
                type: 'error',
                duration: 6000,
                actions: [
                    ['label' => 'Regenerate Logos', 'action' => 'regenerate'],
                    ['label' => 'Go Back', 'action' => 'back_to_gallery']
                ]
            );
            return;
        }

        // Redirect to the API download endpoint
        $this->redirect("/api/logos/{$logoGeneration->id}/download/{$logoId}" . 
            ($colorScheme ? "?color_scheme={$colorScheme}" : ""));
    }

    public function downloadAllLogos(): void
    {
        $logoGeneration = $this->getLogoGeneration();
        
        if ($logoGeneration->generatedLogos->isEmpty()) {
            $this->dispatch('toast', 
                message: 'No logos available for download.',
                type: 'warning',
                duration: 5000
            );
            return;
        }

        // Redirect to batch download API endpoint
        $this->redirect("/api/logos/{$logoGeneration->id}/download-batch");
    }

    public function retryGeneration(): void
    {
        try {
            $logoGeneration = $this->getLogoGeneration();
            
            $response = \Http::post("/api/logos/{$logoGeneration->id}/retry");
            
            if ($response->successful()) {
                $this->dispatch('toast', 
                    message: 'Logo generation restarted successfully',
                    type: 'info',
                    duration: 4000
                );
                
                // Refresh the component to show updated status
                $this->dispatch('$refresh');
            } else {
                throw new \Exception('Retry request failed');
            }
            
        } catch (\Exception) {
            $this->dispatch('toast', 
                message: 'Unable to retry generation. Please try again later.',
                type: 'error',
                duration: 6000
            );
        }
    }

    public function completeGeneration(): void
    {
        try {
            $logoGeneration = $this->getLogoGeneration();
            
            $response = \Http::post("/api/logos/{$logoGeneration->id}/complete");
            
            if ($response->successful()) {
                $this->dispatch('toast', 
                    message: 'Completing logo generation...',
                    type: 'info',
                    duration: 4000
                );
                
                // Refresh the component to show updated status
                $this->dispatch('$refresh');
            } else {
                throw new \Exception('Complete request failed');
            }
            
        } catch (\Exception) {
            $this->dispatch('toast', 
                message: 'Unable to complete generation. Please try again later.',
                type: 'error',
                duration: 6000
            );
        }
    }

    public function useCurrentLogos(): void
    {
        $this->dispatch('toast', 
            message: 'Using currently generated logos. You can still try to generate the remaining ones later.',
            type: 'info',
            duration: 5000
        );
        
        // Just refresh to hide the partial status message
        $this->dispatch('$refresh');
    }

    public function goToNameGenerator(): void
    {
        $this->redirect(route('home'));
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    @php
        $logoGeneration = $this->getLogoGeneration();
        $colorSchemes = $this->getColorSchemes();
        $logosByStyle = $this->getLogosByStyle();
    @endphp

    {{-- Header Section --}}
    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Logo Gallery
                </h1>
                @if($logoGeneration->business_name)
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-1">
                        {{ $logoGeneration->business_name }}
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                {{-- View Toggle --}}
                <div class="flex rounded-lg border border-gray-200 dark:border-gray-700">
                    <button
                        wire:click="toggleViewMode"
                        class="px-3 py-2 text-sm font-medium rounded-l-lg transition-colors
                               {{ $viewMode === 'grid' 
                                   ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400' 
                                   : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        <flux:icon.squares-2x2 class="w-4 h-4" />
                    </button>
                    <button
                        wire:click="toggleViewMode"
                        class="px-3 py-2 text-sm font-medium rounded-r-lg transition-colors
                               {{ $viewMode === 'list' 
                                   ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400' 
                                   : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        <flux:icon.list-bullet class="w-4 h-4" />
                    </button>
                </div>

                {{-- Download All Button --}}
                @if($logoGeneration->status === 'completed' && $logoGeneration->generatedLogos->isNotEmpty())
                    <flux:button
                        wire:click="downloadAllLogos"
                        variant="outline"
                        size="sm"
                    >
                        <flux:icon.arrow-down-tray class="w-4 h-4 mr-2" />
                        Download All
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Status Section with Enhanced Progress --}}
        @if($logoGeneration->status === 'processing')
            <div class="mt-6">
                <x-logo-generation-progress :logo-generation="$logoGeneration" />
            </div>
        @elseif($logoGeneration->status === 'failed')
            <div class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <flux:icon.exclamation-circle class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                Logo generation failed
                            </h3>
                            @if($logoGeneration->error_message)
                                <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                    {{ $logoGeneration->error_message }}
                                </p>
                            @endif
                            <p class="text-xs text-red-500 dark:text-red-400 mt-2">
                                This usually resolves quickly. You can try generating again or contact support if the issue persists.
                            </p>
                        </div>
                    </div>
                    
                    {{-- Recovery Actions --}}
                    <div class="flex gap-2 ml-4">
                        <flux:button
                            wire:click="retryGeneration"
                            variant="outline"
                            size="sm"
                            class="border-red-300 text-red-700 hover:bg-red-100 dark:border-red-600 dark:text-red-300 dark:hover:bg-red-900/30"
                        >
                            <flux:icon.arrow-path class="w-4 h-4 mr-1" />
                            Try Again
                        </flux:button>
                        
                        <flux:button
                            wire:click="goToNameGenerator"
                            variant="ghost"
                            size="sm"
                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            Start Over
                        </flux:button>
                    </div>
                </div>
            </div>
        @elseif($logoGeneration->status === 'partial')
            <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-start">
                        <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-3 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                Partial generation completed
                            </h3>
                            <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                Generated {{ $logoGeneration->logos_completed }} of {{ $logoGeneration->total_logos_requested }} logos successfully. 
                                Some logos failed to generate.
                            </p>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                You can use the generated logos or try to complete the remaining ones.
                            </p>
                        </div>
                    </div>
                    
                    {{-- Completion Actions --}}
                    <div class="flex gap-2 ml-4">
                        <flux:button
                            wire:click="completeGeneration"
                            variant="primary"
                            size="sm"
                        >
                            <flux:icon.plus class="w-4 h-4 mr-1" />
                            Complete
                        </flux:button>
                        
                        <flux:button
                            wire:click="useCurrentLogos"
                            variant="outline"
                            size="sm"
                        >
                            Use Current
                        </flux:button>
                    </div>
                </div>
            </div>
        @elseif($logoGeneration->status === 'completed')
            <div class="mt-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center">
                    <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" />
                    <div>
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ $logoGeneration->generatedLogos->count() }} logos generated successfully
                        </h3>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Color Customization Panel --}}
    @if($logoGeneration->status === 'completed' && $logoGeneration->generatedLogos->isNotEmpty())
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Color Customization
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Color Scheme Selector --}}
                <div class="lg:col-span-8">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Choose Color Scheme
                    </label>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        @foreach($colorSchemes as $scheme)
                            <button
                                wire:click="$set('selectedColorScheme', '{{ $scheme['id'] }}')"
                                class="group relative p-3 rounded-lg border-2 transition-all
                                       {{ $selectedColorScheme === $scheme['id'] 
                                           ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                                           : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                            >
                                {{-- Color Preview --}}
                                <div class="flex space-x-1 mb-2">
                                    @foreach(['primary', 'secondary', 'accent'] as $colorType)
                                        <div 
                                            class="w-4 h-4 rounded-full border border-gray-200 dark:border-gray-600"
                                            style="background-color: {{ $scheme['colors'][$colorType] }}"
                                        ></div>
                                    @endforeach
                                </div>
                                
                                <div class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ $scheme['name'] }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Actions
                    </label>
                    
                    <div class="space-y-3">
                        {{-- Selection Info --}}
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ count($selectedLogos) }} logo(s) selected
                        </div>

                        {{-- Selection Buttons --}}
                        <div class="flex gap-2">
                            <flux:button
                                wire:click="selectAllLogos"
                                variant="ghost"
                                size="sm"
                                class="flex-1"
                            >
                                Select All
                            </flux:button>
                            
                            @if(count($selectedLogos) > 0)
                                <flux:button
                                    wire:click="clearSelection"
                                    variant="ghost"
                                    size="sm"
                                    class="flex-1"
                                >
                                    Clear
                                </flux:button>
                            @endif
                        </div>

                        {{-- Apply Button --}}
                        <flux:button
                            wire:click="applyColorScheme"
                            variant="primary"
                            size="sm"
                            class="w-full"
                            :disabled="$isProcessing || empty($selectedLogos) || empty($selectedColorScheme)"
                            wire:loading.attr="disabled"
                            wire:target="applyColorScheme"
                        >
                            <div wire:loading.remove wire:target="applyColorScheme">
                                <flux:icon.swatch class="w-4 h-4 mr-2" />
                                Apply Color Scheme
                            </div>
                            <div wire:loading wire:target="applyColorScheme" class="flex items-center">
                                <flux:icon.arrow-path class="w-4 h-4 mr-2 animate-spin" />
                                Processing...
                            </div>
                        </flux:button>
                    </div>
                </div>
            </div>

            {{-- Validation Errors --}}
            @error('selectedLogos')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('selectedColorScheme')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Logo Gallery --}}
    @if($logoGeneration->status === 'completed' && !empty($logosByStyle))
        @foreach($logosByStyle as $styleGroup)
            <div class="mb-12">
                {{-- Style Header --}}
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $styleGroup['display_name'] }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ count($styleGroup['logos']) }} variations
                    </p>
                </div>

                {{-- Logo Grid/List --}}
                <div class="{{ $viewMode === 'grid' 
                    ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6' 
                    : 'space-y-4' }}">
                    @foreach($styleGroup['logos'] as $logo)
                        <div class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden
                                   {{ $viewMode === 'list' ? 'flex items-center p-4' : '' }}">
                            
                            {{-- Selection Checkbox --}}
                            <div class="absolute top-3 left-3 z-10">
                                <input
                                    type="checkbox"
                                    wire:click="toggleLogoSelection({{ $logo['id'] }})"
                                    @checked(in_array($logo['id'], $selectedLogos))
                                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                                />
                            </div>

                            {{-- Logo Preview --}}
                            <div class="{{ $viewMode === 'grid' ? 'aspect-square p-8' : 'w-20 h-20 p-2 mr-4' }}">
                                @if($logo['preview_url'])
                                    <img 
                                        src="{{ $logo['preview_url'] }}" 
                                        alt="Logo variation {{ $logo['variation_number'] }}"
                                        class="w-full h-full object-contain"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="w-full h-full bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                    </div>
                                @endif
                            </div>

                            {{-- Logo Info and Actions --}}
                            <div class="{{ $viewMode === 'grid' ? 'p-4' : 'flex-1' }}">
                                <div class="mb-3">
                                    <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                        Variation {{ $logo['variation_number'] }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($logo['file_size'] / 1024, 1) }}KB
                                    </p>
                                </div>

                                {{-- Color Variants --}}
                                @if(!empty($logo['color_variants']))
                                    <div class="mb-3">
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Color Variants
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($logo['color_variants'] as $variant)
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                    {{ $variant['display_name'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Download Actions --}}
                                <div class="space-y-2">
                                    {{-- Original Download --}}
                                    <div class="flex gap-2">
                                        <flux:button
                                            wire:click="downloadLogo({{ $logo['id'] }}, 'svg')"
                                            variant="outline"
                                            size="sm"
                                            class="flex-1"
                                        >
                                            <flux:icon.arrow-down-tray class="w-3 h-3 mr-1" />
                                            SVG
                                        </flux:button>
                                        <flux:button
                                            wire:click="downloadLogo({{ $logo['id'] }}, 'png')"
                                            variant="outline"
                                            size="sm"
                                            class="flex-1"
                                        >
                                            <flux:icon.arrow-down-tray class="w-3 h-3 mr-1" />
                                            PNG
                                        </flux:button>
                                    </div>

                                    {{-- Color Variant Downloads --}}
                                    @foreach($logo['color_variants'] as $variant)
                                        <flux:button
                                            wire:click="downloadLogo({{ $logo['id'] }}, 'svg', '{{ $variant['color_scheme'] }}')"
                                            variant="ghost"
                                            size="sm"
                                            class="w-full justify-start"
                                        >
                                            <flux:icon.swatch class="w-3 h-3 mr-2" />
                                            {{ $variant['display_name'] }} (SVG)
                                        </flux:button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @elseif($logoGeneration->status === 'completed' && empty($logosByStyle))
        {{-- No Logos State --}}
        <div class="text-center py-12">
            <flux:icon.photo class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No logos generated yet
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                The logo generation process hasn't produced any results.
            </p>
        </div>
    @endif

    {{-- Auto-refresh for processing status --}}
    @if($logoGeneration->status === 'processing')
        <div wire:poll.5000ms="refreshStatus" class="hidden"></div>
    @endif
</div>

