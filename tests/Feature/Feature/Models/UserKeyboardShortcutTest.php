<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserKeyboardShortcut;

test('creates keyboard shortcuts for user with defaults', function (): void {
    $user = User::factory()->create();

    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect($shortcuts->user_id)->toBe($user->id)
        ->and($shortcuts->custom_shortcuts)->toBeNull()
        ->and($shortcuts->disabled_shortcuts)->toBeNull();
});

test('returns default shortcuts configuration', function (): void {
    $defaults = UserKeyboardShortcut::getDefaultShortcuts();

    expect($defaults)->toBeArray()
        ->toHaveKeys(['newProject', 'settings', 'themeCustomizer', 'logoGallery', 'help', 'escape'])
        ->and($defaults['newProject'])->toHaveKeys(['key', 'modifiers', 'description', 'enabled'])
        ->and($defaults['newProject']['key'])->toBe('n')
        ->and($defaults['newProject']['modifiers'])->toBe(['ctrl'])
        ->and($defaults['newProject']['enabled'])->toBeTrue();
});

test('returns default shortcuts without customization', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $merged = $shortcuts->getMergedShortcuts();

    expect($merged['newProject']['key'])->toBe('n')
        ->and($merged['newProject']['modifiers'])->toBe(['ctrl'])
        ->and($merged['newProject']['description'])->toBe('New project')
        ->and($merged['newProject']['enabled'])->toBeTrue()
        ->and($merged)->toHaveKey('keyboardShortcuts');
});

test('disables individual shortcuts correctly', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $shortcuts->update([
        'disabled_shortcuts' => ['newProject', 'themeCustomizer'],
    ]);

    $merged = $shortcuts->getMergedShortcuts();

    expect($merged['newProject']['enabled'])->toBeFalse()
        ->and($merged['themeCustomizer']['enabled'])->toBeFalse()
        ->and($merged['logoGallery']['enabled'])->toBeTrue()
        ->and($merged['help']['enabled'])->toBeTrue();
});

test('merges shortcuts correctly', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $merged = $shortcuts->getMergedShortcuts();

    foreach ($merged as $config) {
        expect($config['enabled'])->toBeTrue();
    }
});

test('toggles shortcut on and off', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Disable a shortcut
    $shortcuts->toggleShortcut('newProject');
    expect($shortcuts->fresh()->disabled_shortcuts)->toContain('newProject');

    // Enable it again
    $shortcuts->toggleShortcut('newProject');
    expect($shortcuts->fresh()->disabled_shortcuts)->not->toContain('newProject');
});

test('includes keyboard shortcuts shortcut in defaults', function (): void {
    $defaults = UserKeyboardShortcut::getDefaultShortcuts();

    expect($defaults)->toHaveKey('keyboardShortcuts')
        ->and($defaults['keyboardShortcuts']['key'])->toBe('k')
        ->and($defaults['keyboardShortcuts']['modifiers'])->toBe(['ctrl'])
        ->and($defaults['keyboardShortcuts']['description'])->toBe('Keyboard shortcuts settings');
});

test('can toggle shortcuts on and off', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Disable a shortcut
    $shortcuts->toggleShortcut('keyboardShortcuts');
    expect($shortcuts->fresh()->disabled_shortcuts)->toContain('keyboardShortcuts');

    // Enable it again
    $shortcuts->toggleShortcut('keyboardShortcuts');
    expect($shortcuts->fresh()->disabled_shortcuts)->not->toContain('keyboardShortcuts');
});

test('resets all shortcuts to defaults', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Add disabled shortcuts
    $shortcuts->update([
        'disabled_shortcuts' => ['logoGallery', 'help'],
    ]);

    // Reset all
    $shortcuts->resetAllShortcuts();

    $fresh = $shortcuts->fresh();
    expect($fresh->disabled_shortcuts)->toBeNull();
});

test('checks if shortcut is enabled for user', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect($shortcuts->isShortcutEnabled('newProject'))->toBeTrue();

    // Disable specific shortcut
    $shortcuts->update(['disabled_shortcuts' => ['newProject']]);
    expect($shortcuts->isShortcutEnabled('newProject'))->toBeFalse();

    // Re-enable shortcut
    $shortcuts->update(['disabled_shortcuts' => []]);
    expect($shortcuts->isShortcutEnabled('newProject'))->toBeTrue();
});

test('user relationship works correctly', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect($shortcuts->user)->toBeInstanceOf(User::class)
        ->and($shortcuts->user->id)->toBe($user->id);
});

test('user_id is unique in database', function (): void {
    $user = User::factory()->create();

    UserKeyboardShortcut::create([
        'user_id' => $user->id,
        'enabled' => true,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    UserKeyboardShortcut::create([
        'user_id' => $user->id,
        'enabled' => false,
    ]);
});

test('deleting user cascades to keyboard shortcuts', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeTrue();

    $user->delete();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeFalse();
});
