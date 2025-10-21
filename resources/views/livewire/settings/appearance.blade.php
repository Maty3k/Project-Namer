<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Manage your theme preferences')">
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start gap-4">
                    <flux:icon.information-circle class="size-6 flex-shrink-0 text-zinc-600 dark:text-zinc-400" />
                    <div class="flex-1 space-y-2">
                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ __('Theme Toggle') }}
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Use the theme toggle button in the navigation to switch between light and dark modes. Your preference will be saved automatically.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('Current Mode') }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ \App\Helpers\ThemeHelper::isDarkMode() ? __('Dark Mode') : __('Light Mode') }}
                    </div>
                </div>
                <livewire:theme-quick-toggle />
            </div>
        </div>
    </x-settings.layout>
</section>
