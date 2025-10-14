<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="{{ asset('css/smooth-animations.css') }}">
@fluxAppearance

@php
    $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
    $isDarkMode = \App\Helpers\ThemeHelper::isDarkMode();
@endphp

@if($userTheme)
<style>
    /* Global theme color overrides - apply user's custom theme to all hardcoded colors */
    :root {
        --theme-bg: {{ $userTheme->background_color }};
        --theme-surface: {{ $userTheme->surface_color ?? ($isDarkMode ? '#1f2937' : '#f8fafc') }};
        --theme-text: {{ $userTheme->text_color }};
        --theme-primary: {{ $userTheme->primary_color }};
    }

    /* Override all gray/slate/zinc backgrounds with theme colors */
    .bg-gray-50, .bg-slate-50, .bg-zinc-50,
    .dark .bg-gray-50, .dark .bg-slate-50, .dark .bg-zinc-50 {
        background-color: var(--theme-surface) !important;
    }

    .bg-gray-100, .bg-slate-100, .bg-zinc-100,
    .dark .bg-gray-100, .dark .bg-slate-100, .dark .bg-zinc-100 {
        background-color: {{ $userTheme->primary_color }}10 !important;
    }

    .bg-gray-200, .bg-slate-200, .bg-zinc-200,
    .dark .bg-gray-200, .dark .bg-slate-200, .dark .bg-zinc-200 {
        background-color: {{ $userTheme->primary_color }}20 !important;
    }

    .bg-gray-700, .bg-slate-700, .bg-zinc-700,
    .dark .bg-gray-700, .dark .bg-slate-700, .dark .bg-zinc-700 {
        background-color: {{ $userTheme->primary_color }}15 !important;
    }

    .bg-gray-800, .bg-slate-800, .bg-zinc-800,
    .dark .bg-gray-800, .dark .bg-slate-800, .dark .bg-zinc-800 {
        background-color: {{ $userTheme->primary_color }}10 !important;
    }

    .bg-gray-900, .bg-slate-900, .bg-zinc-900,
    .dark .bg-gray-900, .dark .bg-slate-900, .dark .bg-zinc-900,
    .bg-white, .dark .bg-white {
        background-color: var(--theme-bg) !important;
    }

    /* Override text colors */
    .text-gray-900, .text-slate-900, .text-zinc-900,
    .dark .text-gray-100, .dark .text-slate-100, .dark .text-zinc-100,
    .dark .text-white, .text-white {
        color: var(--theme-text) !important;
    }

    .text-gray-600, .text-slate-600, .text-zinc-600,
    .text-gray-500, .text-slate-500, .text-zinc-500,
    .text-gray-400, .text-slate-400, .text-zinc-400,
    .dark .text-gray-600, .dark .text-slate-600, .dark .text-zinc-600,
    .dark .text-gray-500, .dark .text-slate-500, .dark .text-zinc-500,
    .dark .text-gray-400, .dark .text-slate-400, .dark .text-zinc-400 {
        color: var(--theme-text) !important;
        opacity: 0.7;
    }

    /* Override border colors */
    .border-gray-200, .border-slate-200, .border-zinc-200,
    .border-gray-300, .border-slate-300, .border-zinc-300,
    .dark .border-gray-700, .dark .border-slate-700, .dark .border-zinc-700,
    .dark .border-gray-600, .dark .border-slate-600, .dark .border-zinc-600 {
        border-color: {{ $userTheme->primary_color }}40 !important;
    }

    /* Override hover states */
    .hover\:bg-gray-50:hover, .hover\:bg-slate-50:hover, .hover\:bg-zinc-50:hover,
    .hover\:bg-gray-100:hover, .hover\:bg-slate-100:hover, .hover\:bg-zinc-100:hover,
    .dark .hover\:bg-gray-800:hover, .dark .hover\:bg-slate-800:hover, .dark .hover\:bg-zinc-800:hover,
    .dark .hover\:bg-gray-700:hover, .dark .hover\:bg-slate-700:hover, .dark .hover\:bg-zinc-700:hover {
        background-color: {{ $userTheme->primary_color }}15 !important;
    }
</style>
@endif
