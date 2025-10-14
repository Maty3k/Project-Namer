<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')

        @if(isset($title))
            <title>{{ $title }} - {{ config('app.name') }}</title>
        @endif

        @if(isset($metadata))
            @foreach($metadata as $property => $content)
                @if(str_starts_with($property, 'og:'))
                    <meta property="{{ $property }}" content="{{ $content }}">
                @elseif(str_starts_with($property, 'twitter:'))
                    <meta name="{{ $property }}" content="{{ $content }}">
                @elseif($property === 'description')
                    <meta name="description" content="{{ $content }}">
                @elseif($property === 'author')
                    <meta name="author" content="{{ $content }}">
                @endif
            @endforeach
        @endif

        <script>
            // Use localStorage as primary source (fast, prevents flash)
            (function() {
                try {
                    const localStorageTheme = localStorage.getItem('darkMode');
                    const serverThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

                    // Use localStorage if available, otherwise use server preference
                    const shouldBeDark = localStorageTheme !== null
                        ? localStorageTheme === 'true'
                        : serverThemePreference;

                    // Apply theme immediately
                    if (shouldBeDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('darkMode', 'true');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('darkMode', 'false');
                    }

                    // Lock the theme to prevent unwanted changes
                    window.__themeIsLocked = true;
                    window.__lockedTheme = shouldBeDark;
                } catch (error) {
                    console.warn('Dark mode initialization failed:', error);
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
                            const shouldBeDark = window.__lockedTheme;

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
            })();
        </script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-screen bg-white dark:bg-neutral-900">
            <!-- Simple header for public sharing -->
            <header class="border-b border-gray-200 dark:border-neutral-800">
                <div class="container mx-auto px-4 py-4">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('home') }}" class="flex items-center gap-2 font-medium" wire:navigate>
                            <x-app-logo-icon class="h-8 w-8 fill-current text-black dark:text-white" />
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ config('app.name') }}</span>
                        </a>
                        
                        <div class="text-sm text-gray-500 dark:text-neutral-400">
                            <a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-neutral-300">
                                Create Your Own
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        
        <!-- Toast Notifications -->
        <x-toast-container />
        
        @fluxScripts
    </body>
</html>