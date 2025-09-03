<flux:navlist variant="outline" {{ $attributes->merge(['class' => 'mobile-nav-menu']) }}>
    <flux:navlist.group :heading="__('Platform')" class="grid">
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
        
        <flux:navlist.item icon="photo" 
                           :href="route('logos.index')"
                           :current="request()->routeIs('logos.*')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Logo Gallery') }}
        </flux:navlist.item>

        <flux:navlist.item icon="layout-grid" 
                           href="{{ route('themes.customizer') }}" 
                           :current="request()->routeIs('themes.customizer')"
                           wire:navigate
                           class="touch-target interactive focus-modern">
            {{ __('Theme Customizer') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
