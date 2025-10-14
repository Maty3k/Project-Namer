<div class="space-y-8">
    <!-- Theme Customizer Header -->
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Theme Customizer
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Choose from our predefined theme collection
        </p>
    </div>

    <!-- Seasonal Recommendation -->
    @if($recommendedSeasonalTheme)
        <flux:card class="cursor-pointer hover:shadow-lg transition-all duration-200 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800"
                   wire:click="applySeasonalRecommendation">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="text-4xl">
                            @php
                                $seasonEmoji = match($recommendedSeasonalTheme['season'] ?? '') {
                                    'summer' => '☀️',
                                    'winter' => '❄️',
                                    'spring' => '🌸',
                                    'autumn' => '🍂',
                                    'halloween' => '🎃',
                                    default => '🎨'
                                };
                            @endphp
                            {{ $seasonEmoji }}
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
                    <flux:icon.arrow-right class="size-6 text-gray-400" />
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
            <div class="flex items-center space-x-2 flex-wrap gap-2">
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

        <div class="grid grid-cols-1 gap-6
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4">
            @foreach($this->predefinedThemes as $theme)
                <div wire:click="applyPreset('{{ $theme['name'] }}')"
                     class="group relative cursor-pointer rounded-2xl overflow-hidden transition-all duration-300 transform hover:scale-[1.02] hover:shadow-2xl
                            {{ $themeName === $theme['name'] ? 'ring-4 ring-blue-500 ring-offset-2 dark:ring-offset-gray-900 shadow-2xl scale-[1.02]' : 'hover:ring-2 hover:ring-blue-400/50' }}">

                    <!-- Background Gradient based on theme -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-20 transition-opacity group-hover:opacity-20 dark:group-hover:opacity-30"
                         style="background: linear-gradient(135deg,
                            {{ $theme['is_dark_mode'] ? '#1e293b' : '#f8fafc' }} 0%,
                            {{ $theme['is_dark_mode'] ? '#334155' : '#e2e8f0' }} 100%);"></div>

                    <!-- Card Content -->
                    <div class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
                        <!-- Active Badge (top-right corner) -->
                        @if($themeName === $theme['name'])
                            <div class="absolute top-3 right-3">
                                <div class="flex items-center gap-1.5 bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Active</span>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <!-- Theme Icon with Background Circle -->
                            <div class="flex items-center justify-center">
                                @php
                                    $themeIcon = match($theme['name'] ?? '') {
                                        'default' => '🔵',
                                        'ocean' => '🌊',
                                        'sunset' => '🌅',
                                        'forest' => '🌲',
                                        'cosmic-violet' => '🌌',
                                        'coral-reef' => '🪸',
                                        'midnight-teal' => '🌃',
                                        'summer' => '☀️',
                                        'winter' => '❄️',
                                        'halloween' => '🎃',
                                        'spring' => '🌸',
                                        'autumn' => '🍂',
                                        'neon-cyber' => '🔮',
                                        'electric-blue' => '⚡',
                                        'hot-pink' => '💖',
                                        'lava-red' => '🌋',
                                        'lime-punch' => '🍋',
                                        'gold-rush' => '🏆',
                                        'matrix-green' => '💚',
                                        default => '🎨'
                                    };
                                @endphp
                                <div class="relative">
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full blur-xl group-hover:blur-2xl transition-all"></div>
                                    <span class="relative text-6xl {{ $themeName === $theme['name'] ? 'animate-bounce' : 'group-hover:scale-110' }} transition-transform duration-300 inline-block">
                                        {{ $themeIcon }}
                                    </span>
                                </div>
                            </div>

                            <!-- Theme Info -->
                            <div class="text-center space-y-2">
                                <!-- Theme Name -->
                                <div class="flex items-center justify-center gap-2">
                                    @if(($theme['category'] ?? '') === 'seasonal')
                                        <span class="text-sm">
                                            @switch($theme['season'] ?? '')
                                                @case('summer')
                                                    <span class="text-yellow-500">☀️</span>
                                                    @break
                                                @case('winter')
                                                    <span class="text-blue-400">❄️</span>
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
                                        </span>
                                    @endif
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        {{ $theme['display_name'] }}
                                    </h4>
                                </div>

                                <!-- Mode Badge -->
                                <div class="flex items-center justify-center">
                                    @if($theme['is_dark_mode'])
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-800 dark:bg-gray-700 text-gray-100 border border-gray-700 dark:border-gray-600">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                            </svg>
                                            Dark Mode
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                                            </svg>
                                            Light Mode
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Hover Indicator -->
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                <p class="text-xs text-center text-gray-500 dark:text-gray-400 font-medium">
                                    Click to activate
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Current Theme Info -->
    <flux:card class="bg-gray-50 dark:bg-gray-800">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Current Theme
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        <strong>{{ $themeName }}</strong>
                        <span class="mx-2">•</span>
                        {{ $isDarkMode ? 'Dark Mode' : 'Light Mode' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                        Theme colors are loaded from: /css/themes/{{ $themeName }}.css
                    </p>
                </div>

                <flux:button wire:click="resetToDefault" variant="ghost" size="sm">
                    Reset to Default
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Loading State -->
    <div wire:loading.flex
         wire:target="applyTheme,applyPreset"
         class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-200 dark:border-gray-600">
            <div class="text-6xl mb-4 animate-bounce">🎨</div>
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-300 font-medium">Applying your theme...</p>
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
            // Theme saved successfully
            Livewire.on('theme-saved', () => {
                showToast('Theme saved successfully!', 'success');
            });

            // Error handling for theme operations
            Livewire.on('theme-error', (message) => {
                showToast(message || 'An error occurred while processing the theme', 'error');
            });

            // Toast notification function
            function showToast(message, type = 'info') {
                Livewire.dispatch('show-toast', {
                    message: message,
                    type: type,
                    duration: type === 'error' ? 8000 : 4000
                });
            }
        });
    </script>
</div>
