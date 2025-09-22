{{-- DISABLED: Only theme customizer should control themes --}}
<flux:menu.item disabled icon="{{ $isDarkMode ? 'sun' : 'moon' }}">
    {{ $isDarkMode ? 'Light Mode' : 'Dark Mode' }} (Use Theme Customizer)
</flux:menu.item>