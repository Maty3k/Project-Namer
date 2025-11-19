<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AI-powered business name generator with instant domain availability checking. Find your perfect brand name in seconds.">
    <meta name="keywords" content="business name generator, brand name, domain checker, AI naming, startup naming">

    <title>Brandify - Find Your Perfect Brand Name with AI</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Dynamic theme CSS -->
    <link rel="stylesheet" href="{{ \App\Helpers\ThemeHelper::getThemeCssPath() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>

<body class="bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased">

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-lg border-b border-zinc-200 dark:border-zinc-800">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <div class="flex items-center gap-2">
                    <img src="/images/brandify-logo.png" alt="Brandify" class="h-8 w-auto">
                    <span class="text-xl font-bold">Brandify</span>
                </div>

                {{-- Auth Links --}}
                @if (Route::has('login'))
                    <div class="flex items-center gap-6">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                    Sign up
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </nav>

    {{-- Main Content Wrapper --}}
    <div class="min-h-screen flex flex-col">
        {{-- Spacer for fixed nav --}}
        <div class="h-16"></div>

            {{-- Hero Section --}}
        <section class="pt-8 pb-32 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-4xl">
            <div class="text-center space-y-8">
                {{-- Headline --}}
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight leading-tight">
                    Generate Perfect
                    <span class="gradient-text block mt-2">Brand Names</span>
                </h1>

                {{-- Subheadline --}}
                <p class="text-xl sm:text-2xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed">
                    Let AI craft distinctive, memorable names tailored to your vision. See which domains are ready to claim in real time.
                </p>

                {{-- CTA Button --}}
                <div class="pt-4">
                    <a href="{{ route('register') }}"
                       class="inline-block px-10 py-4 text-lg font-semibold text-purple-600 bg-white hover:bg-purple-50 rounded-xl transition-all duration-200 shadow-xl hover:shadow-2xl hover:scale-105 border-2 border-purple-200">
                        Get Started
                    </a>
                </div>

                {{-- Spacing (trust signals removed) --}}
                <div class="pt-6"></div>
            </div>
        </div>
        </section>

        {{-- Features Section --}}
        <main class="flex-grow">
            <section class="py-24 px-4 sm:px-6 lg:px-8 bg-zinc-50 dark:bg-zinc-800/50">
        <div class="container mx-auto max-w-6xl">
            <div class="grid md:grid-cols-3 gap-8 lg:gap-10">
                {{-- Feature 1 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <div class="flex flex-col items-center text-center space-y-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Smart Generation</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Powered by advanced AI technology that understands your business and creates names that truly resonate with your brand vision.
                        </p>
                    </div>
                </div>

                {{-- Feature 2 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <div class="flex flex-col items-center text-center space-y-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Real-Time Checks</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Verify domain availability instantly across popular extensions. No more falling in love with names you can't use.
                        </p>
                    </div>
                </div>

                {{-- Feature 3 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <div class="flex flex-col items-center text-center space-y-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">Visual Identity</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Generate logo concepts to visualize your brand. Get inspiration and bring your business identity to life quickly.
                        </p>
                    </div>
                </div>
            </div>
        </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
        <div class="container mx-auto max-w-6xl text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                &copy; {{ date('Y') }} Brandify • Powered by AI •
                <a href="{{ route('contact') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Contact</a>
            </p>
        </div>
        </footer>
    </div>

</body>
</html>
