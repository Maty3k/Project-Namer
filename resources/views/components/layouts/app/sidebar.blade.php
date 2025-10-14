<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => \App\Helpers\ThemeHelper::isDarkMode()])>
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
        // SMART THEME PROTECTION - Block automatic switching, allow intentional changes
        (function() {
            try {
                // Get server's theme preference
                const serverThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

                // Check localStorage for user's last saved preference (this takes priority)
                const savedTheme = localStorage.getItem('darkMode');

                // Use saved theme if it exists, otherwise use server preference
                let currentThemePreference;
                if (savedTheme !== null) {
                    currentThemePreference = savedTheme === 'true';
                    console.log('SMART THEME PROTECTION: Using saved theme from localStorage', currentThemePreference ? 'DARK' : 'LIGHT');
                } else {
                    currentThemePreference = serverThemePreference;
                    console.log('SMART THEME PROTECTION: Using server theme preference', currentThemePreference ? 'DARK' : 'LIGHT');
                }

                // Apply theme immediately
                const applyTheme = (isDark) => {
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('darkMode', 'true');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('darkMode', 'false');
                    }
                    currentThemePreference = isDark;
                };

                applyTheme(currentThemePreference);

                // Smart protection variables
                window.__themeProtectionEnabled = true;
                window.__authorizedThemeChange = false;
                window.__lastAuthorizedChange = 0;

                // Function to authorize legitimate theme changes
                window.authorizeThemeChange = function(newTheme, duration = 5000) {
                    console.log('THEME CHANGE AUTHORIZED:', newTheme ? 'DARK' : 'LIGHT');
                    window.__authorizedThemeChange = true;
                    window.__lastAuthorizedChange = Date.now();
                    currentThemePreference = newTheme;

                    applyTheme(newTheme);

                    // Auto-expire authorization
                    setTimeout(() => {
                        window.__authorizedThemeChange = false;
                        console.log('Theme change authorization expired');
                    }, duration);
                };

                // Override system preference detection (but allow manual overrides)
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

            } catch (error) {
                console.warn('Smart theme protection initialization failed:', error);
            }
        })();

        // Theme protection system - prevent automatic theme switching
        (function() {
            // Override any system preference changes after page load
            window.addEventListener('load', function() {
                const lockedTheme = window.__lockedTheme;
                if (window.__themeIsLocked && lockedTheme !== undefined) {
                    if (lockedTheme) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            });

            // SMART mutation observer - block unauthorized changes, allow legitimate ones
            const observer = new MutationObserver(function(mutations) {
                if (!window.__themeProtectionEnabled) return;

                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const currentIsDark = document.documentElement.classList.contains('dark');
                        const expectedTheme = window.currentThemePreference || {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

                        // Check if this is an authorized change
                        const isAuthorized = window.__authorizedThemeChange ||
                                           (Date.now() - window.__lastAuthorizedChange < 2000);

                        if (currentIsDark !== expectedTheme && !isAuthorized) {
                            console.log('UNAUTHORIZED theme change blocked! Restoring:', expectedTheme ? 'DARK' : 'LIGHT');

                            if (expectedTheme) {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('darkMode', 'true');
                            } else {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('darkMode', 'false');
                            }
                        }
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            // SMART monitoring: Only correct unauthorized changes
            setInterval(function() {
                if (window.__themeProtectionEnabled) {
                    const currentIsDark = document.documentElement.classList.contains('dark');

                    // Get expected theme with better fallback logic
                    let expectedTheme;
                    if (window.currentThemePreference !== undefined) {
                        expectedTheme = window.currentThemePreference;
                    } else {
                        // Check localStorage first before falling back to server preference
                        const localStorageTheme = localStorage.getItem('darkMode');
                        if (localStorageTheme !== null) {
                            expectedTheme = localStorageTheme === 'true';
                        } else {
                            expectedTheme = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};
                        }
                    }

                    // Only correct if unauthorized and different
                    const isAuthorized = window.__authorizedThemeChange ||
                                       (Date.now() - window.__lastAuthorizedChange < 2000);

                    if (currentIsDark !== expectedTheme && !isAuthorized) {
                        console.log('SMART correction applied:', expectedTheme ? 'DARK' : 'LIGHT');

                        if (expectedTheme) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('darkMode', 'true');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('darkMode', 'false');
                        }
                    }
                }
            }, 500); // Check every 500ms

            // SMART theme event listeners - allow legitimate changes
            window.addEventListener('theme-changed', function(event) {
                console.log('LEGITIMATE theme change received:', event.detail.isDark ? 'DARK' : 'LIGHT');
                // Authorize this theme change
                if (window.authorizeThemeChange) {
                    window.authorizeThemeChange(event.detail.isDark, 10000); // 10 second authorization
                }
            });

            window.addEventListener('theme-consistency-enforced', function(event) {
                console.log('Theme consistency enforced:', event.detail.isDark ? 'DARK' : 'LIGHT');
                // Authorize this consistency enforcement
                if (window.authorizeThemeChange) {
                    window.authorizeThemeChange(event.detail.isDark, 5000); // 5 second authorization
                }
            });

            // Add authorization for AI generation events that might affect DOM
            ['ai-generation-started', 'ai-generation-completed', 'ai-generation-failed'].forEach(function(eventName) {
                window.addEventListener(eventName, function() {
                    // Authorize current theme during AI events to prevent unwanted corrections
                    const currentIsDark = document.documentElement.classList.contains('dark');
                    if (window.authorizeThemeChange) {
                        window.authorizeThemeChange(currentIsDark, 5000); // 5 second authorization
                    }
                });
            });
        })();

        // Add Livewire error handling and theme preservation
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Livewire navigation errors
            window.addEventListener('livewire:error', function(event) {
                console.warn('Livewire error:', event.detail);
                // Fallback to regular navigation if Livewire fails
                if (event.detail?.message?.includes('createElement') || event.detail?.message?.includes('setAttribute')) {
                    window.location.reload();
                }
            });

            // Listen for theme changes and update localStorage
            window.addEventListener('theme-changed', function(event) {
                const isDark = event.detail.isDark;
                localStorage.setItem('darkMode', isDark ? 'true' : 'false');
                window.currentThemePreference = isDark;
            });

            // Preserve theme state during Livewire navigation
            window.addEventListener('livewire:navigating', function() {
                // Save current theme state before navigation
                const currentIsDark = document.documentElement.classList.contains('dark');
                localStorage.setItem('darkMode', currentIsDark ? 'true' : 'false');
                window.currentThemePreference = currentIsDark;

                // Authorize theme changes during navigation
                if (window.authorizeThemeChange) {
                    window.authorizeThemeChange(currentIsDark, 3000); // 3 second authorization
                }
            });

            // Restore theme state after Livewire navigation
            window.addEventListener('livewire:navigated', function() {
                setTimeout(function() {
                    const savedTheme = localStorage.getItem('darkMode');
                    if (savedTheme !== null) {
                        const isDark = savedTheme === 'true';
                        window.currentThemePreference = isDark;

                        // Apply saved theme if different from current
                        const currentIsDark = document.documentElement.classList.contains('dark');
                        if (currentIsDark !== isDark) {
                            if (window.authorizeThemeChange) {
                                window.authorizeThemeChange(isDark, 2000);
                            }
                            if (isDark) {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                        }
                    }
                }, 100); // Small delay to let DOM settle
            });
        });
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
