@props([
    'models' => [],
    'currentModel' => null,
    'action' => 'Loading',
    'showModelDetails' => true,
])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="text-center space-y-4">
            {{-- Main Loading Animation --}}
            <div class="flex justify-center">
                <div class="relative">
                    <flux:icon.cog-8-tooth class="size-12 text-accent animate-spin" />
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="size-4 bg-accent rounded-full animate-ping"></div>
                    </div>
                </div>
            </div>
            
            {{-- Loading Message --}}
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $action }} AI Models
                </h3>
                @if($currentModel)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                        Currently processing: <span class="font-medium">{{ ucwords(str_replace(['-', '_'], ' ', $currentModel)) }}</span>
                    </p>
                @endif
            </div>
        </div>

        {{-- Model Status List --}}
        @if($showModelDetails && !empty($models))
            <div class="mt-6 space-y-3">
                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 text-center">Model Status</h4>
                <div class="space-y-2">
                    @foreach($models as $model)
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                @if($model === $currentModel)
                                    <flux:icon.arrow-path class="size-4 text-accent animate-spin" />
                                @else
                                    <flux:icon.clock class="size-4 text-zinc-400" />
                                @endif
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ ucwords(str_replace(['-', '_'], ' ', $model)) }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($model === $currentModel)
                                    <span class="text-xs text-accent dark:text-accent font-medium">Processing</span>
                                    <div class="w-16 bg-zinc-200 dark:bg-zinc-600 rounded-full h-2">
                                        <div class="bg-accent h-2 rounded-full animate-pulse" style="width: 60%"></div>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Queued</span>
                                    <div class="w-16 bg-zinc-200 dark:bg-zinc-600 rounded-full h-2"></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Progress Bar --}}
        <div class="mt-6">
            <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400 mb-2">
                <span>Initializing models...</span>
                <span>Please wait</span>
            </div>
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full animate-pulse" style="width: 45%"></div>
            </div>
        </div>

        {{-- Performance Tips --}}
        <div class="mt-6 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
            <div class="flex items-start space-x-2">
                <flux:icon.light-bulb class="size-4 text-amber-500 mt-0.5 flex-shrink-0" />
                <div>
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-200">Performance Tip</p>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                        Multiple models may take longer but provide diverse name options. Consider starting with 1-2 models for faster results.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>