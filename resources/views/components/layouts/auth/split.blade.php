<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => \App\Helpers\ThemeHelper::isDarkMode()])>
    <head>
        @include('partials.head')

        <script>
            // AGGRESSIVE THEME LOCK - Completely disable automatic switching (AUTH)
            (function() {
                try {
                    // Get ONLY the server's authoritative theme preference (ignore everything else)
                    const SERVER_THEME_PREFERENCE = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

                    console.log('AUTH THEME LOCK: Server preference is', SERVER_THEME_PREFERENCE ? 'DARK' : 'LIGHT');

                    // Apply theme immediately and lock it permanently
                    const applyAndLockTheme = () => {
                        if (SERVER_THEME_PREFERENCE) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('darkMode', 'true');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('darkMode', 'false');
                        }
                    };

                    applyAndLockTheme();

                    // Lock theme permanently - NO exceptions
                    window.__THEME_LOCKED_PERMANENTLY = true;
                    window.__SERVER_THEME_PREFERENCE = SERVER_THEME_PREFERENCE;
                    window.__themeIsLocked = true;
                    window.__lockedTheme = SERVER_THEME_PREFERENCE;

                    // Disable all possible sources of theme changes
                    window.__allowThemeChange = false;

                } catch (error) {
                    console.warn('Auth theme lock initialization failed:', error);
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

                // Monitor for unwanted theme changes and revert them
                const observer = new MutationObserver(function(mutations) {
                    if (!window.__themeIsLocked) return;

                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const isDark = document.documentElement.classList.contains('dark');
                            let shouldBeDark = window.__lockedTheme;

                            // Check if we're in theme preservation mode and use preserved theme
                            if (window.__themePreservationMode && window.__preservedTheme !== undefined) {
                                shouldBeDark = window.__preservedTheme;
                            }

                            if (isDark !== shouldBeDark && !window.__allowThemeChange) {
                                if (shouldBeDark) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                            }
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });

                // Listen for legitimate theme changes from our components
                window.addEventListener('theme-changed', function(event) {
                    window.__allowThemeChange = true;
                    window.__lockedTheme = event.detail.isDark;
                    setTimeout(function() {
                        window.__allowThemeChange = false;
                    }, 100);
                });

                // Listen for theme consistency enforcement
                window.addEventListener('theme-consistency-enforced', function(event) {
                    window.__allowThemeChange = true;
                    window.__lockedTheme = event.detail.isDark;

                    // Update the preserved theme if in preservation mode
                    if (window.__themePreservationMode) {
                        window.__preservedTheme = event.detail.isDark;
                    }

                    setTimeout(function() {
                        window.__allowThemeChange = false;
                    }, 100);
                });
            })();
        </script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
                <div class="absolute inset-0 bg-neutral-900"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading>{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
