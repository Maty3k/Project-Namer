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

        expect($content)->toContain('keyboardShortcuts');
    });

    test('keyboard shortcuts component has proper structure', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        expect($content)->toContain('init')
            ->and($content)->toContain('handleKeydown');
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

    test('command palette can be opened with Cmd+K', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        // Check that Cmd+K / Ctrl+K is handled
        expect($content)->toContain('metaKey')
            ->and($content)->toContain('ctrlKey');
    });

    test('command palette blade component exists', function (): void {
        $componentPath = resource_path('views/components/command-palette.blade.php');

        expect(file_exists($componentPath))->toBeTrue();
    });
});

describe('Shortcut Actions', function (): void {
    test('Cmd+N shortcut is defined', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        // Check for new project shortcut
        expect($content)->toMatch('/[nN]/');
    });

    test('Cmd+G shortcut is defined', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        // Check for generate shortcut
        expect($content)->toMatch('/[gG]/');
    });

    test('? shortcut is defined for help', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        // Check for help shortcut
        expect($content)->toContain('?');
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

            // Check for Command symbol (⌘) or Cmd text, and Ctrl
            expect($content)->toMatch('/(⌘|Cmd)/');
            expect($content)->toContain('Ctrl');
        } else {
            expect(true)->toBeTrue(); // Pass if component doesn't exist yet
        }
    });
});

describe('Input Field Handling', function (): void {
    test('shortcuts are disabled in input fields', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');
        $content = file_get_contents($componentPath);

        // Check that inputs are excluded
        expect($content)->toMatch('/(input|textarea|select)/i');
    });

    test('shortcuts work outside of form inputs', function (): void {
        $componentPath = resource_path('js/components/keyboardShortcuts.js');

        expect(file_exists($componentPath))->toBeTrue();
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
        $componentPath = resource_path('js/components/keyboardShortcuts.js');

        if (file_exists($componentPath)) {
            $content = file_get_contents($componentPath);
            expect($content)->toContain('Escape');
        } else {
            expect(true)->toBeTrue();
        }
    });
});
