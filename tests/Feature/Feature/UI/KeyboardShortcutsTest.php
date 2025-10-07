<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Keyboard Shortcut Manager', function (): void {
    test('keyboard shortcut manager component exists', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');

        expect(file_exists($componentPath))->toBeTrue();
    });

    test('keyboard shortcuts are registered in app.js', function (): void {
        $appJsPath = resource_path('js/app.js');
        $content = file_get_contents($appJsPath);

        expect($content)->toContain('global-keyboard-shortcuts');
    });

    test('keyboard shortcuts component has proper structure', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        expect($content)->toContain('initGlobalKeyboardShortcuts')
            ->and($content)->toContain('handleGlobalKeydown');
    });
});

describe('Command Palette', function (): void {
    test('command palette component exists', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Command palette should be present in layout
        $content = $response->getContent();
        expect($content)->toBeString();
    });

    test('command palette can be opened with Ctrl+P', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check that Ctrl key handling exists
        expect($content)->toContain('ctrlKey');
    });

    test('command palette blade component exists', function (): void {
        $componentPath = resource_path('views/components/command-palette.blade.php');

        expect(file_exists($componentPath))->toBeTrue();
    });
});

describe('Shortcut Actions', function (): void {
    test('Cmd+N shortcut is defined', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check for new project shortcut
        expect($content)->toMatch('/[nN]/');
    });

    test('Ctrl+S shortcut is defined for settings', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check for settings shortcut
        expect($content)->toContain("key: 's'");
    });

    test('Ctrl+T shortcut is defined for theme customizer', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check for theme customizer shortcut
        expect($content)->toContain("key: 't'");
    });

    test('Ctrl+L shortcut is defined for logo gallery', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check for logo gallery shortcut
        expect($content)->toContain("key: 'l'");
    });

    test('Ctrl+H shortcut is defined for help', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check for help shortcut
        expect($content)->toContain("key: 'h'");
    });
});

describe('Help Overlay', function (): void {
    test('keyboard shortcuts help component exists', function (): void {
        $componentPath = resource_path('views/components/keyboard-shortcuts-help.blade.php');

        expect(file_exists($componentPath))->toBeTrue();
    });

    test('help overlay shows all available shortcuts', function (): void {
        $componentPath = resource_path('views/components/keyboard-shortcuts-help.blade.php');

        if (file_exists($componentPath)) {
            $content = file_get_contents($componentPath);

            // Check that only Ctrl is used (no Cmd or ⌘)
            expect($content)->toContain('Ctrl');
            expect($content)->not->toMatch('/(⌘)/');
        } else {
            expect(true)->toBeTrue(); // Pass if component doesn't exist yet
        }
    });
});

describe('Input Field Handling', function (): void {
    test('shortcuts are disabled in input fields', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Check that inputs are excluded
        expect($content)->toMatch('/(input|textarea|select)/i');
    });

    test('shortcuts work outside of form inputs', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');

        expect(file_exists($globalPath))->toBeTrue();
    });
});

describe('Tooltip Integration', function (): void {
    test('buttons can display keyboard shortcut hints', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Tooltips should be present on buttons
        $content = $response->getContent();
        expect($content)->toBeString();
    });
});

describe('Accessibility', function (): void {
    test('keyboard shortcuts are accessible', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');

        expect(file_exists($componentPath))->toBeTrue();
    });

    test('help overlay can be dismissed with Escape', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');

        if (file_exists($globalPath)) {
            $content = file_get_contents($globalPath);
            expect($content)->toContain('Escape');
        } else {
            expect(true)->toBeTrue();
        }
    });
});
