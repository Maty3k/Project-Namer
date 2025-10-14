@php
    $isDarkMode = \App\Helpers\ThemeHelper::isDarkMode();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ $isDarkMode ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
<head>
    @include('partials.head')

    <script>
        // Ensure theme persists correctly on page load
        (function() {
            // Always trust the server preference (database) as the source of truth
            const serverThemePreference = {{ $isDarkMode ? 'true' : 'false' }};
            const shouldBeDark = serverThemePreference;

            // Sync localStorage to match the server preference
            localStorage.setItem('darkMode', shouldBeDark ? 'true' : 'false');

            console.log('PROJECT WORKFLOW: Using server theme from database', shouldBeDark ? 'DARK' : 'LIGHT');

            // Apply immediately to html element
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-white dark:bg-slate-900 flex">
    
    <!-- Custom Project Sidebar -->
    <div class="flex-shrink-0">
        @livewire('sidebar', ['activeProjectUuid' => request()->route('uuid')])
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="border-b px-6 py-4 transition-all duration-300 bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <x-app-logo />
                </div>
                <x-desktop-user-menu/>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>

    <x-mobile-user-menu/>

    {{-- Keyboard Shortcuts Components --}}
    <x-command-palette />
    <x-keyboard-shortcuts-help />

    {{-- Undo Toast Component --}}
    <x-undo-toast />

    @fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    @stack('scripts')
</body>
</html>