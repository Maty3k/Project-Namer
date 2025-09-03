@props([
    'isGenerating' => false,
    'currentStep' => '',
    'progressPercentage' => 0,
    'selectedModels' => [],
    'modelProgress' => [],
    'deepThinking' => false,
    'estimatedTimeRemaining' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if($isGenerating)
        {{-- Main Progress Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6 ai-progress-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <flux:icon.cpu-chip class="size-5 text-blue-500" />
                    AI Generation in Progress
                </h3>
                @if($estimatedTimeRemaining)
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Est. {{ $estimatedTimeRemaining }}s remaining
                    </span>
                @endif
            </div>

            {{-- Overall Progress --}}
            <div class="space-y-2 mb-6">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $currentStep ?: 'Initializing...' }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $progressPercentage }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div 
                        class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500 ease-out"
                        style="width: {{ $progressPercentage }}%"
                    >
                        <div class="h-full bg-white/20 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </div>

            {{-- Deep Thinking Mode Indicator --}}
            @if($deepThinking)
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.sparkles class="size-5 text-purple-500 animate-pulse" />
                        <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                            Deep Thinking Mode Active
                        </span>
                    </div>
                    <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                        Enhanced processing for higher quality results
                    </p>
                </div>
            @endif

            {{-- Model Progress --}}
            @if(count($selectedModels) > 1 && !empty($modelProgress))
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Model Progress</h4>
                    <div class="space-y-3 md:space-y-3 ai-progress-models">
                        @foreach($selectedModels as $model)
                            @php
                                $progress = $modelProgress[$model] ?? ['status' => 'pending', 'progress' => 0];
                                $status = $progress['status'];
                                $modelProgressPercentage = $progress['progress'];
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg gap-3 ai-progress-model-item touch-action-manipulation">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="flex-shrink-0">
                                    @if($status === 'processing')
                                        <flux:icon.arrow-path class="size-4 text-blue-500 animate-spin" />
                                    @elseif($status === 'completed')
                                        <flux:icon.check-circle class="size-4 text-green-500" />
                                    @elseif($status === 'failed')
                                        <flux:icon.exclamation-triangle class="size-4 text-red-500" />
                                    @else
                                        <flux:icon.clock class="size-4 text-gray-400" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ ucwords(str_replace(['-', '_'], ' ', $model)) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($status) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 min-w-0">
                                    <div class="w-16 sm:w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2 ai-progress-bar">
                                        <div 
                                            class="h-2 rounded-full transition-all duration-300 {{ $status === 'completed' ? 'bg-green-500' : ($status === 'failed' ? 'bg-red-500' : 'bg-blue-500') }}"
                                            style="width: {{ $modelProgressPercentage }}%"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-8 text-right tabular-nums ai-progress-text">
                                        {{ $modelProgressPercentage }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Generation Steps Indicator --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Generation Pipeline</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($selectedModels) }} model{{ count($selectedModels) !== 1 ? 's' : '' }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @php
                            $steps = [
                                ['name' => 'Initialize', 'threshold' => 10, 'icon' => 'play', 'desc' => 'Starting AI models'],
                                ['name' => 'Process', 'threshold' => 40, 'icon' => 'cpu-chip', 'desc' => 'Processing input'],
                                ['name' => 'Generate', 'threshold' => 80, 'icon' => 'sparkles', 'desc' => 'Creating names'],
                                ['name' => 'Finalize', 'threshold' => 100, 'icon' => 'check', 'desc' => 'Completing generation'],
                            ];
                        @endphp
                        @foreach($steps as $step)
                            <div class="text-center p-3 rounded-lg {{ $progressPercentage >= $step['threshold'] ? 'bg-green-50 dark:bg-green-900/20' : ($progressPercentage >= ($step['threshold'] - 10) ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                                <div class="flex justify-center mb-2">
                                    @if($progressPercentage >= $step['threshold'])
                                        <flux:icon.check class="size-5 text-green-500" />
                                    @elseif($progressPercentage >= ($step['threshold'] - 10))
                                        <flux:icon.arrow-path class="size-5 text-blue-500 animate-spin" />
                                    @else
                                        @switch($step['icon'])
                                            @case('play')
                                                <flux:icon.play class="size-5 text-gray-400" />
                                                @break
                                            @case('cpu-chip')
                                                <flux:icon.cpu-chip class="size-5 text-gray-400" />
                                                @break
                                            @case('sparkles')
                                                <flux:icon.sparkles class="size-5 text-gray-400" />
                                                @break
                                            @default
                                                <flux:icon.check class="size-5 text-gray-400" />
                                        @endswitch
                                    @endif
                                </div>
                                <p class="text-xs font-medium {{ $progressPercentage >= $step['threshold'] ? 'text-green-700 dark:text-green-300' : ($progressPercentage >= ($step['threshold'] - 10) ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400') }}">
                                    {{ $step['name'] }}
                                </p>
                                <p class="text-xs {{ $progressPercentage >= $step['threshold'] ? 'text-green-600 dark:text-green-400' : ($progressPercentage >= ($step['threshold'] - 10) ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-500') }}">
                                    {{ $step['desc'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Metrics --}}
        @if($this->showPerformanceMetrics ?? false)
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-sm">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="font-medium text-gray-900 dark:text-white">{{ count($selectedModels) }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Models</p>
                    </div>
                    <div class="text-center">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $deepThinking ? 'Enhanced' : 'Standard' }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Mode</p>
                    </div>
                    <div class="text-center">
                        <p class="font-medium text-gray-900 dark:text-white" id="generation-timer">00:00</p>
                        <p class="text-gray-500 dark:text-gray-400">Elapsed</p>
                    </div>
                    <div class="text-center">
                        <p class="font-medium text-gray-900 dark:text-white">~{{ 10 * count($selectedModels) }}</p>
                        <p class="text-gray-500 dark:text-gray-400">Names</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Cancel Button --}}
        <div class="text-center">
            <flux:button 
                variant="outline" 
                size="sm"
                wire:click="cancelGeneration"
                class="text-red-600 hover:text-red-700 border-red-300 hover:border-red-400"
            >
                <flux:icon.x-mark class="size-4 mr-1" />
                Cancel Generation
            </flux:button>
        </div>
    @endif
</div>

@if($isGenerating)
    @script
    <script>
        // Timer for elapsed time
        let startTime = Date.now();
        let timerInterval = setInterval(() => {
            let elapsed = Math.floor((Date.now() - startTime) / 1000);
            let minutes = Math.floor(elapsed / 60);
            let seconds = elapsed % 60;
            let timerElement = document.getElementById('generation-timer');
            if (timerElement) {
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);

        // Clean up timer when generation completes
        Livewire.on('generation-completed', () => {
            clearInterval(timerInterval);
        });
        
        Livewire.on('generation-cancelled', () => {
            clearInterval(timerInterval);
        });
    </script>
    @endscript
@endif