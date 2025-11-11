<flux:navlist variant="outline" {{ $attributes->merge(['class' => 'mobile-nav-menu']) }}>
    <flux:navlist.group :heading="__('Platform')" class="grid">
        <flux:navlist.item icon="cog"
                           :href="route('settings.profile')"
                           :current="request()->routeIs('settings.*')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Settings') }}
        </flux:navlist.item>

        <flux:navlist.item icon="home"
                           :href="route('dashboard')"
                           :current="request()->routeIs('dashboard')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Dashboard') }}
        </flux:navlist.item>
    </flux:navlist.group>

    <flux:navlist.group :heading="__('Tools')" class="grid">
        <flux:navlist.item icon="sparkles"
                           :href="route('dashboard')"
                           :current="request()->routeIs('dashboard')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Name Generator') }}
        </flux:navlist.item>

        <flux:navlist.item icon="globe-alt"
                           :href="route('domain-checker')"
                           :current="request()->routeIs('domain-checker')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Domain Checker') }}
        </flux:navlist.item>

        <flux:navlist.item icon="photo"
                           :href="route('logos.index')"
                           :current="request()->routeIs('logos.*')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Logo Gallery') }}
        </flux:navlist.item>

        <flux:navlist.item icon="swatch"
                           :href="route('appearance')"
                           :current="request()->routeIs('appearance')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Appearance') }}
        </flux:navlist.item>

        <flux:navlist.item icon="command-line"
                           href="{{ route('keyboard-shortcuts') }}"
                           :current="request()->routeIs('keyboard-shortcuts')"
                           wire:navigate
                           class="touch-target interactive focus-modern hidden lg:flex">
            {{ __('Keyboard Shortcuts') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
