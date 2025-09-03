<div class="image-uploader">
    <form wire:submit.prevent="uploadImages"
          class="space-y-6">
        
        <!-- Drag and Drop Upload Area -->
        <div class="upload-zone relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8
                    hover:border-blue-400 dark:hover:border-blue-500 transition-colors duration-200
                    {{ count($images) > 0 ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600' : 'bg-gray-50 dark:bg-gray-800' }}"
             x-data="imageDropzone()"
             @drop.prevent="handleDrop($event)"
             @dragover.prevent
             @dragenter.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @click="$refs.fileInput.click()">
            
            <input type="file"
                   wire:model.live="images"
                   multiple
                   accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                   x-ref="fileInput">
            
            <div class="text-center">
                @if(count($images) > 0)
                    <flux:icon.photo
                        class="mx-auto h-12 w-12 text-blue-500 dark:text-blue-400" />
                    <p class="mt-2 text-sm font-medium text-blue-600 dark:text-blue-400">
                        {{ count($images) }} {{ Str::plural('image', count($images)) }} selected
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Click to add more or drag additional files here
                    </p>
                @else
                    <flux:icon.cloud-arrow-up
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        Drag and drop images here, or click to browse
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        JPEG, PNG, WebP, GIF up to 50MB each
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
                    <div class="relative group bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm
                                border border-gray-200 dark:border-gray-700
                                hover:shadow-md transition-shadow duration-200">
                        
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
                                class="absolute top-1 right-1 p-1 rounded-full bg-red-500 text-white opacity-0
                                       group-hover:opacity-100 transition-opacity duration-200
                                       hover:bg-red-600 focus:opacity-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
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

        <!-- Metadata Fields -->
        @if(count($images) > 0)
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Title (Optional)</flux:label>
                    <flux:input wire:model.live="title" 
                               placeholder="Enter a title for these images" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>Description (Optional)</flux:label>
                    <flux:textarea wire:model.live="description" 
                                  rows="3"
                                  placeholder="Describe what these images represent for your project" />
                    <flux:error name="description" />
                </flux:field>

                <!-- Tags -->
                <flux:field>
                    <flux:label>Tags (Optional)</flux:label>
                    <div class="space-y-2">
                        @foreach($tags as $index => $tag)
                            <div class="flex gap-2">
                                <flux:input wire:model.live="tags.{{ $index }}"
                                           placeholder="Enter tag" 
                                           class="flex-1" />
                                <flux:button variant="danger"
                                            size="sm"
                                            wire:click="removeTag({{ $index }})"
                                            type="button">
                                    Remove
                                </flux:button>
                            </div>
                        @endforeach
                        
                        @if(count($tags) < 10)
                            <flux:button variant="ghost"
                                        size="sm"
                                        wire:click="addTag"
                                        type="button">
                                + Add Tag
                            </flux:button>
                        @endif
                    </div>
                </flux:field>

                <!-- Privacy Setting -->
                <flux:field>
                    <flux:checkbox wire:model.live="isPublic">
                        Make images publicly viewable
                    </flux:checkbox>
                </flux:field>
            </div>
        @endif

        <!-- Upload Button -->
        @if(count($images) > 0)
            <div class="flex justify-end">
                <flux:button type="submit" 
                            variant="primary"
                            :disabled="$isUploading">
                    <span wire:loading.remove wire:target="uploadImages">
                        Upload {{ count($images) }} {{ Str::plural('Image', count($images)) }}
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
            
            handleDrop(e) {
                this.isDragging = false;
                const files = Array.from(e.dataTransfer.files);
                
                // Filter for image files
                const imageFiles = files.filter(file => 
                    file.type.startsWith('image/') && 
                    ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)
                );
                
                if (imageFiles.length > 0) {
                    // Update Livewire component with new files
                    @this.upload('images', imageFiles);
                }
            }
        }));
    </script>
    @endscript
</div>
