<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 fade-in">
        <!-- Skip to main content link for accessibility -->
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <flux:sidebar sticky stashable class="glass shadow-soft-lg border-e border-zinc-200/50 bg-white/80 dark:border-zinc-700/50 dark:bg-zinc-900/90 backdrop-blur-xl
                                                xs:w-full
                                                sm:w-72
                                                md:w-80
                                                lg:w-72">
            <flux:sidebar.toggle class="btn-modern focus-modern
                                        xs:block
                                        lg:hidden" 
                                icon="x-mark" />

            <a href="{{ route('dashboard') }}" 
               class="interactive focus-modern me-5 flex items-center space-x-2 rtl:space-x-reverse p-2 rounded-lg
                      xs:justify-center
                      sm:justify-start" 
               wire:navigate>
                <x-app-logo class="slide-up" />
            </a>

            <nav class="slide-up" style="animation-delay: 0.1s;">
                <x-sidebar-top-menu/>
            </nav>

            <flux:spacer />

            <div class="slide-up" style="animation-delay: 0.3s;">
                <x-sidebar-bottom-menu/>
            </div>


            <div class="slide-up" style="animation-delay: 0.4s;">
                <x-desktop-user-menu/>
            </div>
        </flux:sidebar>


        <x-mobile-user-menu/>

        <main id="main-content" role="main">
            {{ $slot }}
        </main>

        <!-- Mobile Bottom Action Bar -->
        <x-mobile-bottom-bar />

        <!-- Toast Notifications -->
        <x-toast-container />

        @fluxScripts
    </body>
</html>
