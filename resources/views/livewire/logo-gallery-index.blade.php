<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Logo Gallery</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">View and manage your generated logos</p>
            </div>
            
            <!-- Actions -->
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                    <flux:icon.plus class="size-4 mr-2" />
                    Generate New Logos
                </flux:button>
            </div>
        </div>

        <!-- Filters -->
        <flux:card class="mb-8">
            <div class="p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <flux:input 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by business name or description..."
                            class="w-full"
                        >
                            <flux:icon.magnifying-glass class="size-5" slot="leading" />
                        </flux:input>
                    </div>
                    
                    <div class="md:w-48">
                        <flux:select wire:model.live="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="processing">Processing</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </flux:select>
                    </div>
                    
                    @if($search || $statusFilter)
                        <flux:button variant="ghost" wire:click="clearFilters">
                            Clear Filters
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Logo Generations Grid -->

        <!-- Skeleton Loading State -->
        <div wire:loading wire:target="search,statusFilter,clearFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @for($i = 0; $i < 8; $i++)
                <x-skeleton-logo-card />
            @endfor
        </div>

        <!-- Actual Content -->
        <div wire:loading.remove wire:target="search,statusFilter,clearFilters">
            @if($logoGenerations->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    @foreach($logoGenerations as $generation)
                    <flux:card class="hover:shadow-lg transition-shadow duration-200">
                        <div class="p-6">
                            <!-- Generation Info -->
                            <div class="mb-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-lg mb-2 line-clamp-2">
                                    {{ $generation->business_name }}
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 text-sm line-clamp-3">
                                    {{ $generation->business_description }}
                                </p>
                            </div>

                            <!-- Status Badge -->
                            <div class="mb-4">
                                @if($generation->status === 'completed')
                                    <flux:badge variant="success">Completed</flux:badge>
                                @elseif($generation->status === 'processing')
                                    <flux:badge variant="warning">Processing</flux:badge>
                                @elseif($generation->status === 'pending')
                                    <flux:badge variant="info">Pending</flux:badge>
                                @else
                                    <flux:badge variant="danger">{{ ucfirst($generation->status) }}</flux:badge>
                                @endif
                            </div>

                            <!-- Logo Preview -->
                            @if($generation->status === 'completed' && $generation->generatedLogos->count() > 0)
                                <div class="mb-4">
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($generation->generatedLogos->take(4) as $logo)
                                            @if($logo->original_file_path && file_exists(storage_path('app/public/' . $logo->original_file_path)))
                                                <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                                    <img 
                                                        src="{{ asset('storage/' . $logo->original_file_path) }}" 
                                                        alt="Logo preview"
                                                        class="w-full h-full object-contain"
                                                    />
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Stats -->
                            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                <span>{{ $generation->generatedLogos->count() }} {{ Str::plural('logo', $generation->generatedLogos->count()) }}</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-2">
                                    @if($generation->status === 'completed' && $generation->generatedLogos->count() > 0)
                                        <flux:button
                                            variant="primary"
                                            size="sm"
                                            wire:click="downloadLogos({{ $generation->id }})"
                                            class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 dark:from-primary-500 dark:to-primary-600 dark:hover:from-primary-600 dark:hover:to-primary-700 text-white font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5"
                                        >
                                            <span class="text-sm">Download All Logos</span>
                                            <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs font-bold">
                                                {{ $generation->generatedLogos->count() }}
                                            </span>
                                        </flux:button>
                                    @else
                                        <flux:button variant="ghost" size="sm" disabled class="flex-1">
                                            {{ ucfirst($generation->status) }}
                                        </flux:button>
                                    @endif

                                    <flux:button
                                        variant="danger"
                                        size="sm"
                                        wire:click="confirmDelete({{ $generation->id }})"
                                        class="px-3 py-2"
                                    >
                                        <flux:icon.trash class="size-4" />
                                    </flux:button>
                                </div>

                                <div class="flex items-center justify-between px-1">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        Generated on
                                    </span>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $generation->created_at->format('M j, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <!-- Pagination -->
            {{ $logoGenerations->links() }}
        @else
            <!-- Empty State -->
            <flux:card>
                <div class="p-12 text-center">
                    <flux:icon.photo class="size-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Logo Generations Found</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        @if($search || $statusFilter)
                            No logo generations match your current filters.
                        @else
                            You haven't generated any logos yet. Start by creating business names and generating logos for them.
                        @endif
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        @if($search || $statusFilter)
                            <flux:button variant="ghost" wire:click="clearFilters">
                                Clear Filters
                            </flux:button>
                        @endif
                        <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                            Generate Your First Logos
                        </flux:button>
                    </div>
                </div>
            </flux:card>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirmation)
        <flux:modal wire:model="showDeleteConfirmation" class="min-w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Logo Generation</flux:heading>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Are you sure you want to delete these logos? This action cannot be undone.
                    </p>

                    @if($generationToDelete)
                        @php
                            $generation = $logoGenerations->firstWhere('id', $generationToDelete);
                        @endphp
                        @if($generation)
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <p class="font-medium text-sm">{{ $generation->business_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $generation->business_description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $generation->generatedLogos->count() }} {{ Str::plural('logo', $generation->generatedLogos->count()) }}
                                </p>
                            </div>
                        @endif
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
                        wire:click="deleteGeneration"
                        variant="danger"
                    >
                        Delete Logos
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

@script
<script>
    // Handle download-file event to trigger logo downloads
    window.addEventListener('download-file', (event) => {
        window.open(event.detail.url, '_blank');
    });
</script>
@endscript