<?php

declare(strict_types=1);

use App\Livewire\Settings\KeyboardShortcuts;
use App\Models\User;
use App\Models\UserKeyboardShortcut;
use Livewire\Livewire;

test('can open edit modal for a shortcut', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->assertSet('editingAction', 'newProject')
        ->assertSet('editingKey', 'n')
        ->assertSet('editingModifiers.cmd', true)
        ->assertSet('showEditModal', true);
});

test('can capture key press in edit modal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->call('captureKey', 'n')
        ->assertSet('editingKey', 'n');
});

test('can update shortcut with custom key binding', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->set('editingKey', 'm')
        ->set('editingModifiers.cmd', true)
        ->set('editingModifiers.alt', false)
        ->set('editingModifiers.shift', false)
        ->call('saveShortcut')
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');

    $userShortcuts = UserKeyboardShortcut::where('user_id', $user->id)->first();
    expect($userShortcuts->custom_shortcuts['newProject']['key'])->toBe('m');
});

test('can reset shortcut from edit modal', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->updateShortcut('newProject', ['key' => 'm', 'modifiers' => ['ctrl']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->call('resetEditingShortcut')
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');

    $userShortcuts = UserKeyboardShortcut::where('user_id', $user->id)->first();
    expect($userShortcuts->custom_shortcuts)->not->toHaveKey('newProject');
});

test('can close edit modal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->call('closeEditModal')
        ->assertSet('editingAction', null)
        ->assertSet('editingKey', '')
        ->assertSet('showEditModal', false);
});

test('preview shows correct key combination', function () {
    $user = User::factory()->create();

    $component = new KeyboardShortcuts;
    $component->editingKey = 'p';
    $component->editingModifiers = [
        'cmd' => true,
        'alt' => true,
        'shift' => false,
    ];

    $preview = $component->getPreviewKeyCombo();
    expect($preview)->toBe('Ctrl + Alt + P');
});

test('can save shortcut with modifiers', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'themeCustomizer')
        ->set('editingKey', 'm')
        ->set('editingModifiers.cmd', true)
        ->set('editingModifiers.shift', true)
        ->call('saveShortcut');

    $userShortcuts = UserKeyboardShortcut::where('user_id', $user->id)->first();
    expect($userShortcuts->custom_shortcuts['themeCustomizer']['key'])->toBe('m')
        ->and($userShortcuts->custom_shortcuts['themeCustomizer']['modifiers'])->toContain('ctrl')
        ->and($userShortcuts->custom_shortcuts['themeCustomizer']['modifiers'])->toContain('shift');
});

test('cannot save shortcut without key', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('openEditModal', 'newProject')
        ->set('editingKey', '')
        ->call('saveShortcut');

    $userShortcuts = UserKeyboardShortcut::where('user_id', $user->id)->first();
    expect($userShortcuts->custom_shortcuts)->toBeNull();
});

test('handles special keys correctly', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('captureKey', 'escape')
        ->assertSet('editingKey', 'Escape');
});

test('displays edit button for each shortcut', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Edit');
});

test('custom shortcuts are reflected in merged shortcuts', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->updateShortcut('newProject', ['key' => 'm', 'modifiers' => ['ctrl', 'alt']]);

    $component = Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class);

    $merged = $component->get('shortcuts');
    expect($merged['newProject']['key'])->toBe('m')
        ->and($merged['newProject']['modifiers'])->toContain('ctrl')
        ->and($merged['newProject']['modifiers'])->toContain('alt');
});
