@props([
    'logoGeneration',
    'showDetails' => true,
    'compact' => false
])

@php
$progressPercentage = $logoGeneration->total_logos_requested > 0 
    ? round(($logoGeneration->logos_completed / $logoGeneration->total_logos_requested) * 100)
    : 0;

$estimatedTimeRemaining = null;
$timeMessage = '';

if ($logoGeneration->status === 'processing' && $logoGeneration->logos_completed > 0) {
    $remainingLogos = $logoGeneration->total_logos_requested - $logoGeneration->logos_completed;
    $estimatedTimeRemaining = $remainingLogos * 30; // 30 seconds per logo
    
    if ($estimatedTimeRemaining < 60) {
        $timeMessage = "About {$estimatedTimeRemaining} seconds remaining";
    } else {
        $minutes = ceil($estimatedTimeRemaining / 60);
        $timeMessage = "About {$minutes} minute" . ($minutes > 1 ? 's' : '') . " remaining";
    }
}

$statusMessages = [
    'pending' => 'Preparing logo generation...',
    'processing' => $progressPercentage > 0 
        ? "Generating logos... {$progressPercentage}% complete"
        : 'Initializing logo generation...',
    'completed' => 'All logos generated successfully!',
    'failed' => 'Logo generation failed',
    'partial' => "Generated {$logoGeneration->logos_completed} of {$logoGeneration->total_logos_requested} logos"
];

$currentMessage = $statusMessages[$logoGeneration->status] ?? 'Processing...';
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
     {{ $attributes->merge(['class' => $compact ? 'p-3' : 'p-6']) }}>
    
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            @if($logoGeneration->status === 'processing')
                <div class="flex-shrink-0 mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            @elseif($logoGeneration->status === 'completed')
                <div class="flex-shrink-0 mr-3">
                    <flux:icon.check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
            @elseif($logoGeneration->status === 'failed')
                <div class="flex-shrink-0 mr-3">
                    <flux:icon.exclamation-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
            @elseif($logoGeneration->status === 'partial')
                <div class="flex-shrink-0 mr-3">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                </div>
            @else
                <div class="flex-shrink-0 mr-3">
                    <flux:icon.clock class="w-5 h-5 text-gray-400" />
                </div>
            @endif
            
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $currentMessage }}
                </h3>
                @if($timeMessage && !$compact)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $timeMessage }}
                    </p>
                @endif
            </div>
        </div>
        
        @if($showDetails && !$compact)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $logoGeneration->logos_completed }}/{{ $logoGeneration->total_logos_requested }}
            </div>
        @endif
    </div>
    
    {{-- Progress Bar --}}
    <div class="relative">
        <div class="flex mb-2 items-center justify-between">
            <div>
                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full
                           {{ $logoGeneration->status === 'completed' 
                               ? 'text-green-600 bg-green-200 dark:bg-green-900 dark:text-green-400'
                               : ($logoGeneration->status === 'failed' 
                                   ? 'text-red-600 bg-red-200 dark:bg-red-900 dark:text-red-400'
                                   : ($logoGeneration->status === 'partial'
                                       ? 'text-amber-600 bg-amber-200 dark:bg-amber-900 dark:text-amber-400'
                                       : 'text-blue-600 bg-blue-200 dark:bg-blue-900 dark:text-blue-400')) }}">
                    {{ $progressPercentage }}%
                </span>
            </div>
            @if($estimatedTimeRemaining && $estimatedTimeRemaining > 0 && $compact)
                <div class="text-right">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $timeMessage }}
                    </span>
                </div>
            @endif
        </div>
        
        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded 
                    {{ $logoGeneration->status === 'completed' 
                        ? 'bg-green-200 dark:bg-green-900'
                        : ($logoGeneration->status === 'failed'
                            ? 'bg-red-200 dark:bg-red-900'
                            : ($logoGeneration->status === 'partial'
                                ? 'bg-amber-200 dark:bg-amber-900'
                                : 'bg-blue-200 dark:bg-blue-900')) }}">
            <div style="width: {{ $progressPercentage }}%"
                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500 ease-out
                        {{ $logoGeneration->status === 'completed' 
                            ? 'bg-green-500 dark:bg-green-400'
                            : ($logoGeneration->status === 'failed'
                                ? 'bg-red-500 dark:bg-red-400'
                                : ($logoGeneration->status === 'partial'
                                    ? 'bg-amber-500 dark:bg-amber-400'
                                    : 'bg-blue-500 dark:bg-blue-400')) }}">
            </div>
        </div>
    </div>
    
    {{-- Detailed Status Information --}}
    @if($showDetails && !$compact)
        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <div class="flex justify-between">
                <span>Business Name:</span>
                <span class="font-medium">{{ $logoGeneration->business_name }}</span>
            </div>
            <div class="flex justify-between">
                <span>Started:</span>
                <span>{{ $logoGeneration->created_at->diffForHumans() }}</span>
            </div>
            @if($logoGeneration->cost_cents > 0)
                <div class="flex justify-between">
                    <span>Estimated Cost:</span>
                    <span>${{ number_format($logoGeneration->cost_cents / 100, 2) }}</span>
                </div>
            @endif
        </div>
    @endif
    
    {{-- Actions for Interactive States --}}
    @if($showDetails && !$compact)
        <div class="mt-4 flex gap-2">
            @if($logoGeneration->status === 'processing')
                <flux:button
                    wire:click="refreshStatus"
                    variant="ghost"
                    size="sm"
                    class="flex-1"
                >
                    <flux:icon.arrow-path class="w-4 h-4 mr-1" />
                    Refresh Status
                </flux:button>
            @elseif($logoGeneration->status === 'failed')
                <flux:button
                    wire:click="retryGeneration"
                    variant="outline"
                    size="sm"
                    class="flex-1"
                >
                    <flux:icon.arrow-path class="w-4 h-4 mr-1" />
                    Try Again
                </flux:button>
            @elseif($logoGeneration->status === 'partial')
                <flux:button
                    wire:click="completeGeneration"
                    variant="primary"
                    size="sm"
                    class="flex-1"
                >
                    <flux:icon.plus class="w-4 h-4 mr-1" />
                    Complete Generation
                </flux:button>
            @elseif($logoGeneration->status === 'completed')
                <flux:button
                    wire:click="downloadAllLogos"
                    variant="primary"
                    size="sm"
                    class="flex-1"
                >
                    <flux:icon.arrow-down-tray class="w-4 h-4 mr-1" />
                    Download All
                </flux:button>
            @endif
        </div>
    @endif
    
    {{-- Auto-refresh indicator for processing status --}}
    @if($logoGeneration->status === 'processing')
        <div class="mt-3 flex items-center justify-center">
            <div class="flex items-center text-xs text-gray-400 dark:text-gray-500">
                <div class="w-2 h-2 bg-blue-400 rounded-full animate-ping mr-2"></div>
                Auto-refreshing every 5 seconds
            </div>
        </div>
    @endif
</div>