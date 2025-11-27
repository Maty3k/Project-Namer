<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased">
        <div class="flex min-h-screen">
            <!-- Left Panel - Gradient Background with Branding -->
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900 relative overflow-hidden">
                <!-- Decorative shapes -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-20 left-20 w-72 h-72 bg-white rounded-full blur-3xl"></div>
                    <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
                </div>

                <!-- Content -->
                <div class="relative z-10 flex flex-col justify-center p-12 text-white w-full min-h-screen">
                    <div class="space-y-12">
                        <!-- Logo and Brand -->
                        <div>
                            <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-3 group">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center transition-transform group-hover:scale-110">
                                    <x-app-logo-icon class="w-7 h-7 text-white" />
                                </div>
                                <span class="text-2xl font-bold">{{ config('app.name', 'Laravel') }}</span>
                            </a>
                        </div>

                        <!-- Main Content -->
                        <div class="space-y-8">
                            <div class="space-y-4">
                                <h2 class="text-5xl font-bold leading-tight">
                                    Create amazing business names with AI
                                </h2>
                                <p class="text-xl text-white/90 leading-relaxed">
                                    Generate unique, brandable names for your next project in seconds.
                                </p>
                            </div>

                            <!-- Features -->
                            <div class="space-y-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg">AI-powered name generation</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg">Domain availability checking</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg">Logo design inspiration</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-sm text-white/70 pt-8">
                            © {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Auth Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:bg-slate-950">
                <div class="w-full max-w-md">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden mb-8 text-center">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex flex-col items-center gap-3 group">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 shadow-lg">
                                <x-app-logo-icon class="w-9 h-9 text-white" />
                            </div>
                            <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ config('app.name', 'Laravel') }}</span>
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
