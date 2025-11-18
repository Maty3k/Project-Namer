<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 py-4 px-3
            sm:py-6 sm:px-4
            lg:py-8 lg:px-8">
    <div class="w-full max-w-full mx-auto
                lg:max-w-7xl">
        {{-- Header --}}
        <div class="mb-6
                    sm:mb-8">
            <div class="flex flex-col gap-4
                        md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-3">
                    {{-- Back Button --}}
                    <a href="{{ route('logos.index') }}"
                       wire:navigate
                       class="inline-flex items-center justify-center w-10 h-10 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg transition-all shadow-sm hover:shadow-md border-[3px] border-gray-300 dark:border-gray-500 hover:border-primary-400 dark:hover:border-primary-600
                              sm:w-11 sm:h-11"
                       title="Back to Logo Generations">
                        <svg class="w-5 h-5
                                    sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>

                    <div>
                        <h1 class="font-bold text-gray-900 dark:text-white text-2xl
                                   sm:text-3xl">
                            Logo Gallery
                        </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 text-xs
                              sm:text-sm">
                        {{ $logoGeneration->business_name }}
                    </p>
                    @if($logoGeneration->business_description)
                        <p class="mt-1 text-gray-500 dark:text-gray-500 text-xs
                                  sm:text-sm">
                            {{ $logoGeneration->business_description }}
                        </p>
                    @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if($logos->isNotEmpty())
                    <div class="flex flex-col gap-2 w-full
                                sm:flex-row sm:gap-3 sm:w-auto">
                        <button
                            wire:click="downloadAll"
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white font-medium rounded-lg transition-all shadow-md hover:shadow-xl text-sm border-[3px] border-primary-700 dark:border-primary-300 hover:scale-105 active:scale-95
                                   sm:px-4"
                        >
                            <svg class="w-4 h-4
                                        sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download All (ZIP)
                        </button>

                        <button
                            wire:click="toggleSaved"
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 {{ $logoGeneration->is_saved ? 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 border-yellow-700 dark:border-yellow-300' : 'bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 border-gray-700 dark:border-gray-300' }} text-white font-medium rounded-lg transition-all shadow-md hover:shadow-xl text-sm border-[3px] hover:scale-105 active:scale-95
                                   sm:px-4"
                        >
                            <svg class="w-4 h-4
                                        sm:w-5 sm:h-5" fill="{{ $logoGeneration->is_saved ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ $logoGeneration->is_saved ? 'Favorited' : 'Favorite' }}
                        </button>
                    </div>
                @endif
            </div>

            {{-- Status Badge --}}
            <div class="mt-4 flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                    {{ $logoGeneration->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                    {{ $logoGeneration->status === 'processing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                    {{ $logoGeneration->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                ">
                    @if($logoGeneration->status === 'completed')
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                    {{ ucfirst($logoGeneration->status) }}
                </span>

                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $logoGeneration->logos_completed }} / {{ $logoGeneration->total_logos_requested }} logos
                </span>
            </div>
        </div>

        {{-- Logo Grid --}}
        @if($logos->isEmpty())
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No logos yet</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Logos are still being generated or generation failed.
                </p>
            </div>
        @else
            <div class="grid gap-3 grid-cols-2
                        xs:gap-4
                        sm:grid-cols-3 sm:gap-5
                        md:grid-cols-4 md:gap-6
                        lg:grid-cols-5 lg:gap-6
                        xl:grid-cols-6">
                @foreach($logos as $logo)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all flex flex-col border-[3px] border-gray-300 dark:border-gray-500 hover:border-primary-400 dark:hover:border-primary-600 hover:-translate-y-1">
                        {{-- Logo Image --}}
                        <div
                            @if($logo->file_path && $logo->status === 'completed')
                                wire:click="previewLogo({{ $logo->id }})"
                                style="background-color: rgb(243, 244, 246) !important;"
                                class="aspect-square flex items-center justify-center p-3 cursor-pointer transition-colors shadow-sm
                                       sm:p-4
                                       md:p-5"
                            @else
                                class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-3
                                       sm:p-4
                                       md:p-5"
                            @endif
                        >
                            @if($logo->file_path && $logo->status === 'completed')
                                <img
                                    src="{{ $logo->url }}"
                                    alt="{{ $logo->style }} logo"
                                    class="w-full h-full object-contain"
                                >
                            @else
                                <div class="text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400 dark:text-gray-600
                                                sm:h-12 sm:w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400
                                              sm:mt-2 sm:text-sm">
                                        {{ ucfirst($logo->status) }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Logo Info --}}
                        <div class="p-2 bg-gray-50 dark:bg-gray-750 border-t-[3px] border-gray-300 dark:border-gray-600
                                    sm:p-2.5
                                    md:p-3">
                            <h3 class="text-xs font-semibold text-gray-900 dark:text-white capitalize
                                       sm:text-sm">
                                {{ $logo->style }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                1024x1024
                            </p>

                            {{-- Action Buttons --}}
                            @if($logo->status === 'completed')
                                <div class="mt-2 flex gap-1
                                            sm:mt-2.5 sm:gap-1.5">
                                    <button
                                        wire:click="downloadLogo({{ $logo->id }})"
                                        class="flex-1 inline-flex items-center justify-center gap-1 px-1.5 py-1.5 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white text-xs font-medium rounded-md transition-colors
                                               sm:px-2
                                               md:gap-1.5"
                                    >
                                        <svg class="w-3 h-3
                                                    sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span class="hidden
                                                     sm:inline">
                                            DL
                                        </span>
                                    </button>

                                    <button
                                        wire:click="confirmDelete({{ $logo->id }})"
                                        class="inline-flex items-center justify-center px-1.5 py-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors
                                               sm:px-2"
                                        title="Delete logo"
                                    >
                                        <svg class="w-3 h-3
                                                    sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Preview Modal --}}
    @if($showPreviewModal && $logoToPreview)
        <flux:modal wire:model="showPreviewModal" class="max-w-4xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg" class="capitalize">{{ $logoToPreview->style }} Style</flux:heading>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $logoGeneration->business_name }}
                    </p>
                </div>

                {{-- Large Logo Preview --}}
                <div class="bg-white dark:bg-white rounded-lg p-8 flex items-center justify-center min-h-[400px] shadow-sm">
                    <img
                        src="{{ $logoToPreview->url }}"
                        alt="{{ $logoToPreview->style }} logo"
                        class="max-w-full max-h-[500px] object-contain"
                    >
                </div>

                {{-- Logo Details --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Style</p>
                        <p class="font-medium text-gray-900 dark:text-white capitalize">{{ $logoToPreview->style }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Size</p>
                        <p class="font-medium text-gray-900 dark:text-white">1024x1024</p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="downloadLogo({{ $logoToPreview->id }})"
                        variant="primary"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </flux:button>
                    <flux:button
                        wire:click="confirmDelete({{ $logoToPreview->id }})"
                        variant="danger"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <flux:modal wire:model="showDeleteModal" class="min-w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Logo</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete this logo? This action cannot be undone.
                    </p>

                    @if($logoToDelete)
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="font-medium text-sm capitalize">{{ $logoToDelete->style }} Style</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $logoGeneration->business_name }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="cancelDelete"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="deleteLogo"
                        variant="danger"
                    >
                        Delete Logo
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
