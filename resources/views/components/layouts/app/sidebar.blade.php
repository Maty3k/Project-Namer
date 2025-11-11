<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \App\Helpers\ThemeHelper::isDarkMode() ? 'dark' : '' }}">
<head>
    @include('partials.head')

    <style>
        /* PREVENT HORIZONTAL SCROLLING ON MOBILE */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
        }

        /* Prevent all elements from causing horizontal scroll */
        * {
            max-width: 100%;
        }

        /* Minimal sidebar styling - let Flux handle most of it */
        aside[data-flux-sidebar] {
            display: flex;
            flex-direction: column;
            /* Smooth background transitions to prevent flash */
            transition: background-color 0ms !important;
        }

        /* Ensure sidebar content doesn't overflow */
        [data-flux-sidebar] {
            overflow-y: auto;
            height: 100vh;
        }

        /* Add top padding to sidebar on mobile to account for fixed header */
        @media (max-width: 1023px) {
            [data-flux-sidebar] {
                padding-top: 96px !important; /* Increased from 80px to 96px */
                z-index: 10000 !important; /* Higher than mobile header */
                position: relative !important;
            }

            /* Ensure sidebar toggle button is visible above mobile header */
            [data-flux-sidebar] .lg\:hidden {
                position: relative !important;
                z-index: 10001 !important;
            }
        }

        /* Force immediate background color application - no transition delay */
        body, [data-flux-sidebar] {
            transition: background-color 0ms !important;
        }

        /* Prevent any background flash during Livewire navigation - SIDEBAR */
        html.dark [data-flux-sidebar] {
            background-color: rgb(24 24 27) !important; /* zinc-900 */
            border-color: rgb(63 63 70) !important; /* zinc-700 */
        }

        html:not(.dark) [data-flux-sidebar] {
            background-color: rgb(244 244 245) !important; /* zinc-100 */
            border-color: rgb(228 228 231) !important; /* zinc-200 */
        }

        /* Prevent any background flash during Livewire navigation - BODY */
        html.dark body {
            background-color: rgb(24 24 27) !important; /* zinc-900 */
        }

        html:not(.dark) body {
            background-color: rgb(250 250 250) !important; /* zinc-50 */
        }

        /* Force all main content areas to match */
        html.dark main,
        html.dark [data-flux-main],
        html.dark .content-area {
            background-color: rgb(24 24 27) !important; /* zinc-900 */
        }

        html:not(.dark) main,
        html:not(.dark) [data-flux-main],
        html:not(.dark) .content-area {
            background-color: rgb(250 250 250) !important; /* zinc-50 */
        }

        /* Aggressively override white backgrounds in dark mode - except themed boxes */
        html.dark .bg-white:not(.themed-create-box):not([style*="background-color"]),
        html.dark .bg-gray-50:not(.themed-create-box):not([style*="background-color"]),
        html.dark .bg-gray-100:not(.themed-create-box):not([style*="background-color"]) {
            background-color: rgb(24 24 27) !important; /* zinc-900 */
        }

        /* Force Livewire component wrappers to dark background */
        html.dark [wire\\:id] {
            background-color: transparent !important;
        }
    </style>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>

    <x-desktop-user-menu class="mt-4" />

    <flux:separator class="my-4" />

    <x-sidebar-top-menu/>

    <flux:spacer />
</flux:sidebar>

<x-mobile-user-menu/>

<flux:main class="min-h-screen w-full overflow-x-hidden">
    <div class="w-full max-w-full overflow-x-hidden px-2 sm:px-4 md:px-6 lg:px-8 pt-20 lg:pt-0">
        {{ $slot }}
    </div>
</flux:main>

<!-- Toast Notifications -->
@livewire('toastnotifications')

{{-- Undo Toast Component --}}
<x-undo-toast />

{{-- Inject disabled shortcuts for authenticated users --}}
@auth
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Inject disabled shortcuts directly into window for immediate access
    window.__disabledShortcuts = @json(\App\Models\UserKeyboardShortcut::findOrCreateForUser(auth()->id())->disabled_shortcuts ?? []);
</script>
@endauth

@fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])

{{-- Keyboard Shortcuts Components - Load after Alpine is initialized --}}
<x-command-palette />
<x-keyboard-shortcuts-help />
</body>
</html>
