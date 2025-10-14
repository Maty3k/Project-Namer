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

        <div class="grid grid-cols-3 gap-8
                    md:grid-cols-4
                    lg:grid-cols-5
                    xl:grid-cols-6">
            @foreach($this->predefinedThemes as $theme)
                <div wire:click="applyPreset('{{ $theme['name'] }}')"
                     class="group relative cursor-pointer transition-all duration-300">

                    <!-- Card with Fixed Dimensions -->
                    <div class="relative bg-white dark:bg-gray-900 rounded-lg p-3 transition-all duration-300 h-32
                                border border-gray-200 dark:border-gray-700
                                shadow-sm hover:shadow-md
                                hover:-translate-y-1
                                flex flex-col items-center justify-center
                                {{ $themeName === $theme['name'] ? 'ring-2 ring-blue-500 shadow-md -translate-y-1' : '' }}">

                        <!-- Active Checkmark -->
                        @if($themeName === $theme['name'])
                            <div class="absolute top-2 right-2">
                                <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                        @endif

                        <!-- Icon -->
                        <div class="mb-2 mt-1">
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
                            <span class="text-2xl">{{ $themeIcon }}</span>
                        </div>

                        <!-- Theme Name -->
                        <h3 class="text-[10px] font-semibold text-gray-900 dark:text-white text-center leading-tight mb-1 px-1 line-clamp-2">
                            {{ $theme['display_name'] }}
                        </h3>

                        <!-- Mode Badge -->
                        <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-[9px] font-medium text-gray-600 dark:text-gray-400">
                            {{ $theme['is_dark_mode'] ? 'Dark' : 'Light' }}
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
