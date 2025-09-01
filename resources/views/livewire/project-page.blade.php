<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-8">
        <!-- Project Header with Editable Name -->
        <div class="mb-8">
            @if($editingName)
                <div class="flex items-center gap-3">
                    <flux:field class="flex-1">
                        <flux:input
                            wire:model="editableName"
                            wire:keydown.enter="saveName"
                            wire:keydown.escape="cancelNameEdit"
                            class="text-3xl font-bold bg-transparent border-0 border-b-2 border-blue-500 focus:ring-0 focus:border-blue-600"
                            placeholder="Project name"
                            autofocus
                        />
                        <flux:error name="editableName" />
                    </flux:field>
                    
                    <div class="flex gap-2">
                        <flux:button 
                            wire:click="saveName"
                            variant="primary"
                            size="sm"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="saveName">Save</span>
                            <span wire:loading wire:target="saveName">Saving...</span>
                        </flux:button>
                        
                        <flux:button 
                            wire:click="cancelNameEdit"
                            variant="ghost"
                            size="sm"
                        >
                            Cancel
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="group flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex-1">
                        {{ $project->name }}
                    </h1>
                    
                    <flux:button 
                        wire:click="editName"
                        variant="ghost"
                        size="sm"
                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        Edit
                    </flux:button>
                </div>
            @endif
        </div>

        <!-- Project Description with Auto-save -->
        <div class="mb-8">
            <flux:field>
                <flux:label for="description" class="text-lg font-semibold mb-3">Description</flux:label>
                <flux:textarea
                    id="description"
                    wire:model.live.debounce.1000ms="editableDescription"
                    wire:blur="saveDescription"
                    placeholder="Describe your project in detail..."
                    rows="8"
                    maxlength="2000"
                    class="w-full"
                />
                <flux:description class="flex justify-between items-center">
                    <span>{{ $this->descriptionCharacterCount }} characters</span>
                    <span wire:loading wire:target="saveDescription" class="text-green-600">
                        Auto-saving...
                    </span>
                </flux:description>
                <flux:error name="editableDescription" />
            </flux:field>
        </div>

        <!-- Project Stats and Metadata -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                <div>
                    <span class="font-medium">Created:</span>
                    {{ $project->created_at->format('M j, Y') }}
                </div>
                <div>
                    <span class="font-medium">Last Updated:</span>
                    {{ $project->updated_at->format('M j, Y g:i A') }}
                </div>
                <div>
                    <span class="font-medium">Project ID:</span>
                    <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">{{ $project->uuid }}</code>
                </div>
            </div>
        </div>
        
        <!-- Future: Name suggestions table will go here -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <h3 class="text-lg font-medium mb-2">Name Suggestions</h3>
                <p class="text-sm">Name generation functionality will be added in the next phase.</p>
            </div>
        </div>
    </div>
</div>