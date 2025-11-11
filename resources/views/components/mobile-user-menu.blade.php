{{-- Modern Mobile Header: Fixed top bar visible only on mobile (below lg breakpoint) --}}
{{-- Height: 56px (h-14) with 44px+ touch targets --}}
{{-- Z-index: 50 (below sidebar which is 60) --}}
<div class="lg:hidden fixed top-0 left-0 right-0 h-14 z-50 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 shadow-sm">
    <div class="h-full flex items-center justify-between px-4">
        {{-- Left: App Logo and Brand Name --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 flex-shrink-0" wire:navigate>
            <x-app-logo-icon class="h-8 w-8" />
            <span class="font-semibold text-base text-zinc-900 dark:text-white">Brandify</span>
        </a>

        {{-- Right: User Profile and Menu Toggle --}}
        <div class="flex items-center gap-3">
            {{-- User Profile Dropdown - Touch target: 44x44px --}}
            <flux:dropdown position="bottom" align="end">
                <div class="flex items-center justify-center h-11 w-11 rounded-full bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ auth()->user()->initials() }}
                    </span>
                </div>

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
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate class="min-h-[44px]">
                        {{ __('Settings') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full hover:bg-red-50 dark:hover:bg-red-900/20 min-h-[44px]"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

            {{-- Sidebar Toggle Button - Touch target: 44x44px --}}
            <flux:sidebar.toggle class="lg:hidden flex items-center justify-center h-11 w-11 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" icon="bars-3" />
        </div>
    </div>
</div>

{{-- Spacer to prevent content from hiding behind fixed header --}}
<div class="lg:hidden h-14"></div>
