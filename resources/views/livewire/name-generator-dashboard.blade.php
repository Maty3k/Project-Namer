@php
$themeStyles = '';
if ($userTheme) {
    $cssVars = $userTheme->generateCssVariables($userTheme->is_dark_mode);
    $themeStyles = implode('; ', array_map(fn($key, $value) => "$key: $value", array_keys($cssVars), $cssVars));
}
@endphp

<div class="h-full w-full themed-generator-dashboard" 
     style="{{ $themeStyles }}"
     x-data="{ themeLoaded: false }"
     x-init="
        // Apply theme immediately if exists
        if ({{ $userTheme ? 'true' : 'false' }}) {
            themeLoaded = true;
        }
        
        // Listen for theme events and refresh component
        $wire.on('theme-loaded', () => {
            themeLoaded = true;
            $wire.$refresh();
        });
        
        $wire.on('theme-updated', () => {
            $wire.$refresh();
        });
        
        $wire.on('theme-applied', () => {
            $wire.$refresh();
        });
     ">
    <flux:tabs wire:model="activeTab" class="h-full flex flex-col">
        <flux:tab name="generate" 
                  class="flex items-center gap-2 transition-colors duration-300" 
                  style="color: {{ $userTheme?->text_color ?? '#1F2937' }}; border-color: {{ $userTheme?->primary_color ?? '#3B82F6' }};"
                  title="Generate business names">
            <flux:icon.sparkles class="size-4" 
                                style="color: {{ $userTheme?->primary_color ?? '#3B82F6' }};" />
            Generate Names
        </flux:tab>
        
        @if($showResults)
            <flux:tab name="results" 
                      class="flex items-center gap-2 transition-colors duration-300"
                      style="color: {{ $userTheme?->text_color ?? '#1F2937' }}; border-color: {{ $userTheme?->accent_color ?? '#10B981' }};">
                <flux:icon.list-bullet class="size-4" 
                                      style="color: {{ $userTheme?->accent_color ?? '#10B981' }};" />
                Results ({{ count($generatedNames) }})
            </flux:tab>
        @endif

        {{-- Generation Tab --}}
        <flux:tab.panel name="generate" 
                        class="flex-1 flex flex-col gap-6 transition-colors duration-300"
                        style="background-color: {{ $userTheme?->background_color ?? '#FFFFFF' }};">
            <div class="max-w-4xl mx-auto w-full space-y-8">
                <h1 class="text-4xl font-bold transition-colors duration-300" 
                    style="color: {{ $userTheme?->primary_color ?? '#1F2937' }};">
                    AI-Powered Business Name Generator
                </h1>
                <div class="rounded-lg p-6 transition-all duration-300" 
                     style="background-color: {{ $userTheme?->surface_color ?? '#F8FAFC' }}; border: 1px solid {{ $userTheme?->primary_color ?? '#3B82F6' }};">
                    <p class="text-lg transition-colors duration-300" 
                       style="color: {{ $userTheme?->text_color ?? '#1F2937' }};">
                        Ready to generate unique business names with AI assistance.
                    </p>
                </div>
            </div>
        </flux:tab.panel>

        {{-- Results Tab --}}
        @if($showResults)
            <flux:tab.panel name="results" 
                            class="flex-1 transition-colors duration-300"
                            style="background-color: {{ $userTheme?->background_color ?? '#FFFFFF' }};">
                <div class="max-w-6xl mx-auto w-full space-y-6">
                    <h2 class="text-2xl font-bold transition-colors duration-300" 
                        style="color: {{ $userTheme?->primary_color ?? '#1F2937' }};">
                        Generated Names
                    </h2>
                    <div class="rounded-lg p-6 transition-all duration-300" 
                         style="background-color: {{ $userTheme?->surface_color ?? '#F8FAFC' }}; border: 1px solid {{ $userTheme?->accent_color ?? '#10B981' }};">
                        <p class="transition-colors duration-300" 
                           style="color: {{ $userTheme?->text_color ?? '#1F2937' }};">
                            Your generated business names will appear here.
                        </p>
                    </div>
                </div>
            </flux:tab.panel>
        @endif
    </flux:tabs>
</div>