<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-4 px-3
            sm:py-6 sm:px-4
            lg:py-8 lg:px-8">
    <div class="w-full max-w-full mx-auto
                lg:max-w-7xl">
        {{-- Header --}}
        <div class="mb-6
                    sm:mb-8">
            <h1 class="font-bold text-gray-900 dark:text-white text-2xl
                       sm:text-3xl">
                Logo Generations
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400 text-xs
                      sm:text-sm">
                View all your AI-generated logo collections
            </p>

            {{-- Search and Filter --}}
            <div class="mt-4 flex flex-col gap-3
                        sm:mt-6 sm:gap-4 sm:flex-row sm:items-center sm:justify-between">
                {{-- Search Bar --}}
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by business name..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                </div>

                {{-- Filter and Actions --}}
                <div class="flex flex-col gap-3
                            sm:flex-row sm:items-center sm:gap-3">
                    <label for="filter" class="font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs
                                                sm:text-sm">
                        Sort by:
                    </label>
                    <select
                        id="filter"
                        wire:model.live="filterBy"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm
                               sm:w-auto sm:px-4"
                    >
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="alphabetical">A-Z</option>
                        <option value="favorited">Favorited</option>
                    </select>

                    @if($logoGenerations->count() >= 2)
                        <button
                            wire:click="confirmDeleteAllGenerations"
                            class="inline-flex items-center justify-center gap-2 w-full px-3 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium rounded-lg transition-colors shadow-sm hover:shadow-md whitespace-nowrap text-sm
                                   sm:w-auto sm:px-4"
                        >
                            <svg class="w-4 h-4
                                        sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete All
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Logo Generations Grid --}}
        @if($logoGenerations->isEmpty())
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No logo generations yet</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Generate logos from the Name Generator to see them here.
                </p>
            </div>
        @else
            <div class="grid gap-4 grid-cols-1
                        xs:grid-cols-2 xs:gap-5
                        sm:grid-cols-3 sm:gap-6
                        md:grid-cols-4
                        lg:grid-cols-5 lg:gap-8
                        xl:grid-cols-6">
                @foreach($logoGenerations as $generation)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow relative">
                        {{-- Delete Button --}}
                        <button
                            wire:click.prevent="confirmDelete({{ $generation->id }})"
                            class="absolute bottom-2 right-2 z-10 p-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-md transition-colors"
                            title="Delete all logos"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>

                        <a href="{{ route('logo.gallery', $generation) }}"
                           wire:navigate
                           class="block">

                        {{-- Preview Grid --}}
                        <div class="aspect-square bg-gray-100 dark:bg-gray-700 p-3">
                            @if($generation->generatedLogos->isNotEmpty())
                                <div class="grid grid-cols-2 gap-2 h-full">
                                    @foreach($generation->generatedLogos->take(4) as $logo)
                                        @if($logo->status === 'completed' && $logo->file_path)
                                            <div class="bg-white dark:bg-gray-600 rounded flex items-center justify-center p-2">
                                                <img src="{{ $logo->url }}"
                                                     alt="{{ $logo->style }}"
                                                     class="max-w-full max-h-full object-contain">
                                            </div>
                                        @else
                                            <div class="bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="flex items-center justify-center h-full">
                                    <svg class="h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="p-3">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $generation->business_name }}
                                </h3>
                                @if($generation->is_saved)
                                    <svg class="w-4 h-4 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                @endif
                            </div>

                            <div class="mt-1.5 flex flex-col gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $generation->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                    {{ $generation->status === 'processing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                    {{ $generation->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                                ">
                                    {{ ucfirst($generation->status) }}
                                </span>

                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $generation->logos_completed }}/{{ $generation->total_logos_requested }} logos
                                </span>

                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ $generation->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <flux:modal wire:model="showDeleteModal" class="min-w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete All Logos</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete all logos for this generation? This action cannot be undone.
                    </p>

                    @if($generationToDelete)
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="font-medium text-sm">{{ $generationToDelete->business_name }}</p>
                            @if($generationToDelete->business_description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $generationToDelete->business_description }}</p>
                            @endif
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <strong>{{ $generationToDelete->generatedLogos->count() }}</strong> {{ $generationToDelete->generatedLogos->count() === 1 ? 'logo' : 'logos' }} will be deleted
                            </p>
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
                        wire:click="deleteAllLogos"
                        variant="danger"
                    >
                        Delete All Logos
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Delete All Generations Confirmation Modal --}}
    @if($showDeleteAllModal)
        <flux:modal wire:model="showDeleteAllModal" class="min-w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete All Logo Generations</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete ALL {{ $logoGenerations->count() }} logo generations? This action cannot be undone.
                    </p>

                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                    Warning: This will permanently delete all generation cards and their logos
                                </p>
                                <p class="mt-1 text-xs text-red-700 dark:text-red-300">
                                    All {{ $logoGenerations->count() }} generation{{ $logoGenerations->count() !== 1 ? 's' : '' }} and their logos will be removed from storage and cannot be recovered.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <flux:button
                        wire:click="cancelDeleteAllGenerations"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        wire:click="deleteAllGenerations"
                        variant="danger"
                    >
                        Delete All Generations
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
