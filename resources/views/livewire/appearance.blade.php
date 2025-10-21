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

        <div class="space-y-6">
            {{-- Current Theme Info --}}
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('Current Theme') }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ collect($themes)->firstWhere('name', $currentTheme)['display_name'] ?? 'Default Blue' }}
                        <span class="ml-1">
                            ({{ $isDarkMode ? __('Dark Mode') : __('Light Mode') }})
                        </span>
                    </div>
                </div>
                <livewire:theme-quick-toggle />
            </div>

            {{-- Theme Grid --}}
            <div>
                <h3 class="mb-4 font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Available Themes') }}
                </h3>

                <div class="grid grid-cols-2 gap-6
                            sm:grid-cols-3
                            md:grid-cols-4
                            lg:grid-cols-5">
                    @foreach($themes as $theme)
                        <button
                            wire:click="selectTheme('{{ $theme['name'] }}')"
                            class="group relative overflow-hidden rounded-xl transition-all duration-300 ease-out
                                   {{ $currentTheme === $theme['name']
                                      ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-blue-400 dark:ring-offset-zinc-900 shadow-lg scale-105'
                                      : 'hover:scale-105 hover:shadow-xl' }}"
                        >
                            {{-- Card Background with Gradient --}}
                            <div class="relative bg-gradient-to-br p-6 transition-all duration-300
                                        {{ $theme['is_dark_mode']
                                           ? 'from-zinc-900 via-zinc-800 to-zinc-900 dark:from-zinc-800 dark:via-zinc-700 dark:to-zinc-800'
                                           : 'from-white via-zinc-50 to-white dark:from-zinc-800 dark:via-zinc-700 dark:to-zinc-800' }}
                                        {{ $currentTheme !== $theme['name'] ? 'group-hover:from-blue-50 group-hover:to-indigo-50 dark:group-hover:from-blue-900/20 dark:group-hover:to-indigo-900/20' : '' }}">

                                {{-- Theme Preview Icon with Glow --}}
                                <div class="mb-4 flex justify-center">
                                    <div class="relative">
                                        <div class="absolute inset-0 rounded-full blur-xl opacity-0 transition-opacity duration-300
                                                    {{ $theme['is_dark_mode'] ? 'bg-purple-500' : 'bg-yellow-400' }}
                                                    {{ $currentTheme === $theme['name'] ? 'opacity-30' : 'group-hover:opacity-20' }}">
                                        </div>
                                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full transition-all duration-300
                                                    {{ $theme['is_dark_mode']
                                                       ? 'bg-gradient-to-br from-indigo-600 to-purple-700 shadow-lg shadow-purple-500/50'
                                                       : 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-orange-500/50' }}
                                                    {{ $currentTheme === $theme['name'] ? 'scale-110' : 'group-hover:scale-110' }}">
                                            @if($theme['is_dark_mode'])
                                                <flux:icon.moon class="size-8 text-white drop-shadow-lg" />
                                            @else
                                                <flux:icon.sun class="size-8 text-white drop-shadow-lg" />
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Theme Name --}}
                                <div class="text-center space-y-1">
                                    <div class="text-sm font-semibold transition-colors duration-300
                                                {{ $theme['is_dark_mode']
                                                   ? 'text-zinc-100 dark:text-zinc-100'
                                                   : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ $theme['display_name'] }}
                                    </div>
                                    <div class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition-all duration-300
                                                {{ $theme['is_dark_mode']
                                                   ? 'bg-purple-500/20 text-purple-300 dark:bg-purple-500/20 dark:text-purple-300'
                                                   : 'bg-amber-500/20 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' }}">
                                        <div class="h-1.5 w-1.5 rounded-full
                                                    {{ $theme['is_dark_mode'] ? 'bg-purple-400' : 'bg-amber-500' }}">
                                        </div>
                                        {{ $theme['is_dark_mode'] ? __('Dark') : __('Light') }}
                                    </div>
                                </div>

                                {{-- Current Theme Badge --}}
                                @if($currentTheme === $theme['name'])
                                    <div class="absolute -right-1 -top-1">
                                        <div class="relative">
                                            <div class="absolute inset-0 animate-ping rounded-full bg-blue-400 opacity-75"></div>
                                            <div class="relative flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 shadow-lg">
                                                <flux:icon.check class="size-5 text-white" />
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Hover Shine Effect --}}
                                <div class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/10 to-transparent"></div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
