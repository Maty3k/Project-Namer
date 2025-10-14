<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')

    <style>
        /* Nuclear option - remove ALL top spacing from sidebar */
        [data-flux-sidebar],
        [data-flux-sidebar] *,
        aside[data-flux-sidebar],
        aside[data-flux-sidebar] * {
            padding-top: 0 !important;
            margin-top: 0 !important;
        }

        /* Specifically target the sidebar container */
        aside[data-flux-sidebar] {
            padding: 0 !important;
        }

        /* Re-add minimal horizontal padding only */
        [data-flux-sidebar] > * {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            padding-top: 0 !important;
            margin-top: 0 !important;
        }

        /* First element should be at absolute top */
        [data-flux-sidebar] > *:first-child {
            padding-top: 0.25rem !important;
            margin-top: 0 !important;
        }

        /* Override any Flux utility classes */
        .pt-0, .pt-1, .pt-2, .pt-3, .pt-4, .pt-5, .pt-6,
        .py-0, .py-1, .py-2, .py-3, .py-4, .py-5, .py-6,
        .p-0, .p-1, .p-2, .p-3, .p-4, .p-5, .p-6 {
            padding-top: 0 !important;
        }
    </style>

    <script>
        // SIMPLIFIED THEME - No automatic switching, only manual changes from theme customizer
        (function() {
            // Block system preference changes
            if (window.matchMedia && !window.__matchMediaOverridden) {
                const originalMatchMedia = window.matchMedia;
                window.matchMedia = function(query) {
                    const result = originalMatchMedia.call(this, query);
                    if (query.includes('prefers-color-scheme')) {
                        // Block automatic system preference changes
                        return {
                            matches: false,
                            addEventListener: () => {},
                            removeEventListener: () => {}
                        };
                    }
                    return result;
                };
                window.__matchMediaOverridden = true;
            }


            // Listen for theme changes from ThemeCustomizer ONLY
            window.addEventListener('theme-changed', function(event) {
                const isDark = event.detail.isDark;
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.setItem('darkMode', isDark ? 'true' : 'false');
                console.log('Theme changed manually:', isDark ? 'DARK' : 'LIGHT');
            });
        })();
    </script>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>

    <x-sidebar-top-menu/>

    <flux:spacer />

    <x-desktop-user-menu/>
</flux:sidebar>

<x-mobile-user-menu/>

{{ $slot }}

<!-- Toast Notifications -->
@livewire('toastnotifications')

{{-- Keyboard Shortcuts Components --}}
<x-command-palette />
<x-keyboard-shortcuts-help />

{{-- Undo Toast Component --}}
<x-undo-toast />

{{-- Inject disabled shortcuts for authenticated users --}}
@auth
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Inject disabled shortcuts directly into window for immediate access
    window.__disabledShortcuts = @json(\App\Models\UserKeyboardShortcut::findOrCreateForUser(auth()->id())->disabled_shortcuts ?? []);
    console.log('[Disabled Shortcuts] Injected from server:', window.__disabledShortcuts);
</script>
@endauth

@fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
