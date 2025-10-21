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

                <div class="grid grid-cols-2 gap-4
                            sm:grid-cols-3
                            md:grid-cols-4
                            lg:grid-cols-5">
                    @foreach($themes as $theme)
                        <button
                            wire:click="selectTheme('{{ $theme['name'] }}')"
                            class="group relative flex flex-col items-center gap-3 rounded-xl border-2 p-4 transition-all duration-200
                                   {{ $currentTheme === $theme['name']
                                      ? 'border-blue-500 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-md dark:border-blue-400 dark:from-blue-900/30 dark:to-indigo-900/30'
                                      : 'border-zinc-200 bg-white hover:border-blue-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-600' }}"
                        >
                            {{-- Theme Preview Circle with Emoji --}}
                            <div class="flex h-14 w-14 items-center justify-center rounded-full text-3xl transition-all duration-200
                                        {{ $theme['is_dark_mode']
                                           ? 'bg-gradient-to-br from-slate-700 to-slate-800 shadow-lg dark:from-slate-600 dark:to-slate-700'
                                           : 'bg-gradient-to-br from-amber-100 to-orange-100 shadow-lg dark:from-amber-900/30 dark:to-orange-900/30' }}
                                        {{ $currentTheme === $theme['name']
                                           ? 'ring-2 ring-blue-400 ring-offset-2 dark:ring-blue-500 dark:ring-offset-zinc-900'
                                           : 'group-hover:scale-110' }}">
                                {{ $themeEmojis[$theme['name']] ?? '🎨' }}
                            </div>

                            {{-- Theme Name --}}
                            <div class="text-center">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $theme['display_name'] }}
                                </div>
                                <div class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $theme['is_dark_mode']
                                               ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                               : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                    {{ $theme['is_dark_mode'] ? __('Dark') : __('Light') }}
                                </div>
                            </div>

                            {{-- Current Theme Indicator --}}
                            @if($currentTheme === $theme['name'])
                                <div class="absolute right-2 top-2 text-lg">
                                    ✓
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
