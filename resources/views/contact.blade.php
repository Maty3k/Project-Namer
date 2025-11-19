<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Get in touch with Brandify. We'd love to hear from you.">

    <title>Contact - Brandify</title>

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
    </style>
</head>

<body class="bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased">

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-lg border-b border-zinc-200 dark:border-zinc-800">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="/images/brandify-logo.png" alt="Brandify" class="h-8 w-auto">
                    <span class="text-xl font-bold">Brandify</span>
                </a>

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

    {{-- Contact Section --}}
    <section class="pt-32 pb-24 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-2xl">
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-bold mb-4">Get in Touch</h1>
                <p class="text-lg text-zinc-600 dark:text-zinc-400">
                    Have a question or feedback? We'd love to hear from you.
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-sm border border-zinc-200 dark:border-zinc-700">
                <form action="#" method="POST" class="space-y-6">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all">
                    </div>

                    {{-- Subject --}}
                    <div>
                        <label for="subject" class="block text-sm font-medium mb-2">Subject</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all">
                    </div>

                    {{-- Message --}}
                    <div>
                        <label for="message" class="block text-sm font-medium mb-2">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all resize-none"></textarea>
                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <button
                            type="submit"
                            class="w-full px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl transition-all duration-200 shadow-xl hover:shadow-2xl hover:scale-105">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
        <div class="container mx-auto max-w-6xl text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                &copy; {{ date('Y') }} Brandify • Powered by AI •
                <a href="{{ route('contact') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">Contact</a>
            </p>
        </div>
    </footer>

</body>
</html>
