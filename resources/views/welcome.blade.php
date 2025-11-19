<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AI-powered business name generator with instant domain availability checking. Find your perfect brand name in seconds.">
    <meta name="keywords" content="business name generator, brand name, domain checker, AI naming, startup naming">

    <title>Project Namer - Find Your Perfect Brand Name with AI</title>

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

        .gradient-border {
            position: relative;
        }

        .gradient-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
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
                    <img src="/images/brandify-logo.png" alt="Project Namer" class="h-8 w-auto">
                    <span class="text-xl font-bold">Project Namer</span>
                </div>

                {{-- Auth Links --}}
                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-6 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-lg transition-all duration-200 shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40">
                                    Get Started Free
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center space-y-8">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 rounded-full border border-purple-200 dark:border-purple-800">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-300">100% Free & Open Source</span>
                </div>

                {{-- Headline --}}
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight">
                    Find Your Perfect
                    <span class="gradient-text block mt-2">Brand Name</span>
                </h1>

                {{-- Subheadline --}}
                <p class="text-xl sm:text-2xl text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto leading-relaxed">
                    Stop wasting hours brainstorming. Get AI-powered business names with
                    <span class="font-semibold text-zinc-900 dark:text-white">instant domain availability</span>
                    in seconds.
                </p>

                {{-- Primary CTA --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                    <a href="{{ route('register') }}"
                       class="w-full sm:w-auto px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-xl transition-all duration-200 shadow-xl shadow-purple-500/30 hover:shadow-purple-500/50 hover:scale-105">
                        Start Naming Free →
                    </a>
                    <a href="#how-it-works"
                       class="w-full sm:w-auto px-8 py-4 text-lg font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-xl transition-all duration-200">
                        See How It Works
                    </a>
                </div>

                {{-- Trust Signals --}}
                <div class="flex flex-wrap items-center justify-center gap-6 pt-8 text-sm text-zinc-500 dark:text-zinc-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>No credit card required</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Free forever</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>10,000+ names generated</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Benefits Section --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-zinc-50 dark:bg-zinc-800/50">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Why Choose Project Namer?</h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-400">Everything you need to find the perfect brand name</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Benefit 1 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">AI-Powered Generation</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Generate 50+ creative, brandable names in seconds using advanced AI models. No more hours of brainstorming.
                    </p>
                </div>

                {{-- Benefit 2 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Instant Domain Check</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        See .com, .io, .co, and .net availability instantly. No more manual checking across multiple sites.
                    </p>
                </div>

                {{-- Benefit 3 --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Logo Inspiration</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Get AI-generated logo ideas for your favorite names. Visualize your brand before you build it.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    <section id="how-it-works" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">How It Works</h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-400">Get your perfect name in 3 simple steps</p>
            </div>

            <div class="grid md:grid-cols-3 gap-12 relative">
                {{-- Connection Lines (hidden on mobile) --}}
                <div class="hidden md:block absolute top-16 left-0 right-0 h-0.5 bg-gradient-to-r from-purple-200 via-purple-400 to-indigo-200 dark:from-purple-800 dark:via-purple-600 dark:to-indigo-800" style="top: 4rem;"></div>

                {{-- Step 1 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg relative z-10">
                        <span class="text-2xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Describe Your Business</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Tell us about your business idea, industry, or the vibe you're going for.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg relative z-10">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">AI Generates Names</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Our AI creates 50+ unique, brandable names tailored to your description.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg relative z-10">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Pick & Launch</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Choose your favorite name, check domain availability, and start building!
                    </p>
                </div>
            </div>

            {{-- CTA --}}
            <div class="text-center mt-16">
                <a href="{{ route('register') }}"
                   class="inline-block px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-xl transition-all duration-200 shadow-xl shadow-purple-500/30 hover:shadow-purple-500/50 hover:scale-105">
                    Start Finding Names →
                </a>
            </div>
        </div>
    </section>

    {{-- Trust Builders Section --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
        <div class="container mx-auto max-w-4xl">
            <div class="bg-white dark:bg-zinc-900 rounded-3xl p-8 sm:p-12 shadow-2xl">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold mb-4">Built for Indie Hackers, By Developers</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6 leading-relaxed">
                            We understand the struggle of finding the perfect name while building your startup.
                            That's why Project Namer is 100% free and open-source. No hidden fees, no credit card required,
                            no BS. Just a tool that works, built by people who've been there.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-zinc-700 dark:text-zinc-300">Open Source on GitHub</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-zinc-700 dark:text-zinc-300">Privacy-First Design</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-zinc-700 dark:text-zinc-300">No Data Selling</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-3xl">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-4">
                {{-- FAQ 1 --}}
                <details class="group bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                    <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-lg">
                        <span>Is Project Namer really free?</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Yes! Project Namer is 100% free with no hidden costs. We believe in making naming tools accessible to everyone,
                        especially indie hackers and startups who are just getting started.
                    </p>
                </details>

                {{-- FAQ 2 --}}
                <details class="group bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                    <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-lg">
                        <span>Do I need a credit card to sign up?</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Nope! No credit card required. Just sign up with your email and start generating names immediately.
                    </p>
                </details>

                {{-- FAQ 3 --}}
                <details class="group bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                    <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-lg">
                        <span>How does the domain checking work?</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        We check domain availability in real-time across popular TLDs (.com, .io, .co, .net).
                        This saves you from manually checking each name on multiple domain registrar sites.
                    </p>
                </details>

                {{-- FAQ 4 --}}
                <details class="group bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                    <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-lg">
                        <span>Can I export my naming results?</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Absolutely! You can export your results as PDF, CSV, or share them via a public link with your team or stakeholders.
                    </p>
                </details>

                {{-- FAQ 5 --}}
                <details class="group bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                    <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-lg">
                        <span>Is my data private and secure?</span>
                        <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Yes! We take privacy seriously. Your business ideas and generated names are private to your account.
                        We never sell your data or use it for any purpose other than providing you the service.
                    </p>
                </details>
            </div>
        </div>
    </section>

    {{-- Final CTA Section --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-purple-600 to-indigo-600 text-white">
        <div class="container mx-auto max-w-4xl text-center">
            <h2 class="text-4xl sm:text-5xl font-bold mb-6">Ready to Name Your Next Big Idea?</h2>
            <p class="text-xl sm:text-2xl mb-8 text-purple-100">
                Join thousands of entrepreneurs who've found their perfect brand name
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto px-10 py-5 text-lg font-bold text-purple-600 bg-white hover:bg-purple-50 rounded-xl transition-all duration-200 shadow-xl hover:shadow-2xl hover:scale-105">
                    Get Started Free →
                </a>
                <a href="{{ route('login') }}"
                   class="w-full sm:w-auto px-10 py-5 text-lg font-bold text-white bg-purple-700/50 hover:bg-purple-700 rounded-xl transition-all duration-200 border-2 border-white/30 hover:border-white/50">
                    I Have an Account
                </a>
            </div>
            <p class="mt-6 text-purple-200 text-sm">No credit card required • Free forever • Get started in 30 seconds</p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-12 px-4 sm:px-6 lg:px-8 bg-zinc-900 dark:bg-zinc-950 text-zinc-400">
        <div class="container mx-auto max-w-6xl">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                {{-- Column 1 --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <img src="/images/brandify-logo.png" alt="Project Namer" class="h-8 w-auto">
                        <span class="text-lg font-bold text-white">Project Namer</span>
                    </div>
                    <p class="text-sm">AI-powered naming for your next big idea.</p>
                </div>

                {{-- Column 2 --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Pricing</a></li>
                    </ul>
                </div>

                {{-- Column 3 --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">GitHub</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                {{-- Column 4 --}}
                <div>
                    <h4 class="font-semibold text-white mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-zinc-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm">&copy; {{ date('Y') }} Project Namer. All rights reserved.</p>
                <p class="text-sm">Made with ❤️ for indie hackers</p>
            </div>
        </div>
    </footer>

</body>
</html>
