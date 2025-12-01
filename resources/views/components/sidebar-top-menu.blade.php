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

        <flux:navlist.item icon="chat-bubble-left-right"
                           :href="route('contact')"
                           :current="request()->routeIs('contact')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Feedback') }}
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

    @if(auth()->user()?->is_admin)
    <flux:navlist.group :heading="__('Admin')" class="grid">
        <flux:navlist.item icon="adjustments-horizontal"
                           :href="route('admin.ai-config')"
                           :current="request()->routeIs('admin.ai-config')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('AI Configuration') }}
        </flux:navlist.item>

        <flux:navlist.item icon="chart-bar"
                           :href="route('admin.ai-dashboard')"
                           :current="request()->routeIs('admin.ai-dashboard')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('AI Dashboard') }}
        </flux:navlist.item>

        <flux:navlist.item icon="currency-dollar"
                           :href="route('admin.ai-costs')"
                           :current="request()->routeIs('admin.ai-costs')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('AI Costs') }}
        </flux:navlist.item>
    </flux:navlist.group>
    @endif
</flux:navlist>
