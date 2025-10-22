<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserKeyboardShortcut;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Disabled Keyboard Shortcuts', function (): void {
    test('API returns empty disabled shortcuts for new user', function (): void {
        $response = $this->getJson('/api/keyboard-shortcuts');

        $response->assertStatus(200)
            ->assertJson([
                'disabled_shortcuts' => [],
            ]);
    });

    test('API returns disabled shortcuts after user disables one', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);
        $userShortcuts->toggleShortcut('settings');

        $response = $this->getJson('/api/keyboard-shortcuts');

        $response->assertStatus(200)
            ->assertJson([
                'disabled_shortcuts' => ['settings'],
            ]);
    });

    test('disabling a shortcut adds it to disabled array', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);

        expect($userShortcuts->disabled_shortcuts)->toBeNull();

        $userShortcuts->toggleShortcut('settings');

        $userShortcuts->refresh();
        expect($userShortcuts->disabled_shortcuts)->toBe(['settings']);
    });

    test('enabling a previously disabled shortcut removes it from array', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);
        $userShortcuts->toggleShortcut('settings');
        $userShortcuts->refresh();

        expect($userShortcuts->disabled_shortcuts)->toBe(['settings']);

        // Toggle again to enable
        $userShortcuts->toggleShortcut('settings');
        $userShortcuts->refresh();

        expect($userShortcuts->disabled_shortcuts)->toBe([]);
    });

    test('can disable multiple shortcuts', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);

        $userShortcuts->toggleShortcut('settings');
        $userShortcuts->toggleShortcut('appearance');
        $userShortcuts->toggleShortcut('logoGallery');

        $userShortcuts->refresh();
        expect($userShortcuts->disabled_shortcuts)->toBe(['settings', 'appearance', 'logoGallery']);
    });

    test('getMergedShortcuts marks disabled shortcuts as not enabled', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);
        $userShortcuts->toggleShortcut('settings');

        $shortcuts = $userShortcuts->getMergedShortcuts();

        expect($shortcuts['settings']['enabled'])->toBeFalse();
        expect($shortcuts['newProject']['enabled'])->toBeTrue();
        expect($shortcuts['appearance']['enabled'])->toBeTrue();
    });

    test('isShortcutEnabled returns false for disabled shortcut', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);
        $userShortcuts->toggleShortcut('settings');

        expect($userShortcuts->isShortcutEnabled('settings'))->toBeFalse();
        expect($userShortcuts->isShortcutEnabled('newProject'))->toBeTrue();
    });

    test('reset all shortcuts clears disabled shortcuts', function (): void {
        $userShortcuts = UserKeyboardShortcut::findOrCreateForUser($this->user->id);
        $userShortcuts->toggleShortcut('settings');
        $userShortcuts->toggleShortcut('themeCustomizer');

        $userShortcuts->refresh();
        expect($userShortcuts->disabled_shortcuts)->not->toBeEmpty();

        $userShortcuts->resetAllShortcuts();
        $userShortcuts->refresh();

        expect($userShortcuts->disabled_shortcuts)->toBeNull();
    });

    test('global keyboard shortcuts file includes disabled check', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Verify that disabled shortcuts are checked
        expect($content)->toContain('disabledShortcuts.includes(shortcut.action)');
        // Console logging removed for quieter console output
        expect($content)->toContain('event.preventDefault()');
    });

    test('global keyboard shortcuts loads from injected data on init', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Verify that it uses injected disabled shortcuts
        expect($content)->toContain('window.__disabledShortcuts');
        expect($content)->toContain('updateDisabledShortcuts()');
    });

    test('global keyboard shortcuts listens for updates', function (): void {
        $globalPath = resource_path('js/global-keyboard-shortcuts.js');
        $content = file_get_contents($globalPath);

        // Verify that it listens for Livewire shortcuts-updated event and fetches from API
        expect($content)->toContain('Livewire.on(\'shortcuts-updated\'');
        expect($content)->toContain('fetch(\'/api/keyboard-shortcuts\'');
    });
});
