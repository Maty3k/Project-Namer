<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- CRITICAL: Apply theme BEFORE page renders to prevent flash --}}
<script>
(function() {
    const localStorageTheme = localStorage.getItem('darkMode');
    const serverThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

    const shouldBeDark = localStorageTheme !== null
        ? localStorageTheme === 'true'
        : serverThemePreference;

    if (shouldBeDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})();
</script>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="{{ asset('css/smooth-animations.css') }}">
@fluxAppearance

{{-- Dynamic theme CSS loading based on user preference --}}
<link rel="stylesheet" href="{{ \App\Helpers\ThemeHelper::getThemeCssPath() }}">
