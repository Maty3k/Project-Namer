<flux:menu.item wire:click="toggleTheme" icon="{{ $isDarkMode ? 'moon' : 'sun' }}">
    {{ $isDarkMode ? 'Dark Mode' : 'Light Mode' }}
    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">(Active)</span>
</flux:menu.item>