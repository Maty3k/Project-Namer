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
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="/images/brandify-logo.png" alt="Brandify" class="h-8 w-auto">
                    <span class="text-xl font-bold">Brandify</span>
                </a>

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
        <div class="h-24"></div>

        {{-- Contact Section --}}
        <main class="flex-grow py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
            <div class="container mx-auto max-w-6xl">
                <div class="grid lg:grid-cols-7 gap-12 lg:gap-16">
                    {{-- Left Column - Contact Info (2 columns) --}}
                    <div class="lg:col-span-2 space-y-8">
                        <div>
                            <h1 class="text-4xl sm:text-5xl font-bold mb-6">
                                Get in <span class="bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Touch</span>
                            </h1>
                            <p class="text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Have a question, feedback, or just want to say hello? We'd love to hear from you. Fill out the form and we'll get back to you as soon as possible.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Email us at</p>
                                    <p class="font-semibold">hello@brandify.com</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Response time</p>
                                    <p class="font-semibold">Within 24 hours</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column - Contact Form (5 columns) --}}
                    <div class="lg:col-span-5">
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 lg:p-10 shadow-xl border-2 border-purple-100 dark:border-purple-900/30">
                            <form action="#" method="POST" class="space-y-6">
                                @csrf

                                {{-- Name and Email Row --}}
                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="block text-sm font-semibold mb-2">Name</label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            required
                                            placeholder="John Doe"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all">
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-semibold mb-2">Email</label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            required
                                            placeholder="john@example.com"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all">
                                    </div>
                                </div>

                                {{-- Subject --}}
                                <div>
                                    <label for="subject" class="block text-sm font-semibold mb-2">Subject</label>
                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        required
                                        placeholder="How can we help?"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all">
                                </div>

                                {{-- Message --}}
                                <div>
                                    <label for="message" class="block text-sm font-semibold mb-2">Message</label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="6"
                                        required
                                        placeholder="Tell us more about your question or feedback..."
                                        class="w-full px-4 py-3 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all resize-none"></textarea>
                                </div>

                                {{-- Submit Button --}}
                                <div>
                                    <button
                                        type="submit"
                                        class="w-full px-8 py-4 text-lg font-bold text-purple-600 bg-white hover:bg-purple-50 rounded-xl transition-all duration-200 shadow-xl hover:shadow-2xl hover:scale-105 border-2 border-purple-200 flex items-center justify-center gap-3">
                                        <span>Send Message</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
