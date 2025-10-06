@props([
    'message' => 'AI is generating names...',
    'estimatedTime' => null,
    'showProgress' => true,
    'animated' => true,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    <div class="max-w-sm mx-auto">
        {{-- Loading Animation --}}
        <div class="relative mb-6">
            @if($animated)
                {{-- Animated AI Brain/Processor Icon --}}
                <div class="relative w-20 h-20 mx-auto">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 animate-pulse"></div>
                    <div class="absolute inset-2 rounded-full bg-white dark:bg-zinc-900 flex items-center justify-center">
                        <flux:icon.cpu-chip class="size-8 text-accent animate-bounce" />
                    </div>
                    {{-- Floating particles --}}
                    <div class="absolute -top-1 -right-1 w-3 h-3 bg-accent rounded-full animate-ping"></div>
                    <div class="absolute -bottom-1 -left-1 w-2 h-2 bg-purple-400 rounded-full animate-ping animation-delay-150"></div>
                    <div class="absolute top-1/2 -right-2 w-2 h-2 bg-green-400 rounded-full animate-ping animation-delay-300"></div>
                </div>
            @else
                <div class="w-16 h-16 mx-auto bg-accent dark:bg-accent rounded-full flex items-center justify-center">
                    <flux:icon.sparkles class="size-8 text-accent" />
                </div>
            @endif
        </div>

        {{-- Loading Message --}}
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
            {{ $message }}
        </h3>
        
        @if($estimatedTime)
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                Estimated time: {{ $estimatedTime }}
            </p>
        @endif

        {{-- Progress Indicator --}}
        @if($showProgress)
            <div class="space-y-4">
                {{-- Animated Progress Steps --}}
                <div class="flex justify-center space-x-2">
                    <div class="flex space-x-1">
                        <div class="w-3 h-3 bg-accent rounded-full animate-bounce"></div>
                        <div class="w-3 h-3 bg-accent rounded-full animate-bounce animation-delay-150"></div>
                        <div class="w-3 h-3 bg-accent rounded-full animate-bounce animation-delay-300"></div>
                    </div>
                </div>
                
                {{-- Progress Steps Text --}}
                <div class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                    <div class="flex items-center justify-center space-x-2">
                        <flux:icon.check class="size-3 text-green-500" />
                        <span>Processing business description</span>
                    </div>
                    <div class="flex items-center justify-center space-x-2">
                        <flux:icon.arrow-path class="size-3 text-accent animate-spin" />
                        <span>Generating creative names</span>
                    </div>
                    <div class="flex items-center justify-center space-x-2">
                        <div class="size-3 rounded-full border border-zinc-300 dark:border-zinc-600"></div>
                        <span>Checking domain availability</span>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Loading Tips --}}
        <div class="mt-8 p-4 bg-accent/20 dark:bg-accent/20 rounded-lg">
            <p class="text-xs text-accent dark:text-accent font-medium mb-1">
                💡 AI Generation Tips
            </p>
            <p class="text-xs text-accent dark:text-accent">
                Our AI models work best with detailed descriptions. The more context you provide, the better the names!
            </p>
        </div>
    </div>
</div>

@pushOnce('styles')
<style>
    .animation-delay-150 {
        animation-delay: 150ms;
    }
    .animation-delay-300 {
        animation-delay: 300ms;
    }
</style>
@endPushOnce