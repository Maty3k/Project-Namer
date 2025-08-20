<?php

use App\Models\LogoGeneration;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Services\ColorPaletteService;
use App\Services\SvgColorProcessor;
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
        return LogoGeneration::with(['generatedLogos.colorVariants'])
            ->findOrFail($this->logoGenerationId);
    }

    public function getColorSchemes()
    {
        return app(ColorPaletteService::class)->getAllColorSchemesWithMetadata();
    }

    public function getLogosByStyle()
    {
        $logoGeneration = $this->getLogoGeneration();
        
        return $logoGeneration->generatedLogos
            ->groupBy('style')
            ->map(function ($logos, $style) {
                return [
                    'style' => $style,
                    'display_name' => ucwords($style),
                    'logos' => $logos->map(function ($logo) {
                        return [
                            'id' => $logo->id,
                            'style' => $logo->style,
                            'variation_number' => $logo->variation_number,
                            'original_file_path' => $logo->original_file_path,
                            'preview_url' => $logo->original_file_path ? asset('storage/' . $logo->original_file_path) : null,
                            'file_size' => $logo->file_size,
                            'color_variants' => $logo->colorVariants->map(function ($variant) {
                                return [
                                    'color_scheme' => $variant->color_scheme,
                                    'display_name' => $this->getColorSchemeDisplayName($variant->color_scheme),
                                    'file_path' => $variant->file_path,
                                    'preview_url' => asset('storage/' . $variant->file_path),
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->values()->toArray();
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
                    // Check if customization already exists
                    $existingVariant = $logo->colorVariants()
                        ->where('color_scheme', $this->selectedColorScheme)
                        ->first();

                    if ($existingVariant) {
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
                    $logo->colorVariants()->create([
                        'color_scheme' => $this->selectedColorScheme,
                        'file_path' => $customizedPath,
                        'file_size' => strlen($customizedContent),
                    ]);

                    $customizedCount++;

                } catch (\Exception $e) {
                    \Log::warning('Failed to customize logo in gallery', [
                        'logo_id' => $logo->id,
                        'color_scheme' => $this->selectedColorScheme,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->dispatch('toast', message: "{$customizedCount} logos customized successfully");

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to customize logos. Please try again.');
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
            $this->dispatch('toast', message: 'File not found.');
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
            $this->dispatch('toast', message: 'No logos available for download.');
            return;
        }

        // Redirect to batch download API endpoint
        $this->redirect("/api/logos/{$logoGeneration->id}/download-batch");
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

        {{-- Status Section --}}
        @if($logoGeneration->status === 'processing')
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center">
                    <flux:icon.arrow-path class="w-5 h-5 text-blue-600 dark:text-blue-400 animate-spin mr-3" />
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Generating logos...
                        </h3>
                        <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                            {{ $logoGeneration->logos_completed }} of {{ $logoGeneration->total_logos_requested }} completed
                            ({{ $logoGeneration->total_logos_requested > 0 ? round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100) : 0 }}%)
                        </p>
                    </div>
                    <flux:button
                        wire:click="refreshStatus"
                        variant="ghost"
                        size="sm"
                    >
                        Refresh
                    </flux:button>
                </div>
                
                {{-- Progress Bar --}}
                <div class="mt-3 w-full bg-blue-100 dark:bg-blue-800 rounded-full h-2">
                    <div 
                        class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $logoGeneration->total_logos_requested > 0 ? round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100) : 0 }}%"
                    ></div>
                </div>
            </div>
        @elseif($logoGeneration->status === 'failed')
            <div class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <flux:icon.exclamation-circle class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" />
                    <div>
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            Generation failed
                        </h3>
                        @if($logoGeneration->error_message)
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                {{ $logoGeneration->error_message }}
                            </p>
                        @endif
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
        <script>
            setTimeout(() => {
                @this.call('refreshStatus');
            }, 5000); // Refresh every 5 seconds
        </script>
    @endif
</div>