<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ \App\Helpers\ThemeHelper::isDarkMode() ? 'dark' : '' }}">
<head>
    @include('partials.head')

    <style>
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

        /* Mobile Sidebar Styling - Clean z-index layering */
        @media (max-width: 1023px) {
            /* Sidebar appears above mobile header (z-50) when open */
            aside[data-flux-sidebar] {
                z-index: 60 !important;
                max-width: 280px !important;
                width: 280px !important;
                padding: 0.75rem !important;
            }
        }

        /* Extra narrow screens - even smaller sidebar */
        @media (max-width: 500px) {
            aside[data-flux-sidebar] {
                max-width: 240px !important;
                width: 240px !important;
                padding: 0.5rem !important;
            }
        }

        /* Compact sidebar content on mobile */
        @media (max-width: 1023px) {
            /* Make logo smaller */
            [data-flux-sidebar] a[href*="dashboard"] {
                margin-right: 0.5rem !important;
            }

            /* Compact navigation items */
            [data-flux-navlist-item] {
                padding: 0.5rem !important;
                font-size: 0.875rem !important;
                gap: 0.5rem !important;
            }

            /* Make icons smaller */
            [data-flux-navlist-item] svg {
                width: 1rem !important;
                height: 1rem !important;
            }

            /* Compact group headings */
            [data-flux-navlist-group] > span {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            /* Reduce spacing between groups */
            [data-flux-navlist-group] {
                gap: 0.25rem !important;
            }

            /* Make user menu more compact */
            [data-flux-profile] {
                padding: 0.5rem !important;
                font-size: 0.875rem !important;
            }

            /* Compact separator */
            [data-flux-separator] {
                margin: 0.5rem 0 !important;
            }
        }

        /* Hide sidebar toggle button on desktop */
        @media (min-width: 1024px) {
            [data-flux-sidebar-toggle] {
                display: none !important;
            }
        }

        /* Prevent horizontal overflow on mobile that would affect fixed positioning */
        @media (max-width: 1023px) {
            html {
                overflow-x: hidden !important;
                overflow-y: auto !important;
                width: 100vw !important;
                max-width: 100vw !important;
                position: relative !important;
            }

            body {
                overflow-x: hidden !important;
                overflow-y: auto !important;
                max-width: 100vw !important;
                width: 100vw !important;
                position: relative !important;
                margin: 0 !important;
            }

            /* Ensure main content doesn't overflow */
            [data-flux-main] {
                max-width: 100vw !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }

            /* Ensure sidebar doesn't cause overflow when closed */
            [data-flux-sidebar] {
                max-width: 100vw !important;
                box-sizing: border-box !important;
            }

            /* Lock all direct children of body to viewport width */
            body > * {
                max-width: 100vw !important;
                box-sizing: border-box !important;
            }
        }

        /* Extra small screens - nuclear option to prevent any overflow */
        @media (max-width: 500px) {
            * {
                max-width: 100vw !important;
                box-sizing: border-box !important;
            }

            html, body {
                overflow-x: hidden !important;
                overflow-y: auto !important;
                width: 100vw !important;
                max-width: 100vw !important;
                position: relative !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
<flux:sidebar sticky collapsible="mobile" close-on-navigate class="border-e border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
    <a href="{{ route('dashboard') }}" class="me-3 lg:me-5 flex items-center space-x-1 lg:space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo class="scale-75 lg:scale-100" />
    </a>

    <x-desktop-user-menu class="mt-4" />

    <flux:separator class="my-4" />

    <x-sidebar-top-menu/>

    <flux:spacer />
</flux:sidebar>

<x-mobile-user-menu/>

<flux:main class="min-h-screen w-full">
    <div class="w-full px-2 sm:px-4 md:px-6 lg:px-8">
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

{{-- Ensure sidebar starts closed on mobile and closes on form submit --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
document.addEventListener('DOMContentLoaded', function() {
    // Close sidebar on mobile on page load
    if (window.innerWidth < 1024) {
        const sidebar = document.querySelector('[data-flux-sidebar]');
        if (sidebar) {
            // Dispatch close event to Flux sidebar
            window.dispatchEvent(new CustomEvent('flux-sidebar-close'));
        }
    }

    // Close sidebar when any form is submitted on mobile
    document.addEventListener('submit', function(e) {
        if (window.innerWidth < 1024) {
            const sidebar = document.querySelector('[data-flux-sidebar]');
            if (sidebar) {
                window.dispatchEvent(new CustomEvent('flux-sidebar-close'));
            }
        }
    });

    // Close sidebar on Livewire navigation on mobile
    document.addEventListener('livewire:navigating', function() {
        if (window.innerWidth < 1024) {
            window.dispatchEvent(new CustomEvent('flux-sidebar-close'));
        }
    });
});
</script>
</body>
</html>
