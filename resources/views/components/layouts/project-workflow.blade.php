<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \App\Helpers\ThemeHelper::isDarkMode() ? 'dark' : '' }}">
<head>
    @include('partials.head')
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