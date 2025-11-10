<flux:header {{ $attributes->class(['lg:hidden', 'glass', 'shadow-soft', 'backdrop-blur-xl', 'border-b', 'border-white/20', 'dark:border-white/10', 'fixed', 'top-0', 'left-0', 'right-0', 'z-50', 'h-14']) }}>
    <div class="flex items-center justify-between w-full h-full px-4">
        <flux:sidebar.toggle class="lg:hidden btn-modern focus-modern touch-action-manipulation" icon="bars-2" inset="left"/>

        <flux:dropdown position="top" align="end">
        <div class="transition-all duration-200 ease-out hover:scale-105 active:scale-95">
            <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    class="transition-all duration-200 ease-out"
            />
        </div>

        <flux:menu class="animate-in slide-in-from-bottom-2 fade-in-0 duration-200 ease-out">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.item
                :href="route('settings.profile')"
                icon="cog"
                wire:navigate
                class="transition-all duration-200 ease-out hover:scale-[1.02] hover:shadow-sm"
            >
                {{ __('Settings') }}
            </flux:menu.item>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full transition-all duration-200 ease-out hover:scale-[1.02] hover:shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
    </div>
</flux:header>
