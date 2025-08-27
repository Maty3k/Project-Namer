<flux:navlist variant="outline" {{ $attributes->merge(['class' => 'mobile-nav-secondary']) }}>
    <flux:navlist.group :heading="__('Resources')" class="grid">
        <flux:navlist.item icon="question-mark-circle" 
                           href="#" 
                           onclick="document.querySelector('[x-data]')?.dispatchEvent(new CustomEvent('open-help-modal'))"
                           class="touch-target interactive focus-modern">
            {{ __('Help & Support') }}
        </flux:navlist.item>
        
        <flux:navlist.item icon="folder-git-2" 
                           href="https://github.com/laravel/livewire-starter-kit" 
                           target="_blank"
                           class="touch-target interactive focus-modern">
            {{ __('Repository') }}
        </flux:navlist.item>

        <flux:navlist.item icon="book-open-text" 
                           href="https://laravel.com/docs/starter-kits#livewire" 
                           target="_blank"
                           class="touch-target interactive focus-modern">
            {{ __('Documentation') }}
        </flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
