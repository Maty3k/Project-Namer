<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- CRITICAL: Apply theme BEFORE page renders to prevent flash --}}
<script>
(function() {
    // Get theme preferences from both sources
    const localStorageTheme = localStorage.getItem('darkMode');
    const localStorageThemeName = localStorage.getItem('themeName');
    const serverThemePreference = {{ \App\Helpers\ThemeHelper::isDarkMode() ? 'true' : 'false' }};
    const serverThemeName = '{{ \App\Helpers\ThemeHelper::getThemeName() }}';

    // Determine final theme name (localStorage takes precedence)
    const themeName = localStorageThemeName || serverThemeName;

    // Determine dark mode state
    let shouldBeDark;
    if (localStorageTheme !== null) {
        // localStorage exists, use it
        shouldBeDark = localStorageTheme === 'true';
    } else {
        // No localStorage, use server preference
        shouldBeDark = serverThemePreference;
    }

    console.log('=== THEME INITIALIZATION ===');
    console.log('localStorage darkMode:', localStorageTheme, '(type:', typeof localStorageTheme + ')');
    console.log('localStorage themeName:', localStorageThemeName);
    console.log('Server isDarkMode:', serverThemePreference, '(type:', typeof serverThemePreference + ')');
    console.log('Server themeName:', serverThemeName);
    console.log('---');
    console.log('FINAL themeName:', themeName);
    console.log('FINAL shouldBeDark:', shouldBeDark, '(type:', typeof shouldBeDark + ')');
    console.log('ACTION: Will', shouldBeDark ? 'ADD' : 'REMOVE', 'dark class');

    // Apply dark mode class
    if (shouldBeDark) {
        document.documentElement.classList.add('dark');
        console.log('✓ Dark class ADDED to <html>');
    } else {
        document.documentElement.classList.remove('dark');
        console.log('✓ Dark class REMOVED from <html>');
    }

    // Load theme CSS file
    const themeCssPath = '/css/themes/' + themeName + '.css';
    let themeLink = document.getElementById('theme-css-link');
    if (!themeLink) {
        themeLink = document.createElement('link');
        themeLink.id = 'theme-css-link';
        themeLink.rel = 'stylesheet';
        document.head.appendChild(themeLink);
        console.log('✓ Created theme CSS link element');
    }
    themeLink.href = themeCssPath;
    console.log('✓ Loading theme CSS:', themeCssPath);
    console.log('=== END THEME INITIALIZATION ===\n');

    // Verify theme state after DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        const htmlClasses = document.documentElement.classList;
        const hasDarkClass = htmlClasses.contains('dark');

        console.log('=== THEME VERIFICATION (DOMContentLoaded) ===');
        console.log('Expected dark class:', shouldBeDark);
        console.log('Actual dark class present:', hasDarkClass);
        console.log('HTML element classes:', Array.from(htmlClasses).join(', ') || '(none)');

        if (shouldBeDark !== hasDarkClass) {
            console.warn('⚠️ MISMATCH: Expected', shouldBeDark, 'but found', hasDarkClass);
            console.warn('⚠️ Correcting dark class...');
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } else {
            console.log('✓ Theme state is correct');
        }
        console.log('=== END VERIFICATION ===\n');
    });

    // CRITICAL: Prevent Flux UI or other libraries from overriding theme
    // Monitor for unauthorized dark class changes and block them immediately
    const darkModeObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const hasDarkClass = document.documentElement.classList.contains('dark');

                // Only correct if there's a mismatch and we're not in the middle of a manual change
                if (hasDarkClass !== shouldBeDark && !window.__allowingThemeChange) {
                    console.warn('🚫 BLOCKED: Something tried to change dark class!');
                    console.warn('   Expected:', shouldBeDark, 'Found:', hasDarkClass);
                    console.warn('   Reverting immediately...');

                    if (shouldBeDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        });
    });

    // Start monitoring immediately
    darkModeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
    console.log('✓ Dark class protection active (MutationObserver monitoring)');
})();
</script>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script>
    // Ensure Alpine components are registered before Alpine initializes
    document.addEventListener('alpine:init', () => {
        console.log('[Alpine Init] Event fired - components should be registered');
    });
</script>
<link rel="stylesheet" href="{{ asset('css/smooth-animations.css') }}">
@fluxAppearance

{{-- Theme CSS is loaded dynamically via script above --}}
