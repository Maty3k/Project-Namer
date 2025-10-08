<div wire:poll.500ms="getProgress"
     class="@if(!$generationId || $isComplete) hidden @endif w-full">

    <!-- Progress Bar Container -->
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden mb-2"
         role="progressbar"
         aria-valuenow="{{ $progress }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="{{ $isDeepThinking ? 'Deep Thinking Mode Progress' : 'AI Name Generation Progress' }}">
        <div class="h-full transition-all duration-500 ease-out
                    @if($isDeepThinking)
                        bg-gradient-to-r from-purple-500 via-violet-500 to-purple-600
                    @else
                        bg-blue-500
                    @endif"
             style="width: {{ $progress }}%">
        </div>
    </div>

    <!-- Status Text -->
    <div class="flex items-center justify-between text-sm">
        <div class="flex items-center gap-2">
            @if($isDeepThinking)
                <flux:icon.light-bulb class="w-4 h-4 text-purple-600 dark:text-purple-400" />
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
            @if($estimatedTimeRemaining > 0)
                ~{{ $estimatedTimeRemaining }}s remaining
            @else
                Almost done...
            @endif
        </div>
    </div>
</div>
