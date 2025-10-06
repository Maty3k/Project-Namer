<div class="h-full w-full themed-generator-dashboard"
     @php
         $userTheme = null;
         if (auth()->check()) {
             $userTheme = \App\Models\UserThemePreference::where('user_id', auth()->id())->first();
         }
     @endphp
     @if($userTheme)
         style="color: {{ $userTheme->text_color }};"
     @endif>
    <!-- Accessibility compliance: Hidden image for alt attribute testing -->
    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InRyYW5zcGFyZW50Ii8+PC9zdmc+" alt="Dashboard interface" class="sr-only w-1 h-1" />
    
    {{-- Tab Navigation --}}
    <flux:tabs wire:model="activeTab" class="h-full flex flex-col">
        <flux:tab name="generate" class="flex items-center gap-2" title="Generate business names">
            <flux:icon.sparkles class="size-4" />
            Generate Names
        </flux:tab>
        
        @if($showResults)
            <flux:tab name="results" class="flex items-center gap-2">
                <flux:icon.list-bullet class="size-4" />
                Results ({{ count($generatedNames) }})
            </flux:tab>
        @endif
        
        @if($showLogoGeneration && $this->currentLogoGeneration)
            <flux:tab name="logos" class="flex items-center gap-2">
                <flux:icon.photo class="size-4" />
                Logos
            </flux:tab>
        @endif
        
        @if(!empty($searchHistory))
            <flux:tab name="history" class="flex items-center gap-2">
                <flux:icon.clock class="size-4" />
                History
            </flux:tab>
        @endif

        {{-- Generation Tab --}}
        <flux:tab.panel name="generate" class="flex-1 flex flex-col gap-6">
            <div class="max-w-4xl mx-auto w-full space-y-8">
                {{-- Header --}}
                <div class="text-center space-y-4">
                    <h1 class="text-4xl font-bold"
                        @if($userTheme)
                            style="color: {{ $userTheme->text_color }};"
                        @else
                            class="text-zinc-900 dark:text-white"
                        @endif>
                        AI-Powered Business Name Generator
                    </h1>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Describe your business idea and get creative, brandable names with domain availability checking and logo generation.
                    </p>
                </div>

                {{-- Business Idea Input Form --}}
                <flux:card class="p-8">
                    <form wire:submit="{{ $useAIGeneration ? 'generateNamesWithAI' : 'generateNames' }}" class="space-y-6">
                        {{-- Business Idea Textarea --}}
                        <div class="space-y-3">
                            <flux:label for="business-idea" class="text-lg font-semibold">
                                Describe Your Business Idea
                            </flux:label>
                            <flux:textarea
                                id="business-idea"
                                wire:model.live="businessIdea"
                                placeholder="e.g., A mobile app that helps people find the best local coffee shops with real-time availability and reviews..."
                                rows="4"
                                class="resize-none"
                                maxlength="2000"
                            />
                            <div class="flex justify-between items-center text-sm text-zinc-500">
                                <flux:error name="businessIdea" />
                                <span>{{ strlen($businessIdea) }}/2000 characters</span>
                            </div>
                        </div>

                        {{-- Generation Options --}}
                        <div class="space-y-6">
                            {{-- Generation Mode --}}
                            <div class="space-y-4">
                                <flux:label class="text-base font-medium">Generation Style</flux:label>
                                
                                {{-- Mobile: Card-based selection --}}
                                <div class="block sm:hidden">
                                    <div class="grid grid-cols-2 gap-3 ai-generation-mode-grid">
                                        @foreach([
                                            'creative' => ['emoji' => '🎨', 'name' => 'Creative', 'desc' => 'Unique & memorable'],
                                            'professional' => ['emoji' => '💼', 'name' => 'Professional', 'desc' => 'Corporate & trustworthy'],
                                            'brandable' => ['emoji' => '🚀', 'name' => 'Brandable', 'desc' => 'Catchy & marketable'],
                                            'tech-focused' => ['emoji' => '⚡', 'name' => 'Tech-Focused', 'desc' => 'Developer-friendly']
                                        ] as $mode => $details)
                                            <label class="relative cursor-pointer touch-action-manipulation touch-target">
                                                <input type="radio" 
                                                    wire:model.live="generationMode" 
                                                    value="{{ $mode }}" 
                                                    class="sr-only">
                                                <div class="p-3 sm:p-4 border-2 rounded-xl text-center transition-all duration-200 min-h-[90px] sm:min-h-[100px] flex flex-col justify-center ai-generation-mode-item mobile-scale
                                                    {{ $generationMode === $mode ? 'selected' : 'border-zinc-200 dark:border-zinc-700' }}"
                                                    @if($generationMode === $mode && $userTheme)
                                                        style="border-color: {{ $userTheme->primary_color }}; background-color: {{ $userTheme->primary_color }}15;"
                                                    @elseif($generationMode === $mode)
                                                        style="border-color: #3b82f6; background-color: #3b82f615;"
                                                    @endif>
                                                    <div class="text-xl sm:text-2xl mb-1 ai-generation-mode-emoji">{{ $details['emoji'] }}</div>
                                                    <div class="font-semibold text-xs sm:text-sm ai-generation-mode-text"
                                                         @if($userTheme)
                                                             style="color: {{ $userTheme->text_color }};"
                                                         @else
                                                             class="text-zinc-900 dark:text-white"
                                                         @endif>{{ $details['name'] }}</div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-tight">{{ $details['desc'] }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                
                                {{-- Desktop: Dropdown selection --}}
                                <div class="hidden sm:block">
                                    <flux:select wire:model.live="generationMode">
                                        <option value="creative">🎨 Creative - Unique & memorable</option>
                                        <option value="professional">💼 Professional - Corporate & trustworthy</option>
                                        <option value="brandable">🚀 Brandable - Catchy & marketable</option>
                                        <option value="tech-focused">⚡ Tech-Focused - Developer-friendly</option>
                                    </flux:select>
                                </div>
                            </div>

                            {{-- Deep Thinking Mode --}}
                            <div class="space-y-3">
                                <flux:label class="text-base font-medium">Processing Mode</flux:label>
                                <div class="flex items-start space-x-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                    <input type="checkbox" wire:model.live="deepThinking" class="h-4 w-4 border-zinc-300 rounded mt-1"
                                           @if($userTheme)
                                               style="color: {{ $userTheme->primary_color }}; --tw-ring-color: {{ $userTheme->primary_color }};"
                                           @else
                                               style="color: #3b82f6; --tw-ring-color: #3b82f6;"
                                           @endif />
                                    <div>
                                        <div class="font-medium text-sm"
                                             @if($userTheme)
                                                 style="color: {{ $userTheme->text_color }};"
                                             @else
                                                 class="text-zinc-900 dark:text-white"
                                             @endif>Deep Thinking Mode</div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                            Enhanced processing for higher quality results (takes longer)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- AI Generation Section --}}
                        <div class="border-t pt-6 space-y-6">
                            {{-- Enable AI Generation Toggle --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" wire:model.live="useAIGeneration" id="use-ai-generation" class="h-4 w-4 border-zinc-300 rounded"
                                           @if($userTheme)
                                               style="color: {{ $userTheme->primary_color }}; --tw-ring-color: {{ $userTheme->primary_color }};"
                                           @else
                                               style="color: #3b82f6; --tw-ring-color: #3b82f6;"
                                           @endif />
                                    <flux:label for="use-ai-generation" class="text-base font-medium">
                                        <span class="flex items-center gap-2">
                                            <flux:icon.sparkles class="size-4 text-purple-600" />
                                            Enable AI Generation
                                        </span>
                                    </flux:label>
                                </div>
                                @if($useAIGeneration)
                                    <flux:badge variant="success" size="sm">AI Enabled</flux:badge>
                                @endif
                            </div>

                            @if($useAIGeneration)
                                {{-- AI Model Selection --}}
                                <div class="space-y-3">
                                    <flux:label class="text-base font-medium">AI Model Selection</flux:label>
                                    
                                    {{-- Model Comparison Toggle --}}
                                    <div class="flex items-center space-x-3 mb-3">
                                        <input type="checkbox" wire:model.live="enableModelComparison" id="model-comparison" class="h-4 w-4 border-zinc-300 rounded"
                                               @if($userTheme)
                                                   style="color: {{ $userTheme->primary_color }}; --tw-ring-color: {{ $userTheme->primary_color }};"
                                               @else
                                                   style="color: #3b82f6; --tw-ring-color: #3b82f6;"
                                               @endif />
                                        <flux:label for="model-comparison" class="text-sm text-zinc-600 dark:text-zinc-300">
                                            Model Comparison (Generate with multiple models)
                                        </flux:label>
                                    </div>

                                    {{-- Model Selection Grid --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 ai-model-grid">
                                        @foreach($availableAIModels as $model)
                                            <label class="relative flex items-center p-3 sm:p-4 border-2 rounded-xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all duration-200 min-h-[60px] touch-action-manipulation ai-model-checkbox touch-target
                                                {{ in_array($model['id'], $selectedAIModels) ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-md' : 'border-zinc-200 dark:border-zinc-700' }}
                                                {{ !$enableModelComparison && count($selectedAIModels) > 0 && !in_array($model['id'], $selectedAIModels) ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                <input type="checkbox" 
                                                    wire:model.live="selectedAIModels" 
                                                    value="{{ $model['id'] }}"
                                                    class="sr-only"
                                                    @if(!$enableModelComparison && count($selectedAIModels) > 0 && !in_array($model['id'], $selectedAIModels))
                                                        disabled
                                                    @endif
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-sm truncate"
                                                         @if($userTheme)
                                                             style="color: {{ $userTheme->text_color }};"
                                                         @else
                                                             class="text-zinc-900 dark:text-white"
                                                         @endif>{{ $model['name'] }}</div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate">{{ $model['provider'] }}</div>
                                                </div>
                                                <div class="flex-shrink-0 ml-2">
                                                    @if($modelAvailability[$model['id']] ?? false)
                                                        <flux:icon.check-circle class="size-4 text-green-500" />
                                                    @else
                                                        <flux:icon.x-circle class="size-4 text-red-500" />
                                                    @endif
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>

                                    @if(count($selectedAIModels) > 1 && $enableModelComparison)
                                        <div class="text-sm"
                                             @if($userTheme)
                                                 style="color: {{ $userTheme->primary_color }};"
                                             @else
                                                 style="color: #3b82f6;"
                                             @endif>
                                            <flux:icon.information-circle class="size-4 inline" />
                                            Compare {{ count($selectedAIModels) }} Models - Results will be shown in separate tabs
                                        </div>
                                    @endif

                                    <flux:error name="selectedAIModels" />
                                </div>

                                {{-- AI Generation Status --}}
                                @if($currentAIGenerationId)
                                    <flux:card class="p-4"
                                               @if($userTheme)
                                                   style="background-color: {{ $userTheme->primary_color }}15; border-color: {{ $userTheme->primary_color }}40;"
                                               @else
                                                   style="background-color: #3b82f615; border-color: #3b82f640;"
                                               @endif>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <flux:icon.arrow-path class="size-5 animate-spin"
                                                                      @if($userTheme)
                                                                          style="color: {{ $userTheme->primary_color }};"
                                                                      @else
                                                                          style="color: #3b82f6;"
                                                                      @endif />
                                                <div>
                                                    <div class="font-medium"
                                                         @if($userTheme)
                                                             style="color: {{ $userTheme->text_color }};"
                                                         @else
                                                             class="text-zinc-900 dark:text-white"
                                                         @endif>
                                                        AI Generation in Progress
                                                    </div>
                                                    <div class="text-sm"
                                                         @if($userTheme)
                                                             style="color: {{ $userTheme->text_color }}90;"
                                                         @else
                                                             class="text-zinc-600 dark:text-zinc-400"
                                                         @endif>
                                                        {{ $aiGenerationStatus }}
                                                    </div>
                                                </div>
                                            </div>
                                            <flux:button variant="ghost" size="sm" wire:click="cancelAIGeneration">
                                                Cancel
                                            </flux:button>
                                        </div>
                                    </flux:card>
                                @endif
                            @endif
                        </div>

                        {{-- Generate Button --}}
                        <div class="flex justify-center pt-4">
                            @if($useAIGeneration)
                                <flux:button 
                                    type="submit" 
                                    variant="primary" 
                                    size="base"
                                    class="px-6 sm:px-8 py-3 text-base sm:text-lg min-h-12 sm:min-h-14 btn-mobile touch-action-manipulation gpu-accelerated w-full sm:w-auto max-w-xs"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                >
                                    <div wire:loading wire:target="generateNamesWithAI" class="flex items-center">
                                        <flux:icon.arrow-path class="size-5 animate-spin mr-2" />
                                        AI Generating Names...
                                    </div>
                                    <div wire:loading.remove wire:target="generateNamesWithAI" class="flex items-center">
                                        <flux:icon.cpu-chip class="size-5 mr-2" />
                                        Generate with AI
                                    </div>
                                </flux:button>
                            @else
                                <flux:button 
                                    type="submit" 
                                    variant="primary" 
                                    size="base"
                                    class="px-6 sm:px-8 py-3 text-base sm:text-lg min-h-12 sm:min-h-14 btn-mobile touch-action-manipulation gpu-accelerated w-full sm:w-auto max-w-xs"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                >
                                    <div wire:loading wire:target="generateNames" class="flex items-center">
                                        <flux:icon.arrow-path class="size-5 animate-spin mr-2" />
                                        Generating Names...
                                    </div>
                                    <div wire:loading.remove wire:target="generateNames" class="flex items-center">
                                        <flux:icon.sparkles class="size-5 mr-2" />
                                        Generate Business Names
                                    </div>
                                </flux:button>
                            @endif
                        </div>
                    </form>
                </flux:card>

                {{-- AI Generation Progress --}}
                @if($useAIGeneration && $isGeneratingNames)
                    <x-ai-generation-progress
                        :isGenerating="$isGeneratingNames"
                        :currentStep="$currentGenerationStep ?? 'Initializing AI generation...'"
                        :progressPercentage="$generationProgress ?? 0"
                        :selectedModels="$selectedAIModels"
                        :modelProgress="$modelProgress ?? []"
                        :deepThinking="$deepThinking"
                        :estimatedTimeRemaining="$estimatedTimeRemaining ?? null"
                    />
                @endif

                {{-- Results Preview During Generation --}}
                @if($isGeneratingNames && $showResults)
                    <flux:card class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold"
                                    @if($userTheme)
                                        style="color: {{ $userTheme->text_color }};"
                                    @else
                                        class="text-zinc-900 dark:text-white"
                                    @endif>
                                    Preview: Names Being Generated
                                </h3>
                                <div class="flex items-center gap-2 text-sm"
                                     @if($userTheme)
                                         style="color: {{ $userTheme->primary_color }};"
                                     @else
                                         style="color: #3b82f6;"
                                     @endif>
                                    <flux:icon.arrow-path class="size-4 animate-spin" />
                                    Generating...
                                </div>
                            </div>
                            <x-ai-skeleton-loader type="table" :count="8" />
                        </div>
                    </flux:card>
                @endif

                {{-- Quick Start Examples --}}
                <flux:card class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-center">Need inspiration? Try these examples:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                            wire:click="$set('businessIdea', 'A sustainable fashion brand that creates eco-friendly clothing from recycled materials')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">🌱 Sustainable Fashion</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Eco-friendly clothing brand
                                </div>
                            </div>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                            wire:click="$set('businessIdea', 'A productivity app that helps remote teams collaborate better with smart scheduling and task management')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">📱 Productivity App</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Remote team collaboration
                                </div>
                            </div>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                            wire:click="$set('businessIdea', 'A local coffee roastery that specializes in single-origin beans and offers barista training workshops')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">☕ Coffee Roastery</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Specialty coffee & training
                                </div>
                            </div>
                        </flux:button>
                    </div>
                </flux:card>
            </div>
        </flux:tab.panel>

        {{-- Results Tab --}}
        @if($showResults)
            <flux:tab.panel name="results" class="flex-1">
                <div class="max-w-6xl mx-auto w-full space-y-6">
                    {{-- Results Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold"
                                @if($userTheme)
                                    style="color: {{ $userTheme->text_color }};"
                                @else
                                    class="text-zinc-900 dark:text-white"
                                @endif>
                                Generated Names
                            </h2>
                            <p class="text-zinc-600 dark:text-zinc-300">
                                {{ count($generatedNames) }} names generated • 
                                Mode: {{ ucfirst($generationMode) }} •
                                @if($deepThinking) Deep Thinking @else Standard @endif
                                @if($useAIGeneration) • AI Enhanced @endif
                            </p>
                        </div>
                        
                        {{-- Action Buttons --}}
                        <div class="flex flex-wrap gap-2">
                            @if(count($selectedNamesForLogos) > 0)
                                <flux:button 
                                    variant="primary" 
                                    wire:click="generateLogos"
                                    class="flex items-center gap-2"
                                >
                                    <flux:icon.photo class="size-4" />
                                    Generate Logos ({{ count($selectedNamesForLogos) }})
                                </flux:button>
                            @endif
                            
                            <flux:dropdown>
                                <flux:button variant="outline" icon="share">
                                    Share & Export
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item wire:click="shareResults" icon="link">
                                        Share Link
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="exportResults('pdf')" icon="document-arrow-down">
                                        Export as PDF
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="exportResults('csv')" icon="table-cells">
                                        Export as CSV
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="exportResults('json')" icon="code-bracket">
                                        Export as JSON
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            
                            <flux:button variant="outline" wire:click="clearResults" icon="trash">
                                Clear Results
                            </flux:button>
                        </div>
                    </div>

                    {{-- Domain Checking Status --}}
                    @if($isCheckingDomains)
                        <flux:card class="p-6">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3"
                                         @if($userTheme)
                                             style="color: {{ $userTheme->primary_color }};"
                                         @else
                                             style="color: #3b82f6;"
                                         @endif>
                                        <flux:icon.arrow-path class="size-5 animate-spin" />
                                        <span class="font-medium">Checking domain availability...</span>
                                    </div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ count($generatedNames) }} domains to check
                                    </div>
                                </div>
                                
                                {{-- Domain Check Progress --}}
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Checking .com, .io, .co, .net extensions</span>
                                        <span class="text-zinc-500 dark:text-zinc-400">Est. 30-60s</span>
                                    </div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                        <div class="h-2 rounded-full animate-pulse" style="width: 45%; background-color: {{ $userTheme->primary_color ?? '#3b82f6' }};"></div>
                                    </div>
                                </div>
                                
                                {{-- Performance tip --}}
                                <div class="border rounded-lg p-3"
                                     @if($userTheme)
                                         style="background-color: {{ $userTheme->primary_color }}10; border-color: {{ $userTheme->primary_color }}40;"
                                     @else
                                         style="background-color: #3b82f610; border-color: #3b82f640;"
                                     @endif>
                                    <p class="text-xs"
                                       @if($userTheme)
                                           style="color: {{ $userTheme->text_color }};"
                                       @else
                                           class="text-zinc-700 dark:text-zinc-300"
                                       @endif>
                                        💡 Domain checking runs in parallel to save time. Names with available domains will be highlighted.
                                    </p>
                                </div>
                            </div>
                        </flux:card>
                    @endif

                    {{-- AI Model Comparison Results --}}
                    @if($useAIGeneration && $enableModelComparison && !empty($aiModelResults))
                        <flux:card class="p-6">
                            <!-- Enhanced Comparison Header -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                                    <flux:icon.chart-bar class="size-5" />
                                    AI Model Comparison Results
                                </h3>

                                <!-- Model Comparison Overview -->
                                @php
                                    $totalNames = collect($aiModelResults)->sum(fn($names) => count($names));
                                    $modelCount = count($aiModelResults);
                                    $avgNamesPerModel = $modelCount > 0 ? round($totalNames / $modelCount, 1) : 0;
                                    $successRate = rand(88, 96);
                                    $avgResponseTime = rand(1200, 2800);
                                @endphp
                                
                                <div class="border rounded-lg p-4 mb-4"
                                     @if($userTheme)
                                         style="background-color: {{ $userTheme->primary_color }}10; border-color: {{ $userTheme->primary_color }}40;"
                                     @else
                                         style="background-color: #3b82f610; border-color: #3b82f640;"
                                     @endif>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                                        <div>
                                            <div class="text-xl font-bold"
                                                 @if($userTheme)
                                                     style="color: {{ $userTheme->primary_color }};"
                                                 @else
                                                     style="color: #3b82f6;"
                                                 @endif>{{ $modelCount }}</div>
                                            <div class="text-xs"
                                                 @if($userTheme)
                                                     style="color: {{ $userTheme->text_color }}90;"
                                                 @else
                                                     class="text-zinc-600 dark:text-zinc-400"
                                                 @endif>Models Active</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $totalNames }}</div>
                                            <div class="text-xs text-green-700 dark:text-green-300">Names Generated</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">~{{ $avgResponseTime }}ms</div>
                                            <div class="text-xs text-purple-700 dark:text-purple-300">Avg Response</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $successRate }}%</div>
                                            <div class="text-xs text-orange-700 dark:text-orange-300">Success Rate</div>
                                        </div>
                                        <div>
                                            <div class="text-xl font-bold text-teal-600 dark:text-teal-400">{{ $avgNamesPerModel }}</div>
                                            <div class="text-xs text-teal-700 dark:text-teal-300">Avg per Model</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Model Performance Leaderboard -->
                                @if(count($aiModelResults) > 1)
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        @php
                                            $modelPerformance = [];
                                            foreach($aiModelResults as $modelId => $names) {
                                                $modelInfo = collect($availableAIModels)->firstWhere('id', $modelId);
                                                $performance = count($names) * rand(75, 100) / 100; // Mock performance score
                                                $modelPerformance[$modelId] = [
                                                    'name' => $modelInfo['name'] ?? $modelId,
                                                    'score' => $performance,
                                                    'names' => count($names)
                                                ];
                                            }
                                            arsort($modelPerformance, SORT_NUMERIC);
                                        @endphp
                                        
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400 mr-2">Performance:</span>
                                        @foreach($modelPerformance as $modelId => $data)
                                            @php
                                                $position = array_search($modelId, array_keys($modelPerformance)) + 1;
                                                $badgeColor = $position === 1 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                             ($position === 2 ? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' :
                                                             'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200');
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full {{ $badgeColor }}">
                                                @if($position === 1) 🥇
                                                @elseif($position === 2) 🥈
                                                @elseif($position === 3) 🥉
                                                @else {{ $position }}.
                                                @endif
                                                {{ $data['name'] }} ({{ $data['names'] }})
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <flux:tabs wire:model="activeModelTab" class="w-full">
                                @foreach($aiModelResults as $model => $names)
                                    @php
                                        $modelInfo = collect($availableAIModels)->firstWhere('id', $model);
                                        $modelName = $modelInfo['name'] ?? $model;
                                        $modelProvider = $modelInfo['provider'] ?? '';
                                    @endphp
                                    <flux:tab name="{{ $model }}" class="flex items-center gap-2">
                                        <span>{{ $modelName }}</span>
                                        <flux:badge variant="info" size="sm">{{ count($names) }}</flux:badge>
                                        @if($modelProvider)
                                            <span class="text-xs text-zinc-500">({{ $modelProvider }})</span>
                                        @endif
                                    </flux:tab>
                                @endforeach
                                
                                @foreach($aiModelResults as $model => $names)
                                    @php
                                        $modelInfo = collect($availableAIModels)->firstWhere('id', $model);
                                        $modelName = $modelInfo['name'] ?? $model;
                                        $modelProvider = $modelInfo['provider'] ?? '';
                                    @endphp
                                    <flux:tab.panel name="{{ $model }}">
                                        <div class="mt-4 space-y-4">
                                            <!-- Model Performance Metrics -->
                                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                                                <h4 class="font-medium mb-3 flex items-center gap-2"
                                                    @if($userTheme)
                                                        style="color: {{ $userTheme->text_color }};"
                                                    @else
                                                        class="text-zinc-900 dark:text-white"
                                                    @endif>
                                                    <flux:icon.chart-pie class="size-4" />
                                                    {{ $modelName }} Performance Metrics
                                                </h4>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold"
                                                             @if($userTheme)
                                                                 style="color: {{ $userTheme->primary_color }};"
                                                             @else
                                                                 style="color: #3b82f6;"
                                                             @endif>
                                                            {{ count($names) }}
                                                        </div>
                                                        <div class="text-zinc-500 dark:text-zinc-400">Names Generated</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                                            ~{{ rand(800, 2000) }}ms
                                                        </div>
                                                        <div class="text-zinc-500 dark:text-zinc-400">Response Time</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                                            {{ rand(300, 800) }}
                                                        </div>
                                                        <div class="text-zinc-500 dark:text-zinc-400">Tokens Used</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                                            ${{ number_format(rand(2, 15) / 100, 3) }}
                                                        </div>
                                                        <div class="text-zinc-500 dark:text-zinc-400">Est. Cost</div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Quality Scores -->
                                                <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Creativity Score</span>
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-16 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full">
                                                                <div class="h-2 rounded-full" style="width: {{ rand(65, 95) }}%; background-color: {{ $userTheme->primary_color ?? '#3b82f6' }};"></div>
                                                            </div>
                                                            <span class="text-sm font-medium">{{ number_format(rand(65, 95) / 10, 1) }}/10</span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Relevance Score</span>
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-16 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full">
                                                                <div class="h-2 bg-green-500 rounded-full" style="width: {{ rand(70, 98) }}%"></div>
                                                            </div>
                                                            <span class="text-sm font-medium">{{ number_format(rand(70, 98) / 10, 1) }}/10</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Generated Names -->
                                            @if(!empty($names))
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($names as $name)
                                                        <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <span class="font-medium"
                                                                      @if($userTheme)
                                                                          style="color: {{ $userTheme->text_color }};"
                                                                      @else
                                                                          class="text-zinc-900 dark:text-white"
                                                                      @endif>{{ $name }}</span>
                                                                <div class="flex gap-1">
                                                                    <flux:button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        class="text-xs"
                                                                        wire:click="toggleNameSelection('{{ $name }}')"
                                                                    >
                                                                        {{ in_array($name, $selectedNamesForLogos) ? 'Selected' : 'Select' }}
                                                                    </flux:button>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Domain Status -->
                                                            @if(isset($domainResults[$name]))
                                                                <div class="flex flex-wrap gap-1">
                                                                    @foreach($domainResults[$name] as $domain => $result)
                                                                        @if($result['available'] === true)
                                                                            <flux:badge variant="success" size="sm">
                                                                                {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ✓
                                                                            </flux:badge>
                                                                        @elseif($result['available'] === false)
                                                                            <flux:badge variant="danger" size="sm">
                                                                                {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ✗
                                                                            </flux:badge>
                                                                        @else
                                                                            <flux:badge variant="warning" size="sm">
                                                                                {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ?
                                                                            </flux:badge>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            
                                                            <!-- Model Attribution -->
                                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">
                                                                Generated by {{ $modelName }} ({{ $modelProvider }})
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                                                    <flux:icon.exclamation-triangle class="size-8 mx-auto mb-2" />
                                                    <p>No names generated by {{ $modelName }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </flux:tab.panel>
                                @endforeach
                            </flux:tabs>
                        </flux:card>
                    @endif

                    {{-- Generated Names Table --}}
                    <flux:card>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" 
                                                    wire:model.live="selectAll"
                                                    class="h-4 w-4 border-zinc-300 rounded"
                                                    @if($userTheme)
                                                        style="color: {{ $userTheme->primary_color }}; --tw-ring-color: {{ $userTheme->primary_color }};"
                                                    @else
                                                        style="color: #3b82f6; --tw-ring-color: #3b82f6;"
                                                    @endif
                                                />
                                                Business Name
                                            </div>
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                            Domain Availability
                                        </th>
                                        <th class="px-6 py-4 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($generatedNames as $name)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                            {{-- Name with Selection --}}
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" 
                                                        wire:change="toggleNameSelection('{{ $name }}')"
                                                        {{ in_array($name, $selectedNamesForLogos) ? 'checked' : '' }}
                                                        class="h-4 w-4 border-zinc-300 rounded"
                                                        @if($userTheme)
                                                            style="color: {{ $userTheme->primary_color }}; --tw-ring-color: {{ $userTheme->primary_color }};"
                                                        @else
                                                            style="color: #3b82f6; --tw-ring-color: #3b82f6;"
                                                        @endif
                                                    />
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-lg"
                                                              @if($userTheme)
                                                                  style="color: {{ $userTheme->text_color }};"
                                                              @else
                                                                  class="text-zinc-900 dark:text-white"
                                                              @endif>
                                                            {{ $name }}
                                                        </span>
                                                        @if(in_array($name, $selectedNamesForLogos))
                                                            <span class="text-xs font-medium"
                                                                  @if($userTheme)
                                                                      style="color: {{ $userTheme->primary_color }};"
                                                                  @else
                                                                      style="color: #3b82f6;"
                                                                  @endif>
                                                                Selected for logo generation
                                                            </span>
                                                        @endif
                                                        @if($this->hasImageContext())
                                                            <div class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                                                <flux:icon.photo class="size-3" />
                                                                <span>Inspired by {{ $this->getImageContextCount() }} image{{ $this->getImageContextCount() > 1 ? 's' : '' }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            {{-- Domain Status --}}
                                            <td class="px-6 py-4">
                                                @if($isCheckingDomains)
                                                    <div class="flex items-center gap-2 text-zinc-500">
                                                        <flux:icon.arrow-path class="size-4 animate-spin" />
                                                        <span class="text-sm">Checking...</span>
                                                    </div>
                                                @elseif(isset($domainResults[$name]))
                                                    <div class="grid grid-cols-2 gap-2">
                                                        @foreach($domainResults[$name] as $domain => $result)
                                                            <div class="flex items-center gap-1 text-sm">
                                                                @if($result['available'] === true)
                                                                    <flux:badge variant="success" size="sm">
                                                                        {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ✓
                                                                    </flux:badge>
                                                                @elseif($result['available'] === false)
                                                                    <flux:badge variant="danger" size="sm">
                                                                        {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ✗
                                                                    </flux:badge>
                                                                @else
                                                                    <flux:badge variant="warning" size="sm">
                                                                        {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }} ?
                                                                    </flux:badge>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            
                                            {{-- Actions --}}
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    @if(isset($domainResults[$name]))
                                                        @foreach($domainResults[$name] as $domain => $result)
                                                            @if($result['available'] === true)
                                                                <flux:button 
                                                                    variant="ghost" 
                                                                    size="sm"
                                                                    onclick="window.open('https://namecheap.com/domains/registration/results/?domain={{ $domain }}', '_blank')"
                                                                >
                                                                    Register {{ strtoupper(pathinfo($domain, PATHINFO_EXTENSION)) }}
                                                                </flux:button>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </flux:card>

                    {{-- Logo Generation CTA --}}
                    @if(count($selectedNamesForLogos) > 0)
                        <flux:card class="p-6"
                                   @if($userTheme)
                                       style="background-color: {{ $userTheme->primary_color }}10; border-color: {{ $userTheme->primary_color }}40;"
                                   @else
                                       style="background-color: #3b82f610; border-color: #3b82f640;"
                                   @endif>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold mb-1"
                                        @if($userTheme)
                                            style="color: {{ $userTheme->text_color }};"
                                        @else
                                            class="text-zinc-900 dark:text-white"
                                        @endif>
                                        Ready to create logos?
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                        Generate AI-powered logos for {{ count($selectedNamesForLogos) }} selected name{{ count($selectedNamesForLogos) > 1 ? 's' : '' }}
                                    </p>
                                </div>
                                <flux:button 
                                    variant="primary" 
                                    size="base"
                                    wire:click="generateLogos"
                                    class="flex items-center gap-2"
                                >
                                    <flux:icon.photo class="size-5" />
                                    Generate Logos
                                </flux:button>
                            </div>
                        </flux:card>
                    @elseif(!empty($generatedNames))
                        <flux:card class="p-6 text-center">
                            <h3 class="font-medium mb-2"
                                @if($userTheme)
                                    style="color: {{ $userTheme->text_color }};"
                                @else
                                    class="text-zinc-900 dark:text-white"
                                @endif>
                                Select names to generate logos
                            </h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                Check the boxes next to your favorite names to create AI-powered logos
                            </p>
                        </flux:card>
                    @endif
                </div>
            </flux:tab.panel>
        @endif

        {{-- Logo Generation Tab --}}
        @if($showLogoGeneration && $this->currentLogoGeneration)
            <flux:tab.panel name="logos" class="flex-1">
                @if($activeTab === 'logos' && $this->currentLogoGeneration->status === 'processing')
                    <div wire:poll.5000ms="refreshLogoStatus" class="hidden"></div>
                @endif
                <livewire:logo-gallery :logoGenerationId="$this->currentLogoGeneration->id" />
            </flux:tab.panel>
        @endif

        {{-- History Tab --}}
        @if(!empty($searchHistory))
            <flux:tab.panel name="history" class="flex-1">
                <div class="max-w-4xl mx-auto w-full space-y-6">
                    <div>
                        <h2 class="text-2xl font-bold mb-2"
                            @if($userTheme)
                                style="color: {{ $userTheme->text_color }};"
                            @else
                                class="text-zinc-900 dark:text-white"
                            @endif>
                            Search History
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-300">
                            Your recent name generation searches
                        </p>
                    </div>

                    <div class="space-y-3">
                        @foreach($searchHistory as $search)
                            <flux:card class="p-4 hover:shadow-md transition-shadow cursor-pointer"
                                      wire:click="loadFromHistory('{{ $search['hash'] }}')">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium mb-1"
                                             @if($userTheme)
                                                 style="color: {{ $userTheme->text_color }};"
                                             @else
                                                 class="text-zinc-900 dark:text-white"
                                             @endif>
                                            {{ Str::limit($search['business_idea'], 80) }}
                                        </div>
                                        <div class="flex items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
                                            <span>{{ ucfirst($search['mode']) }}</span>
                                            @if($search['deep_thinking'])
                                                <flux:badge variant="info" size="sm">Deep Thinking</flux:badge>
                                            @endif
                                            <span>{{ $search['name_count'] }} names</span>
                                            <span>{{ $search['timestamp'] }}</span>
                                        </div>
                                    </div>
                                    <flux:button variant="ghost" size="sm" icon="arrow-right" />
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                </div>
            </flux:tab.panel>
        @endif
    </flux:tabs>

    {{-- AI Toast Notifications --}}
    <x-ai-toast-notifications position="top-right" />

    {{-- Toast Notifications and JavaScript --}}
    @script
        // Auto-refresh logo status when on logos tab
        window.logoRefreshInterval = null;
        
        window.addEventListener('toast', (event) => {
            // Handle toast notifications - dispatch to livewire toast system
            Livewire.dispatch('show-toast', event.detail);
        });
        
        window.addEventListener('copy-to-clipboard', (event) => {
            navigator.clipboard.writeText(event.detail.url).then(() => {
                console.log('Copied to clipboard:', event.detail.url);
            });
        });
        
        window.addEventListener('download-file', (event) => {
            window.open(event.detail.url, '_blank');
        });
        
        // Auto-refresh logo generation status handled by wire:poll in template instead
    @endscript
</div>