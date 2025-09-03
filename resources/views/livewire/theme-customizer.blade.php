<div class="space-y-8">
    <!-- Theme Customizer Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Theme Customizer
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Customize your application theme and colors
            </p>
        </div>
        
        <div class="flex space-x-3">
            <flux:button wire:click="resetToDefault" variant="ghost" size="sm">
                Reset to Default
            </flux:button>
            
            <flux:button wire:click="save" variant="primary" size="sm">
                Save Theme
            </flux:button>
        </div>
    </div>

    <!-- Predefined Themes -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Predefined Themes
        </h3>
        
        <div class="grid grid-cols-1 gap-4
                    sm:grid-cols-2 
                    lg:grid-cols-3 
                    xl:grid-cols-5">
            @foreach($this->predefinedThemes as $theme)
                <div wire:click="applyPreset('{{ $theme['name'] }}')"
                     class="cursor-pointer rounded-lg border-2 p-4 transition-all duration-200
                            hover:border-gray-400 dark:hover:border-gray-600
                            {{ $themeName === $theme['name'] ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800' : 'border-gray-200 dark:border-gray-700' }}">
                    <div class="space-y-3">
                        <!-- Theme Preview -->
                        <div class="flex h-12 overflow-hidden rounded">
                            <div class="w-1/2" style="background-color: {{ $theme['primary_color'] }}"></div>
                            <div class="w-1/4" style="background-color: {{ $theme['accent_color'] }}"></div>
                            <div class="w-1/4" style="background-color: {{ $theme['background_color'] }}"></div>
                        </div>
                        
                        <!-- Theme Info -->
                        <div class="text-center">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $theme['display_name'] }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $theme['is_dark_mode'] ? 'Dark Mode' : 'Light Mode' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Custom Color Controls -->
    <div class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Custom Colors
        </h3>
        
        <div class="grid grid-cols-1 gap-6
                    sm:grid-cols-2
                    lg:grid-cols-4">
            <!-- Primary Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Primary Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="primaryColor" 
                                  type="color" 
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="primaryColor" 
                                  placeholder="#3b82f6" 
                                  class="flex-1" />
                    </div>
                    <flux:error name="primaryColor" />
                </flux:field>
            </div>

            <!-- Accent Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Accent Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="accentColor" 
                                  type="color" 
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="accentColor" 
                                  placeholder="#10b981" 
                                  class="flex-1" />
                    </div>
                    <flux:error name="accentColor" />
                </flux:field>
            </div>

            <!-- Background Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Background Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="backgroundColor" 
                                  type="color" 
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="backgroundColor" 
                                  placeholder="#ffffff" 
                                  class="flex-1" />
                    </div>
                    <flux:error name="backgroundColor" />
                </flux:field>
            </div>

            <!-- Text Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Text Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="textColor" 
                                  type="color" 
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="textColor" 
                                  placeholder="#111827" 
                                  class="flex-1" />
                    </div>
                    <flux:error name="textColor" />
                </flux:field>
            </div>
        </div>

        <!-- Theme Name and Options -->
        <div class="grid grid-cols-1 gap-6
                    sm:grid-cols-2">
            <flux:field>
                <flux:label>Theme Name</flux:label>
                <flux:input wire:model.live="themeName" 
                          placeholder="My Custom Theme" />
                <flux:error name="themeName" />
            </flux:field>

            <flux:field>
                <flux:label>Theme Mode</flux:label>
                <flux:switch wire:model.live="isDarkMode">
                    Dark Mode
                </flux:switch>
            </flux:field>
        </div>
    </div>

    <!-- Accessibility Feedback -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Accessibility Analysis
        </h3>
        
        <div class="rounded-lg border p-4
                    {{ $accessibilityScore >= 0.7 ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : ($accessibilityScore >= 0.5 ? 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20') }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium
                           {{ $accessibilityScore >= 0.7 ? 'text-green-800 dark:text-green-200' : ($accessibilityScore >= 0.5 ? 'text-yellow-800 dark:text-yellow-200' : 'text-red-800 dark:text-red-200') }}">
                    Accessibility Score: {{ number_format($accessibilityScore * 100, 1) }}%
                </span>
                
                <flux:badge variant="{{ $accessibilityScore >= 0.7 ? 'success' : ($accessibilityScore >= 0.5 ? 'warning' : 'danger') }}" size="sm">
                    {{ $accessibilityScore >= 0.7 ? 'Excellent' : ($accessibilityScore >= 0.5 ? 'Good' : 'Needs Improvement') }}
                </flux:badge>
            </div>

            @if(count($accessibilityFeedback['warnings']) > 0)
                <div class="space-y-2">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Warnings:
                    </h4>
                    <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                        @foreach($accessibilityFeedback['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($accessibilityFeedback['suggestions']) > 0)
                <div class="space-y-2 mt-3">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Suggestions:
                    </h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                        @foreach($accessibilityFeedback['suggestions'] as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Preview -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Live Preview
        </h3>
        
        <div class="rounded-lg border p-6 space-y-4"
             style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};">
            <flux:button style="background-color: {{ $primaryColor }}; color: {{ $backgroundColor }};">
                Primary Button
            </flux:button>
            
            <flux:button style="background-color: {{ $accentColor }}; color: {{ $backgroundColor }};">
                Accent Button
            </flux:button>
            
            <p class="text-sm">
                This is sample text showing how your chosen colors work together.
                {{ $themeName }} theme provides 
                @if($isDarkMode)
                    dark mode styling
                @else
                    light mode styling
                @endif
                for optimal user experience.
            </p>
        </div>
    </div>

    <!-- CSS Output -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Generated CSS
        </h3>
        
        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4">
            <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto">{{ $this->generatedCss }}</pre>
        </div>
    </div>

    <!-- Import/Export -->
    <div class="flex flex-col space-y-4
                sm:flex-row sm:space-y-0 sm:space-x-6 sm:items-end">
        <div class="flex-1">
            <flux:field>
                <flux:label>Import Theme</flux:label>
                <flux:input wire:model="themeFile" 
                          type="file" 
                          accept=".json" />
                <flux:error name="themeFile" />
            </flux:field>
            
            @if($themeFile)
                <flux:button wire:click="importTheme" 
                           variant="primary" 
                           size="sm" 
                           class="mt-2">
                    Import Theme
                </flux:button>
            @endif
        </div>

        <flux:button wire:click="exportTheme" 
                   variant="ghost" 
                   size="sm">
            Export Current Theme
        </flux:button>
    </div>

    <!-- Loading States -->
    <div wire:loading.flex 
         wire:target="save,applyPreset,importTheme,resetToDefault"
         class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-300">Processing theme changes...</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('error'))
        <flux:callout variant="danger">
            {{ session('error') }}
        </flux:callout>
    @endif

    <!-- JavaScript for download functionality -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('download-theme', (themeJson) => {
                const blob = new Blob([themeJson], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `theme-${@this.themeName}.json`;
                a.click();
                window.URL.revokeObjectURL(url);
            });

            Livewire.on('theme-updated', () => {
                // Apply CSS custom properties in real-time
                const css = @this.generatedCss;
                
                // Remove existing theme styles
                const existingStyle = document.getElementById('live-theme-styles');
                if (existingStyle) {
                    existingStyle.remove();
                }
                
                // Add new theme styles
                const style = document.createElement('style');
                style.id = 'live-theme-styles';
                style.textContent = css;
                document.head.appendChild(style);
            });

            Livewire.on('theme-saved', () => {
                // Show success notification
                console.log('Theme saved successfully');
            });

            Livewire.on('theme-imported', () => {
                // Show success notification and apply styles
                console.log('Theme imported successfully');
                Livewire.dispatch('theme-updated');
            });
        });
    </script>
</div>
