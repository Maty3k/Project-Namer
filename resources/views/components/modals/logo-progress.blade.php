{{-- Logo Generation Progress Modal Content --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h4 class="mt-3 text-lg font-medium text-gray-900 dark:text-gray-100">
            Generating Logos
        </h4>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Creating AI-powered logo designs for "{{ $data['name'] ?? 'your business' }}"
        </p>
    </div>

    {{-- Progress Steps --}}
    <div class="space-y-4">
        @php
            $steps = [
                ['key' => 'analyzing', 'label' => 'Analyzing your business name', 'description' => 'Understanding brand characteristics and style preferences'],
                ['key' => 'generating', 'label' => 'Generating logo concepts', 'description' => 'Creating multiple design variations with AI'],
                ['key' => 'optimizing', 'label' => 'Optimizing designs', 'description' => 'Refining colors, typography, and composition'],
                ['key' => 'finalizing', 'label' => 'Preparing downloads', 'description' => 'Converting to multiple formats (PNG, SVG)']
            ];
            $currentStep = $data['currentStep'] ?? 'analyzing';
            $completedSteps = $data['completedSteps'] ?? [];
        @endphp

        @foreach($steps as $index => $step)
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    @if(in_array($step['key'], $completedSteps))
                        {{-- Completed Step --}}
                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-800">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    @elseif($step['key'] === $currentStep)
                        {{-- Current Step --}}
                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-800">
                            <div class="w-3 h-3 bg-blue-600 dark:bg-blue-400 rounded-full animate-pulse"></div>
                        </div>
                    @else
                        {{-- Pending Step --}}
                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium {{ in_array($step['key'], $completedSteps) ? 'text-green-600 dark:text-green-400' : ($step['key'] === $currentStep ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400') }}">
                        {{ $step['label'] }}
                        @if($step['key'] === $currentStep)
                            <span class="inline-flex items-center ml-2">
                                <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $step['description'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Progress Bar --}}
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        @php
            $totalSteps = count($steps);
            $completedCount = count($completedSteps);
            $currentIndex = array_search($currentStep, array_column($steps, 'key'));
            $progressPercentage = (($completedCount + ($currentIndex !== false ? 0.5 : 0)) / $totalSteps) * 100;
        @endphp
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-300 ease-out" 
             style="width: {{ $progressPercentage }}%"></div>
    </div>

    {{-- Estimated Time --}}
    <div class="text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            @if(isset($data['estimatedTimeRemaining']))
                Estimated time remaining: {{ $data['estimatedTimeRemaining'] }}
            @else
                This usually takes 30-60 seconds
            @endif
        </p>
    </div>

    {{-- Cancel Option --}}
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-center">
            <flux:button 
                wire:click="cancelLogoGeneration"
                variant="outline"
                size="sm"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                Cancel Generation
            </flux:button>
        </div>
    </div>
</div>