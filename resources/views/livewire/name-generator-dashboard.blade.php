<div class="h-full w-full themed-generator-dashboard">
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

        {{-- Generation Tab --}}
        <flux:tab.panel name="generate" class="flex-1 flex flex-col gap-6">
            <div class="w-full max-w-full sm:max-w-4xl mx-auto space-y-8">
                {{-- Header --}}
                <div class="text-center space-y-4">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                        AI-Powered Business Name Generator
                    </h1>
                    <p class="text-sm sm:text-base md:text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Ready to generate unique business names with AI assistance.
                    </p>
                </div>

                {{-- Business Idea Input Form --}}
                <flux:card class="p-4 sm:p-6 md:p-8">
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
                            <div class="flex justify-between items-center text-sm text-gray-500">
                                <flux:error name="businessIdea" />
                                <span>{{ strlen($businessIdea) }}/2000 characters</span>
                            </div>
                        </div>

                        {{-- Generation Options --}}
                        <div class="space-y-6">
                            {{-- Generation Mode --}}
                            <div class="space-y-4">
                                <flux:label class="text-base font-medium">Generation Style</flux:label>
                                <flux:select wire:model.live="generationMode">
                                    <option value="creative">🎨 Creative - Unique & memorable</option>
                                    <option value="professional">💼 Professional - Corporate & trustworthy</option>
                                    <option value="brandable">🚀 Brandable - Catchy & marketable</option>
                                    <option value="tech-focused">⚡ Tech-Focused - Developer-friendly</option>
                                </flux:select>
                            </div>
                        </div>

                        {{-- AI Generation Section --}}
                        <div class="border-t pt-6 space-y-6">
                            {{-- Enable AI Generation Toggle --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" wire:model.live="useAIGeneration" id="use-ai-generation" class="h-4 w-4 border-gray-300 rounded" />
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
                                        <input type="checkbox" wire:model.live="enableModelComparison" id="model-comparison" class="h-4 w-4 border-gray-300 rounded" />
                                        <flux:label for="model-comparison" class="text-sm text-gray-600 dark:text-gray-300">
                                            Model Comparison (Generate with multiple models)
                                        </flux:label>
                                    </div>

                                    {{-- Model Selection Grid --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                        @foreach($availableAIModels as $model)
                                            <label class="relative flex items-center p-3 sm:p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 min-h-[60px] {{ in_array($model['id'], $selectedAIModels) ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-md' : 'border-gray-200 dark:border-gray-700' }}">
                                                <input type="checkbox"
                                                    wire:model.live="selectedAIModels"
                                                    value="{{ $model['id'] }}"
                                                    class="sr-only"
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-sm truncate text-gray-900 dark:text-white">{{ $model['name'] }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $model['provider'] }}</div>
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
                                        <div class="text-sm text-blue-600">
                                            <flux:icon.information-circle class="size-4 inline" />
                                            Compare {{ count($selectedAIModels) }} Models - Results will be shown in separate tabs
                                        </div>
                                    @endif

                                    <flux:error name="selectedAIModels" />
                                </div>
                            @endif
                        </div>

                        {{-- Generate Button --}}
                        <div class="flex justify-center pt-4">
                            @if($useAIGeneration)
                                <flux:tooltip content="Generate names (⌘G)">
                                    <flux:button
                                        type="submit"
                                        variant="primary"
                                        size="base"
                                        class="px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base min-h-10 sm:min-h-12"
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
                                </flux:tooltip>
                            @else
                                <flux:tooltip content="Generate names (⌘G)">
                                    <flux:button
                                        type="submit"
                                        variant="primary"
                                        size="base"
                                        class="px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base min-h-10 sm:min-h-12"
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
                                </flux:tooltip>
                            @endif
                        </div>
                    </form>
                </flux:card>
            </div>
        </flux:tab.panel>

        {{-- Results Tab --}}
        @if($showResults)
            <flux:tab.panel name="results" class="flex-1">
                <div class="max-w-6xl mx-auto w-full space-y-6">
                    {{-- Header with Generate More Button --}}
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Generated Names ({{ count($generatedNames) }})
                        </h2>

                        <flux:button
                            wire:click="generateMoreNames"
                            variant="primary"
                            size="sm"
                            class="whitespace-nowrap"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                        >
                            <flux:icon.sparkles class="size-4 mr-2" />
                            Generate More Names
                        </flux:button>
                    </div>

                    @if(!empty($generatedNames))
                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                            Business Name
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                            Domain Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                    @foreach($generatedNames as $name)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-lg text-gray-900 dark:text-white">
                                                        {{ $name }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                        Check domain availability
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
                            <p class="text-gray-700 dark:text-gray-300">
                                No names generated yet. Click "Generate Names" to get started!
                            </p>
                        </div>
                    @endif
                </div>
            </flux:tab.panel>
        @endif
    </flux:tabs>
</div>