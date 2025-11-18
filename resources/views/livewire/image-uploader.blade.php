@push('styles')
<style>
    body.dragging-files {
        background: rgb(59 130 246 / 0.1);
    }

    body.dragging-files .upload-zone {
        transform: scale(1.02);
        border-color: rgb(59 130 246) !important;
        background-color: rgb(59 130 246 / 0.1) !important;
        box-shadow: 0 10px 25px -5px rgb(59 130 246 / 0.3);
    }

    .upload-zone {
        transition: all 0.2s ease-out;
    }
</style>
@endpush

<div class="image-uploader">
    <form wire:submit.prevent="uploadImages"
          class="space-y-6">
        
        <!-- Drag and Drop Upload Area -->
        <div class="upload-zone relative border-[4px] border-dashed rounded-lg transition-all duration-200 p-2
                    sm:p-3
                    md:p-4
                    lg:p-6
                    {{ count($images) > 0 ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-600' : 'bg-gray-50 dark:bg-gray-800' }}"
             x-data="imageDropzone()"
             x-init="init()"
             @drop="handleDrop($event)"
             @dragover="handleDragOver($event)"
             @dragenter="handleDragEnter($event)"
             @dragleave="handleDragLeave($event)"
             @click="!isDialogOpen && !isDragging && openFileDialog()"
             :class="{
                 'border-primary-400 dark:border-primary-500 bg-primary-100 dark:bg-primary-900/40 scale-[1.02] cursor-copy': isDragging,
                 'border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500 cursor-pointer': !isDragging
             }">
            
            <input type="file"
                   wire:model="newFiles"
                   multiple
                   accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                   x-ref="fileInput">
            
            <div class="text-center">
                @if(count($images) > 0)
                    <flux:icon.photo
                        class="mx-auto h-6 w-6 text-primary-500 dark:text-primary-400
                               sm:h-8 sm:w-8
                               md:h-10 md:w-10
                               lg:h-12 lg:w-12" />
                    <p class="mt-1 text-[11px] font-medium text-primary-600 dark:text-primary-400
                              sm:text-xs
                              md:mt-2 md:text-sm">
                        {{ count($images) }} {{ Str::plural('image', count($images)) }} selected
                    </p>
                    <p class="mt-0.5 text-[9px] text-gray-500 dark:text-gray-400
                              sm:text-[10px]
                              md:mt-1 md:text-xs"
                       x-show="!isDragging">
                        Click to add more or drag additional files here
                    </p>
                    <p class="mt-0.5 text-[9px] text-primary-600 dark:text-primary-400 font-medium
                              sm:text-[10px]
                              md:mt-1 md:text-xs"
                       x-show="isDragging"
                       style="display: none;">
                        Drop files to add them to your selection
                    </p>
                @else
                    <flux:icon.cloud-arrow-up
                        class="mx-auto h-6 w-6 text-gray-400 dark:text-gray-500
                               sm:h-8 sm:w-8
                               md:h-10 md:w-10
                               lg:h-12 lg:w-12"
                        x-show="!isDragging" />
                    <flux:icon.photo
                        class="mx-auto h-6 w-6 text-primary-500 dark:text-primary-400 animate-bounce
                               sm:h-8 sm:w-8
                               md:h-10 md:w-10
                               lg:h-12 lg:w-12"
                        x-show="isDragging"
                        style="display: none;" />
                    <p class="mt-1 text-[11px] font-medium text-gray-900 dark:text-gray-100
                              sm:text-xs
                              md:mt-2 md:text-sm"
                       x-show="!isDragging">
                        Drag and drop images here, or click to browse
                    </p>
                    <p class="mt-1 text-[11px] font-medium text-primary-600 dark:text-primary-400
                              sm:text-xs
                              md:mt-2 md:text-sm"
                       x-show="isDragging"
                       style="display: none;">
                        Drop your images here!
                    </p>
                    <p class="mt-0.5 text-[9px] text-gray-500 dark:text-gray-400
                              sm:text-[10px]
                              md:mt-1 md:text-xs"
                       x-show="!isDragging">
                        JPEG, PNG, WebP, GIF up to 50MB each
                    </p>
                    <p class="mt-0.5 text-[9px] text-primary-500 dark:text-primary-400
                              sm:text-[10px]
                              md:mt-1 md:text-xs"
                       x-show="isDragging"
                       style="display: none;">
                        Multiple files supported
                    </p>
                @endif
            </div>
        </div>

        @error('images')
            <flux:callout variant="danger">
                {{ $message }}
            </flux:callout>
        @enderror

        <!-- Image Preview Grid -->
        @if(count($images) > 0)
            <div class="grid grid-cols-2 gap-4
                        sm:grid-cols-3
                        md:grid-cols-4
                        lg:grid-cols-6">
                @foreach($images as $index => $image)
                    <div class="relative group rounded-lg overflow-hidden shadow-sm
                                border-[3px] border-gray-300 dark:border-gray-600
                                hover:shadow-md hover:border-primary-400 dark:hover:border-primary-500 transition-all duration-200">
                        
                        @if($image->temporaryUrl())
                            <img src="{{ $image->temporaryUrl() }}" 
                                 alt="Preview"
                                 class="w-full h-24 object-cover" />
                        @else
                            <div class="w-full h-24 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <flux:icon.photo class="h-6 w-6 text-gray-400" />
                            </div>
                        @endif
                        
                        <div class="p-2">
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                {{ $image->getClientOriginalName() }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                {{ round($image->getSize() / 1024 / 1024, 2) }} MB
                            </p>
                        </div>
                        
                        <!-- Remove button -->
                        <button type="button"
                                wire:click="removeImage({{ $index }})"
                                class="absolute top-1 right-1 p-1 rounded-full bg-red-500 dark:bg-red-600 text-white opacity-0 border-[2px] border-red-700 dark:border-red-400
                                       group-hover:opacity-100 transition-all duration-200 hover:scale-110 active:scale-95
                                       hover:bg-red-600 dark:hover:bg-red-700 focus:opacity-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            <flux:icon.x-mark class="h-3 w-3" />
                        </button>

                        @error("images.{$index}")
                            <div class="absolute inset-0 bg-red-500/20 rounded-lg flex items-center justify-center">
                                <span class="text-xs text-red-600 dark:text-red-400 text-center px-2">
                                    Error: File invalid
                                </span>
                            </div>
                        @enderror
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Inspiration Field -->
        @if(count($images) > 0)
            <flux:field>
                <flux:label>Inspiration <span class="text-red-500">*</span></flux:label>
                <flux:textarea wire:model.live="inspiration"
                              rows="3"
                              placeholder="Describe the inspiration for these images (e.g., 'River theme', 'Mountain landscape', 'Modern minimalist')"
                              required />
                <flux:description>
                    This will help guide the AI in generating names that match your visual inspiration.
                </flux:description>
                <flux:error name="inspiration" />
            </flux:field>
        @endif

        <!-- Upload Button -->
        @if(count($images) > 0)
            <div class="flex justify-end">
                <flux:button type="submit"
                            variant="primary"
                            :disabled="$isUploading || !trim($inspiration)"
                            class="border-[3px] border-primary-700 dark:border-primary-300 hover:scale-105 active:scale-95 transition-all">
                    <span wire:loading.remove wire:target="uploadImages">
                        @if(empty(trim($inspiration)))
                            Enter inspiration to upload
                        @else
                            Upload {{ count($images) }} {{ Str::plural('Image', count($images)) }}
                        @endif
                    </span>
                    <span wire:loading wire:target="uploadImages">
                        {{ $uploadProgress ?: 'Uploading...' }}
                    </span>
                </flux:button>
            </div>
        @endif

        @error('upload')
            <flux:callout variant="danger">
                {{ $message }}
            </flux:callout>
        @enderror
    </form>

    @script
    <script>
        Alpine.data('imageDropzone', () => ({
            isDragging: false,
            isDialogOpen: false,

            init() {
                // Prevent default drag behaviors on the whole document
                document.addEventListener('dragenter', this.preventDefaults, false);
                document.addEventListener('dragover', this.preventDefaults, false);
                document.addEventListener('drop', this.preventDefaults, false);

                // Add visual feedback for dragging over the page
                document.addEventListener('dragenter', (e) => {
                    if (e.dataTransfer.types.includes('Files')) {
                        document.body.classList.add('dragging-files');
                    }
                });

                document.addEventListener('dragleave', (e) => {
                    if (!e.relatedTarget || e.relatedTarget.nodeName === 'HTML') {
                        document.body.classList.remove('dragging-files');
                    }
                });

                document.addEventListener('drop', () => {
                    document.body.classList.remove('dragging-files');
                });

                // Listen for file input changes and reset dialog state
                const fileInput = this.$refs?.fileInput;
                if (fileInput) {
                    fileInput.addEventListener('change', () => {
                        this.isDialogOpen = false;
                    });

                    // Reset dialog state when file dialog is canceled/closed
                    fileInput.addEventListener('cancel', () => {
                        this.isDialogOpen = false;
                    });
                }
            },

            preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            },

            openFileDialog() {
                if (this.isDialogOpen || this.isDragging) {
                    return;
                }

                this.isDialogOpen = true;
                this.$refs.fileInput.click();

                // Reset state after file dialog interaction
                setTimeout(() => {
                    this.isDialogOpen = false;
                }, 500);
            },

            handleDrop(e) {
                this.preventDefaults(e);
                this.isDragging = false;

                const files = Array.from(e.dataTransfer.files);

                // Filter for image files
                const imageFiles = files.filter(file => {
                    return file.type.startsWith('image/') &&
                        ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'].includes(file.type);
                });

                if (imageFiles.length > 0) {
                    // Create a DataTransfer object to simulate file input
                    const dataTransfer = new DataTransfer();
                    imageFiles.forEach(file => {
                        dataTransfer.items.add(file);
                    });

                    // Set the files to the actual file input
                    const fileInput = this.$refs.fileInput;
                    fileInput.files = dataTransfer.files;

                    // Trigger the change event to notify Livewire
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    // Show error message for invalid files
                    if (files.length > 0 && imageFiles.length === 0) {
                        // Use a more user-friendly notification instead of alert
                        this.$dispatch('notify', {
                            message: 'Please drop only image files (JPEG, PNG, WebP, GIF)',
                            type: 'warning'
                        });
                    }
                }
            },

            handleDragOver(e) {
                this.preventDefaults(e);
                e.dataTransfer.dropEffect = 'copy';
            },

            handleDragEnter(e) {
                this.preventDefaults(e);
                this.isDragging = true;
            },

            handleDragLeave(e) {
                this.preventDefaults(e);
                // Only set isDragging to false if we're leaving the dropzone completely
                if (!e.currentTarget.contains(e.relatedTarget)) {
                    this.isDragging = false;
                }
            }
        }));
    </script>
    @endscript
</div>
