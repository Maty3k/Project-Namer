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

        <div class="grid grid-cols-1 gap-4
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-5">
            @foreach($this->predefinedThemes as $theme)
                <div wire:click="applyPreset('{{ $theme['name'] }}')"
                     class="group cursor-pointer rounded-lg border-2 p-4 transition-all duration-300 transform hover:scale-105 hover:shadow-lg
                            {{ $themeName === $theme['name'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 scale-105 shadow-lg' : 'border-gray-300 dark:border-gray-700 hover:border-blue-400' }}">
                    <div class="space-y-3">
                        <!-- Theme Icon -->
                        <div class="flex items-center justify-center h-16">
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
                            <span class="text-5xl {{ $themeName === $theme['name'] ? 'animate-bounce' : '' }}">
                                {{ $themeIcon }}
                            </span>
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
                                @endif
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 group-hover:font-bold transition-all duration-300">
                                    {{ $theme['display_name'] }}
                                </h4>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors duration-300">
                                {{ $theme['is_dark_mode'] ? 'Dark Mode' : 'Light Mode' }}
                            </p>

                            @if($themeName === $theme['name'])
                                <flux:badge variant="primary" size="sm" class="mt-2">
                                    Active
                                </flux:badge>
                            @endif
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
