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

    public function mount(?int $logoGenerationId = null): void
    {
        $this->logoGenerationId = $logoGenerationId;
        $this->selectedColorScheme = '';
        $this->selectedLogos = [];
    }

    public function getLogoGeneration()
    {
        if (!$this->logoGenerationId) {
            return null;
        }

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
        if (!$this->logoGenerationId) {
            return [];
        }

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
        if ($logoGeneration) {
            $this->selectedLogos = $logoGeneration->generatedLogos->pluck('id')->toArray();
        }
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
            if (!$logoGeneration) {
                throw new \Exception('Logo generation not found');
            }

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
        if (!$logoGeneration) {
            $this->dispatch('toast', 
                message: 'Logo generation not found.',
                type: 'error',
                duration: 6000
            );
            return;
        }

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
                duration: 6000
            );
            return;
        }

        // Dispatch download event
        $downloadUrl = Storage::disk('public')->url($filePath);
        $this->dispatch('download-file', url: $downloadUrl);
    }

    public function downloadAllLogos(): void
    {
        $logoGeneration = $this->getLogoGeneration();
        if (!$logoGeneration) {
            $this->dispatch('toast', 
                message: 'Logo generation not found.',
                type: 'error',
                duration: 6000
            );
            return;
        }
        
        if ($logoGeneration->generatedLogos->isEmpty()) {
            $this->dispatch('toast', 
                message: 'No logos available for download.',
                type: 'warning',
                duration: 5000
            );
            return;
        }

        // Dispatch download batch event
        $this->dispatch('download-file', url: "/api/logos/{$logoGeneration->id}/download-batch");
    }
    
    protected function serializeProperty($property)
    {
        if ($property instanceof \App\Models\LogoGeneration) {
            return $property->id;
        }

        if ($property instanceof \App\Models\GeneratedLogo) {
            return $property->id;
        }

        if ($property instanceof \Illuminate\Database\Eloquent\Collection) {
            return $property->pluck('id')->toArray();
        }

        return parent::serializeProperty($property);
    }

    protected function hydrateProperty($property, $value)
    {
        // Don't hydrate computed properties - let them be computed fresh
        if (in_array($property, ['logoGeneration', 'colorSchemes', 'logosByStyle'])) {
            return null;
        }

        return parent::hydrateProperty($property, $value);
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
                @if($logoGeneration && $logoGeneration->business_name)
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
                        Grid
                    </button>
                    <button
                        wire:click="toggleViewMode"
                        class="px-3 py-2 text-sm font-medium rounded-r-lg transition-colors
                               {{ $viewMode === 'list' 
                                   ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400' 
                                   : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        List
                    </button>
                </div>

                {{-- Download All Button --}}
                @if($logoGeneration && $logoGeneration->status === 'completed' && $logoGeneration->generatedLogos->isNotEmpty())
                    <flux:button
                        wire:click="downloadAllLogos"
                        variant="outline"
                        size="sm"
                    >
                        Download All
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Status Section --}}
    @if($logoGeneration && $logoGeneration->status === 'completed' && $logoGeneration->generatedLogos->isNotEmpty())
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center">
                <div class="w-5 h-5 text-green-600 dark:text-green-400 mr-3">✓</div>
                <div>
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ $logoGeneration->generatedLogos->count() }} logos generated successfully
                    </h3>
                </div>
            </div>
        </div>
    @endif

    {{-- Gallery Content --}}
    @if($logoGeneration && $logoGeneration->status === 'completed' && !empty($logosByStyle))
        <div class="space-y-8">
            {{-- Color Customization Panel --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
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
                                    Apply Color Scheme
                                </div>
                                <div wire:loading wire:target="applyColorScheme" class="flex items-center">
                                    Processing...
                                </div>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Logo Grid --}}
            @foreach($logosByStyle as $styleGroup)
                <div class="mb-12">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $styleGroup['display_name'] }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ count($styleGroup['logos']) }} variations
                        </p>
                    </div>

                    <div class="{{ $viewMode === 'grid' 
                        ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6' 
                        : 'space-y-4' }}">
                        @foreach($styleGroup['logos'] as $logo)
                            <div class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                {{-- Selection Checkbox --}}
                                <div class="absolute top-3 left-3 z-10">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleLogoSelection({{ $logo['id'] }})"
                                        @checked(in_array($logo['id'], $selectedLogos))
                                        class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded"
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
                                            <span class="text-gray-400">No Preview</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Logo Info and Actions --}}
                                <div class="p-4">
                                    <div class="mb-3">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">
                                            Variation {{ $logo['variation_number'] }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ number_format($logo['file_size'] / 1024, 1) }}KB
                                        </p>
                                    </div>

                                    {{-- Download Actions --}}
                                    <div class="space-y-2">
                                        <div class="flex gap-2">
                                            <flux:button
                                                wire:click="downloadLogo({{ $logo['id'] }}, 'svg')"
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                            >
                                                SVG
                                            </flux:button>
                                            <flux:button
                                                wire:click="downloadLogo({{ $logo['id'] }}, 'png')"
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                            >
                                                PNG
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($logoGeneration && $logoGeneration->status === 'completed' && empty($logosByStyle))
        {{-- No Logos State --}}
        <div class="text-center py-12">
            <div class="w-16 h-16 text-gray-400 mx-auto mb-4">📷</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No logos generated yet
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                The logo generation process hasn't produced any results.
            </p>
        </div>
    @elseif($logoGeneration && $logoGeneration->status === 'processing')
        {{-- Processing State --}}
        <div class="text-center py-12">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
            @php
                $progressPercentage = $logoGeneration->total_logos_requested > 0 
                    ? round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100)
                    : 0;
                $statusMessage = $progressPercentage > 0 
                    ? "Generating logos... {$progressPercentage}% complete"
                    : 'Initializing logo generation...';
            @endphp
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                {{ $statusMessage }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                {{ $logoGeneration->logos_completed }}/{{ $logoGeneration->total_logos_requested }}
            </p>
        </div>
        <div wire:poll.5000ms="refreshStatus" class="hidden"></div>
    @elseif($logoGeneration && $logoGeneration->status === 'failed')
        {{-- Failed State --}}
        <div class="text-center py-12">
            <div class="w-16 h-16 text-red-400 mx-auto mb-4">❌</div>
            <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-2">
                Logo generation failed
            </h3>
            @if($logoGeneration->error_message)
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ $logoGeneration->error_message }}
                </p>
            @endif
            <p class="text-gray-600 dark:text-gray-400">
                Please try generating logos again or contact support if the problem persists.
            </p>
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12">
            <div class="w-16 h-16 text-gray-400 mx-auto mb-4">📷</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No Logo Generation Found
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Please start a logo generation process first.
            </p>
        </div>
    @endif
</div>