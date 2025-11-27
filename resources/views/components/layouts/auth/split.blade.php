<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')
    </head>
    <body class="antialiased overflow-hidden">
        <div class="h-screen grid lg:grid-cols-2">
            <!-- Left Panel - Brand & Features -->
            <div class="hidden lg:flex flex-col justify-center bg-indigo-600 text-white relative overflow-hidden">
                <div class="px-16 py-12">

                <!-- Content -->
                <div class="relative z-10 max-w-lg">
                    <!-- Logo -->
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-3 group mb-16">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-white/30">
                            <x-app-logo-icon class="w-8 h-8" />
                        </div>
                        <span class="text-3xl font-bold">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <!-- Hero Text -->
                    <h1 class="text-6xl font-bold mb-6 leading-tight">
                        Generate Perfect Business Names
                    </h1>
                    <p class="text-2xl text-white/90 mb-12 leading-relaxed">
                        AI-powered naming that finds available domains in seconds
                    </p>

                    <!-- Features Grid -->
                    <div class="grid gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-1">Lightning Fast</h3>
                                <p class="text-white/80">Generate hundreds of names in seconds with AI</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-1">Domain Ready</h3>
                                <p class="text-white/80">Instant availability checking across TLDs</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold mb-1">Logo Inspiration</h3>
                                <p class="text-white/80">Get AI-generated logo concepts instantly</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Right Panel - Auth Form -->
            <div class="flex items-center justify-center bg-white dark:bg-slate-950 overflow-y-auto">
                <div class="px-8 py-12 w-full max-w-md mx-auto">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden mb-12 text-center">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex flex-col items-center gap-4">
                            <div class="w-20 h-20 bg-gradient-to-br from-indigo-600 to-pink-600 rounded-3xl flex items-center justify-center shadow-xl">
                                <x-app-logo-icon class="w-10 h-10 text-white" />
                            </div>
                            <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ config('app.name', 'Laravel') }}</span>
                        </a>
                    </div>

                    <!-- Form Content -->
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
