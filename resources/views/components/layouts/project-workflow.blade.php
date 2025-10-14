<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')

    <script>
        // Use localStorage as primary source (fast, prevents flash)
        (function() {
            const localStorageTheme = localStorage.getItem('darkMode');
            const serverThemePreference = {{ $isDarkMode ? 'true' : 'false' }};

            // Use localStorage if available, otherwise use server preference
            const shouldBeDark = localStorageTheme !== null
                ? localStorageTheme === 'true'
                : serverThemePreference;

            // Apply theme immediately
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }

            console.log('PROJECT WORKFLOW: Applied theme', shouldBeDark ? 'DARK' : 'LIGHT', '(localStorage:', localStorageTheme, 'database:', serverThemePreference, ')');
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