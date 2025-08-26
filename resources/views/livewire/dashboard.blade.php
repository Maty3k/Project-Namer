<div class="h-full w-full">
    {{-- Tab Navigation --}}
    <flux:tabs wire:model="activeTab" class="h-full flex flex-col">
        <flux:tab name="generate" class="flex items-center gap-2">
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
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                        AI-Powered Business Name Generator
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Describe your business idea and get creative, brandable names with domain availability checking and logo generation.
                    </p>
                </div>

                {{-- Business Idea Input Form --}}
                <flux:card class="p-8">
                    <form wire:submit="generateNames" class="space-y-6">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Generation Mode --}}
                            <div class="space-y-3">
                                <flux:label class="text-base font-medium">Generation Style</flux:label>
                                <flux:select wire:model.live="generationMode">
                                    <option value="creative">🎨 Creative - Unique & memorable</option>
                                    <option value="professional">💼 Professional - Corporate & trustworthy</option>
                                    <option value="brandable">🚀 Brandable - Catchy & marketable</option>
                                    <option value="tech-focused">⚡ Tech-Focused - Developer-friendly</option>
                                </flux:select>
                            </div>

                            {{-- Deep Thinking Mode --}}
                            <div class="space-y-3">
                                <flux:label class="text-base font-medium">Processing Mode</flux:label>
                                <div class="flex items-center space-x-3">
                                    <flux:switch wire:model.live="deepThinking" />
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        Deep Thinking Mode (Higher quality, slower generation)
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Generate Button --}}
                        <div class="flex justify-center pt-4">
                            <flux:button 
                                type="submit" 
                                variant="primary" 
                                size="base"
                                class="px-8 py-3 text-lg"
                                :disabled="$isGeneratingNames || empty(trim($businessIdea))"
                            >
                                @if($isGeneratingNames)
                                    <flux:icon.arrow-path class="size-5 animate-spin mr-2" />
                                    Generating Names...
                                @else
                                    <flux:icon.sparkles class="size-5 mr-2" />
                                    Generate Business Names
                                @endif
                            </flux:button>
                        </div>
                    </form>
                </flux:card>

                {{-- Quick Start Examples --}}
                <flux:card class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-center">Need inspiration? Try these examples:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:click="$set('businessIdea', 'A sustainable fashion brand that creates eco-friendly clothing from recycled materials')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">🌱 Sustainable Fashion</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Eco-friendly clothing brand
                                </div>
                            </div>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:click="$set('businessIdea', 'A productivity app that helps remote teams collaborate better with smart scheduling and task management')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">📱 Productivity App</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Remote team collaboration
                                </div>
                            </div>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            class="text-left p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:click="$set('businessIdea', 'A local coffee roastery that specializes in single-origin beans and offers barista training workshops')"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">☕ Coffee Roastery</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
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
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Generated Names
                            </h2>
                            <p class="text-gray-600 dark:text-gray-300">
                                {{ count($generatedNames) }} names generated • 
                                Mode: {{ ucfirst($generationMode) }} •
                                @if($deepThinking) Deep Thinking @else Standard @endif
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
                        <flux:card class="p-4">
                            <div class="flex items-center gap-3 text-blue-600 dark:text-blue-400">
                                <flux:icon.arrow-path class="size-5 animate-spin" />
                                <span>Checking domain availability...</span>
                            </div>
                        </flux:card>
                    @endif

                    {{-- Generated Names Table --}}
                    <flux:card>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <div class="flex items-center gap-2">
                                                <flux:checkbox 
                                                    wire:click="$toggle('selectAll')"
                                                    :checked="count($selectedNamesForLogos) === count($generatedNames)"
                                                />
                                                Business Name
                                            </div>
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Domain Availability
                                        </th>
                                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($generatedNames as $name)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            {{-- Name with Selection --}}
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <flux:checkbox 
                                                        wire:click="toggleNameSelection('{{ $name }}')"
                                                        :checked="in_array('{{ $name }}', $selectedNamesForLogos)"
                                                    />
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900 dark:text-white text-lg">
                                                            {{ $name }}
                                                        </span>
                                                        @if(in_array($name, $selectedNamesForLogos))
                                                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                                                Selected for logo generation
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            {{-- Domain Status --}}
                                            <td class="px-6 py-4">
                                                @if($isCheckingDomains)
                                                    <div class="flex items-center gap-2 text-gray-500">
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
                        <flux:card class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200 dark:border-blue-800">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                        Ready to create logos?
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
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
                            <h3 class="font-medium text-gray-900 dark:text-white mb-2">
                                Select names to generate logos
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
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
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Search History
                        </h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Your recent name generation searches
                        </p>
                    </div>

                    <div class="space-y-3">
                        @foreach($searchHistory as $search)
                            <flux:card class="p-4 hover:shadow-md transition-shadow cursor-pointer"
                                      wire:click="loadFromHistory('{{ $search['hash'] }}')">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white mb-1">
                                            {{ Str::limit($search['business_idea'], 80) }}
                                        </div>
                                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
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

    {{-- Toast Notifications and JavaScript --}}
    @script
    <script>
        // Auto-refresh logo status when on logos tab
        let logoRefreshInterval;
        
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
    </script>
    @endscript
</div>