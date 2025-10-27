<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Logo Gallery
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ $logoGeneration->business_name }}
                    </p>
                    @if($logoGeneration->business_description)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                            {{ $logoGeneration->business_description }}
                        </p>
                    @endif
                </div>

                {{-- Action Buttons --}}
                @if($logos->isNotEmpty())
                    <div class="flex gap-3">
                        <button
                            wire:click="downloadAll"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download All (ZIP)
                        </button>

                        <button
                            wire:click="toggleSaved"
                            class="inline-flex items-center gap-2 px-4 py-2 {{ $logoGeneration->is_saved ? 'bg-yellow-600 hover:bg-yellow-700 dark:bg-yellow-500 dark:hover:bg-yellow-600' : 'bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600' }} text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md"
                        >
                            <svg class="w-5 h-5" fill="{{ $logoGeneration->is_saved ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-8">
                @foreach($logos as $logo)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        {{-- Logo Image --}}
                        <div
                            @if($logo->file_path && $logo->status === 'completed')
                                wire:click="previewLogo({{ $logo->id }})"
                                class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-3 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            @else
                                class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-3"
                            @endif
                        >
                            @if($logo->file_path && $logo->status === 'completed')
                                <img
                                    src="{{ $logo->url }}"
                                    alt="{{ $logo->style }} logo"
                                    class="max-w-full max-h-full object-contain"
                                >
                            @else
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($logo->status) }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Logo Info --}}
                        <div class="p-3">
                            <h3 class="text-xs font-semibold text-gray-900 dark:text-white capitalize">
                                {{ $logo->style }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                256x256
                            </p>

                            {{-- Action Buttons --}}
                            @if($logo->status === 'completed')
                                <div class="mt-3 flex gap-1.5">
                                    <button
                                        wire:click="downloadLogo({{ $logo->id }})"
                                        class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white text-xs font-medium rounded-md transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </button>

                                    <button
                                        wire:click="confirmDelete({{ $logo->id }})"
                                        class="inline-flex items-center justify-center px-2 py-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white text-xs font-medium rounded-md transition-colors"
                                        title="Delete logo"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 flex items-center justify-center min-h-[400px]">
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
                        <p class="font-medium text-gray-900 dark:text-white">256x256</p>
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
