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

                            {{-- Theme Emoji with Background --}}
                            <div class="flex items-center justify-center w-14 h-14 rounded-xl text-3xl transition-transform duration-200
                                        sm:w-16 sm:h-16
                                        {{ $theme['is_dark_mode']
                                           ? 'bg-slate-100 dark:bg-slate-800'
                                           : 'bg-amber-50 dark:bg-amber-900/20' }}
                                        group-hover:scale-105">
                                {{ $themeEmojis[$theme['name']] ?? '🎨' }}
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
