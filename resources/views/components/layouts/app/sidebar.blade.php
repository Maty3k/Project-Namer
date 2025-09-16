<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => \App\Helpers\ThemeHelper::isDarkMode()])>
<head>
    @include('partials.head')

    <script>
        // SMART THEME PROTECTION - Block automatic switching, allow intentional changes
        (function() {
            try {
                // Get server's theme preference
                let currentThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

                console.log('SMART THEME PROTECTION: Initial theme is', currentThemePreference ? 'DARK' : 'LIGHT');

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
                    const expectedTheme = window.currentThemePreference || {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

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
        })();

        // Add Livewire error handling
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

@fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
