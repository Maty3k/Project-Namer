<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

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

    // ALWAYS trust localStorage as source of truth if it exists
    // Server preference is only used for first-time initialization
    let shouldBeDark;
    if (localStorageTheme !== null && localStorageTheme !== 'null') {
        // localStorage exists and is valid, ALWAYS use it
        shouldBeDark = localStorageTheme === 'true';
        console.log('Using localStorage darkMode (trusted source)');
    } else {
        // No localStorage, use server preference and save it
        shouldBeDark = serverThemePreference;
        localStorage.setItem('darkMode', shouldBeDark ? 'true' : 'false');
        console.log('Using server preference (first time), saved to localStorage');
    }

    // Also save theme name if not present
    if (!localStorageThemeName || localStorageThemeName === 'null') {
        localStorage.setItem('themeName', serverThemeName);
        console.log('Saved server theme name to localStorage:', serverThemeName);
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

    // Listen for Livewire navigation events to temporarily pause protection
    let isNavigating = false;

    document.addEventListener('livewire:navigating', function() {
        isNavigating = true;
        window.__allowingThemeChange = true;

        // Apply theme IMMEDIATELY during navigation to prevent flash
        if (shouldBeDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        console.log('🔄 Livewire navigation started - theme pre-applied:', shouldBeDark ? 'dark' : 'light');
    });

    document.addEventListener('livewire:navigated', function() {
        // Ensure theme is still correct after navigation completes
        const currentDarkClass = document.documentElement.classList.contains('dark');
        if (currentDarkClass !== shouldBeDark) {
            console.log('🔧 Correcting theme after navigation:', shouldBeDark ? 'dark' : 'light');
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Short delay before resuming protection
        setTimeout(function() {
            isNavigating = false;
            window.__allowingThemeChange = false;
            console.log('✓ Livewire navigation complete - theme protection resumed');
        }, 150); // Reduced from 300ms since theme is pre-applied
    });

    // LIGHTER theme protection - only enforce after page is stable
    const darkModeProtector = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const hasDarkClass = document.documentElement.classList.contains('dark');

                // Don't interfere during navigation or authorized changes
                if (hasDarkClass !== shouldBeDark && !window.__allowingThemeChange && !isNavigating) {
                    console.warn('⚠️ Unexpected theme change detected! Fixing...');

                    // Fix the theme immediately
                    setTimeout(function() {
                        // Double-check the flags before applying fix
                        if (!window.__allowingThemeChange && !isNavigating) {
                            if (shouldBeDark) {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                            console.log('✓ Theme corrected to user preference');
                        }
                    }, 50); // Minimal debounce for instant correction
                }
            }
        });
    });

    // Start monitoring (but allow navigation)
    darkModeProtector.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
    console.log('✓ Smart theme protection active');
})();
</script>

<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

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
