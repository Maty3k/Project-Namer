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

    <!-- Seasonal Recommendation -->
    @if($recommendedSeasonalTheme)
        <flux:card class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-800">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-16 overflow-hidden rounded-lg shadow-md">
                                <div class="w-1/2" style="background-color: {{ $recommendedSeasonalTheme['primary_color'] }}"></div>
                                <div class="w-1/4" style="background-color: {{ $recommendedSeasonalTheme['accent_color'] }}"></div>
                                <div class="w-1/4" style="background-color: {{ $recommendedSeasonalTheme['background_color'] }}"></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                🎨 Recommended: {{ $recommendedSeasonalTheme['display_name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Perfect for {{ ucfirst($recommendedSeasonalTheme['season'] ?? 'this time of year') }}
                            </p>
                        </div>
                    </div>
                    <flux:button 
                        wire:click="applySeasonalRecommendation"
                        variant="primary"
                        size="sm"
                    >
                        Apply Theme
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Theme Categories -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Predefined Themes
            </h3>
            
            <!-- Category Filter -->
            <div class="flex items-center space-x-2">
                <flux:button 
                    wire:click="changeCategory('all')"
                    variant="{{ $selectedCategory === 'all' ? 'primary' : 'ghost' }}"
                    size="sm"
                >
                    All
                </flux:button>
                @foreach($this->availableCategories as $category)
                    <flux:button 
                        wire:click="changeCategory('{{ $category }}')"
                        variant="{{ $selectedCategory === $category ? 'primary' : 'ghost' }}"
                        size="sm"
                    >
                        {{ ucfirst($category) }}
                    </flux:button>
                @endforeach
            </div>
        </div>
        
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
                            <div class="flex items-center justify-center space-x-1 mb-1">
                                @if(($theme['category'] ?? '') === 'seasonal')
                                    @switch($theme['season'] ?? '')
                                        @case('summer')
                                            <span class="text-yellow-500">☀️</span>
                                            @break
                                        @case('winter') 
                                            <span class="text-blue-500">❄️</span>
                                            @break
                                        @case('halloween')
                                            <span class="text-orange-500">🎃</span>
                                            @break
                                        @case('spring')
                                            <span class="text-green-500">🌸</span>
                                            @break
                                        @case('autumn')
                                            <span class="text-orange-600">🍂</span>
                                            @break
                                    @endswitch
                                @endif
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $theme['display_name'] }}
                                </h4>
                            </div>
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

    <!-- JavaScript for theme functionality -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Download theme functionality
            Livewire.on('download-theme', (themeJson) => {
                try {
                    const blob = new Blob([themeJson], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `theme-${@this.themeName}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    // Show success feedback
                    showToast('Theme downloaded successfully', 'success');
                } catch (error) {
                    console.error('Download failed:', error);
                    showToast('Failed to download theme', 'error');
                }
            });

            // Real-time theme updates
            Livewire.on('theme-updated', () => {
                requestAnimationFrame(() => {
                    try {
                        // Get current theme CSS from component
                        const primaryColor = @this.primaryColor;
                        const accentColor = @this.accentColor;
                        const backgroundColor = @this.backgroundColor;
                        const textColor = @this.textColor;
                        
                        // Apply CSS custom properties in real-time
                        const css = `:root {
                            --color-primary: ${primaryColor};
                            --color-accent: ${accentColor};
                            --color-background: ${backgroundColor};
                            --color-text: ${textColor};
                        }`;
                        
                        // Remove existing theme styles
                        const existingStyle = document.getElementById('live-theme-styles');
                        if (existingStyle) {
                            existingStyle.remove();
                        }
                        
                        // Add new theme styles to document head
                        const style = document.createElement('style');
                        style.id = 'live-theme-styles';
                        
                        // Enhanced CSS with smooth transitions for theme changes
                        const enhancedCss = css + `
                            
                            /* Smooth transitions for theme changes */
                            *, *::before, *::after {
                                transition: 
                                    background-color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                                    border-color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                                    color 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                                    fill 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                                    stroke 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                                    box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            }
                            
                            /* Preserve smooth animations for interactive elements */
                            .transition-all, .transition-colors, .transition-opacity, 
                            .transition-transform, .transition-shadow {
                                transition-duration: 0.15s !important;
                            }
                            
                            /* Enhanced hover transitions */
                            button, [role="button"], a, input, select, textarea {
                                transition: 
                                    background-color 0.15s cubic-bezier(0.4, 0, 0.2, 1),
                                    border-color 0.15s cubic-bezier(0.4, 0, 0.2, 1),
                                    color 0.15s cubic-bezier(0.4, 0, 0.2, 1),
                                    box-shadow 0.15s cubic-bezier(0.4, 0, 0.2, 1),
                                    transform 0.15s cubic-bezier(0.4, 0, 0.2, 1),
                                    opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                            }
                            
                            /* Optimize for reduced motion preferences */
                            @media (prefers-reduced-motion: reduce) {
                                *, *::before, *::after {
                                    transition-duration: 0.01s !important;
                                    animation-duration: 0.01s !important;
                                }
                            }
                        `;
                        
                        style.textContent = enhancedCss;
                        document.head.appendChild(style);
                        
                        // Apply theme immediately to body for global changes with smooth transitions
                        const root = document.documentElement;
                        root.style.setProperty('--color-primary', primaryColor);
                        root.style.setProperty('--color-accent', accentColor);
                        root.style.setProperty('--color-background', backgroundColor);
                        root.style.setProperty('--color-text', textColor);
                        
                    } catch (error) {
                        console.error('Theme update failed:', error);
                    }
                });
            });

            // Theme saved successfully
            Livewire.on('theme-saved', () => {
                showToast('Theme saved successfully! Your preferences have been updated.', 'success');
            });

            // Theme imported successfully
            Livewire.on('theme-imported', () => {
                showToast('Theme imported successfully!', 'success');
                // Trigger theme update
                Livewire.dispatch('theme-updated');
            });

            // Error handling for theme operations
            Livewire.on('theme-error', (message) => {
                showToast(message || 'An error occurred while processing the theme', 'error');
            });

            // Toast notification function
            function showToast(message, type = 'info') {
                // Check if Flux toast is available
                if (window.Flux && window.Flux.toast) {
                    window.Flux.toast({
                        message: message,
                        type: type,
                        duration: 4000
                    });
                    return;
                }
                
                // Fallback to Livewire events if available
                if (window.Livewire) {
                    Livewire.dispatch('toast', { 
                        message: message, 
                        type: type 
                    });
                    return;
                }
                
                // Ultimate fallback to console and basic alert
                console.log(`${type.toUpperCase()}: ${message}`);
                if (type === 'error') {
                    alert(message);
                }
            }

            // Initialize theme on page load
            Livewire.on('init', () => {
                // Apply current theme immediately
                Livewire.dispatch('theme-updated');
            });

            // Handle theme persistence across page navigation
            window.addEventListener('beforeunload', () => {
                // The theme is already saved via Livewire, so this is just for cleanup
                const existingStyle = document.getElementById('live-theme-styles');
                if (existingStyle) {
                    existingStyle.remove();
                }
            });
        });
    </script>
</div>
