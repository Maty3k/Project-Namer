{{-- Mobile Header: Fixed top bar visible only on mobile (below lg breakpoint) --}}
<div class="lg:hidden fixed top-0 left-0 right-0 h-16 z-[60] bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 shadow-sm">
    <div class="h-full flex items-center justify-between px-4">
        {{-- Left: App Logo and Name --}}
        <div class="flex items-center gap-2">
            <x-app-logo-icon class="h-8 w-8" />
            <span class="font-semibold text-base text-gray-900 dark:text-white">Brandify</span>
        </div>

        {{-- Right: Hamburger Menu and Profile --}}
        <div class="flex items-center gap-3">
            {{-- Hamburger Menu Button --}}
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />

            {{-- User Profile Dropdown --}}
            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                                    {{ auth()->user()->initials() }}
                                </span>
                                <div class="flex-1">
                                    <div class="font-semibold text-sm">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator/>

                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>

                    <flux:menu.separator/>

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full hover:bg-red-50 dark:hover:bg-red-900/20">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
</div>
