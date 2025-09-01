<div class="max-w-4xl mx-auto p-4 sm:p-6">
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-4 sm:p-6 lg:p-8">
        <!-- Project Header with Editable Name -->
        <div class="mb-8">
            @if($editingName)
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <flux:field class="flex-1">
                        <flux:input
                            wire:model="editableName"
                            wire:keydown.enter="saveName"
                            wire:keydown.escape="cancelNameEdit"
                            class="text-2xl sm:text-3xl font-bold bg-transparent border-0 border-b-2 border-blue-500 focus:ring-0 focus:border-blue-600"
                            placeholder="Project name"
                            autofocus
                        />
                        <flux:error name="editableName" />
                    </flux:field>
                    
                    <div class="flex gap-2 sm:flex-shrink-0">
                        <flux:button 
                            wire:click="saveName"
                            variant="primary"
                            size="sm"
                            wire:loading.attr="disabled"
                            class="flex-1 sm:flex-none"
                        >
                            <span wire:loading.remove wire:target="saveName">Save</span>
                            <span wire:loading wire:target="saveName">Saving...</span>
                        </flux:button>
                        
                        <flux:button 
                            wire:click="cancelNameEdit"
                            variant="ghost"
                            size="sm"
                            class="flex-1 sm:flex-none"
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
        
        <!-- Name Suggestions Section -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Name Suggestions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if($this->suggestionCounts['total'] > 0)
                            {{ $this->suggestionCounts['visible'] }} visible, {{ $this->suggestionCounts['hidden'] }} hidden
                        @else
                            No suggestions generated yet
                        @endif
                    </p>
                </div>

                <!-- Filter Controls -->
                @if($this->suggestionCounts['total'] > 0)
                    <div class="flex items-center space-x-2 sm:flex-shrink-0">
                        <flux:button
                            wire:click="setResultsFilter('visible')"
                            variant="{{ $resultsFilter === 'visible' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:loading.attr="disabled"
                            wire:target="setResultsFilter"
                        >
                            <span wire:loading.remove wire:target="setResultsFilter">Visible ({{ $this->suggestionCounts['visible'] }})</span>
                            <span wire:loading wire:target="setResultsFilter">Loading...</span>
                        </flux:button>
                        
                        @if($this->suggestionCounts['hidden'] > 0)
                            <flux:button
                                wire:click="setResultsFilter('hidden')"
                                variant="{{ $resultsFilter === 'hidden' ? 'primary' : 'ghost' }}"
                                size="sm"
                                wire:loading.attr="disabled"
                                wire:target="setResultsFilter"
                            >
                                <span wire:loading.remove wire:target="setResultsFilter">Hidden ({{ $this->suggestionCounts['hidden'] }})</span>
                                <span wire:loading wire:target="setResultsFilter">Loading...</span>
                            </flux:button>
                        @endif
                        
                        <flux:button
                            wire:click="setResultsFilter('all')"
                            variant="{{ $resultsFilter === 'all' ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:loading.attr="disabled"
                            wire:target="setResultsFilter"
                        >
                            <span wire:loading.remove wire:target="setResultsFilter">All ({{ $this->suggestionCounts['total'] }})</span>
                            <span wire:loading wire:target="setResultsFilter">Loading...</span>
                        </flux:button>
                    </div>
                @endif
            </div>

            <!-- Name Suggestions List -->
            <div class="relative">
                <!-- Loading Overlay -->
                <div wire:loading wire:target="setResultsFilter" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-10 rounded-lg">
                    <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Filtering suggestions...</span>
                    </div>
                </div>

            @if($this->filteredSuggestions->isEmpty())
                <div class="text-center py-12">
                    @if($this->suggestionCounts['total'] === 0)
                        <!-- No suggestions at all -->
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Ready to generate names</h4>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Start by describing your project above, then generate AI-powered name suggestions.</p>
                        <flux:button variant="primary">
                            Generate Names
                        </flux:button>
                    @else
                        <!-- No suggestions for current filter -->
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <p>No {{ $resultsFilter }} suggestions found</p>
                        </div>
                    @endif
                </div>
            @else
                <!-- Suggestions Table -->
                <div class="space-y-4">
                    @foreach($this->filteredSuggestions as $suggestion)
                        @livewire('name-result-card', ['suggestion' => $suggestion], key('suggestion-'.$suggestion->id))
                    @endforeach
                </div>
            @endif
            </div>
        </div>
    </div>
</div>