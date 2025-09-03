<div class="photo-gallery relative" 
     x-data="photoGalleryComponent()"
     x-init="init(); showUploader = false;"
     @keydown.escape.window="closeViewer()"
     @keydown.arrow-left.window="previousImage()"
     @keydown.arrow-right.window="nextImage()">

    <!-- Gallery Header -->
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex flex-col space-y-3
                    sm:flex-row sm:space-y-0 sm:items-center sm:justify-between">
            
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Photo Gallery
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                    ({{ $images->total() }} {{ Str::plural('photo', $images->total()) }})
                </span>
            </h2>

            <!-- Actions -->
            <div class="flex items-center space-x-2">
                <!-- Upload Button -->
                <button 
                    @click="showUploader = !showUploader"
                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span>Upload</span>
                </button>

                <!-- View Mode Toggle -->
                <div class="flex items-center space-x-1 border-l pl-2 border-gray-300 dark:border-gray-600">
                    <button 
                        @click="toggleViewMode('grid')"
                        :class="viewMode === 'grid' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400'"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>
                    <button 
                        @click="toggleViewMode('list')"
                        :class="viewMode === 'list' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400'"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Section (toggleable) -->
    <div x-show="showUploader" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="mb-4">
        @livewire('image-uploader', ['project' => $project], key('uploader-'.$project->id))
    </div>

    <!-- Photo Grid (Phone Gallery Style) -->
    <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden">
        @if($images->count() > 0)
            <!-- Grid View -->
            <div x-show="viewMode === 'grid'" 
                 class="grid grid-cols-3 gap-0.5
                        sm:grid-cols-4 sm:gap-1
                        md:grid-cols-5
                        lg:grid-cols-6
                        xl:grid-cols-8">
                @foreach($images as $index => $image)
                    <div class="relative aspect-square overflow-hidden cursor-pointer group"
                         @click="openViewer({{ $index }})"
                         wire:key="image-{{ $image->uuid }}"
                         data-image-index="{{ $index }}"
                         data-uuid="{{ $image->uuid }}"
                         data-title="{{ $image->title ?? '' }}"
                         data-filename="{{ $image->original_filename }}"
                         data-description="{{ $image->description ?? '' }}"
                         data-size="{{ number_format($image->file_size / 1024, 0) }} KB"
                         data-date="{{ $image->created_at->format('M j, Y') }}">
                        
                        <!-- Thumbnail -->
                        @if($image->thumbnail_path)
                            <img src="{{ Storage::url($image->thumbnail_path) }}" 
                                 alt="{{ $image->title ?? $image->original_filename }}"
                                 class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-110"
                                 loading="lazy">
                        @else
                            <img src="{{ Storage::url($image->file_path) }}" 
                                 alt="{{ $image->title ?? $image->original_filename }}"
                                 class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-110"
                                 loading="lazy">
                        @endif

                        <!-- Selection Checkbox (appears on hover) -->
                        <div class="absolute top-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <input type="checkbox" 
                                   wire:model="selectedImages"
                                   value="{{ $image->uuid }}"
                                   @click.stop
                                   class="w-5 h-5 rounded border-2 border-white shadow-sm">
                        </div>

                        <!-- Processing Status Badge -->
                        @if($image->processing_status !== 'completed')
                            <div class="absolute bottom-2 right-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                           {{ $image->processing_status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($image->processing_status) }}
                                </span>
                            </div>
                        @endif

                        <!-- Video/GIF Indicator -->
                        @if(Str::contains($image->mime_type, 'video') || $image->mime_type === 'image/gif')
                            <div class="absolute bottom-2 left-2">
                                <svg class="w-6 h-6 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- List View -->
            <div x-show="viewMode === 'list'" class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($images as $index => $image)
                    <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                         @click="openViewer({{ $index }})"
                         wire:key="list-image-{{ $image->uuid }}">
                        
                        <!-- Thumbnail -->
                        <div class="w-16 h-16 flex-shrink-0 mr-4">
                            @if($image->thumbnail_path)
                                <img src="{{ Storage::url($image->thumbnail_path) }}" 
                                     alt="{{ $image->title ?? $image->original_filename }}"
                                     class="w-full h-full object-cover rounded-lg">
                            @else
                                <img src="{{ Storage::url($image->file_path) }}" 
                                     alt="{{ $image->title ?? $image->original_filename }}"
                                     class="w-full h-full object-cover rounded-lg">
                            @endif
                        </div>

                        <!-- Image Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $image->title ?? $image->original_filename }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $image->getFileSizeFormatted() }} • {{ $image->created_at->format('M d, Y') }}
                            </p>
                        </div>

                        <!-- Selection Checkbox -->
                        <div class="ml-4">
                            <input type="checkbox" 
                                   wire:model="selectedImages"
                                   value="{{ $image->uuid }}"
                                   @click.stop
                                   class="w-5 h-5 rounded">
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-12">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No photos yet</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Upload your first photo to get started</p>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if($images->hasPages())
        <div class="mt-4">
            {{ $images->links() }}
        </div>
    @endif

    <!-- Fullscreen Image Viewer (Phone Gallery Style) -->
    <div x-show="showViewer" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-black"
         style="display: none;"
         @touchstart="handleTouchStart"
         @touchmove="handleTouchMove"
         @touchend="handleTouchEnd">
        
        <!-- Close Button -->
        <button @click="closeViewer()"
                class="absolute top-4 right-4 z-50 p-2 text-white hover:bg-white/10 rounded-full transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Image Counter -->
        <div class="absolute top-4 left-4 z-50 text-white text-sm font-medium">
            <span x-text="currentIndex + 1"></span> / <span x-text="totalImages"></span>
        </div>

        <!-- Main Image Container -->
        <div class="flex items-center justify-center h-full">
            <!-- Previous Button -->
            <button @click="previousImage()"
                    x-show="currentIndex > 0"
                    class="absolute left-4 z-50 p-3 text-white hover:bg-white/10 rounded-full transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <!-- Image -->
            <template x-if="currentImage">
                <img :src="currentImage.url" 
                     :alt="currentImage.title"
                     class="max-w-full max-h-full object-contain"
                     @load="imageLoaded = true">
            </template>

            <!-- Loading Spinner -->
            <div x-show="!imageLoaded" class="absolute inset-0 flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
            </div>

            <!-- Next Button -->
            <button @click="nextImage()"
                    x-show="currentIndex < totalImages - 1"
                    class="absolute right-4 z-50 p-3 text-white hover:bg-white/10 rounded-full transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>

        <!-- Image Info Bar -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6">
            <template x-if="currentImage">
                <div class="text-white">
                    <h3 class="text-lg font-medium" x-text="currentImage.title || currentImage.filename"></h3>
                    <p class="text-sm text-gray-300 mt-1">
                        <span x-text="currentImage.size"></span> • 
                        <span x-text="currentImage.date"></span>
                    </p>
                    <p class="text-sm text-gray-400 mt-2" x-text="currentImage.description"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    @if($showBulkActions)
        <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 z-40">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($selectedImages) }} {{ Str::plural('photo', count($selectedImages)) }} selected
                </span>
                <div class="flex items-center space-x-2">
                    <button wire:click="clearSelection()" 
                            class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        Cancel
                    </button>
                    <button wire:click="$set('bulkAction', 'delete')" 
                            wire:confirm="Are you sure you want to delete {{ count($selectedImages) }} images?"
                            wire:click="performBulkAction"
                            class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function photoGalleryComponent() {
    return {
        viewMode: @entangle('viewMode'),
        showViewer: false,
        showUploader: false,
        currentIndex: 0,
        totalImages: {{ $images->count() }},
        images: [],
        currentImage: null,
        imageLoaded: false,
        touchStartX: 0,
        touchEndX: 0,

        init() {
            // Initialize images array from DOM data
            this.images = this.buildImagesArray();
            
            // Preload next/previous images when viewer opens
            this.$watch('showViewer', value => {
                if (value) {
                    this.preloadAdjacentImages();
                }
            });
        },

        buildImagesArray() {
            // Build images array from the grid items
            const imageElements = this.$el.querySelectorAll('[data-image-index]');
            const images = [];
            
            imageElements.forEach((element, index) => {
                const img = element.querySelector('img');
                if (img) {
                    images.push({
                        uuid: element.dataset.uuid || '',
                        file_path: img.src.replace('/storage/', ''),
                        thumbnail_path: img.src.replace('/storage/', ''),
                        title: element.dataset.title || '',
                        filename: element.dataset.filename || '',
                        description: element.dataset.description || '',
                        size: element.dataset.size || '',
                        date: element.dataset.date || ''
                    });
                }
            });
            
            return images;
        },

        toggleViewMode(mode) {
            this.viewMode = mode;
            @this.set('viewMode', mode);
        },

        openViewer(index) {
            this.currentIndex = index;
            this.showViewer = true;
            this.imageLoaded = false;
            this.updateCurrentImage();
            document.body.style.overflow = 'hidden';
        },

        closeViewer() {
            this.showViewer = false;
            document.body.style.overflow = '';
        },

        previousImage() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.imageLoaded = false;
                this.updateCurrentImage();
            }
        },

        nextImage() {
            if (this.currentIndex < this.totalImages - 1) {
                this.currentIndex++;
                this.imageLoaded = false;
                this.updateCurrentImage();
            }
        },

        updateCurrentImage() {
            const image = this.images[this.currentIndex];
            if (image) {
                this.currentImage = {
                    url: '/storage/' + image.file_path,
                    title: image.title,
                    filename: image.original_filename,
                    description: image.description,
                    size: this.formatFileSize(image.file_size),
                    date: new Date(image.created_at).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    })
                };
            }
        },

        preloadAdjacentImages() {
            // Preload next image
            if (this.currentIndex < this.totalImages - 1) {
                const nextImage = new Image();
                nextImage.src = '/storage/' + this.images[this.currentIndex + 1].file_path;
            }
            // Preload previous image
            if (this.currentIndex > 0) {
                const prevImage = new Image();
                prevImage.src = '/storage/' + this.images[this.currentIndex - 1].file_path;
            }
        },

        formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            
            return size.toFixed(2) + ' ' + units[unitIndex];
        },

        // Touch gesture support for mobile
        handleTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
        },

        handleTouchMove(e) {
            this.touchEndX = e.touches[0].clientX;
        },

        handleTouchEnd() {
            const swipeThreshold = 50;
            const diff = this.touchStartX - this.touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    this.nextImage();
                } else {
                    // Swipe right - previous image
                    this.previousImage();
                }
            }
        }
    }
}
</script>
@endpush