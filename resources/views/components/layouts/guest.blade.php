<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
            // SIMPLIFIED THEME - No automatic switching
            (function() {
                // Block system preference changes
                if (window.matchMedia && !window.__matchMediaOverridden) {
                    const originalMatchMedia = window.matchMedia;
                    window.matchMedia = function(query) {
                        const result = originalMatchMedia.call(this, query);
                        if (query.includes('prefers-color-scheme')) {
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