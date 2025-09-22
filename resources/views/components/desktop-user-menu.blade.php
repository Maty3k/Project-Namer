@php
    $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
@endphp

<flux:dropdown {{ $attributes->class(['hidden lg:block']) }} position="bottom" align="start">
    <div class="{{ $userTheme ? 'user-menu-wrapper' : '' }} transition-all duration-200 ease-out hover:scale-105 active:scale-95"
         @if($userTheme) style="color: #374151 !important;" @endif>
        <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down"
                class="transition-all duration-200 ease-out"
        />
    </div>

    <flux:menu class="w-[220px] animate-in slide-in-from-top-2 fade-in-0 duration-200 ease-out">
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

        <flux:menu.radio.group>
            <flux:menu.item
                :href="route('settings.profile')"
                icon="cog"
                wire:navigate.hover
                class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 ease-out hover:scale-[1.02] hover:shadow-sm"
            >
                {{ __('Settings') }}
            </flux:menu.item>
        </flux:menu.radio.group>

        <flux:menu.separator/>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full transition-all duration-200 ease-out hover:scale-[1.02] hover:shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20">
                {{ __('Log Out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
