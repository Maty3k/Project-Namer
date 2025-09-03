<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 sm:px-6">
            <div class="flex flex-col gap-4
                        sm:flex-row sm:items-center sm:justify-between">
                <!-- Title and Mood Board Selector -->
                <div class="flex flex-col gap-2
                            sm:flex-row sm:items-center sm:gap-4">
                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">
                        Mood Board Canvas
                    </flux:heading>

                    @if($moodBoards->count() > 0)
                        <flux:select wire:model.live="activeMoodBoard.uuid"
                                   wire:change="loadMoodBoard($event.target.value)"
                                   class="w-full sm:w-64">
                            <option value="">Select mood board...</option>
                            @foreach($moodBoards as $board)
                                <option value="{{ $board->uuid }}">
                                    {{ $board->name }}
                                </option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="showCreateModal"
                               variant="primary"
                               size="sm">
                        Create New
                    </flux:button>

                    @if($activeMoodBoard)
                        <flux:button wire:click="toggleEditMode"
                                   variant="{{ $isEditMode ? 'danger' : 'filled' }}"
                                   size="sm">
                            {{ $isEditMode ? 'Exit Edit' : 'Edit Mode' }}
                        </flux:button>

                        <flux:button wire:click="showExportModal"
                                   variant="filled"
                                   size="sm">
                            Export
                        </flux:button>

                        <flux:button wire:click="toggleSharing"
                                   variant="{{ $activeMoodBoard->is_public ? 'danger' : 'filled' }}"
                                   size="sm">
                            {{ $activeMoodBoard->is_public ? 'Stop Sharing' : 'Share' }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <!-- Canvas Settings (when mood board is active) -->
            @if($activeMoodBoard && $isEditMode)
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 gap-4
                                sm:grid-cols-2 
                                lg:grid-cols-4">
                        <!-- Background Color -->
                        <flux:field>
                            <flux:label>Background Color</flux:label>
                            <input type="color"
                                   wire:model.live="layoutConfig.background_color"
                                   wire:change="updateCanvas"
                                   class="w-full h-10 rounded border border-gray-300 dark:border-gray-600">
                        </flux:field>

                        <!-- Grid Size -->
                        <flux:field>
                            <flux:label>Grid Size</flux:label>
                            <flux:input type="number"
                                      wire:model.live="layoutConfig.grid_size"
                                      wire:change="updateCanvas"
                                      min="10"
                                      max="50" />
                        </flux:field>

                        <!-- Snap to Grid -->
                        <flux:field>
                            <flux:label>Snap to Grid</flux:label>
                            <flux:checkbox wire:model.live="layoutConfig.snap_to_grid"
                                         wire:change="updateCanvas" />
                        </flux:field>

                        <!-- Layout Templates -->
                        <flux:field>
                            <flux:label>Apply Layout</flux:label>
                            <flux:select wire:change="applyLayoutTemplate($event.target.value)">
                                <option value="">Choose layout...</option>
                                @foreach($layoutOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col h-[calc(100vh-120px)]
                lg:flex-row">
        <!-- Sidebar with Available Images -->
        <div class="w-full border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-y-auto
                    lg:w-80">
            <div class="p-4">
                <flux:heading size="md" class="mb-4 text-gray-900 dark:text-gray-100">
                    Available Images
                </flux:heading>

                @if($availableImages->count() > 0)
                    <div class="grid grid-cols-2 gap-3
                                sm:grid-cols-3
                                lg:grid-cols-2">
                        @foreach($availableImages as $image)
                            <div draggable="true"
                                 x-data="{ 
                                     dragStart(e) {
                                         e.dataTransfer.setData('image-uuid', '{{ $image->uuid }}');
                                         e.dataTransfer.effectAllowed = 'copy';
                                     }
                                 }"
                                 @dragstart="dragStart($event)"
                                 class="group relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden cursor-move border-2 border-transparent transition-all duration-200
                                        hover:border-blue-300 dark:hover:border-blue-600
                                        hover:shadow-lg">
                                
                                @if($image->thumbnail_path)
                                    <img src="{{ Storage::url($image->thumbnail_path) }}"
                                         alt="{{ $image->title ?? $image->original_filename }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <flux:icon name="photo" 
                                                 class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                    </div>
                                @endif

                                <!-- Image Info Overlay -->
                                <div class="absolute inset-0 bg-black bg-opacity-0 flex items-end transition-all duration-200
                                            group-hover:bg-opacity-50">
                                    <div class="w-full p-2 text-white text-xs opacity-0 transition-opacity duration-200
                                                group-hover:opacity-100">
                                        <p class="font-medium truncate">
                                            {{ $image->title ?? $image->original_filename }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Add to Canvas Button -->
                                <button wire:click="addImageToCanvas('{{ $image->uuid }}')"
                                        class="absolute top-2 right-2 p-1 bg-blue-600 text-white rounded-full opacity-0 transition-all duration-200
                                               group-hover:opacity-100
                                               hover:bg-blue-700">
                                    <flux:icon name="plus" class="w-4 h-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:icon name="photo" class="mx-auto w-12 h-12 text-gray-400 dark:text-gray-500 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            No images available for mood boards yet.
                        </p>
                        <flux:button variant="primary" size="sm">
                            Upload Images
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="flex-1 relative overflow-hidden">
            @if($activeMoodBoard)
                <!-- Canvas Container -->
                <div x-data="{
                         handleDrop(e) {
                             e.preventDefault();
                             const rect = e.currentTarget.getBoundingClientRect();
                             const x = e.clientX - rect.left;
                             const y = e.clientY - rect.top;
                             const imageUuid = e.dataTransfer.getData('image-uuid');
                             
                             if (imageUuid) {
                                 $wire.handleImageDrop(imageUuid, { x: x, y: y });
                             }
                         },
                         handleDragOver(e) {
                             e.preventDefault();
                             e.dataTransfer.dropEffect = 'copy';
                         }
                     }"
                     @drop="handleDrop($event)"
                     @dragover="handleDragOver($event)"
                     style="background-color: {{ $layoutConfig['background_color'] ?? '#ffffff' }}"
                     class="relative w-full h-full min-h-96 overflow-auto">
                    
                    <!-- Grid Background (when enabled) -->
                    @if($layoutConfig['snap_to_grid'] ?? false)
                        <div class="absolute inset-0 opacity-20 pointer-events-none"
                             style="background-image: 
                                    linear-gradient(to right, #000 1px, transparent 1px),
                                    linear-gradient(to bottom, #000 1px, transparent 1px);
                                    background-size: {{ $layoutConfig['grid_size'] ?? 20 }}px {{ $layoutConfig['grid_size'] ?? 20 }}px;">
                        </div>
                    @endif

                    <!-- Canvas Images -->
                    @foreach($canvasImages as $canvasImage)
                        @php $image = $canvasImage['image']; $position = $canvasImage['position']; @endphp
                        <div x-data="{ 
                                 isDragging: false,
                                 startX: 0,
                                 startY: 0,
                                 currentX: {{ $position['x'] }},
                                 currentY: {{ $position['y'] }},
                                 
                                 startDrag(e) {
                                     if (!{{ $isEditMode ? 'true' : 'false' }}) return;
                                     this.isDragging = true;
                                     this.startX = e.clientX - this.currentX;
                                     this.startY = e.clientY - this.currentY;
                                     e.target.style.zIndex = '1000';
                                 },
                                 
                                 drag(e) {
                                     if (!this.isDragging) return;
                                     this.currentX = e.clientX - this.startX;
                                     this.currentY = e.clientY - this.startY;
                                 },
                                 
                                 stopDrag(e) {
                                     if (!this.isDragging) return;
                                     this.isDragging = false;
                                     e.target.style.zIndex = '{{ $position['z_index'] }}';
                                     
                                     // Snap to grid if enabled
                                     @if($layoutConfig['snap_to_grid'] ?? false)
                                         const gridSize = {{ $layoutConfig['grid_size'] ?? 20 }};
                                         this.currentX = Math.round(this.currentX / gridSize) * gridSize;
                                         this.currentY = Math.round(this.currentY / gridSize) * gridSize;
                                     @endif
                                     
                                     $wire.updateImagePosition('{{ $image->uuid }}', {
                                         x: this.currentX,
                                         y: this.currentY
                                     });
                                 }
                             }"
                             @mousedown="startDrag($event)"
                             @mousemove="drag($event)"
                             @mouseup="stopDrag($event)"
                             :style="`
                                 position: absolute;
                                 left: ${currentX}px;
                                 top: ${currentY}px;
                                 width: {{ $position['width'] }}px;
                                 height: {{ $position['height'] }}px;
                                 transform: rotate({{ $position['rotation'] }}deg);
                                 z-index: {{ $position['z_index'] }};
                                 cursor: {{ $isEditMode ? 'move' : 'pointer' }};
                             `"
                             class="group select-none {{ $isEditMode ? 'ring-2 ring-blue-300 dark:ring-blue-600' : '' }}">
                            
                            <!-- Image -->
                            @if($image->thumbnail_path)
                                <img src="{{ Storage::url($image->thumbnail_path) }}"
                                     alt="{{ $image->title ?? $image->original_filename }}"
                                     class="w-full h-full object-cover rounded-lg shadow-lg">
                            @else
                                <div class="w-full h-full bg-gray-200 dark:bg-gray-600 rounded-lg shadow-lg flex items-center justify-center">
                                    <flux:icon name="photo" class="w-8 h-8 text-gray-400" />
                                </div>
                            @endif

                            <!-- Edit Controls (visible in edit mode) -->
                            @if($isEditMode)
                                <div class="absolute -top-2 -right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <!-- Remove Button -->
                                    <button wire:click="removeImageFromCanvas('{{ $image->uuid }}')"
                                            class="p-1 bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 transition-colors">
                                        <flux:icon name="x-mark" class="w-3 h-3" />
                                    </button>
                                </div>

                                <!-- Resize Handles -->
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-blue-600 rounded-tl cursor-se-resize opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Drop Zone Indicator -->
                    <div class="absolute inset-0 border-2 border-dashed border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20 rounded-lg opacity-0 transition-opacity duration-200 pointer-events-none"
                         x-show="false"
                         x-transition>
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <flux:icon name="cloud-arrow-down" class="mx-auto w-12 h-12 text-blue-400 dark:text-blue-500 mb-2" />
                                <p class="text-blue-600 dark:text-blue-400 font-medium">
                                    Drop image here to add to mood board
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="flex items-center justify-center h-full">
                    <div class="text-center max-w-md mx-auto p-8">
                        <flux:icon name="squares-plus" class="mx-auto w-16 h-16 text-gray-400 dark:text-gray-500 mb-4" />
                        <flux:heading size="lg" class="text-gray-900 dark:text-gray-100 mb-2">
                            Create Your First Mood Board
                        </flux:heading>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            Mood boards help you organize and visualize your project's aesthetic direction. 
                            Start by creating a new mood board or selecting an existing one.
                        </p>
                        <flux:button wire:click="showCreateModal"
                                   variant="primary">
                            Create Mood Board
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Mood Board Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-md">
        <flux:heading size="lg">Create New Mood Board</flux:heading>

        <div class="space-y-4 mt-6">
            <flux:field>
                <flux:label required>Name</flux:label>
                <flux:input wire:model="newMoodBoardName"
                          placeholder="Enter mood board name"
                          maxlength="255" />
                <flux:error name="newMoodBoardName" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="newMoodBoardDescription"
                             placeholder="Describe your mood board (optional)"
                             rows="3"
                             maxlength="1000" />
                <flux:error name="newMoodBoardDescription" />
            </flux:field>

            <flux:field>
                <flux:label required>Layout Type</flux:label>
                <flux:select wire:model="newMoodBoardLayout">
                    @foreach($layoutOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="newMoodBoardLayout" />
            </flux:field>
        </div>

        <div class="flex gap-2 mt-6">
            <flux:button wire:click="resetCreateModal" variant="filled" class="flex-1">
                Cancel
            </flux:button>
            <flux:button wire:click="createMoodBoard" variant="primary" class="flex-1">
                Create
            </flux:button>
        </div>
    </flux:modal>

    <!-- Export Modal -->
    <flux:modal wire:model="showExportModal" class="max-w-md">
        <flux:heading size="lg">Export Mood Board</flux:heading>

        <div class="space-y-4 mt-6">
            <flux:field>
                <flux:label required>Format</flux:label>
                <flux:select wire:model="exportFormat">
                    <option value="pdf">PDF Document</option>
                    <option value="png">PNG Image</option>
                    <option value="jpg">JPEG Image</option>
                </flux:select>
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Width (px)</flux:label>
                    <flux:input type="number"
                              wire:model="exportOptions.width"
                              min="800"
                              max="4000" />
                </flux:field>

                <flux:field>
                    <flux:label>Height (px)</flux:label>
                    <flux:input type="number"
                              wire:model="exportOptions.height"
                              min="600"
                              max="4000" />
                </flux:field>
            </div>

            @if($exportFormat !== 'pdf')
                <flux:field>
                    <flux:label>Quality (%)</flux:label>
                    <flux:input type="range"
                              wire:model.live="exportOptions.quality"
                              min="60"
                              max="100"
                              class="w-full" />
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Current: {{ $exportOptions['quality'] }}%
                    </div>
                </flux:field>
            @endif

            <flux:field>
                <flux:checkbox wire:model="exportOptions.include_metadata">
                    Include metadata and project information
                </flux:checkbox>
            </flux:field>
        </div>

        <div class="flex gap-2 mt-6">
            <flux:button wire:click="$set('showExportModal', false)" variant="filled" class="flex-1">
                Cancel
            </flux:button>
            <flux:button wire:click="exportMoodBoard" variant="primary" class="flex-1">
                Export
            </flux:button>
        </div>
    </flux:modal>

    <!-- Mood Board Management Sidebar -->
    @if($moodBoards->count() > 1 || ($moodBoards->count() > 0 && !$activeMoodBoard))
        <div class="fixed bottom-4 left-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 max-w-xs
                    lg:relative lg:bottom-auto lg:left-auto lg:shadow-none lg:border-0 lg:bg-transparent lg:dark:bg-transparent lg:p-0">
            <flux:heading size="md" class="mb-3 text-gray-900 dark:text-gray-100">
                Your Mood Boards
            </flux:heading>

            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($moodBoards as $board)
                    <div class="flex items-center justify-between p-2 rounded border border-gray-200 dark:border-gray-600 {{ $activeMoodBoard && $activeMoodBoard->id === $board->id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-700' }}">
                        <div class="flex-1 min-w-0">
                            <button wire:click="loadMoodBoard('{{ $board->uuid }}')"
                                    class="text-left w-full">
                                <p class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                                    {{ $board->name }}
                                </p>
                                @if($board->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ $board->description }}
                                    </p>
                                @endif
                            </button>
                        </div>

                        @if($isEditMode)
                            <button wire:click="deleteMoodBoard('{{ $board->uuid }}')"
                                    wire:confirm="Are you sure you want to delete this mood board?"
                                    class="p-1 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                <flux:icon name="trash" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>