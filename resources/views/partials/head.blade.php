<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- CRITICAL: Apply theme BEFORE page renders to prevent flash --}}
<script>
(function() {
    // Apply dark/light mode
    const localStorageTheme = localStorage.getItem('darkMode');
    const serverThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};

    const shouldBeDark = localStorageTheme !== null
        ? localStorageTheme === 'true'
        : serverThemePreference;

    console.log('=== THEME DEBUG ===');
    console.log('localStorage darkMode:', localStorageTheme);
    console.log('Server preference:', serverThemePreference);
    console.log('Final shouldBeDark:', shouldBeDark);
    console.log('Applying dark class:', shouldBeDark ? 'YES' : 'NO');

    if (shouldBeDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // Load correct theme CSS file
    const storedThemeName = localStorage.getItem('themeName');
    const serverThemeName = '{{ \App\Helpers\ThemeHelper::getThemeName() }}';
    const themeName = storedThemeName || serverThemeName;

    console.log('localStorage themeName:', storedThemeName);
    console.log('Server themeName:', serverThemeName);
    console.log('Final themeName:', themeName);
    console.log('Loading CSS:', '/css/themes/' + themeName + '.css');

    // Create or update theme CSS link
    let themeLink = document.getElementById('theme-css-link');
    if (!themeLink) {
        themeLink = document.createElement('link');
        themeLink.id = 'theme-css-link';
        themeLink.rel = 'stylesheet';
        document.head.appendChild(themeLink);
    }
    themeLink.href = '/css/themes/' + themeName + '.css';
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

{{-- Theme CSS is loaded dynamically via script above --}}
