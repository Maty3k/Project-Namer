<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => \App\Helpers\ThemeHelper::isDarkMode()])>
<head>
    @include('partials.head')

    @php
        $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
        $themeVars = $userTheme ? $userTheme->generateCssVariables($userTheme->is_dark_mode) : [];
    @endphp

    <!-- Dynamic Theme CSS Variables -->
    <style id="theme-css-variables">
        :root {
            @if($userTheme)
                /* Standard CSS variables */
                --primary-color: {{ $userTheme->primary_color }};
                --accent-color: {{ $userTheme->accent_color ?? $userTheme->primary_color }};
                --background-color: {{ $userTheme->is_dark_mode ? ($userTheme->dark_background_color ?? $userTheme->background_color) : $userTheme->background_color }};
                --text-color: {{ $userTheme->is_dark_mode ? ($userTheme->dark_text_primary_color ?? $userTheme->text_color) : $userTheme->text_color }};
                --surface-color: {{ $userTheme->is_dark_mode ? ($userTheme->dark_surface_color ?? '#374151') : ($userTheme->surface_color ?? '#f8fafc') }};
                --text-secondary-color: {{ $userTheme->is_dark_mode ? ($userTheme->dark_text_secondary_color ?? '#d1d5db') : ($userTheme->text_secondary_color ?? '#6b7280') }};
                --text-muted-color: {{ $userTheme->is_dark_mode ? '#9ca3af' : '#6b7280' }};
                --success-color: {{ $userTheme->is_dark_mode ? '#22c55e' : '#059669' }};
                --danger-color: {{ $userTheme->is_dark_mode ? '#ef4444' : '#dc2626' }};
                --danger-bg-hover: {{ $userTheme->is_dark_mode ? '#7f1d1d' : '#fef2f2' }};

                /* Alternative naming for compatibility */
                --color-primary: {{ $userTheme->primary_color }};
                --color-accent: {{ $userTheme->accent_color ?? $userTheme->primary_color }};
                --color-background: {{ $userTheme->is_dark_mode ? ($userTheme->dark_background_color ?? $userTheme->background_color) : $userTheme->background_color }};
                --color-surface: {{ $userTheme->is_dark_mode ? ($userTheme->dark_surface_color ?? '#374151') : ($userTheme->surface_color ?? '#f8fafc') }};
                --color-text-primary: {{ $userTheme->is_dark_mode ? ($userTheme->dark_text_primary_color ?? $userTheme->text_color) : $userTheme->text_color }};
                --color-text-secondary: {{ $userTheme->is_dark_mode ? ($userTheme->dark_text_secondary_color ?? '#d1d5db') : ($userTheme->text_secondary_color ?? '#6b7280') }};

                @foreach($themeVars as $property => $value)
                {{ $property }}: {{ $value }};
                @endforeach
            @else
                /* Default theme variables */
                --primary-color: #3b82f6;
                --accent-color: #10b981;
                --background-color: #ffffff;
                --text-color: #111827;
                --surface-color: #f8fafc;
                --text-secondary-color: #6b7280;
                --text-muted-color: #6b7280;
                --success-color: #059669;
                --danger-color: #dc2626;
                --danger-bg-hover: #fef2f2;

                /* Alternative naming for compatibility */
                --color-primary: #3b82f6;
                --color-accent: #10b981;
                --color-background: #ffffff;
                --color-surface: #f8fafc;
                --color-text-primary: #111827;
                --color-text-secondary: #6b7280;
            @endif
        }
    </style>

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

                // Function to update CSS variables dynamically
                window.updateThemeCssVariables = function(colors) {
                    console.log('UPDATING CSS VARIABLES:', colors);
                    const styleElement = document.getElementById('theme-css-variables');
                    if (styleElement) {
                        const css = `:root {
                            --primary-color: ${colors.primaryColor || '#3b82f6'};
                            --accent-color: ${colors.accentColor || colors.primaryColor || '#10b981'};
                            --background-color: ${colors.backgroundColor || '#ffffff'};
                            --text-color: ${colors.textColor || '#111827'};
                            --surface-color: ${colors.surfaceColor || (colors.isDarkMode ? '#374151' : '#f8fafc')};
                        }`;
                        styleElement.innerHTML = css;
                    }
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

            // SMART mutation observer - only block truly unauthorized changes
            const observer = new MutationObserver(function(mutations) {
                if (!window.__themeProtectionEnabled) return;

                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const currentIsDark = document.documentElement.classList.contains('dark');

                        // Get expected theme - prioritize currentThemePreference which is updated by theme customizer
                        let expectedTheme;
                        if (window.currentThemePreference !== undefined) {
                            expectedTheme = window.currentThemePreference;
                        } else {
                            const localStorageTheme = localStorage.getItem('darkMode');
                            if (localStorageTheme !== null) {
                                expectedTheme = localStorageTheme === 'true';
                            } else {
                                expectedTheme = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};
                            }
                        }

                        // Check if this is an authorized change (longer window for theme customizer)
                        const isAuthorized = window.__authorizedThemeChange ||
                                           (Date.now() - window.__lastAuthorizedChange < 5000);

                        // Only block if there's a real mismatch and it's not authorized
                        if (currentIsDark !== expectedTheme && !isAuthorized) {
                            console.log('UNAUTHORIZED theme change blocked! Restoring:', expectedTheme ? 'DARK' : 'LIGHT');

                            // Temporarily disable protection to avoid loop
                            window.__themeProtectionEnabled = false;

                            if (expectedTheme) {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('darkMode', 'true');
                            } else {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('darkMode', 'false');
                            }

                            // Re-enable protection after a short delay
                            setTimeout(() => {
                                window.__themeProtectionEnabled = true;
                            }, 100);
                        } else if (currentIsDark !== expectedTheme && isAuthorized) {
                            // This is an authorized change, update our expectation
                            window.currentThemePreference = currentIsDark;
                            localStorage.setItem('darkMode', currentIsDark ? 'true' : 'false');
                        }
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });

            // SMART monitoring: Only correct unauthorized changes (less frequent)
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

                    // Only correct if unauthorized and different (longer authorization window)
                    const isAuthorized = window.__authorizedThemeChange ||
                                       (Date.now() - window.__lastAuthorizedChange < 5000);

                    if (currentIsDark !== expectedTheme && !isAuthorized) {
                        console.log('SMART correction applied:', expectedTheme ? 'DARK' : 'LIGHT');

                        // Temporarily disable protection to avoid triggering mutation observer
                        window.__themeProtectionEnabled = false;

                        if (expectedTheme) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('darkMode', 'true');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('darkMode', 'false');
                        }

                        // Re-enable protection after a short delay
                        setTimeout(() => {
                            window.__themeProtectionEnabled = true;
                        }, 100);
                    }
                }
            }, 2000); // Check every 2 seconds (less frequent)

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
<body class="min-h-screen" style="background-color: var(--background-color)">
<flux:sidebar sticky stashable class="border-e" style="border-color: var(--text-secondary-color, #d1d5db); background-color: var(--surface-color)">
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
