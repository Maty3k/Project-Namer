<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metadata['title'] }}</title>
    <meta name="description" content="{{ $metadata['description'] }}">
    
    <!-- Social Media Meta Tags -->
    <meta property="og:title" content="{{ $metadata['title'] }}">
    <meta property="og:description" content="{{ $metadata['description'] }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metadata['title'] }}">
    <meta name="twitter:description" content="{{ $metadata['description'] }}">
    
    @vite(['resources/css/app.css'])
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-4">
                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                            {{ $moodBoard->name }}
                        </flux:heading>
                        
                        @if($moodBoard->description)
                            <span class="text-gray-500 dark:text-gray-400 text-sm">
                                {{ $moodBoard->description }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Created by {{ $moodBoard->user->name }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <div class="mx-auto max-w-full h-screen">
                <!-- Canvas Display -->
                <div class="relative w-full h-full overflow-auto"
                     style="background-color: {{ $moodBoard->layout_config['background_color'] ?? '#ffffff' }};">
                    
                    <!-- Grid Background (when enabled) -->
                    @if($moodBoard->layout_config['snap_to_grid'] ?? false)
                        <div class="absolute inset-0 opacity-10 pointer-events-none"
                             style="background-image: 
                                    linear-gradient(to right, #000 1px, transparent 1px),
                                    linear-gradient(to bottom, #000 1px, transparent 1px);
                                    background-size: {{ $moodBoard->layout_config['grid_size'] ?? 20 }}px {{ $moodBoard->layout_config['grid_size'] ?? 20 }}px;">
                        </div>
                    @endif

                    <!-- Images -->
                    @foreach(($moodBoard->layout_config['images'] ?? []) as $imageConfig)
                        @php
                            $image = $moodBoard->projectImages->firstWhere('uuid', $imageConfig['image_uuid']);
                        @endphp
                        
                        @if($image)
                            <div class="absolute transition-all duration-200 hover:shadow-lg hover:z-50"
                                 style="
                                     left: {{ $imageConfig['x'] }}px;
                                     top: {{ $imageConfig['y'] }}px;
                                     width: {{ $imageConfig['width'] }}px;
                                     height: {{ $imageConfig['height'] }}px;
                                     transform: rotate({{ $imageConfig['rotation'] ?? 0 }}deg);
                                     z-index: {{ $imageConfig['z_index'] ?? 1 }};
                                 ">
                                
                                @if($image->file_path)
                                    <img src="{{ Storage::url($image->file_path) }}"
                                         alt="{{ $image->title ?? $image->original_filename }}"
                                         class="w-full h-full object-cover rounded-lg shadow-md border border-white/50">
                                @endif
                                
                                <!-- Image Info Tooltip -->
                                <div class="absolute -top-8 left-0 bg-black/80 text-white text-xs px-2 py-1 rounded opacity-0 hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                                    {{ $image->title ?? $image->original_filename }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Created {{ $moodBoard->created_at->format('M j, Y') }}
                    </div>
                    
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $moodBoard->projectImages->count() }} image{{ $moodBoard->projectImages->count() !== 1 ? 's' : '' }}
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>