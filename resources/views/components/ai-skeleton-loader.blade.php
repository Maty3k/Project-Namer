@props([
    'type' => 'cards', // cards, table, list, form
    'count' => 3,
    'showText' => true,
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-4']) }}>
    @if($type === 'cards')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @for($i = 0; $i < $count; $i++)
                <div class="bg-gray-200 dark:bg-gray-700 rounded-lg p-6">
                    <div class="space-y-3">
                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                        <div class="space-y-2">
                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-5/6"></div>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    @elseif($type === 'table')
        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-48"></div>
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @for($i = 0; $i < $count; $i++)
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                <div class="space-y-2 flex-1">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/3"></div>
                                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'list')
        <div class="space-y-3">
            @for($i = 0; $i < $count; $i++)
                <div class="flex items-center space-x-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                    <div class="space-y-2 flex-1">
                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4"></div>
                        <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                    </div>
                    <div class="h-8 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                </div>
            @endfor
        </div>
    @elseif($type === 'form')
        <div class="space-y-6">
            @for($i = 0; $i < $count; $i++)
                <div class="space-y-2">
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600 w-full"></div>
                </div>
            @endfor
            <div class="h-10 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
        </div>
    @endif
    
    @if($showText)
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
            <flux:icon.arrow-path class="inline size-4 animate-spin mr-2" />
            Loading AI content...
        </div>
    @endif
</div>