<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Choose your theme and mode preferences')">
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

                <div class="grid grid-cols-2 gap-4
                            sm:grid-cols-3
                            md:grid-cols-4
                            lg:grid-cols-5">
                    @foreach($themes as $theme)
                        <button
                            wire:click="selectTheme('{{ $theme['name'] }}')"
                            class="group relative flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all
                                   {{ $currentTheme === $theme['name']
                                      ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                      : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600' }}"
                        >
                            {{-- Theme Preview Circle --}}
                            <div class="flex h-12 w-12 items-center justify-center rounded-full
                                        {{ $theme['is_dark_mode']
                                           ? 'bg-zinc-800 dark:bg-zinc-700'
                                           : 'bg-zinc-100 dark:bg-zinc-800' }}
                                        border-2
                                        {{ $currentTheme === $theme['name']
                                           ? 'border-blue-500 dark:border-blue-400'
                                           : 'border-zinc-300 dark:border-zinc-600' }}">
                                @if($theme['is_dark_mode'])
                                    <flux:icon.moon class="size-6 text-zinc-100 dark:text-zinc-300" />
                                @else
                                    <flux:icon.sun class="size-6 text-zinc-700 dark:text-zinc-400" />
                                @endif
                            </div>

                            {{-- Theme Name --}}
                            <div class="text-center">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $theme['display_name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $theme['is_dark_mode'] ? __('Dark') : __('Light') }}
                                </div>
                            </div>

                            {{-- Current Theme Indicator --}}
                            @if($currentTheme === $theme['name'])
                                <div class="absolute right-2 top-2">
                                    <flux:icon.check-circle class="size-5 text-blue-500 dark:text-blue-400" />
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
