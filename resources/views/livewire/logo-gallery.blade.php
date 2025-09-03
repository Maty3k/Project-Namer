<div class="max-w-7xl mx-auto w-full space-y-6">
    @if($this->logoGeneration)
        {{-- Header with Progress --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Logo Generation
                </h2>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ $this->logoGeneration->business_name }}
                </p>
            </div>
            
            {{-- Status and Actions --}}
            <div class="flex flex-wrap items-center gap-3">
                @if($this->logoGeneration->status === 'processing')
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <flux:icon.arrow-path class="size-4 animate-spin" />
                        <span class="text-sm font-medium">Processing...</span>
                    </div>
                @elseif($this->logoGeneration->status === 'completed')
                    <flux:badge variant="success">
                        <flux:icon.check class="size-3 mr-1" />
                        Completed
                    </flux:badge>
                @elseif($this->logoGeneration->status === 'failed')
                    <flux:badge variant="danger">
                        <flux:icon.exclamation-triangle class="size-3 mr-1" />
                        Failed
                    </flux:badge>
                @elseif($this->logoGeneration->status === 'pending')
                    <flux:badge variant="warning">
                        <flux:icon.clock class="size-3 mr-1" />
                        Pending
                    </flux:badge>
                @endif
                
                {{-- View Mode Toggle --}}
                <flux:button 
                    variant="outline" 
                    size="sm"
                    wire:click="toggleViewMode"
                    icon="{{ $viewMode === 'grid' ? 'list-bullet' : 'squares-2x2' }}"
                >
                    {{ $viewMode === 'grid' ? 'List' : 'Grid' }}
                </flux:button>
                
                {{-- Download All --}}
                @if($this->logoGeneration->status === 'completed' && $this->logoGeneration->generatedLogos->isNotEmpty())
                    <flux:dropdown>
                        <flux:button variant="primary" icon="arrow-down-tray">
                            Download All
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="downloadBatch()" icon="archive-box">
                                Original Logos (ZIP)
                            </flux:menu.item>
                            <flux:menu.separator />
                            @foreach($this->colorSchemes as $schemeId => $scheme)
                                <flux:menu.item wire:click="downloadBatch('{{ $schemeId }}')" class="text-sm">
                                    {{ $scheme['name'] }} (ZIP)
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                    
                    {{-- Export Generator --}}
                    @livewire('export-generator', ['logoGeneration' => $this->logoGeneration])
                @endif
            </div>
        </div>

        {{-- Progress Bar (for processing status) --}}
        @if($this->logoGeneration->status === 'processing')
            <flux:card class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                            Generation Progress
                        </h3>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $this->progress['completed'] }}/{{ $this->progress['total'] }} logos
                        </span>
                    </div>
                    
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div 
                            class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
                            style="width: {{ $this->progress['percentage'] }}%"
                        ></div>
                    </div>
                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                        {{ $this->progress['percentage'] }}% complete
                        @if($this->logoGeneration->status === 'processing')
                            • This may take a few minutes
                        @endif
                    </p>
                </div>
            </flux:card>
        @endif

        {{-- File Upload Section --}}
        <flux:card class="p-6">
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            Upload Your Own Logos
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Upload PNG, JPG, or SVG logo files (max 5MB each)
                        </p>
                    </div>
                </div>

                {{-- Drag and Drop Upload Zone --}}
                <div 
                    x-data="{
                        draggedOver: @entangle('isDraggedOver'),
                        isUploading: @entangle('isUploading'),
                        uploadProgress: @entangle('uploadProgress')
                    }"
                    @dragover.prevent="draggedOver = true"
                    @dragleave.prevent="draggedOver = false"
                    @drop.prevent="
                        draggedOver = false;
                        let files = Array.from($event.dataTransfer.files);
                        if (files.length > 0) {
                            @this.set('uploadedFiles', files);
                        }
                    "
                    class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-200"
                    :class="{
                        'border-blue-500 bg-blue-50 dark:bg-blue-900/20': draggedOver,
                        'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500': !draggedOver && !isUploading,
                        'border-green-500 bg-green-50 dark:bg-green-900/20': isUploading && uploadProgress === 100,
                        'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20': isUploading && uploadProgress < 100
                    }"
                >
                    <div x-show="!isUploading" class="space-y-4">
                        <flux:icon.cloud-arrow-up class="mx-auto size-12 text-gray-400 dark:text-gray-500" />
                        
                        <div class="space-y-2">
                            <p class="text-lg font-medium text-gray-900 dark:text-white">
                                Drop logo files here
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                or click to browse your computer
                            </p>
                        </div>

                        <div class="flex flex-wrap justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">PNG</span>
                            <span class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">JPG</span>
                            <span class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">SVG</span>
                            <span class="text-gray-400">•</span>
                            <span>Max 5MB each</span>
                            <span class="text-gray-400">•</span>
                            <span>Min 100x100px</span>
                        </div>

                        {{-- File Input --}}
                        <input 
                            type="file" 
                            wire:model="uploadedFiles"
                            accept=".png,.jpg,.jpeg,.svg"
                            multiple
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >
                    </div>

                    {{-- Upload Progress --}}
                    <div x-show="isUploading" class="space-y-4">
                        <flux:icon.arrow-path class="mx-auto size-12 animate-spin text-blue-500" />
                        
                        <div class="space-y-2">
                            <p class="text-lg font-medium text-gray-900 dark:text-white">
                                <span x-show="uploadProgress < 100">Uploading logos...</span>
                                <span x-show="uploadProgress === 100">Upload complete!</span>
                            </p>
                            
                            {{-- Progress Bar --}}
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div 
                                    class="bg-blue-600 h-3 rounded-full transition-all duration-300 ease-out"
                                    :style="{ width: uploadProgress + '%' }"
                                ></div>
                            </div>
                            
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="uploadProgress"></span>% complete
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Upload Button --}}
                @if(count($uploadedFiles) > 0 && !$isUploading)
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ count($uploadedFiles) }} file(s) selected
                        </p>
                        
                        <div class="flex gap-2">
                            <flux:button 
                                variant="ghost" 
                                wire:click="$set('uploadedFiles', [])"
                                size="sm"
                            >
                                Clear
                            </flux:button>
                            
                            <flux:button 
                                variant="primary" 
                                wire:click="uploadLogos"
                                size="sm"
                            >
                                <flux:icon.arrow-up-tray class="size-4 mr-1" />
                                Upload Logos
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Upload Errors --}}
                @error('uploadedFiles.*')
                    <div class="text-sm text-red-600 dark:text-red-400 mt-2">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </flux:card>

        {{-- Color Customization Panel --}}
        @if($this->logoGeneration->status === 'completed' && $this->logoGeneration->generatedLogos->isNotEmpty())
            <flux:card class="p-6">
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                Color Customization
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Select logos and apply different color schemes
                            </p>
                        </div>
                        
                        @if(count($selectedLogos) > 0)
                            <div class="flex items-center gap-2">
                                <flux:badge variant="info">
                                    {{ count($selectedLogos) }} selected
                                </flux:badge>
                                <flux:button 
                                    variant="ghost" 
                                    size="sm"
                                    wire:click="clearSelection"
                                    icon="x-mark"
                                >
                                    Clear
                                </flux:button>
                            </div>
                        @endif
                    </div>

                    {{-- Color Scheme Selection --}}
                    @if(count($selectedLogos) > 0)
                        <div class="space-y-4">
                            <flux:label class="text-base font-medium">Choose Color Scheme</flux:label>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                                @foreach($this->colorSchemes as $schemeId => $scheme)
                                    <div class="relative">
                                        <input 
                                            type="radio" 
                                            id="scheme-{{ $schemeId }}" 
                                            name="colorScheme" 
                                            value="{{ $schemeId }}"
                                            wire:model.live="selectedColorScheme"
                                            class="sr-only peer"
                                        />
                                        <label 
                                            for="scheme-{{ $schemeId }}" 
                                            class="flex flex-col items-center p-3 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-gray-300 dark:hover:border-gray-600 peer-checked:border-blue-500 dark:peer-checked:border-blue-400 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all"
                                        >
                                            {{-- Color Palette Preview --}}
                                            <div class="flex gap-1 mb-2">
                                                <div class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: {{ $scheme['colors']['primary'] }}"></div>
                                                <div class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: {{ $scheme['colors']['secondary'] }}"></div>
                                                <div class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: {{ $scheme['colors']['accent'] }}"></div>
                                            </div>
                                            
                                            <span class="text-xs font-medium text-center text-gray-900 dark:text-white">
                                                {{ $scheme['name'] }}
                                            </span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                                {{ Str::limit($scheme['description'], 30) }}
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            @if($selectedColorScheme)
                                <div class="flex justify-center pt-2">
                                    <flux:button 
                                        variant="primary" 
                                        wire:click="applyColorScheme"
                                        :disabled="$isCustomizingColors"
                                        class="px-6"
                                    >
                                        @if($isCustomizingColors)
                                            <flux:icon.arrow-path class="size-4 animate-spin mr-2" />
                                            Customizing Colors...
                                        @else
                                            <flux:icon.sparkles class="size-4 mr-2" />
                                            Apply Color Scheme
                                        @endif
                                    </flux:button>
                                </div>
                            @endif
                            
                            <flux:error name="selectedColorScheme" />
                            <flux:error name="selectedLogos" />
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        {{-- Logo Gallery --}}
        @if($this->logoGeneration->generatedLogos->isNotEmpty() || $this->uploadedLogos->isNotEmpty())
            @if($viewMode === 'grid')
                {{-- Grid View --}}
                <div class="space-y-8">
                    {{-- Uploaded Logos Section --}}
                    @if($this->uploadedLogos->isNotEmpty())
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Uploaded Logos
                                    <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                                        ({{ $this->uploadedLogos->count() }} logos)
                                    </span>
                                </h3>
                                <flux:badge variant="outline" class="text-xs">
                                    Your uploads
                                </flux:badge>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                                @foreach($this->uploadedLogos as $uploadedLogo)
                                    <div class="relative group">
                                        {{-- Logo Card --}}
                                        <div class="aspect-square bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition-all duration-200">
                                            {{-- Logo Image --}}
                                            <div class="w-full h-full flex items-center justify-center">
                                                @if($uploadedLogo->fileExists())
                                                    <img 
                                                        src="{{ $uploadedLogo->getFileUrl() }}" 
                                                        alt="{{ $uploadedLogo->getDisplayName() }}"
                                                        class="max-w-full max-h-full object-contain"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="w-full h-full bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                                        <flux:icon.photo class="size-8 text-gray-400" />
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            {{-- Logo Actions Overlay --}}
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center gap-2">
                                                <flux:button 
                                                    variant="primary" 
                                                    size="sm"
                                                    wire:click="downloadUploadedLogo({{ $uploadedLogo->id }})"
                                                    class="text-xs"
                                                >
                                                    <flux:icon.arrow-down-tray class="size-3 mr-1" />
                                                    Download
                                                </flux:button>
                                                <flux:button 
                                                    variant="danger" 
                                                    size="sm"
                                                    wire:click="deleteUploadedLogo({{ $uploadedLogo->id }})"
                                                    wire:confirm="Are you sure you want to delete this logo?"
                                                    class="text-xs"
                                                >
                                                    <flux:icon.trash class="size-3 mr-1" />
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </div>
                                        
                                        {{-- Logo Info --}}
                                        <div class="mt-2 text-center">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $uploadedLogo->original_name }}">
                                                {{ $uploadedLogo->getDisplayName() }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                                {{ $uploadedLogo->getFormattedFileSize() }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Generated Logos Sections --}}
                    @foreach($this->logosByStyle as $styleGroup)
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $styleGroup['display_name'] }} Style
                                    <span class="text-sm font-normal text-gray-600 dark:text-gray-400">
                                        ({{ $styleGroup['logos']->count() }} logos)
                                    </span>
                                </h3>
                                <flux:badge variant="outline" class="text-xs">
                                    AI Generated
                                </flux:badge>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                                @foreach($styleGroup['logos'] as $logo)
                                    <div class="relative group">
                                        {{-- Logo Card --}}
                                        <div class="aspect-square bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-lg transition-all duration-200 @if(in_array($logo->id, $selectedLogos)) ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-900 @endif">
                                            {{-- Selection Checkbox --}}
                                            @if($this->logoGeneration->status === 'completed')
                                                <div class="absolute top-2 left-2 z-10">
                                                    <input 
                                                        type="checkbox" 
                                                        wire:click="toggleLogoSelection({{ $logo->id }})"
                                                        @if(in_array($logo->id, $selectedLogos)) checked @endif
                                                        class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                    >
                                                </div>
                                            @endif
                                            
                                            {{-- Logo Image --}}
                                            <div class="w-full h-full flex items-center justify-center">
                                                @if(Storage::disk('public')->exists($logo->original_file_path))
                                                    <img 
                                                        src="{{ Storage::disk('public')->url($logo->original_file_path) }}" 
                                                        alt="Logo variation {{ $logo->variation_number }}"
                                                        class="max-w-full max-h-full object-contain"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="w-full h-full bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                                        <flux:icon.photo class="size-8 text-gray-400" />
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            {{-- Logo Actions Overlay --}}
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center gap-2">
                                                <flux:button 
                                                    variant="primary" 
                                                    size="sm"
                                                    wire:click="downloadLogo({{ $logo->id }}, 'svg')"
                                                    class="text-xs"
                                                >
                                                    <flux:icon.arrow-down-tray class="size-3 mr-1" />
                                                    SVG
                                                </flux:button>
                                                <flux:button 
                                                    variant="primary" 
                                                    size="sm"
                                                    wire:click="downloadLogo({{ $logo->id }}, 'png')"
                                                    class="text-xs"
                                                >
                                                    <flux:icon.arrow-down-tray class="size-3 mr-1" />
                                                    PNG
                                                </flux:button>
                                            </div>
                                        </div>
                                        
                                        {{-- Color Variants --}}
                                        @if($logo->colorVariants->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($logo->colorVariants as $variant)
                                                    <flux:button 
                                                        variant="ghost" 
                                                        size="sm"
                                                        wire:click="downloadLogo({{ $logo->id }}, 'svg', '{{ $variant->color_scheme }}')"
                                                        class="text-xs px-2 py-1"
                                                        title="Download {{ ucwords(str_replace('_', ' ', $variant->color_scheme)) }} variant"
                                                    >
                                                        <div class="w-3 h-3 rounded-full mr-1" style="background-color: {{ $this->colorSchemes[$variant->color_scheme]['colors']['primary'] ?? '#000' }}"></div>
                                                        {{ ucwords(str_replace('_', ' ', $variant->color_scheme)) }}
                                                    </flux:button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- List View --}}
                <flux:card>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    @if($this->logoGeneration->status === 'completed')
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <input 
                                                type="checkbox" 
                                                class="rounded"
                                                @if(count($selectedLogos) === $this->logoGeneration->generatedLogos->count() && count($selectedLogos) > 0) checked @endif
                                            >
                                        </th>
                                    @endif
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Preview
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Style
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Details
                                    </th>
                                    <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                {{-- Uploaded Logos --}}
                                @foreach($this->uploadedLogos as $uploadedLogo)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        @if($this->logoGeneration->status === 'completed')
                                            <td class="px-6 py-4">
                                                {{-- No checkbox for uploaded logos in color customization --}}
                                                <span class="text-gray-400">-</span>
                                            </td>
                                        @endif
                                        
                                        <td class="px-6 py-4">
                                            <div class="w-16 h-16 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded p-2 flex items-center justify-center">
                                                @if($uploadedLogo->fileExists())
                                                    <img 
                                                        src="{{ $uploadedLogo->getFileUrl() }}" 
                                                        alt="{{ $uploadedLogo->getDisplayName() }}"
                                                        class="max-w-full max-h-full object-contain"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <flux:icon.photo class="size-6 text-gray-400" />
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <flux:badge variant="info" class="text-xs">
                                                Uploaded
                                            </flux:badge>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $uploadedLogo->original_name }}">
                                                    {{ $uploadedLogo->getDisplayName() }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $uploadedLogo->getFormattedFileSize() }}
                                                    @if($uploadedLogo->image_width && $uploadedLogo->image_height)
                                                        • {{ $uploadedLogo->image_width }}x{{ $uploadedLogo->image_height }}px
                                                    @endif
                                                </p>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button 
                                                    variant="outline" 
                                                    size="sm"
                                                    wire:click="downloadUploadedLogo({{ $uploadedLogo->id }})"
                                                >
                                                    <flux:icon.arrow-down-tray class="size-4" />
                                                </flux:button>
                                                <flux:button 
                                                    variant="danger" 
                                                    size="sm"
                                                    wire:click="deleteUploadedLogo({{ $uploadedLogo->id }})"
                                                    wire:confirm="Are you sure you want to delete this logo?"
                                                >
                                                    <flux:icon.trash class="size-4" />
                                                </flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Generated Logos --}}
                                @foreach($this->logoGeneration->generatedLogos as $logo)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 @if(in_array($logo->id, $selectedLogos)) bg-blue-50 dark:bg-blue-900/20 @endif">
                                        @if($this->logoGeneration->status === 'completed')
                                            <td class="px-6 py-4">
                                                <input 
                                                    type="checkbox" 
                                                    wire:click="toggleLogoSelection({{ $logo->id }})"
                                                    @if(in_array($logo->id, $selectedLogos)) checked @endif
                                                    class="rounded"
                                                >
                                            </td>
                                        @endif
                                        
                                        <td class="px-6 py-4">
                                            <div class="w-16 h-16 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded p-2 flex items-center justify-center">
                                                @if(Storage::disk('public')->exists($logo->original_file_path))
                                                    <img 
                                                        src="{{ Storage::disk('public')->url($logo->original_file_path) }}" 
                                                        alt="Logo {{ $logo->variation_number }}"
                                                        class="max-w-full max-h-full object-contain"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <flux:icon.photo class="size-6 text-gray-400" />
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <flux:badge variant="outline">
                                                {{ ucfirst($logo->style) }}
                                            </flux:badge>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            @if($logo->colorVariants->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($logo->colorVariants as $variant)
                                                        <div class="flex items-center gap-1 text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $this->colorSchemes[$variant->color_scheme]['colors']['primary'] ?? '#000' }}"></div>
                                                            {{ ucwords(str_replace('_', ' ', $variant->color_scheme)) }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Original only</span>
                                            @endif
                                        </td>
                                        
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:dropdown>
                                                    <flux:button variant="outline" size="sm" icon="arrow-down-tray">
                                                        Download
                                                    </flux:button>
                                                    <flux:menu>
                                                        <flux:menu.item wire:click="downloadLogo({{ $logo->id }}, 'svg')" icon="document">
                                                            Original SVG
                                                        </flux:menu.item>
                                                        <flux:menu.item wire:click="downloadLogo({{ $logo->id }}, 'png')" icon="photo">
                                                            Original PNG
                                                        </flux:menu.item>
                                                        
                                                        @if($logo->colorVariants->isNotEmpty())
                                                            <flux:menu.separator />
                                                            @foreach($logo->colorVariants as $variant)
                                                                <flux:menu.item wire:click="downloadLogo({{ $logo->id }}, 'svg', '{{ $variant->color_scheme }}')" class="text-sm">
                                                                    {{ ucwords(str_replace('_', ' ', $variant->color_scheme)) }} SVG
                                                                </flux:menu.item>
                                                            @endforeach
                                                        @endif
                                                    </flux:menu>
                                                </flux:dropdown>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </flux:card>
            @endif
        @elseif($this->logoGeneration->status === 'completed' && $this->uploadedLogos->isEmpty())
            {{-- No Logos Available --}}
            <flux:card class="p-12 text-center">
                <flux:icon.photo class="size-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    No logos available
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    No logos have been generated or uploaded yet.
                </p>
            </flux:card>
        @endif

        {{-- Error State --}}
        @if($this->logoGeneration->status === 'failed')
            <flux:card class="p-8 text-center">
                <flux:icon.exclamation-triangle class="size-16 text-red-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Logo generation failed
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ $this->logoGeneration->error_message ?? 'An error occurred during logo generation.' }}
                </p>
                <flux:button variant="outline" wire:click="loadLogoGeneration">
                    <flux:icon.arrow-path class="size-4 mr-2" />
                    Refresh Status
                </flux:button>
            </flux:card>
        @endif
    @else
        {{-- Logo Generation Not Found --}}
        <flux:card class="p-12 text-center">
            <flux:icon.exclamation-triangle class="size-16 text-yellow-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Logo generation not found
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                The requested logo generation could not be found or has been deleted.
            </p>
        </flux:card>
    @endif
</div>