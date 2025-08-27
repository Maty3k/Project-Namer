{{-- Logo Gallery Skeleton Loader --}}
<div class="max-w-7xl mx-auto px-4 py-8 animate-pulse">
    {{-- Header Skeleton --}}
    <div class="mb-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="h-8 bg-gray-300 dark:bg-gray-700 rounded w-48 mb-2"></div>
                <div class="h-5 bg-gray-200 dark:bg-gray-800 rounded w-32"></div>
            </div>
            <div class="flex gap-2">
                <div class="h-9 bg-gray-200 dark:bg-gray-800 rounded w-24"></div>
                <div class="h-9 bg-gray-200 dark:bg-gray-800 rounded w-32"></div>
            </div>
        </div>
    </div>

    {{-- Status Skeleton --}}
    <div class="mb-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center">
            <div class="w-5 h-5 bg-blue-300 dark:bg-blue-600 rounded-full mr-3"></div>
            <div class="flex-1">
                <div class="h-4 bg-blue-200 dark:bg-blue-800 rounded w-40 mb-2"></div>
                <div class="h-3 bg-blue-100 dark:bg-blue-900 rounded w-64"></div>
            </div>
        </div>
        <div class="mt-3 w-full bg-blue-100 dark:bg-blue-800 rounded-full h-2">
            <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full w-1/3 animate-pulse"></div>
        </div>
    </div>

    {{-- Color Customization Skeleton --}}
    <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="h-5 bg-gray-300 dark:bg-gray-700 rounded w-48 mb-4"></div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="h-4 bg-gray-200 dark:bg-gray-800 rounded w-32 mb-3"></div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    @for($i = 0; $i < 10; $i++)
                        <div class="p-3 rounded-lg border-2 border-gray-200 dark:border-gray-700">
                            <div class="flex space-x-1 mb-2">
                                <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-16"></div>
                        </div>
                    @endfor
                </div>
            </div>
            
            <div class="lg:col-span-4">
                <div class="h-4 bg-gray-200 dark:bg-gray-800 rounded w-16 mb-3"></div>
                <div class="space-y-3">
                    <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-32"></div>
                    <div class="flex gap-2">
                        <div class="h-8 bg-gray-200 dark:bg-gray-800 rounded flex-1"></div>
                        <div class="h-8 bg-gray-200 dark:bg-gray-800 rounded flex-1"></div>
                    </div>
                    <div class="h-8 bg-gray-300 dark:bg-gray-700 rounded w-full"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Logo Gallery Skeletons --}}
    @for($styleIndex = 0; $styleIndex < 4; $styleIndex++)
        <div class="mb-12">
            <div class="mb-6">
                <div class="h-6 bg-gray-300 dark:bg-gray-700 rounded w-32 mb-1"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-800 rounded w-24"></div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @for($logoIndex = 0; $logoIndex < 3; $logoIndex++)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Selection Checkbox Skeleton --}}
                        <div class="absolute top-3 left-3 z-10">
                            <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                        </div>

                        {{-- Logo Preview Skeleton --}}
                        <div class="aspect-square p-8">
                            <div class="w-full h-full bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded"></div>
                            </div>
                        </div>

                        {{-- Logo Info Skeleton --}}
                        <div class="p-4">
                            <div class="mb-3">
                                <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-24 mb-1"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-16"></div>
                            </div>

                            {{-- Download Actions Skeleton --}}
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <div class="h-8 bg-gray-200 dark:bg-gray-800 rounded flex-1"></div>
                                    <div class="h-8 bg-gray-200 dark:bg-gray-800 rounded flex-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endfor
</div>