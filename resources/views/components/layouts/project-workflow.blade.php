<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 flex">
    
    <!-- Custom Project Sidebar -->
    <div class="flex-shrink-0">
        @livewire('sidebar', ['activeProjectUuid' => request()->route('uuid')])
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 px-6 py-4">
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

    @fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>