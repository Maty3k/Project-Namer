<?php

declare(strict_types=1);

use App\Livewire\KeyboardShortcuts;
use App\Models\User;
use App\Models\UserKeyboardShortcut;
use Livewire\Livewire;

test('component loads user keyboard shortcuts preferences', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('shortcuts', fn ($shortcuts) => is_array($shortcuts) && isset($shortcuts['newProject']))
        ->assertSet('disabledShortcuts', []);
});

test('component displays all default shortcuts', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('New project')
        ->assertSee('Open settings')
        ->assertSee('Appearance settings')
        ->assertSee('Logo gallery')
        ->assertSee('Show keyboard shortcuts')
        ->assertSee('Close modals');
});

test('component displays formatted key combinations', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('N', escape: false)
        ->assertSee('S', escape: false)
        ->assertSee('T', escape: false)
        ->assertSee('L', escape: false)
        ->assertSee('H', escape: false);
});

test('can manage shortcuts', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('shortcuts', function ($shortcuts) {
            return count($shortcuts) === 7; // 7 default shortcuts including keyboardShortcuts
        });
});

test('can toggle individual shortcut', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('toggleShortcut', 'newProject')
        ->assertSet('disabledShortcuts', ['newProject'])
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');
});

test('can enable previously disabled shortcut', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['disabled_shortcuts' => ['newProject']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSet('disabledShortcuts', ['newProject'])
        ->call('toggleShortcut', 'newProject')
        ->assertSet('disabledShortcuts', []);
});

test('displays new keyboard shortcuts shortcut', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Keyboard shortcuts settings')
        ->assertSee('K', escape: false);
});

test('can reset all shortcuts to defaults', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update([
        'disabled_shortcuts' => ['themeCustomizer'],
    ]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->call('resetAllShortcuts')
        ->assertSet('disabledShortcuts', [])
        ->assertDispatched('shortcuts-updated')
        ->assertDispatched('toast');

    $fresh = $shortcuts->fresh();
    expect($fresh->disabled_shortcuts)->toBeNull();
});

test('formats key combo with ctrl modifier', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('newProject');

    expect($formatted)->toBe('Ctrl + N');
});

test('formats key combo for all platforms', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('newProject');

    expect($formatted)->toContain('Ctrl');
});

test('formats key combo with ctrl and letter', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $component = new KeyboardShortcuts;
    $component->shortcuts = $shortcuts->getMergedShortcuts();

    $formatted = $component->formatKeyCombo('help');

    expect($formatted)->toBe('Ctrl + H');
});

test('displays shortcuts', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('New project')
        ->assertSee('Open settings');
});

test('shows disabled state for individually disabled shortcuts', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['disabled_shortcuts' => ['newProject']]);

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Disabled');
});

test('only authenticated users can access', function (): void {
    $this->get(route('keyboard-shortcuts'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access keyboard shortcuts page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('keyboard-shortcuts'))
        ->assertOk();

    expect($response->content())->toContain('Keyboard Shortcuts');
});

test('displays help tip about pressing ctrl h', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KeyboardShortcuts::class)
        ->assertSee('Press')
        ->assertSee('Ctrl+H')
        ->assertSee('anytime to view all available keyboard shortcuts');
});
