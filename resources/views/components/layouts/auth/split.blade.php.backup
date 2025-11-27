<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')

        <script>
            // AGGRESSIVE THEME LOCK - Completely disable automatic switching (AUTH)
            (function() {
                try {
                    // Get ONLY the server's authoritative theme preference (ignore everything else)
                    const SERVER_THEME_PREFERENCE = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

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
                    // Silently fail - theme lock will use default
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
    <body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 antialiased dark:bg-gradient-to-br dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950">
        <div class="relative flex lg:grid h-screen lg:grid-cols-2">
            <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 relative hidden h-full flex-col p-10 text-white lg:flex dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/95 via-purple-600/95 to-pink-600/95 dark:from-indigo-950/95 dark:via-purple-950/95 dark:to-pink-950/95"></div>

                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium group transition-transform duration-300 hover:scale-105" wire:navigate>
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 backdrop-blur-sm transition-all duration-300 group-hover:bg-white/20 group-hover:shadow-lg">
                        <x-app-logo-icon class="me-2 h-8 fill-current text-white" />
                    </span>
                    <span class="ml-3 text-xl font-bold">{{ config('app.name', 'Laravel') }}</span>
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto space-y-8">
                    <div class="h-1 w-24 rounded-full bg-white/30"></div>
                    <blockquote class="space-y-4 min-h-[120px]">
                        <flux:heading size="lg" class="text-2xl font-light leading-relaxed text-white/95">
                            &ldquo;{{ trim($message) }}&rdquo;
                        </flux:heading>
                        <footer class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-white/20"></div>
                            <flux:heading class="text-sm font-medium text-white/80">{{ trim($author) }}</flux:heading>
                        </footer>
                    </blockquote>
                </div>

                <div class="absolute bottom-10 right-10 opacity-10">
                    <svg class="h-64 w-64" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M47.1,-57.1C59.9,-45.6,68.5,-28.9,71.4,-11.1C74.3,6.7,71.5,25.6,62.3,40.7C53.1,55.8,37.5,67.1,19.8,72.8C2.1,78.5,-17.7,78.6,-35.3,72.4C-52.9,66.2,-68.3,53.7,-76.2,37.4C-84.1,21.1,-84.5,1,-78.8,-16.5C-73.1,-34,-61.3,-48.9,-46.7,-60C-32.1,-71.1,-16.1,-78.4,0.7,-79.3C17.4,-80.2,34.3,-68.6,47.1,-57.1Z" transform="translate(100 100)" />
                    </svg>
                </div>
            </div>

            <div class="flex flex-1 items-center lg:justify-start justify-center p-6 lg:p-16 lg:pl-20 min-h-screen lg:min-h-0">
                <div class="w-full max-w-[420px] space-y-6">
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 font-medium lg:hidden mb-8 group" wire:navigate>
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-xl">
                            <x-app-logo-icon class="size-8 fill-current text-white" />
                        </span>
                        <span class="text-xl font-bold text-slate-900 dark:text-white">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <div class="rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-xl shadow-2xl dark:bg-slate-900/80 dark:border-slate-800 p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
