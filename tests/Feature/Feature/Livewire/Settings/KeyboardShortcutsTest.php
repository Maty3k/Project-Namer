<?php

declare(strict_types=1);

use App\Livewire\Settings\KeyboardShortcuts;
use App\Models\User;
use App\Models\UserKeyboardShortcut;
use Livewire\Livewire;

test('component loads user keyboard shortcuts preferences', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('shortcuts', function ($shortcuts) {
            return is_array($shortcuts) && isset($shortcuts['newProject']);
        })
        ->assertSet('disabledShortcuts', []);
});

test('component displays all default shortcuts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('New project')
        ->assertSee('Open settings')
        ->assertSee('Theme customizer')
        ->assertSee('Logo gallery')
        ->assertSee('Show keyboard shortcuts')
        ->assertSee('Close modals');
});

test('component displays formatted key combinations', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('N', escape: false)
        ->assertSee('S', escape: false)
        ->assertSee('T', escape: false)
        ->assertSee('L', escape: false)
        ->assertSee('H', escape: false);
});

test('can manage shortcuts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('shortcuts', function ($shortcuts) {
            return count($shortcuts) === 6; // 6 default shortcuts
        });
});

test('can toggle individual shortcut', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('toggleShortcut', 'newProject')
        ->assertSet('disabledShortcuts', ['newProject'])
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');
});

test('can enable previously disabled shortcut', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['disabled_shortcuts' => ['newProject']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('disabledShortcuts', ['newProject'])
        ->call('toggleShortcut', 'newProject')
        ->assertSet('disabledShortcuts', []);
});

test('can reset single shortcut to default', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->updateShortcut('newProject', ['key' => 'm', 'modifiers' => ['ctrl']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('resetShortcut', 'newProject')
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');

    expect($shortcuts->fresh()->custom_shortcuts)->not->toHaveKey('newProject');
});

test('can reset all shortcuts to defaults', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update([
        'custom_shortcuts' => ['newProject' => ['key' => 'm', 'modifiers' => ['ctrl']]],
        'disabled_shortcuts' => ['themeCustomizer'],
    ]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('resetAllShortcuts')
        ->assertSet('disabledShortcuts', [])
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');

    $fresh = $shortcuts->fresh();
    expect($fresh->custom_shortcuts)->toBeNull()
        ->and($fresh->disabled_shortcuts)->toBeNull();
});

test('formats key combo with ctrl modifier', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('newProject');

    expect($formatted)->toBe('Ctrl + N');
});

test('formats key combo for all platforms', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('newProject');

    expect($formatted)->toContain('Ctrl');
});

test('formats key combo with ctrl and letter', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('help');

    expect($formatted)->toBe('Ctrl + H');
});

test('displays shortcuts', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('New project')
        ->assertSee('Open settings');
});

test('shows disabled state for individually disabled shortcuts', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['disabled_shortcuts' => ['newProject']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Disabled');
});

test('only authenticated users can access', function () {
    $this->get(route('settings.keyboard-shortcuts'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('settings.keyboard-shortcuts'))
        ->assertOk();

    expect($response->content())->toContain('Keyboard Shortcuts');
});

test('displays reset confirmation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Are you sure you want to reset all keyboard shortcuts to defaults?');
});

test('displays help tip about pressing ctrl h', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Press')
        ->assertSee('Ctrl+H')
        ->assertSee('anytime to view all available keyboard shortcuts');
});
