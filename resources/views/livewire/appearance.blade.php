<section class="w-full">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ __('Appearance') }}
            </h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Choose your theme and mode preferences') }}
            </p>
        </div>

        {{-- Confirmation Message --}}
        @if($selectedThemeData)
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-500 dark:bg-green-800"
                 x-data="{ show: true }"
                 x-show="show"
                 x-transition>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 text-green-600 dark:text-green-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-900 dark:text-white">
                            Theme successfully updated!
                        </p>

                        {{-- Color Swatches - Desktop Only --}}
                        <div class="mt-3 hidden md:block">
                            <p class="text-xs font-medium text-green-800 dark:text-green-100 mb-2">
                                Theme Colors:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedThemeData as $colorName => $colorValue)
                                    <div class="flex items-center gap-1.5 rounded-md bg-white dark:bg-zinc-700 px-2 py-1 border border-zinc-200 dark:border-zinc-500">
                                        <div class="w-4 h-4 rounded border border-zinc-300 dark:border-zinc-400"
                                             style="background-color: {{ $colorValue }};"></div>
                                        <span class="text-xs text-zinc-700 dark:text-white capitalize">
                                            {{ str_replace('-', ' ', $colorName) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <button @click="show = false"
                            class="flex-shrink-0 text-green-600 hover:text-green-800 dark:text-green-200 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Theme Grid --}}
        <div class="space-y-6">
            <div>
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Available Themes') }}
                </h3>

                <div class="grid grid-cols-2 gap-3
                            sm:grid-cols-3 sm:gap-4
                            md:grid-cols-4
                            lg:grid-cols-5
                            xl:grid-cols-6">
                    @foreach($themes as $theme)
                        <button
                            wire:click="selectTheme('{{ $theme['name'] }}')"
                            class="group relative flex flex-col items-center gap-3 rounded-xl border p-3 transition-all duration-200
                                   sm:gap-3.5 sm:p-4
                                   {{ $currentTheme === $theme['name']
                                      ? 'border-blue-500 bg-blue-50 shadow-md dark:border-blue-400 dark:bg-blue-950/50'
                                      : 'border-zinc-200 bg-white hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}"
                        >
                            {{-- Current Theme Indicator --}}
                            @if($currentTheme === $theme['name'])
                                <div class="absolute right-2 top-2 text-blue-500 text-sm">
                                    ✓
                                </div>
                            @endif

                            {{-- Theme Preview Image --}}
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl overflow-hidden transition-transform duration-200
                                        sm:w-16 sm:h-16
                                        {{ $theme['is_dark_mode']
                                           ? 'bg-slate-100 dark:bg-slate-800'
                                           : 'bg-white dark:bg-zinc-800' }}
                                        ring-1 ring-zinc-200 dark:ring-zinc-700
                                        group-hover:scale-105">
                                @php
                                    $imagePath = null;
                                    foreach (['svg', 'jpg', 'png'] as $ext) {
                                        if (file_exists(public_path('images/theme-previews/' . $theme['name'] . '.' . $ext))) {
                                            $imagePath = 'images/theme-previews/' . $theme['name'] . '.' . $ext;
                                            break;
                                        }
                                    }
                                @endphp
                                @if($imagePath)
                                    <img src="{{ asset($imagePath) }}"
                                         alt="{{ $theme['display_name'] }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <span class="text-3xl">{{ $themeEmojis[$theme['name']] ?? '🎨' }}</span>
                                @endif
                            </div>

                            {{-- Theme Name --}}
                            <div class="text-center w-full">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-tight mb-1
                                            sm:text-base sm:mb-1.5">
                                    {{ $theme['display_name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $theme['is_dark_mode'] ? 'Dark' : 'Light' }}
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
