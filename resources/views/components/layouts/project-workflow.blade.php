@php
    $userTheme = \App\Helpers\ThemeHelper::getCurrentUserTheme();
    $isDarkMode = \App\Helpers\ThemeHelper::isDarkMode();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $isDarkMode ? 'dark' : '' }}">
<head>
    @include('partials.head')
    @if($userTheme)
        <style>
            :root {
                --color-primary: {{ $userTheme->primary_color }};
                --color-accent: {{ $userTheme->accent_color }};
                --color-background: {{ $userTheme->background_color }};
                --color-text: {{ $userTheme->text_color }};
                --color-surface: {{ $userTheme->surface_color }};
                --color-text-primary: {{ $userTheme->text_primary_color }};
                --color-text-secondary: {{ $userTheme->text_secondary_color }};
            }
            
            /* Apply theme colors immediately on page load */
            body {
                background: {{ $userTheme->background_color }} !important;
                color: {{ $userTheme->text_color }} !important;
            }
            
            /* Override any blue colors with theme primary - comprehensive coverage */
            [class*="text-blue"],
            [class*="bg-blue"], 
            [class*="border-blue"],
            .text-primary-600,
            .text-primary-800,
            .dark .text-blue-200,
            .dark .text-blue-100,
            .dark .text-blue-400,
            .bg-blue-200,
            .bg-blue-400,
            .bg-blue-50,
            .border-blue-400,
            .hover\\:border-blue-400:hover,
            .peer-checked\\:border-blue-400 {
                --tw-text-opacity: 1 !important;
                color: {{ $userTheme->primary_color }} !important;
            }
            
            [class*="bg-blue"],
            .bg-blue-200,
            .bg-blue-400,
            .bg-blue-50,
            .bg-primary-100,
            .bg-primary-50 {
                background-color: {{ $userTheme->primary_color }}33 !important;
            }
            
            [class*="border-blue"],
            .border-blue-400,
            .hover\\:border-blue-400:hover,
            .peer-checked\\:border-blue-400,
            .border-primary-500 {
                border-color: {{ $userTheme->primary_color }} !important;
            }
            
            /* Override specific blue shades */
            .bg-blue-400,
            .bg-primary-600,
            .dark .bg-blue-400 {
                background-color: {{ $userTheme->primary_color }} !important;
            }
            
            /* Override animate-ping blue elements */
            .animate-ping.bg-blue-400,
            .animate-pulse.bg-blue-400 {
                background-color: {{ $userTheme->primary_color }} !important;
            }
            
            /* Sidebar-specific styling - highest priority */
            .themed-sidebar {
                background-color: {{ $isDarkMode ? $userTheme->background_color : ($userTheme->surface_color ?? '#f8fafc') }} !important;
                color: {{ $userTheme->text_color }} !important;
                border-color: {{ $userTheme->primary_color }}50 !important;
            }
            
            /* Ensure sidebar never gets wrong background regardless of other CSS */
            @if(!$isDarkMode)
            /* Light mode - sidebar should be light */
            .themed-sidebar {
                background-color: {{ $userTheme->surface_color ?? '#f8fafc' }} !important;
            }
            /* Override any dark classes that might affect sidebar */
            .themed-sidebar,
            .themed-sidebar * {
                background-color: inherit !important;
            }
            .themed-sidebar [class*="bg-slate-"],
            .themed-sidebar [class*="bg-gray-900"],
            .themed-sidebar [class*="bg-black"] {
                background-color: transparent !important;
            }
            @endif
            
            /* Ensure text readability across all elements */
            @if($isDarkMode)
            /* Dark mode - ensure light text */
            body, main, div, p, h1, h2, h3, h4, h5, h6, span, label {
                color: {{ $userTheme->text_color }} !important;
            }
            
            /* Override any dark text in dark mode */
            [class*="text-gray-900"], [class*="text-gray-800"], [class*="text-gray-700"],
            [class*="text-black"], [class*="text-slate-900"], [class*="text-slate-800"] {
                color: {{ $userTheme->text_color }} !important;
            }
            @else
            /* Light mode - ensure dark text */
            body, main, div, p, h1, h2, h3, h4, h5, h6, span, label {
                color: {{ $userTheme->text_color }} !important;
            }
            
            /* Override any light text in light mode */
            [class*="text-white"], [class*="text-gray-100"], [class*="text-gray-200"],
            [class*="text-slate-100"], [class*="text-slate-200"] {
                color: {{ $userTheme->text_color }} !important;
            }
            @endif
            
            /* Force theme colors on Livewire components */
            [wire\\:id] {
                --color-primary: {{ $userTheme->primary_color }} !important;
                --color-accent: {{ $userTheme->accent_color }} !important;
                --color-background: {{ $userTheme->background_color }} !important;
                --color-text: {{ $userTheme->text_color }} !important;
            }
            
            /* Override all Flux button primary variant colors */
            button[class*="flux"],
            [class*="flux"] button,
            .flux-button,
            button[data-flux-button],
            flux-button,
            [data-flux-component="button"] {
                background-color: {{ $userTheme->primary_color }} !important;
                border-color: {{ $userTheme->primary_color }} !important;
                color: {{ $isDarkMode ? '#ffffff' : '#000000' }} !important;
            }
            
            /* Override Flux button hover states */
            button[class*="flux"]:hover,
            [class*="flux"] button:hover,
            .flux-button:hover,
            button[data-flux-button]:hover {
                background-color: {{ $userTheme->primary_color }}CC !important;
                border-color: {{ $userTheme->primary_color }}CC !important;
                opacity: 0.9 !important;
            }
            
            /* Specifically target primary variant buttons */
            button[class*="primary"],
            [data-variant="primary"],
            .btn-primary {
                background-color: {{ $userTheme->primary_color }} !important;
                border-color: {{ $userTheme->primary_color }} !important;
                color: {{ $isDarkMode ? '#ffffff' : '#000000' }} !important;
            }
            
            button[class*="primary"]:hover,
            [data-variant="primary"]:hover,
            .btn-primary:hover {
                background-color: {{ $userTheme->primary_color }}DD !important;
                border-color: {{ $userTheme->primary_color }}DD !important;
            }
            
            @if($isDarkMode)
            .dark {
                --color-background: {{ $userTheme->background_color }};
                --color-text: {{ $userTheme->text_color }};
            }
            /* Enhanced dark mode styling */
            .dark body {
                background: {{ $userTheme->background_color }};
                color: {{ $userTheme->text_color }};
            }
            /* Consistent sidebar colors in dark mode - but not for themed sidebars */
            .dark [class*="bg-slate-900"]:not(.themed-sidebar) {
                background-color: {{ $userTheme->background_color }} !important;
            }
            .dark [class*="border-slate-"] {
                border-color: {{ $userTheme->primary_color }}44 !important;
            }
            @else
            /* Light mode - ensure sidebar doesn't get dark colors */
            .themed-sidebar {
                background-color: {{ $userTheme->surface_color ?? '#f8fafc' }} !important;
            }
            /* Prevent any dark classes from affecting light mode sidebar */
            .themed-sidebar [class*="bg-slate-900"],
            .themed-sidebar [class*="bg-gray-900"],
            .themed-sidebar [class*="bg-black"] {
                background-color: transparent !important;
            }
            @endif
        </style>
    @endif
</head>
<body class="min-h-screen bg-white dark:bg-slate-900 flex" 
      @if($isDarkMode && $userTheme) 
        style="background: {{ $userTheme->background_color }}; color: {{ $userTheme->text_color }};" 
      @endif>
    
    <!-- Custom Project Sidebar -->
    <div class="flex-shrink-0">
        @livewire('sidebar', ['activeProjectUuid' => request()->route('uuid')])
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="border-b px-6 py-4 transition-all duration-300"
                @if($userTheme)
                  style="background: {{ $userTheme->background_color }}; 
                         border-color: {{ $userTheme->primary_color }}40;"
                @else
                  class="bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700"
                @endif>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <x-app-logo />
                </div>
                <x-desktop-user-menu/>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>

    <x-mobile-user-menu/>

    {{-- Keyboard Shortcuts Components --}}
    <x-command-palette />
    <x-keyboard-shortcuts-help />

    @fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
    @stack('scripts')
</body>
</html>