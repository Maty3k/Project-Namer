<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserKeyboardShortcut;

test('creates keyboard shortcuts for user with defaults', function () {
    $user = User::factory()->create();

    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect($shortcuts->user_id)->toBe($user->id)
        ->and($shortcuts->custom_shortcuts)->toBeNull()
        ->and($shortcuts->disabled_shortcuts)->toBeNull();
});

test('returns default shortcuts configuration', function () {
    $defaults = UserKeyboardShortcut::getDefaultShortcuts();

    expect($defaults)->toBeArray()
        ->toHaveKeys(['newProject', 'settings', 'themeCustomizer', 'logoGallery', 'help', 'escape'])
        ->and($defaults['newProject'])->toHaveKeys(['key', 'modifiers', 'description', 'enabled'])
        ->and($defaults['newProject']['key'])->toBe('n')
        ->and($defaults['newProject']['modifiers'])->toBe(['ctrl'])
        ->and($defaults['newProject']['enabled'])->toBeTrue();
});

test('merges default shortcuts with custom shortcuts', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $shortcuts->update([
        'custom_shortcuts' => [
            'newProject' => [
                'key' => 'm',
                'modifiers' => ['ctrl'],
            ],
        ],
    ]);

    $merged = $shortcuts->getMergedShortcuts();

    expect($merged['newProject']['key'])->toBe('m')
        ->and($merged['newProject']['modifiers'])->toBe(['ctrl'])
        ->and($merged['newProject']['description'])->toBe('New project')
        ->and($merged['newProject']['enabled'])->toBeTrue();
});

test('disables individual shortcuts correctly', function () {
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

test('merges shortcuts correctly', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $merged = $shortcuts->getMergedShortcuts();

    foreach ($merged as $action => $config) {
        expect($config['enabled'])->toBeTrue();
    }
});

test('toggles shortcut on and off', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Disable a shortcut
    $shortcuts->toggleShortcut('newProject');
    expect($shortcuts->fresh()->disabled_shortcuts)->toContain('newProject');

    // Enable it again
    $shortcuts->toggleShortcut('newProject');
    expect($shortcuts->fresh()->disabled_shortcuts)->not->toContain('newProject');
});

test('updates custom shortcut binding', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $shortcuts->updateShortcut('newProject', [
        'key' => 'm',
        'modifiers' => ['ctrl'],
    ]);

    $customShortcuts = $shortcuts->fresh()->custom_shortcuts;
    expect($customShortcuts['newProject']['key'])->toBe('m')
        ->and($customShortcuts['newProject']['modifiers'])->toBe(['ctrl']);
});

test('resets single shortcut to default', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Add custom shortcut
    $shortcuts->updateShortcut('newProject', [
        'key' => 'm',
        'modifiers' => ['ctrl'],
    ]);

    expect($shortcuts->fresh()->custom_shortcuts)->toHaveKey('newProject');

    // Reset it
    $shortcuts->resetShortcut('newProject');

    expect($shortcuts->fresh()->custom_shortcuts)->not->toHaveKey('newProject');
});

test('resets all shortcuts to defaults', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    // Add customizations
    $shortcuts->update([
        'custom_shortcuts' => [
            'newProject' => ['key' => 'm', 'modifiers' => ['ctrl']],
            'themeCustomizer' => ['key' => 'x', 'modifiers' => ['ctrl']],
        ],
        'disabled_shortcuts' => ['logoGallery', 'help'],
    ]);

    // Reset all
    $shortcuts->resetAllShortcuts();

    $fresh = $shortcuts->fresh();
    expect($fresh->custom_shortcuts)->toBeNull()
        ->and($fresh->disabled_shortcuts)->toBeNull();
});

test('checks if shortcut is enabled for user', function () {
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

test('user relationship works correctly', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect($shortcuts->user)->toBeInstanceOf(User::class)
        ->and($shortcuts->user->id)->toBe($user->id);
});

test('user_id is unique in database', function () {
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

test('deleting user cascades to keyboard shortcuts', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeTrue();

    $user->delete();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeFalse();
});
