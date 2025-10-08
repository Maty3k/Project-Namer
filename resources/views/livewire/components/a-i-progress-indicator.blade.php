<div wire:poll.500ms="getProgress"
     class="w-full transition-opacity duration-300 {{ !$generationId ? 'hidden' : ($isComplete ? 'opacity-0' : 'opacity-100') }}">

    <!-- Title -->
    <div class="mb-3 text-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Generating Names with AI
        </h3>
    </div>

    <!-- Progress Bar Container - Enhanced Size -->
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden mb-3 shadow-inner"
         role="progressbar"
         aria-valuenow="{{ $progress }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $isDeepThinking ? 'Deep Thinking Mode Progress' : 'AI Name Generation Progress' }}">
        <div class="h-full transition-all duration-500 ease-out flex items-center justify-end pr-2
                    @if($progress === 100)
                        bg-green-500
                    @elseif($isDeepThinking)
                        bg-gradient-to-r from-purple-500 via-violet-500 to-purple-600
                    @else
                        bg-blue-500
                    @endif"
             style="width: {{ $progress }}%">
            <span class="text-white text-xs font-bold">{{ $progress }}%</span>
        </div>
    </div>

    <!-- Status Text -->
    <div class="flex items-center justify-between text-sm">
        <div class="flex items-center gap-2">
            @if($progress === 100)
                <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                <span class="text-green-700 dark:text-green-300 font-medium">
                    Complete!
                </span>
            @elseif($isDeepThinking)
                <flux:icon.light-bulb class="w-4 h-4 text-purple-600 dark:text-purple-400 animate-pulse" />
                <span class="text-purple-700 dark:text-purple-300 font-medium">
                    Deep Thinking Mode
                </span>
            @else
                <flux:icon.sparkles class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                <span class="text-gray-700 dark:text-gray-300">
                    Generating Names
                </span>
            @endif
        </div>

        <div class="text-gray-600 dark:text-gray-400">
            @if($progress === 100)
                <span class="text-green-600 dark:text-green-400">✓</span>
            @elseif($estimatedTimeRemaining > 0)
                ~{{ $estimatedTimeRemaining }}s remaining
            @else
                Almost done...
            @endif
        </div>
    </div>
</div>
