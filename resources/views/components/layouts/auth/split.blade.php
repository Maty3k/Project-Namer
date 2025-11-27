<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }} }"
      :class="{ 'dark': darkMode }">
    <head>
        @include('partials.head')
    </head>
    <body class="antialiased overflow-hidden">
        <div class="h-screen grid grid-cols-1 lg:grid-cols-2 relative">
            <!-- Zigzag Divider (Desktop Only) -->
            <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-16 -ml-8 z-20 pointer-events-none">
                <svg class="w-full h-full" viewBox="0 0 100 1000" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- White section -->
                    <polygon points="50,0 100,50 50,100 0,50" fill="white"/>
                    <!-- Black section -->
                    <polygon points="50,100 100,150 50,200 0,150" fill="black"/>
                    <!-- White section -->
                    <polygon points="50,200 100,250 50,300 0,250" fill="white"/>
                    <!-- Black section -->
                    <polygon points="50,300 100,350 50,400 0,350" fill="black"/>
                    <!-- White section -->
                    <polygon points="50,400 100,450 50,500 0,450" fill="white"/>
                    <!-- Black section -->
                    <polygon points="50,500 100,550 50,600 0,550" fill="black"/>
                    <!-- White section -->
                    <polygon points="50,600 100,650 50,700 0,650" fill="white"/>
                    <!-- Black section -->
                    <polygon points="50,700 100,750 50,800 0,750" fill="black"/>
                    <!-- White section -->
                    <polygon points="50,800 100,850 50,900 0,850" fill="white"/>
                    <!-- Black section -->
                    <polygon points="50,900 100,950 50,1000 0,950" fill="black"/>
                </svg>
            </div>

            <!-- Left Panel - Black Background (Desktop Only) -->
            <div class="hidden lg:flex flex-col justify-center items-center bg-black text-white relative overflow-hidden">
                <div class="w-full max-w-2xl px-20 py-16 -mt-32">

                <!-- Content -->
                <div class="relative z-10 space-y-24">
                    <!-- Logo -->
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-4 group">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center transition-all group-hover:scale-110 group-hover:bg-white/30">
                            <x-app-logo-icon class="w-9 h-9" />
                        </div>
                        <span class="text-4xl font-bold">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <!-- Hero Text -->
                    <div>
                        <h1 class="text-6xl font-bold leading-tight">
                            Generate Perfect Business Names
                        </h1>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid gap-24">
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-white/60 leading-none">•</span>
                            <div>
                                <h3 class="text-2xl font-semibold mb-2">Lightning Fast</h3>
                                <p class="text-lg text-white/80">Generate hundreds of names in seconds with AI</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-white/60 leading-none">•</span>
                            <div>
                                <h3 class="text-2xl font-semibold mb-2">Domain Ready</h3>
                                <p class="text-lg text-white/80">Instant availability checking across TLDs</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-white/60 leading-none">•</span>
                            <div>
                                <h3 class="text-2xl font-semibold mb-2">Logo Inspiration</h3>
                                <p class="text-lg text-white/80">Get AI-generated logo concepts instantly</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Right Panel - White Background -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-center bg-white overflow-y-auto">
                <div class="w-full max-w-md mx-auto px-6 py-8 lg:px-8 lg:py-12">
                    <!-- Mobile Logo -->
                    <div class="lg:hidden mb-8 text-center">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex flex-col items-center gap-3">
                            <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl">
                                <x-app-logo-icon class="w-8 h-8 text-white" />
                            </div>
                            <span class="text-2xl font-bold text-slate-900">{{ config('app.name', 'Laravel') }}</span>
                        </a>
                    </div>

                    <!-- Form Content -->
                    <div class="flex-1 flex items-center lg:block">
                        <div class="w-full">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
