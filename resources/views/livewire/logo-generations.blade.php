<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 py-4 px-3
            sm:py-6 sm:px-4
            lg:py-8 lg:px-8">
    <div class="w-full max-w-full mx-auto
                lg:max-w-7xl">
        {{-- Header --}}
        <div class="mb-6 p-6 bg-white dark:bg-gray-800 rounded-xl border-[4px] border-gray-300 dark:border-gray-600 shadow-lg
                    sm:mb-8 sm:p-8">
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
                        class="block w-full pl-10 pr-3 py-2 border-[3px] border-gray-300 dark:border-gray-500 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:border-primary-400 shadow-sm hover:shadow-md transition-all"
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
                        class="block w-full px-3 py-2 pr-8 border-[3px] border-gray-300 dark:border-gray-500 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:border-primary-400 text-sm appearance-none bg-no-repeat shadow-sm hover:shadow-md transition-all
                               sm:w-auto sm:px-4 sm:pr-10"
                        style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27none%27%3e%3cpath d=%27M7 7l3 3 3-3%27 stroke=%27%239ca3af%27 stroke-width=%271.5%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3e%3c/svg%3e'); background-position: right 0.25rem center; background-size: 1.25rem 1.25rem;"
                    >
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="alphabetical">A-Z</option>
                        <option value="favorited">Favorited</option>
                    </select>

                    @if($logoGenerations->count() >= 2)
                        <button
                            wire:click="confirmDeleteAllGenerations"
                            class="inline-flex items-center justify-center gap-2 w-full px-3 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium rounded-lg transition-all shadow-md hover:shadow-xl whitespace-nowrap text-sm border-[3px] border-red-700 dark:border-red-400 hover:scale-105 active:scale-95
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
                        sm:grid-cols-2 sm:gap-5
                        md:grid-cols-3 md:gap-6
                        lg:grid-cols-4 lg:gap-8
                        xl:grid-cols-5
                        2xl:grid-cols-6">
                @foreach($logoGenerations as $generation)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all relative flex flex-col border-[3px] border-gray-300 dark:border-gray-500 hover:border-primary-400 dark:hover:border-primary-600 hover:-translate-y-1">
                        <a href="{{ route('logo.gallery', $generation) }}"
                           wire:navigate
                           class="block flex-1 flex flex-col">

                        {{-- Preview Grid --}}
                        <div class="aspect-square p-1.5
                                    sm:p-2
                                    md:p-3"
                             style="background-color: rgb(243, 244, 246) !important;">
                            @if($generation->generatedLogos->isNotEmpty())
                                <div class="grid grid-cols-2 gap-1 h-full
                                            sm:gap-1.5
                                            md:gap-2">
                                    @foreach($generation->generatedLogos->take(4) as $logo)
                                        @if($logo->status === 'completed' && $logo->file_path)
                                            <div class="rounded flex items-center justify-center p-1 shadow-sm
                                                        sm:p-1.5
                                                        md:p-2"
                                                 style="background-color: rgb(229, 231, 235) !important;">
                                                <img src="{{ $logo->url }}"
                                                     alt="{{ $logo->style }}"
                                                     class="w-full h-full object-contain">
                                            </div>
                                        @else
                                            <div class="bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center p-1
                                                        sm:p-1.5
                                                        md:p-2">
                                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500
                                                            sm:h-6 sm:w-6
                                                            md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="flex items-center justify-center h-full">
                                    <svg class="h-10 w-10 text-gray-400 dark:text-gray-600
                                                sm:h-12 sm:w-12
                                                md:h-16 md:w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="p-2.5 pr-16 bg-gray-50 dark:bg-gray-750 border-t-[3px] border-gray-300 dark:border-gray-600
                                    sm:p-3 sm:pr-20">
                            <div class="flex items-center gap-1.5">
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white truncate
                                           sm:text-sm">
                                    {{ $generation->business_name }}
                                </h3>
                                @if($generation->is_saved)
                                    <svg class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0
                                                sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                @endif
                            </div>

                            <div class="mt-1.5 flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium w-fit
                                            sm:px-2
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

                        {{-- Action Buttons --}}
                        <div class="absolute bottom-2 right-2 z-10 flex gap-1
                                    sm:bottom-2 sm:right-2 sm:gap-1.5">
                            {{-- Share Button --}}
                            <button
                                wire:click.prevent="$dispatch('openShareModal', { generationId: {{ $generation->id }} })"
                                class="p-1.5 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white rounded-md transition-all shadow-lg hover:shadow-xl border-2 border-primary-700 dark:border-primary-300 hover:scale-110 active:scale-95
                                       sm:p-2"
                                title="Share project"
                            >
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </button>

                            {{-- Delete Button --}}
                            <button
                                wire:click.prevent="confirmDelete({{ $generation->id }})"
                                class="p-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-md transition-all shadow-lg hover:shadow-xl border-2 border-red-700 dark:border-red-300 hover:scale-110 active:scale-95
                                       sm:p-2"
                                title="Delete all logos"
                            >
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
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

    {{-- Share Modals for Each Generation --}}
    @foreach($logoGenerations as $generation)
        @livewire('share-modal', ['logoGeneration' => $generation], key('share-modal-'.$generation->id))
    @endforeach
</div>
