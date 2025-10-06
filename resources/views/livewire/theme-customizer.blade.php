<div class="space-y-8 relative overflow-hidden">
    <!-- Clean theme customizer interface -->

    <!-- Theme Customizer Header -->
    <div class="flex items-center justify-between relative z-10">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Theme Customizer
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Customize your application theme and colors
            </p>
        </div>
        
    </div>

    <!-- Seasonal Recommendation -->
    @if($recommendedSeasonalTheme)
        <flux:card class="cursor-pointer hover:shadow-lg transition-all duration-200 relative z-10"
                   style="background-color: {{ $accentColor }}10; border-color: {{ $accentColor }}40;"
                   wire:click="applySeasonalRecommendation">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-16 overflow-hidden rounded-lg shadow-md">
                                <div class="w-1/2" style="background-color: {{ $recommendedSeasonalTheme['accent_color'] }}"></div>
                                <div class="w-1/4" style="background-color: {{ $recommendedSeasonalTheme['accent_content_color'] ?? $recommendedSeasonalTheme['accent_color'] }}"></div>
                                <div class="w-1/4" style="background-color: {{ $recommendedSeasonalTheme['accent_foreground_color'] ?? '#ffffff' }}"></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Recommended: {{ $recommendedSeasonalTheme['display_name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Perfect for {{ ucfirst($recommendedSeasonalTheme['season'] ?? 'this time of year') }}
                            </p>
                        </div>
                    </div>
                    <!-- Theme can be applied by clicking on the card itself -->
                </div>
            </div>
        </flux:card>
    @endif

    <!-- Theme Categories -->
    <div class="space-y-4 relative z-10">
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
                     class="group cursor-pointer rounded-lg border-2 p-4 transition-all duration-300 transform hover:scale-105 hover:shadow-lg
                            {{ $themeName === $theme['name'] ? 'scale-105 shadow-lg' : '' }}"
                     style="border-color: {{ $themeName === $theme['name'] ? $accentColor : '#d1d5db' }};
                            {{ $themeName === $theme['name'] ? 'box-shadow: 0 0 0 3px ' . $accentColor . '20;' : '' }}
                            hover:border-color: {{ $accentColor }}80;">
                    <div class="space-y-3">
                        <!-- Clean Theme Preview -->
                        <div class="flex h-12 overflow-hidden rounded border border-gray-200 dark:border-gray-600">
                            <div class="w-1/2" style="background-color: {{ $theme['accent_color'] }}"></div>
                            <div class="w-1/4" style="background-color: {{ $theme['accent_content_color'] ?? $theme['accent_color'] }}"></div>
                            <div class="w-1/4" style="background-color: {{ $theme['accent_foreground_color'] ?? '#ffffff' }}"></div>
                        </div>
                        
                        <!-- Theme Info -->
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1 mb-1">
                                @if(($theme['category'] ?? '') === 'seasonal')
                                    @switch($theme['season'] ?? '')
                                        @case('summer')
                                            <span class="text-yellow-500 animate-pulse">☀️</span>
                                            @break
                                        @case('winter')
                                            <span class="animate-pulse" style="color: {{ $accentColor }};">❄️</span>
                                            @break
                                        @case('halloween')
                                            <span class="text-orange-500">🎃</span>
                                            @break
                                        @case('spring')
                                            <span class="text-green-500 animate-pulse">🌸</span>
                                            @break
                                        @case('autumn')
                                            <span class="text-orange-600">🍂</span>
                                            @break
                                    @endswitch
                                @endif
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 group-hover:font-bold transition-all duration-300">
                                    {{ $theme['display_name'] }}
                                </h4>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors duration-300">
                                {{ $theme['is_dark_mode'] ? 'Dark Mode' : 'Light Mode' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Custom Color Controls -->
    <div class="space-y-6 relative z-10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            FluxUI Theme Colors
        </h3>

        <div class="grid grid-cols-1 gap-6
                    sm:grid-cols-2
                    lg:grid-cols-3">
            <!-- Accent Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Accent Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="accentColor"
                                  type="color"
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="accentColor"
                                  placeholder="#3b82f6"
                                  class="flex-1" />
                    </div>
                    <flux:description>Main interactive color (auto-calculates content color)</flux:description>
                    <flux:error name="accentColor" />
                </flux:field>
            </div>

            <!-- Accent Content Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Accent Content Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="accentContentColor"
                                  type="color"
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="accentContentColor"
                                  placeholder="#2563eb"
                                  class="flex-1" />
                    </div>
                    <flux:description>Text/icon color for accent backgrounds</flux:description>
                    <flux:error name="accentContentColor" />
                </flux:field>
            </div>

            <!-- Accent Foreground Color -->
            <div class="space-y-2">
                <flux:field>
                    <flux:label>Accent Foreground Color</flux:label>
                    <div class="flex space-x-2">
                        <flux:input wire:model.live="accentForegroundColor"
                                  type="color"
                                  class="w-16 h-10 rounded cursor-pointer" />
                        <flux:input wire:model.live="accentForegroundColor"
                                  placeholder="#ffffff"
                                  class="flex-1" />
                    </div>
                    <flux:description>Foreground color on accent elements</flux:description>
                    <flux:error name="accentForegroundColor" />
                </flux:field>
            </div>
        </div>

        <!-- Base Color Shade Selector -->
        <div class="space-y-2">
            <flux:field>
                <flux:label>Base Color Shade</flux:label>
                <flux:select wire:model.live="baseColorShade">
                    <option value="zinc">Zinc (Default)</option>
                    <option value="slate">Slate</option>
                    <option value="neutral">Neutral</option>
                    <option value="gray">Gray</option>
                    <option value="stone">Stone</option>
                </flux:select>
                <flux:description>Remaps zinc shades to another neutral palette</flux:description>
                <flux:error name="baseColorShade" />
            </flux:field>
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
                <flux:switch wire:model.live="isDarkMode" wire:click="toggleDarkMode">
                    Dark Mode
                </flux:switch>
            </flux:field>
        </div>
    </div>

    <!-- Accessibility Feedback -->
    <div class="space-y-4 relative z-10">
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
                    <h4 class="text-sm font-medium" style="color: {{ $accentColor }}AA;">
                        Suggestions:
                    </h4>
                    <ul class="text-sm list-disc list-inside space-y-1" style="color: {{ $accentColor }}CC;">
                        @foreach($accessibilityFeedback['suggestions'] as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Preview -->
    <div class="space-y-4 relative z-10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Live Preview
        </h3>

        <div class="rounded-lg border p-6 space-y-4 {{ $isDarkMode ? 'border-gray-600 dark:border-gray-600 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900' }}">
            <div class="space-y-3">
                <flux:button style="background-color: {{ $accentColor }}; color: {{ $accentForegroundColor }};">
                    Accent Button
                </flux:button>

                <flux:button style="background-color: {{ $accentContentColor ?? $accentColor }}; color: {{ $accentForegroundColor }};">
                    Accent Content Button
                </flux:button>
            </div>

            <div class="border-t pt-4" style="border-color: {{ $isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' }}">
                <h4 class="font-semibold mb-2">
                    {{ $themeName }} Theme
                </h4>
                <p class="text-sm opacity-90">
                    This is sample text showing how your chosen FluxUI accent colors work together.
                    @if($isDarkMode)
                        Dark mode is enabled for better visibility in low-light environments.
                    @else
                        Light mode provides optimal readability in bright conditions.
                    @endif
                </p>
                <p class="text-xs mt-2 opacity-75">
                    Accent: {{ $accentColor }} | Content: {{ $accentContentColor ?? 'Auto' }} | Foreground: {{ $accentForegroundColor }} | Base: {{ $baseColorShade }}
                </p>
            </div>
        </div>
    </div>

    <!-- CSS Output -->
    <div class="space-y-4 relative z-10">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Generated CSS
        </h3>
        
        <div class="rounded-lg {{ $isDarkMode ? 'bg-gray-900 border border-gray-700' : 'bg-gray-100' }} p-4">
            <pre class="text-sm {{ $isDarkMode ? 'text-gray-300' : 'text-gray-800' }} overflow-x-auto font-mono">{{ $this->generatedCss }}</pre>
        </div>
    </div>

    <!-- Import/Export -->
    <div class="flex flex-col space-y-4 relative z-10
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
         wire:target="applyTheme,applyPreset,importTheme"
         class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-200 dark:border-gray-600">
            <div class="text-6xl mb-4 animate-bounce">🎨</div>
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 mx-auto mb-4" style="border-color: {{ $accentColor }};"></div>
            <p class="text-gray-600 dark:text-gray-300 font-medium">Applying your magical theme...</p>
            <div class="flex justify-center gap-2 mt-4">
                <span class="animate-bounce text-2xl" style="animation-delay: 0s;">✨</span>
                <span class="animate-bounce text-2xl" style="animation-delay: 0.2s;">🌈</span>
                <span class="animate-bounce text-2xl" style="animation-delay: 0.4s;">✨</span>
            </div>
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

            // Real-time theme updates with subtle background changes
            Livewire.on('theme-updated', () => {
                requestAnimationFrame(() => {
                    try {
                        // Get current theme CSS from component (FluxUI variables)
                        const accentColor = @this.accentColor;
                        const accentContentColor = @this.accentContentColor || accentColor;
                        const accentForegroundColor = @this.accentForegroundColor;
                        const baseColorShade = @this.baseColorShade;

                        // Apply CSS custom properties in real-time (FluxUI format)
                        const css = `@theme {
                            --color-accent: ${accentColor};
                            --color-accent-content: ${accentContentColor};
                            --color-accent-foreground: ${accentForegroundColor};
                        }
                        @layer theme {
                            .dark {
                                --color-accent: ${accentColor};
                                --color-accent-content: ${accentContentColor};
                                --color-accent-foreground: ${accentForegroundColor};
                            }
                        }`;

                        // Remove existing theme styles
                        const existingStyle = document.getElementById('live-theme-styles');
                        if (existingStyle) {
                            existingStyle.remove();
                        }

                        // Create subtle tinted background for whole UI
                        const isDarkMode = @this.isDarkMode;
                        const baseBackground = isDarkMode ? '#111827' : '#ffffff';
                        const tintedBg = blendColors(baseBackground, accentColor, 0.02); // 2% blend
                        
                        // Add new theme styles to document head
                        const style = document.createElement('style');
                        style.id = 'live-theme-styles';
                        
                        // Enhanced CSS with smooth transitions and subtle background tinting
                        const enhancedCss = css + `
                            
                            /* Apply subtle background tinting to the whole UI */
                            body {
                                background-color: ${tintedBg} !important;
                                transition: background-color 0.5s ease !important;
                            }
                            
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
                        root.style.setProperty('--color-accent', accentColor);
                        root.style.setProperty('--color-accent-content', accentContentColor);
                        root.style.setProperty('--color-accent-foreground', accentForegroundColor);

                        // Dispatch global theme-applied event
                        Livewire.dispatch('theme-applied', {
                            accentColor: accentColor,
                            accentContentColor: accentContentColor,
                            accentForegroundColor: accentForegroundColor,
                            baseColorShade: baseColorShade,
                            isDarkMode: @this.isDarkMode
                        });
                        
                    } catch (error) {
                        console.error('Theme update failed:', error);
                    }
                });
            });
            
            // Helper function to blend two colors for subtle background tinting
            function blendColors(color1, color2, percentage) {
                const hex = (color) => {
                    const hex = color.replace('#', '');
                    return {
                        r: parseInt(hex.substr(0, 2), 16),
                        g: parseInt(hex.substr(2, 2), 16),
                        b: parseInt(hex.substr(4, 2), 16)
                    };
                };
                
                const rgb1 = hex(color1);
                const rgb2 = hex(color2);
                
                const r = Math.round(rgb1.r * (1 - percentage) + rgb2.r * percentage);
                const g = Math.round(rgb1.g * (1 - percentage) + rgb2.g * percentage);
                const b = Math.round(rgb1.b * (1 - percentage) + rgb2.b * percentage);
                
                return `rgb(${r}, ${g}, ${b})`;
            }

            // Theme applied with enhanced 1-second animation and dark mode handling
            Livewire.on('theme-applied', (data) => {
                // Add pulse animation to all themed elements
                const animateThemeChange = () => {
                    const themedElements = document.querySelectorAll('.themed-sidebar, .themed-create-box, header');
                    themedElements.forEach(element => {
                        element.style.transform = 'scale(1.02)';
                        element.style.transition = 'all 1s cubic-bezier(0.4, 0, 0.2, 1)';

                        // Reset after animation
                        setTimeout(() => {
                            element.style.transform = 'scale(1)';
                        }, 500);
                    });
                };

                // Apply theme colors to themed elements (FluxUI accent colors)
                const applyThemeColors = () => {
                    const baseBackground = data.isDarkMode ? '#111827' : '#ffffff';
                    const baseText = data.isDarkMode ? '#f9fafb' : '#1f2937';

                    // Update sidebar
                    const sidebar = document.querySelector('.themed-sidebar');
                    if (sidebar) {
                        sidebar.style.backgroundColor = baseBackground;
                        sidebar.style.borderColor = `${data.accentColor}50`;
                        sidebar.style.color = baseText;
                        sidebar.style.transition = 'all 1s ease-in-out';
                    }

                    // Update create project box
                    const createBox = document.querySelector('.themed-create-box');
                    if (createBox) {
                        createBox.style.backgroundColor = baseBackground;
                        createBox.style.boxShadow = `0 10px 25px ${data.accentColor}20`;
                        createBox.style.color = baseText;
                        createBox.style.transition = 'all 1s ease-in-out';
                    }

                    // Update header
                    const header = document.querySelector('header');
                    if (header) {
                        header.style.backgroundColor = baseBackground;
                        header.style.borderColor = `${data.accentColor}40`;
                        header.style.color = baseText;
                        header.style.transition = 'all 1s ease-in-out';
                    }
                };
                
                // Handle dark mode toggle
                const html = document.documentElement;
                if (data.isDarkMode) {
                    html.classList.add('dark');
                    
                    // Apply consistent sidebar colors in dark mode
                    const sidebarElements = document.querySelectorAll('[class*="bg-slate-900"]');
                    sidebarElements.forEach(element => {
                        element.style.backgroundColor = '#111827';
                        element.style.transition = 'background-color 1s ease-in-out';
                    });

                    const borderElements = document.querySelectorAll('[class*="border-slate-"]');
                    borderElements.forEach(element => {
                        element.style.borderColor = data.accentColor + '44';
                        element.style.transition = 'border-color 1s ease-in-out';
                    });
                    
                    // Ensure text is white in dark mode
                    document.body.style.color = '#f9fafb';
                } else {
                    html.classList.remove('dark');
                    
                    // Reset sidebar colors for light mode
                    const sidebarElements = document.querySelectorAll('[class*="bg-slate-900"]');
                    sidebarElements.forEach(element => {
                        element.style.backgroundColor = '';
                        element.style.transition = 'background-color 1s ease-in-out';
                    });
                    
                    const borderElements = document.querySelectorAll('[class*="border-slate-"]');
                    borderElements.forEach(element => {
                        element.style.borderColor = '';
                        element.style.transition = 'border-color 1s ease-in-out';
                    });
                    
                    // Reset text color for light mode
                    document.body.style.color = '';
                }
                
                // Execute animations with timing
                requestAnimationFrame(() => {
                    applyThemeColors();
                    setTimeout(() => {
                        animateThemeChange();
                    }, 100);
                });
                
                // Show visual feedback with themed notification
                showThemedNotification(data);
            });

            // Theme saved successfully
            Livewire.on('theme-saved', () => {
                showToast('Theme saved successfully! Your preferences have been updated.', 'success');

                // Force refresh of all Livewire components that might display theme state
                setTimeout(() => {
                    Livewire.dispatch('refresh-theme-components');
                }, 100);
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
                // Dispatch to Livewire with object format
                Livewire.dispatch('show-toast', {
                    message: message,
                    type: type,
                    duration: type === 'error' ? 8000 : 4000
                });
            }
            
            // Show themed notification with colors
            function showThemedNotification(data) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-xl transform transition-all duration-1000';
                notification.style.backgroundColor = data.accentColor;
                notification.style.color = data.accentForegroundColor;
                notification.style.fontWeight = 'bold';
                notification.style.transform = 'translateX(100%) scale(0.8)';
                notification.style.opacity = '0';

                notification.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl animate-pulse">🎨</span>
                        <span>Theme Applied!</span>
                    </div>
                `;

                document.body.appendChild(notification);

                // Animate in
                requestAnimationFrame(() => {
                    notification.style.transform = 'translateX(0) scale(1)';
                    notification.style.opacity = '1';

                    // Animate out after 2 seconds
                    setTimeout(() => {
                        notification.style.transform = 'translateX(100%) scale(0.8)';
                        notification.style.opacity = '0';

                        // Remove after animation
                        setTimeout(() => {
                            notification.remove();
                        }, 1000);
                    }, 2000);
                });
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
