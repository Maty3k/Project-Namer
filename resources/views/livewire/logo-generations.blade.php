<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Logo Generations
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                View all your AI-generated logo collections
            </p>
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
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-8">
                @foreach($logoGenerations as $generation)
                    <a href="{{ route('logo.gallery', $generation) }}"
                       wire:navigate
                       class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow block">

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
                @endforeach
            </div>
        @endif
    </div>
</div>
