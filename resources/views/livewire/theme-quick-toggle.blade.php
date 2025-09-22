<flux:menu.item wire:click="toggleTheme" icon="{{ $isDarkMode ? 'sun' : 'moon' }}">
    {{ $isDarkMode ? 'Light Mode' : 'Dark Mode' }}
</flux:menu.item>