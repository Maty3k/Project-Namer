@php
    $userTheme = null;
    if (auth()->check()) {
        $userTheme = \App\Models\UserThemePreference::where('user_id', auth()->id())->first();
    }
    $isDarkMode = $userTheme ? $userTheme->is_dark_mode : false;
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
            }
            @if($isDarkMode)
            .dark {
                --color-background: {{ $userTheme->background_color }};
                --color-text: {{ $userTheme->text_color }};
            }
            /* Enhanced dark mode styling */
            .dark body {
                background: linear-gradient(135deg, {{ $userTheme->background_color }}ee 0%, {{ $userTheme->primary_color }}22 100%);
                color: {{ $userTheme->text_color }};
            }
            /* Consistent sidebar colors in dark mode */
            .dark [class*="bg-slate-900"] {
                background-color: {{ $userTheme->background_color }} !important;
            }
            .dark [class*="border-slate-"] {
                border-color: {{ $userTheme->primary_color }}44 !important;
            }
            @endif
        </style>
    @endif
</head>
<body class="min-h-screen bg-white dark:bg-slate-900 flex" 
      @if($isDarkMode && $userTheme) 
        style="background: linear-gradient(135deg, {{ $userTheme->background_color }} 0%, {{ $userTheme->primary_color }}22 100%); color: {{ $userTheme->text_color }};" 
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
                  style="background: linear-gradient(135deg, {{ $userTheme->background_color }}f5 0%, {{ $userTheme->primary_color }}15 100%); 
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

    @fluxScripts(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>