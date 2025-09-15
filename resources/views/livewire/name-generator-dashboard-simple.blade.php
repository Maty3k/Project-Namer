<div class="h-full w-full themed-generator-dashboard">
    <!-- Simple test version -->
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
            <div class="max-w-4xl mx-auto w-full space-y-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white">AI-Powered Business Name Generator</h1>
                <p>Test content</p>
            </div>
        </flux:tab.panel>

        {{-- Results Tab --}}
        @if($showResults)
            <flux:tab.panel name="results" class="flex-1">
                <div class="max-w-6xl mx-auto w-full space-y-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generated Names</h2>
                    <p>Results content</p>
                </div>
            </flux:tab.panel>
        @endif
    </flux:tabs>
</div>