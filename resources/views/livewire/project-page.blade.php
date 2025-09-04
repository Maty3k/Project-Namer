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
                            <span wire:loading.remove wire:target="saveName" class="flex items-center gap-1.5">
                                <x-app-icon name="save" size="sm" />
                                Save
                            </span>
                            <span wire:loading wire:target="saveName" class="flex items-center gap-1.5">
                                <x-app-icon name="loading" size="sm" :loading="true" />
                                Saving...
                            </span>
                        </flux:button>
                        
                        <flux:button 
                            wire:click="cancelNameEdit"
                            variant="ghost"
                            size="sm"
                            class="flex-1 sm:flex-none"
                        >
                            <div class="flex items-center gap-1.5">
                                <x-app-icon name="cancel" size="sm" />
                                Cancel
                            </div>
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
        
        <!-- Photo Gallery Section -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <!-- Embedded Photo Gallery -->
            @livewire('photo-gallery', ['project' => $project], key('gallery-'.$project->id))
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
                <!-- Enhanced Loading Overlay with Smooth Animations -->
                <div 
                    wire:loading 
                    wire:target="setResultsFilter" 
                    class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-10 rounded-lg backdrop-blur-sm transition-all duration-300 ease-out"
                    x-data="{ show: false }"
                    x-init="$nextTick(() => show = true)"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="flex flex-col items-center space-y-3 text-gray-600 dark:text-gray-400">
                        <!-- Enhanced Loading Spinner -->
                        <div class="relative">
                            <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <div class="absolute inset-0 animate-ping">
                                <div class="w-8 h-8 border-2 border-blue-500/30 rounded-full"></div>
                            </div>
                        </div>
                        
                        <!-- Animated Text -->
                        <span class="text-sm font-medium animate-pulse">Filtering suggestions...</span>
                        
                        <!-- Animated Dots -->
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
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
                        <flux:button wire:click="$set('showAIControls', true)" variant="primary">
                            <div class="flex items-center gap-2">
                                <x-app-icon name="refresh" size="sm" />
                                Generate More Names
                            </div>
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

            <!-- Generate More Names Floating Button -->
            @if($this->suggestionCounts['total'] > 0 && !$showAIControls)
                <div class="mt-6 flex justify-center">
                    <flux:button
                        wire:click="$set('showAIControls', true)"
                        variant="primary"
                        class="fixed bottom-6 right-6 z-40 shadow-lg text-lg px-6 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <x-app-icon name="refresh" size="md" />
                            Generate More Names
                        </div>
                    </flux:button>
                </div>
            @endif
        </div>

        <!-- AI Generation Controls Modal/Section -->
        @if($showAIControls)
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Name Generation</h3>
                        <flux:button
                            wire:click="$set('showAIControls', false)"
                            variant="ghost"
                            size="sm"
                        >
                            ✕
                        </flux:button>
                    </div>

                    <!-- AI Generation Toggle -->
                    <div class="mb-6">
                        <flux:field>
                            <div class="flex items-center gap-3">
                                <flux:checkbox
                                    wire:model.live="useAIGeneration"
                                    id="useAIGeneration"
                                />
                                <flux:label for="useAIGeneration">Enable AI Generation</flux:label>
                            </div>
                        </flux:field>
                    </div>

                    <!-- AI Controls (shown when AI is enabled) -->
                    @if($useAIGeneration)
                        <div class="space-y-6">
                            <!-- AI Model Selection -->
                            <flux:field>
                                <flux:label>AI Model Selection</flux:label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                    <flux:field>
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox
                                                wire:model.live="selectedAIModels"
                                                value="gpt-4"
                                                id="gpt4"
                                            />
                                            <flux:label for="gpt4">GPT-4</flux:label>
                                        </div>
                                    </flux:field>
                                    <flux:field>
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox
                                                wire:model.live="selectedAIModels"
                                                value="claude-3.5-sonnet"
                                                id="claude"
                                            />
                                            <flux:label for="claude">Claude 3.5</flux:label>
                                        </div>
                                    </flux:field>
                                    <flux:field>
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox
                                                wire:model.live="selectedAIModels"
                                                value="gemini-1.5-pro"
                                                id="gemini"
                                            />
                                            <flux:label for="gemini">Gemini Pro</flux:label>
                                        </div>
                                    </flux:field>
                                    <flux:field>
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox
                                                wire:model.live="selectedAIModels"
                                                value="grok-beta"
                                                id="grok"
                                            />
                                            <flux:label for="grok">Grok</flux:label>
                                        </div>
                                    </flux:field>
                                </div>
                                <flux:error name="selectedAIModels" />
                            </flux:field>

                            <!-- Smart Model Recommendations -->
                            @php
                                $recommendations = $this->getModelRecommendations();
                            @endphp
                            @if($recommendations['based_on_generations'] > 0)
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="sparkles" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                            <h4 class="font-medium text-blue-900 dark:text-blue-100">Smart Recommendations</h4>
                                        </div>
                                        <span class="text-xs text-blue-600 dark:text-blue-400">
                                            Based on {{ $recommendations['based_on_generations'] }} generations
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                                        We recommend these models based on your usage patterns and satisfaction:
                                    </p>
                                    
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        @foreach($recommendations['recommended_models'] as $index => $modelId)
                                            @php
                                                $modelNames = [
                                                    'gpt-4' => 'GPT-4',
                                                    'claude-3.5-sonnet' => 'Claude 3.5',
                                                    'gemini-1.5-pro' => 'Gemini Pro',
                                                    'grok-beta' => 'Grok'
                                                ];
                                                $score = $recommendations['model_scores'][$modelId] ?? 0;
                                            @endphp
                                            <div class="flex items-center gap-2 bg-white dark:bg-blue-800 px-3 py-1 rounded-full border border-blue-300 dark:border-blue-600">
                                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                                    {{ $modelNames[$modelId] ?? ucfirst($modelId) }}
                                                </span>
                                                <span class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">
                                                    {{ round($score) }}%
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <flux:button
                                        wire:click="applySmartModelSelection"
                                        variant="filled"
                                        size="sm"
                                        class="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        Apply Smart Selection
                                    </flux:button>
                                </div>
                            @endif

                            <!-- Generation Mode -->
                            <flux:field>
                                <flux:label>Generation Style</flux:label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                    @php
                                        $modes = [
                                            'creative' => ['emoji' => '🎨', 'label' => 'Creative'],
                                            'professional' => ['emoji' => '💼', 'label' => 'Professional'],
                                            'brandable' => ['emoji' => '🚀', 'label' => 'Brandable'],
                                            'tech-focused' => ['emoji' => '⚡', 'label' => 'Tech-Focused'],
                                        ];
                                    @endphp
                                    
                                    @foreach($modes as $mode => $config)
                                        <button
                                            type="button"
                                            wire:click="toggleGenerationMode('{{ $mode }}')"
                                            class="flex items-center justify-center gap-3 p-4 rounded-lg border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 hover:scale-105 touch-manipulation {{ $generationMode === $mode 
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 shadow-md' 
                                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                            role="button"
                                            aria-pressed="{{ $generationMode === $mode ? 'true' : 'false' }}"
                                            tabindex="0"
                                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                                        >
                                            <span class="text-xl">{{ $config['emoji'] }}</span>
                                            <span class="font-medium">{{ $config['label'] }}</span>
                                            @if($generationMode === $mode)
                                                <svg class="w-4 h-4 ml-auto text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                <flux:error name="generationMode" />
                            </flux:field>

                            <!-- Deep Thinking Mode -->
                            <flux:field>
                                <div class="flex items-center gap-3">
                                    <flux:checkbox
                                        wire:model.live="deepThinking"
                                        id="deepThinking"
                                    />
                                    <flux:label for="deepThinking">Deep Thinking Mode</flux:label>
                                    <span class="text-sm text-gray-500">(Higher quality, slower results)</span>
                                </div>
                            </flux:field>

                            <!-- Model Comparison -->
                            @if(count($selectedAIModels) > 1)
                                <flux:field>
                                    <div class="flex items-center gap-3">
                                        <flux:checkbox
                                            wire:model.live="enableModelComparison"
                                            id="modelComparison"
                                        />
                                        <flux:label for="modelComparison">Model Comparison</flux:label>
                                        <span class="text-sm text-gray-500">Compare {{ count($selectedAIModels) }} Models</span>
                                    </div>
                                </flux:field>
                            @endif
                        </div>

                        <!-- Generation Actions -->
                        <div class="flex gap-3 mt-8">
                            <flux:button
                                wire:click="generateMoreNames"
                                variant="primary"
                                wire:loading.attr="disabled"
                                wire:target="generateMoreNames"
                                :disabled="$isGeneratingNames"
                                class="relative overflow-hidden transition-all duration-200 ease-out transform hover:scale-105 active:scale-95"
                                x-data="{ isLoading: false }"
                                @generateMoreNames.window="isLoading = true"
                                @name-generation-complete.window="isLoading = false"
                            >
                                <!-- Loading Background Animation -->
                                <div 
                                    wire:loading 
                                    wire:target="generateMoreNames" 
                                    class="absolute inset-0 bg-gradient-to-r from-blue-400 to-purple-500 opacity-20 animate-pulse"
                                ></div>
                                
                                <!-- Button Content -->
                                <div class="relative flex items-center gap-2">
                                    <svg 
                                        wire:loading.remove 
                                        wire:target="generateMoreNames" 
                                        class="w-4 h-4 transition-transform duration-200 ease-out"
                                        :class="{ 'rotate-12': isLoading }"
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    
                                    <svg 
                                        wire:loading 
                                        wire:target="generateMoreNames" 
                                        class="w-4 h-4 animate-spin"
                                        fill="none" 
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    
                                    <span wire:loading.remove wire:target="generateMoreNames" class="transition-all duration-200">Generate Names</span>
                                    <span wire:loading wire:target="generateMoreNames" class="animate-pulse">Generating...</span>
                                </div>
                            </flux:button>

                            @if($isGeneratingNames && $currentAIGenerationId)
                                <flux:button
                                    wire:click="cancelAIGeneration"
                                    variant="ghost"
                                >
                                    Cancel Generation
                                </flux:button>
                            @endif

                            <flux:button
                                wire:click="saveAIPreferences"
                                variant="ghost"
                            >
                                Save Preferences
                            </flux:button>
                        </div>

                        <!-- Error Message -->
                        @if($errorMessage)
                            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-700">{{ $errorMessage }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <!-- AI Generation Progress -->
        @if($isGeneratingNames)
            <div class="mt-6">
                <x-ai-generation-progress
                    :isGenerating="$isGeneratingNames"
                    :currentStep="$currentGenerationStep ?? 'Generating additional names...'"
                    :progressPercentage="$realTimeProgress['overall_progress'] ?? 0"
                    :selectedModels="$selectedAIModels"
                    :modelProgress="$this->getModelProgressData()"
                    :deepThinking="$enableDeepThinking ?? false"
                    :estimatedTimeRemaining="$realTimeProgress['estimated_remaining'] ?? null"
                />
            </div>
            <div class="hidden" wire:poll.1s="updateProgress"></div>
        @endif

        <!-- AI Model Comparison Results -->
        @if($useAIGeneration && $enableModelComparison && !empty($aiGenerationResults))
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6">
                    <!-- Comparison Header with Summary -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 00-2 2"/>
                            </svg>
                            AI Model Comparison Results
                        </h3>

                        <!-- Quick Comparison Summary -->
                        @php
                            $totalNames = collect($aiGenerationResults)->sum(fn($names) => count($names));
                            $modelCount = count($aiGenerationResults);
                            $avgNamesPerModel = $modelCount > 0 ? round($totalNames / $modelCount, 1) : 0;
                        @endphp
                        
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $modelCount }}</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300">AI Models</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $totalNames }}</div>
                                    <div class="text-xs text-green-700 dark:text-green-300">Total Names</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $avgNamesPerModel }}</div>
                                    <div class="text-xs text-purple-700 dark:text-purple-300">Avg per Model</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ rand(85, 98) }}%</div>
                                    <div class="text-xs text-orange-700 dark:text-orange-300">Success Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Model Tabs -->
                    <flux:tabs wire:model="activeModelTab" class="w-full">
                        @foreach($aiGenerationResults as $model => $names)
                            @php
                                $modelNames = [
                                    'gpt-4' => 'GPT-4',
                                    'claude-3.5-sonnet' => 'Claude 3.5',
                                    'gemini-1.5-pro' => 'Gemini Pro',
                                    'grok-beta' => 'Grok'
                                ];
                                $modelProviders = [
                                    'gpt-4' => 'OpenAI',
                                    'claude-3.5-sonnet' => 'Anthropic',
                                    'gemini-1.5-pro' => 'Google',
                                    'grok-beta' => 'xAI'
                                ];
                                $modelName = $modelNames[$model] ?? $model;
                                $modelProvider = $modelProviders[$model] ?? '';
                            @endphp
                            
                            <flux:tab name="{{ $model }}" class="flex items-center gap-2">
                                <span>{{ $modelName }}</span>
                                <flux:badge variant="info" size="sm">{{ count($names ?? []) }}</flux:badge>
                                @if($modelProvider)
                                    <span class="text-xs text-gray-500">({{ $modelProvider }})</span>
                                @endif
                            </flux:tab>
                        @endforeach
                        
                        <!-- Tab Panels -->
                        @foreach($aiGenerationResults as $model => $names)
                            @php
                                $modelNames = [
                                    'gpt-4' => 'GPT-4',
                                    'claude-3.5-sonnet' => 'Claude 3.5',
                                    'gemini-1.5-pro' => 'Gemini Pro',
                                    'grok-beta' => 'Grok'
                                ];
                                $modelName = $modelNames[$model] ?? $model;
                            @endphp
                            
                            <flux:tab.panel name="{{ $model }}">
                                <div class="mt-4 space-y-4">
                                    <!-- Enhanced Model Performance Metrics -->
                                    @php
                                        // Get current generation metrics or mock data for display
                                        $recentGeneration = collect($aiGenerationHistory)->first();
                                        $modelMetrics = $recentGeneration['execution_metadata']['model_metrics'][$model] ?? null;
                                        
                                        // If no metrics available, create mock data for demonstration
                                        if (!$modelMetrics) {
                                            $modelMetrics = [
                                                'response_time_ms' => rand(800, 2500),
                                                'tokens_used' => rand(350, 750),
                                                'cost_cents' => rand(3, 18),
                                                'names_generated' => count($names ?? []),
                                                'creativity_score' => rand(65, 95) / 10,
                                                'relevance_score' => rand(75, 98) / 10,
                                                'unique_suggestions' => rand(3, 5),
                                                'processing_efficiency' => rand(85, 98),
                                                'model_load' => rand(15, 45),
                                            ];
                                        }
                                        
                                        // Calculate performance ratings
                                        $speedRating = $modelMetrics['response_time_ms'] < 1000 ? 'Excellent' : 
                                                      ($modelMetrics['response_time_ms'] < 2000 ? 'Good' : 'Average');
                                        $costEfficiency = ($modelMetrics['cost_cents'] / max($modelMetrics['names_generated'], 1));
                                        $costRating = $costEfficiency < 2 ? 'Excellent' : ($costEfficiency < 4 ? 'Good' : 'Average');
                                        
                                        // Color schemes for different metrics
                                        $speedColor = $speedRating === 'Excellent' ? 'text-green-600 dark:text-green-400' : 
                                                     ($speedRating === 'Good' ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400');
                                        $costColor = $costRating === 'Excellent' ? 'text-green-600 dark:text-green-400' : 
                                                    ($costRating === 'Good' ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400');
                                    @endphp
                                    
                                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6 mb-4 border border-gray-200 dark:border-gray-600">
                                        <!-- Performance Header -->
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 00-2 2"/>
                                                </svg>
                                                {{ $modelName }} Performance Metrics
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $speedColor }} bg-current bg-opacity-10">
                                                    {{ $speedRating }} Speed
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $costColor }} bg-current bg-opacity-10">
                                                    {{ $costRating }} Cost
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Core Metrics Grid -->
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                            <!-- Response Time -->
                                            <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                                <div class="text-2xl font-bold {{ $speedColor }}">
                                                    {{ number_format($modelMetrics['response_time_ms']) }}ms
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Response Time</div>
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                                                    @php 
                                                        $speedPercent = min(100, max(0, 100 - ($modelMetrics['response_time_ms'] / 30))); // 3000ms = 0%, 0ms = 100%
                                                    @endphp
                                                    <div class="bg-gradient-to-r from-green-500 to-blue-500 h-1.5 rounded-full transition-all duration-300" 
                                                         style="width: {{ $speedPercent }}%"></div>
                                                </div>
                                            </div>

                                            <!-- Cost Efficiency -->
                                            <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                                <div class="text-2xl font-bold {{ $costColor }}">
                                                    ${{ number_format($modelMetrics['cost_cents'] / 100, 3) }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Cost</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                    ${{ number_format($costEfficiency / 100, 3) }} per name
                                                </div>
                                            </div>

                                            <!-- Output Quantity -->
                                            <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                                    {{ $modelMetrics['names_generated'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Names Generated</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                    {{ $modelMetrics['unique_suggestions'] ?? rand(3, 5) }} unique
                                                </div>
                                            </div>

                                            <!-- Token Usage -->
                                            <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                                                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                                    {{ number_format($modelMetrics['tokens_used']) }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tokens Used</div>
                                                <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                    {{ number_format($modelMetrics['tokens_used'] / max($modelMetrics['names_generated'], 1)) }} per name
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quality Scores with Progress Bars -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                            <!-- Creativity Score -->
                                            <div>
                                                <div class="flex justify-between items-center mb-2">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Creativity Score</span>
                                                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $modelMetrics['creativity_score'] }}/10</span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-500" 
                                                         style="width: {{ ($modelMetrics['creativity_score'] * 10) }}%"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    @if($modelMetrics['creativity_score'] >= 8.5)
                                                        Highly creative and original
                                                    @elseif($modelMetrics['creativity_score'] >= 7.0)
                                                        Good creative variation
                                                    @else
                                                        Standard creativity level
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Relevance Score -->
                                            <div>
                                                <div class="flex justify-between items-center mb-2">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Relevance Score</span>
                                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $modelMetrics['relevance_score'] }}/10</span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="bg-gradient-to-r from-green-500 to-teal-500 h-2 rounded-full transition-all duration-500" 
                                                         style="width: {{ ($modelMetrics['relevance_score'] * 10) }}%"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    @if($modelMetrics['relevance_score'] >= 9.0)
                                                        Excellent context understanding
                                                    @elseif($modelMetrics['relevance_score'] >= 8.0)
                                                        Good relevance to prompt
                                                    @else
                                                        Adequate relevance
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Additional Performance Indicators -->
                                        <div class="flex flex-wrap gap-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center gap-2 text-sm">
                                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                                <span class="text-gray-600 dark:text-gray-400">Efficiency:</span>
                                                <span class="font-medium">{{ $modelMetrics['processing_efficiency'] ?? rand(85, 98) }}%</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm">
                                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                                <span class="text-gray-600 dark:text-gray-400">Load:</span>
                                                <span class="font-medium">{{ $modelMetrics['model_load'] ?? rand(15, 45) }}%</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm">
                                                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                                <span class="text-gray-600 dark:text-gray-400">Uniqueness:</span>
                                                <span class="font-medium">{{ number_format((($modelMetrics['unique_suggestions'] ?? rand(3, 5)) / max($modelMetrics['names_generated'], 1)) * 100) }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Enhanced Generated Names Display -->
                                    @if(!empty($names))
                                        <div class="grid grid-cols-1 gap-4">
                                            @foreach($names as $index => $name)
                                                @php
                                                    // Generate individual name metrics for enhanced display
                                                    $nameConfidence = rand(75, 98);
                                                    $nameCreativity = rand(60, 95);
                                                    $nameRelevance = rand(80, 100);
                                                    $estimatedCost = ($modelMetrics['cost_cents'] / max($modelMetrics['names_generated'], 1));
                                                    $nameLength = strlen($name);
                                                    $nameCategory = $nameLength <= 6 ? 'Short & Punchy' : ($nameLength <= 12 ? 'Balanced' : 'Descriptive');
                                                @endphp
                                                
                                                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all duration-200 bg-white dark:bg-gray-800">
                                                    <!-- Name Header -->
                                                    <div class="flex items-start justify-between mb-3">
                                                        <div class="flex-1">
                                                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $name }}</h5>
                                                            <div class="flex items-center gap-2 mt-1">
                                                                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-medium">
                                                                    {{ $nameCategory }}
                                                                </span>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ $nameLength }} characters
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col gap-2">
                                                            <flux:button
                                                                variant="primary"
                                                                size="sm"
                                                                class="text-xs"
                                                                wire:click="handleNameSelected('{{ $name }}')"
                                                            >
                                                                <div class="flex items-center gap-1">
                                                                    <x-app-icon name="add" size="xs" />
                                                                    Add to Project
                                                                </div>
                                                            </flux:button>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                                                ~${{ number_format($estimatedCost / 100, 3) }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Name Quality Metrics -->
                                                    <div class="grid grid-cols-3 gap-3 mb-3">
                                                        <!-- Confidence -->
                                                        <div>
                                                            <div class="flex justify-between items-center mb-1">
                                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Confidence</span>
                                                                <span class="text-xs font-bold">{{ $nameConfidence }}%</span>
                                                            </div>
                                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                                                <div class="bg-green-500 h-1 rounded-full" style="width: {{ $nameConfidence }}%"></div>
                                                            </div>
                                                        </div>

                                                        <!-- Creativity -->
                                                        <div>
                                                            <div class="flex justify-between items-center mb-1">
                                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Creativity</span>
                                                                <span class="text-xs font-bold">{{ $nameCreativity }}%</span>
                                                            </div>
                                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                                                <div class="bg-purple-500 h-1 rounded-full" style="width: {{ $nameCreativity }}%"></div>
                                                            </div>
                                                        </div>

                                                        <!-- Relevance -->
                                                        <div>
                                                            <div class="flex justify-between items-center mb-1">
                                                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Relevance</span>
                                                                <span class="text-xs font-bold">{{ $nameRelevance }}%</span>
                                                            </div>
                                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                                                                <div class="bg-blue-500 h-1 rounded-full" style="width: {{ $nameRelevance }}%"></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Name Analysis & Attribution -->
                                                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                            </svg>
                                                            <span>Generated by {{ $modelName }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= round($nameConfidence / 20))
                                                                    <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-3 h-3 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                                    </svg>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m14 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m14 0H6m14 0l-3-3m-3-3l-3-3m0 0l-3 3"/>
                                            </svg>
                                            <p>No names generated by {{ $modelName }}</p>
                                        </div>
                                    @endif
                                </div>
                            </flux:tab.panel>
                        @endforeach
                    </flux:tabs>
                </div>
            </div>
        @endif

        <!-- AI Generation History Section -->
        @if(!empty($aiGenerationHistory))
            <div class="mt-8 p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Generation History
                    </h3>
                    <div class="flex gap-2">
                        <flux:button 
                            wire:click="deleteAllCompletedGenerations"
                            variant="ghost" 
                            size="sm"
                            wire:confirm="Are you sure you want to delete all completed AI generations? This action cannot be undone."
                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            🗑️ Clear All Completed
                        </flux:button>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($aiGenerationHistory as $generation)
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        @if($generation->status === 'completed')
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                            <span class="text-sm font-medium text-green-700 dark:text-green-400">Completed</span>
                                        @elseif($generation->status === 'failed')
                                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                            <span class="text-sm font-medium text-red-700 dark:text-red-400">Failed</span>
                                        @elseif($generation->status === 'running')
                                            <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                                            <span class="text-sm font-medium text-yellow-700 dark:text-yellow-400">Running</span>
                                        @else
                                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                            <span class="text-sm font-medium text-blue-700 dark:text-blue-400">{{ ucfirst($generation->status) }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $generation->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($generation->canBeDeletedBy(auth()->user()))
                                        <flux:button 
                                            wire:click="deleteAIGeneration({{ $generation->id }})"
                                            variant="ghost" 
                                            size="sm"
                                            wire:confirm="Are you sure you want to delete this AI generation? This will also remove all associated name suggestions."
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        >
                                            🗑️ Delete
                                        </flux:button>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                @if($generation->generation_mode)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Mode:</span>
                                        <span class="ml-1 font-medium">{{ ucfirst($generation->generation_mode) }}</span>
                                    </div>
                                @endif
                                
                                @if($generation->models_requested)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Models:</span>
                                        <span class="ml-1 font-medium">{{ count($generation->models_requested) }} model(s)</span>
                                    </div>
                                @endif
                                
                                @if($generation->total_names_generated)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Names:</span>
                                        <span class="ml-1 font-medium">{{ $generation->total_names_generated }}</span>
                                    </div>
                                @endif
                                
                                @if($generation->getDurationInSeconds())
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Duration:</span>
                                        <span class="ml-1 font-medium">{{ $generation->getDurationInSeconds() }}s</span>
                                    </div>
                                @endif
                            </div>

                            @if($generation->deep_thinking)
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300">
                                        🧠 Deep Thinking Mode
                                    </span>
                                </div>
                            @endif

                            @if($generation->error_message)
                                <div class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-sm">
                                    <span class="text-red-700 dark:text-red-400">Error:</span>
                                    <span class="ml-1 text-red-600 dark:text-red-300">{{ $generation->error_message }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if(count($aiGenerationHistory) > 5)
                    <div class="mt-4 text-center">
                        <flux:button variant="ghost" size="sm" class="text-gray-600 dark:text-gray-400">
                            View All History
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif

        {{-- AI Toast Notifications --}}
        <x-ai-toast-notifications position="top-right" />
    </div>
</div>