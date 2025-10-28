<div class="max-w-4xl mx-auto p-4 sm:p-6" wire:key="project-page-{{ $project->id }}">
    <div class="rounded-lg shadow-lg p-4 sm:p-6 lg:p-8 themed-project-box"
         @php
             $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
         @endphp
         @if($userTheme)
             style="background-color: {{ $userTheme->background_color }};
                    color: {{ $userTheme->text_color }};"
         @else
             class="bg-white dark:bg-gray-900"
         @endif>
        <!-- Project Header with Editable Name -->
        <div class="mb-8">
            <livewire:project-name-editor :project="$project" />
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
                    <code class="px-2 py-1 rounded text-xs">{{ $project->uuid }}</code>
                </div>
            </div>
        </div>
        
        @if($showAIControls && $this->filteredSuggestions->isEmpty())
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-primary-50 dark:bg-gray-800 rounded-lg p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Name Generation</h3>
                    </div>

                    <!-- AI Controls -->
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
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 shadow-md'
                                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                            role="button"
                                            aria-pressed="{{ $generationMode === $mode ? 'true' : 'false' }}"
                                            tabindex="0"
                                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                                        >
                                            <span class="text-xl">{{ $config['emoji'] }}</span>
                                            <span class="font-medium">{{ $config['label'] }}</span>
                                            @if($generationMode === $mode)
                                                <svg class="w-4 h-4 ml-auto text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                <flux:error name="generationMode" />
                            </flux:field>

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
                                    class="absolute inset-0 bg-blue-400 opacity-20 animate-pulse"
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
                                    
                                    <span wire:loading.remove wire:target="generateMoreNames" class="transition-all duration-200">
                                        Generate Names
                                    </span>
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
                        </div>

                        <!-- Error Message -->
                        @if($errorMessage)
                            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-700">{{ $errorMessage }}</p>
                            </div>
                        @endif
                </div>
            </div>
        @endif

        <!-- Optional Tools Section Header -->
        @if($this->filteredSuggestions->isEmpty())
            <div class="mt-8 pt-8 border-t-2 border-dashed border-gray-300 dark:border-gray-600">
                <div class="bg-gradient-to-r from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20
                            rounded-lg border-2 border-primary-200 dark:border-primary-700 p-6 mb-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                Optional Tools
                                <span class="ml-2 text-sm font-normal bg-primary-600 text-white px-2 py-1 rounded-full">
                                    Not Required
                                </span>
                            </h2>
                            <p class="text-base text-gray-700 dark:text-gray-300">
                                Enhance your results with these optional features below. Everything works great without them!
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <div class="absolute inset-0 bg-primary-400 dark:bg-primary-600 rounded-full blur-md opacity-50 animate-pulse"></div>
                                <div class="relative w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 dark:from-primary-600 dark:to-primary-700
                                            rounded-full flex items-center justify-center shadow-lg animate-bounce">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Photo Gallery Section - Only shown before name generation -->
        @if($this->filteredSuggestions->isEmpty())
            <div class="mt-6">
                <!-- Embedded Photo Gallery -->
                @livewire(\App\Livewire\PhotoGallery::class, ['project' => $project], 'gallery-'.$project->id)
            </div>

            <!-- Try Your Own Name Section -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <!-- Section Header -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                            Try Your Own Name
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Have a name or domain in mind? Check its availability and generate a logo.
                        </p>
                    </div>

                    <!-- Manual Name Input -->
                    <div class="space-y-4">
                        <div>
                            <flux:field>
                                <flux:label>Enter Your Name Idea</flux:label>
                                <flux:input
                                    wire:model.live="manualNameInput"
                                    placeholder="e.g., MyAwesomeBrand or myawesomebrand.com"
                                    class="w-full"
                                />
                                @error('manualNameInput')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3">
                            <flux:button
                                wire:click="checkManualName"
                                wire:loading.attr="disabled"
                                wire:target="checkManualName"
                                variant="primary"
                                :disabled="!$manualNameInput"
                            >
                                <span wire:loading.remove wire:target="checkManualName">
                                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Check Domain Availability
                                </span>
                                <span wire:loading wire:target="checkManualName">
                                    <svg class="w-4 h-4 mr-2 inline-block animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Checking...
                                </span>
                            </flux:button>

                            @if($manualNameChecked)
                                <flux:button
                                    wire:click="generateManualNameLogo"
                                    wire:loading.attr="disabled"
                                    wire:target="generateManualNameLogo"
                                    variant="ghost"
                                >
                                    <span wire:loading.remove wire:target="generateManualNameLogo">
                                        <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Generate Logo
                                    </span>
                                    <span wire:loading wire:target="generateManualNameLogo">
                                        <svg class="w-4 h-4 mr-2 inline-block animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Generating...
                                    </span>
                                </flux:button>
                            @endif
                        </div>

                        <!-- Domain Availability Results -->
                        @if($manualNameChecked && !empty($manualNameDomains))
                            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    Domain Availability for "{{ $cleanManualName }}"
                                </h4>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    @foreach($manualNameDomains as $domain => $info)
                                        <div class="flex items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded border
                                                    @if($info['available'] === true) border-green-200 dark:border-green-800
                                                    @elseif($info['available'] === false) border-red-200 dark:border-red-800
                                                    @else border-gray-200 dark:border-gray-700 @endif">
                                            @if($info['available'] === true)
                                                <svg class="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span class="text-sm text-green-700 dark:text-green-300 font-medium">{{ $info['extension'] }}</span>
                                            @elseif($info['available'] === false)
                                                <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span class="text-sm text-red-700 dark:text-red-300">{{ $info['extension'] }}</span>
                                            @else
                                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $info['extension'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Success Message if name was saved -->
                                @if($manualNameSaved)
                                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-green-900 dark:text-green-100">
                                                    Name saved successfully!
                                                </p>
                                                <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                                                    Your name "{{ $cleanManualName }}" has been added to your suggestions. You can view it in the Name Suggestions section after generating names.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Name Suggestions Section - Only shown when suggestions exist or generation is in progress -->
        @if($this->filteredSuggestions->isNotEmpty() || $isGeneratingNames)
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Name Suggestions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if($this->filteredSuggestions->isNotEmpty())
                            {{ $this->filteredSuggestions->count() }} suggestion{{ $this->filteredSuggestions->count() !== 1 ? 's' : '' }}
                        @else
                            Generating suggestions...
                        @endif
                    </p>
                </div>

                <!-- Generate More Names Button - Removed: Users can only generate once per project -->
            </div>

            <!-- Name Suggestions List -->
            <div class="relative">
            @if($this->filteredSuggestions->isEmpty())
                <!-- Generating placeholder -->
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h3 class="text-lg font-medium mb-2">Generating names...</h3>
                        <p>Please wait while we generate AI-powered name suggestions for your project.</p>
                    </div>
                </div>
            @else
                <!-- Suggestions Table -->
                <div class="space-y-6">
                    @foreach($this->filteredSuggestions as $suggestion)
                        <livewire:name-result-card
                            :suggestion="$suggestion"
                            :key="'name-result-v2-' . $suggestion->id"
                        />
                    @endforeach
                </div>
            @endif
            </div>

            <!-- Generate More Names Floating Button - Removed: Users can only generate once per project -->
        </div>
        @endif

        <!-- AI Generation Controls Modal/Section -->

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


        <!-- AI Generation History Section - Only shown when names have been generated -->
        @if(!empty($aiGenerationHistory) && $this->filteredSuggestions->isNotEmpty())
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
                                            <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                                            <span class="text-sm font-medium text-primary-700 dark:text-primary-400">{{ ucfirst($generation->status) }}</span>
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
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ ucfirst($generation->generation_mode) }}</span>
                                    </div>
                                @endif

                                @if($generation->models_requested)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Models:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ count($generation->models_requested) }} model(s)</span>
                                    </div>
                                @endif
                                
                                @if($generation->total_names_generated)
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Names:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $generation->total_names_generated }}</span>
                                    </div>
                                @endif

                                @if($generation->getDurationInSeconds())
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Duration:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $generation->getDurationInSeconds() }}s</span>
                                    </div>
                                @endif
                            </div>

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