{{-- Modern Mobile Header - Profile button with dropdown menu --}}
<div class="lg:hidden fixed top-4 right-4 z-50">
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" class="!p-0 !h-10 !w-10">
            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                <span class="text-sm font-medium text-zinc-900 dark:text-white">
                    {{ auth()->user()->initials() }}
                </span>
            </div>
        </flux:button>

        <flux:menu class="w-56">
            {{-- User Info Section --}}
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center h-10 w-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex-shrink-0">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ auth()->user()->initials() }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-zinc-900 dark:text-white truncate">
                            {{ auth()->user()->name }}
                        </div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400 truncate">
                            {{ auth()->user()->email }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>

            <flux:menu.separator />

            {{-- Tools Section --}}
            <flux:menu.item :href="route('logos.index')" icon="photo" wire:navigate>
                {{ __('Logo Gallery') }}
            </flux:menu.item>

            <flux:menu.item :href="route('domain-checker')" icon="globe-alt" wire:navigate>
                {{ __('Domain Checker') }}
            </flux:menu.item>

            <flux:menu.item :href="route('appearance')" icon="swatch" wire:navigate>
                {{ __('Appearance') }}
            </flux:menu.item>

            <flux:menu.separator />

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full hover:bg-red-50 dark:hover:bg-red-900/20"
                >
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</div>
