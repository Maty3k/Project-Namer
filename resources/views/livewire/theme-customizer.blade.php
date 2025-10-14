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

        <div class="grid grid-cols-2 gap-7
                    sm:grid-cols-3
                    lg:grid-cols-5
                    xl:grid-cols-6
                    2xl:grid-cols-7">
            @foreach($this->predefinedThemes as $theme)
                <div wire:click="applyPreset('{{ $theme['name'] }}')"
                     class="group relative cursor-pointer transition-all duration-500 ease-out">

                    <!-- Premium Card with Depth -->
                    <div class="relative bg-white dark:bg-gray-900 rounded-xl p-4 transition-all duration-500
                                border border-gray-200/50 dark:border-gray-700/50
                                shadow-[0_4px_20px_rgb(0,0,0,0.04)] dark:shadow-[0_4px_20px_rgb(0,0,0,0.3)]
                                hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)]
                                hover:-translate-y-0.5
                                {{ $themeName === $theme['name'] ? 'ring-2 ring-blue-500/50 shadow-[0_8px_30px_rgb(59,130,246,0.15)] dark:shadow-[0_8px_30px_rgb(59,130,246,0.3)] -translate-y-0.5' : '' }}">

                        <!-- Subtle Background Pattern -->
                        <div class="absolute inset-0 rounded-xl overflow-hidden pointer-events-none">
                            <div class="absolute inset-0 bg-gradient-to-br from-gray-50/50 to-transparent dark:from-gray-800/30 dark:to-transparent"></div>
                        </div>

                        <!-- Active Indicator - Elegant Checkmark -->
                        @if($themeName === $theme['name'])
                            <div class="absolute -top-1.5 -right-1.5 z-10">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                        @endif

                        <div class="relative space-y-3">
                            <!-- Premium Icon Display -->
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
                                <div class="relative w-14 h-14 flex items-center justify-center">
                                    <!-- Ambient Glow -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 via-purple-400/20 to-pink-400/20 rounded-full blur-lg scale-125 group-hover:scale-150 transition-transform duration-700"></div>

                                    <!-- Icon Container -->
                                    <div class="relative w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 shadow-inner">
                                        <span class="text-2xl transform group-hover:scale-110 transition-transform duration-500 ease-out">
                                            {{ $themeIcon }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Theme Information -->
                            <div class="text-center space-y-1.5">
                                <!-- Theme Name with Seasonal Indicator -->
                                <div>
                                    <h3 class="text-xs font-semibold tracking-tight text-gray-900 dark:text-white leading-tight">
                                        {{ $theme['display_name'] }}
                                    </h3>

                                    @if(($theme['category'] ?? '') === 'seasonal')
                                        <div class="flex items-center justify-center gap-1 mt-0.5">
                                            @switch($theme['season'] ?? '')
                                                @case('summer')
                                                    <span class="text-[9px] text-yellow-600 dark:text-yellow-400 font-medium">☀️ Seasonal</span>
                                                    @break
                                                @case('winter')
                                                    <span class="text-[9px] text-blue-600 dark:text-blue-400 font-medium">❄️ Seasonal</span>
                                                    @break
                                                @case('halloween')
                                                    <span class="text-[9px] text-orange-600 dark:text-orange-400 font-medium">🎃 Seasonal</span>
                                                    @break
                                                @case('spring')
                                                    <span class="text-[9px] text-green-600 dark:text-green-400 font-medium">🌸 Seasonal</span>
                                                    @break
                                                @case('autumn')
                                                    <span class="text-[9px] text-orange-700 dark:text-orange-400 font-medium">🍂 Seasonal</span>
                                                    @break
                                            @endswitch
                                        </div>
                                    @endif
                                </div>

                                <!-- Elegant Mode Indicator -->
                                <div class="flex items-center justify-center">
                                    @if($theme['is_dark_mode'])
                                        <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-gray-900/5 dark:bg-white/5 border border-gray-900/10 dark:border-white/10">
                                            <div class="w-1 h-1 rounded-full bg-gray-900 dark:bg-gray-100"></div>
                                            <span class="text-[9px] font-medium text-gray-700 dark:text-gray-300 tracking-wide">DARK</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-yellow-500/5 dark:bg-yellow-400/10 border border-yellow-500/20 dark:border-yellow-400/20">
                                            <div class="w-1 h-1 rounded-full bg-yellow-500 dark:bg-yellow-400"></div>
                                            <span class="text-[9px] font-medium text-yellow-700 dark:text-yellow-300 tracking-wide">LIGHT</span>
                                        </div>
                                    @endif
                                </div>
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
