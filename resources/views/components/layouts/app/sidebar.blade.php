<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')

    <style>
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
                console.log('=== theme-changed EVENT RECEIVED ===');
                console.log('Event detail isDark:', isDark);
                console.log('Current dark class before change:', document.documentElement.classList.contains('dark'));

                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.setItem('darkMode', isDark ? 'true' : 'false');

                console.log('Updated localStorage darkMode to:', isDark ? 'true' : 'false');
                console.log('Current dark class after change:', document.documentElement.classList.contains('dark'));
                console.log('=== END theme-changed EVENT ===\n');
            });

            // Final verification after page fully loads
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const localStorageDarkMode = localStorage.getItem('darkMode');
                    const shouldBeDark = localStorageDarkMode === 'true';
                    const hasDarkClass = document.documentElement.classList.contains('dark');

                    console.log('=== FINAL THEME CHECK (window.load) ===');
                    console.log('localStorage darkMode:', localStorageDarkMode);
                    console.log('Should be dark:', shouldBeDark);
                    console.log('Has dark class:', hasDarkClass);

                    if (shouldBeDark !== hasDarkClass) {
                        console.warn('⚠️ FINAL CHECK: Mismatch detected! Correcting...');
                        if (shouldBeDark) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                        console.log('✓ Corrected dark class to match localStorage');
                    } else {
                        console.log('✓ Theme state is correct on final check');
                    }
                    console.log('=== END FINAL CHECK ===\n');
                }, 100); // Small delay to ensure everything else has run
            });
        })();
    </script>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
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

{{-- Keyboard Shortcuts Components - Load after Alpine is initialized --}}
<x-command-palette />
<x-keyboard-shortcuts-help />
</body>
</html>
