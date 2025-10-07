<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserKeyboardShortcut;

test('requires authentication to access API endpoint', function (): void {
    $this->getJson('/api/keyboard-shortcuts')
        ->assertUnauthorized();
});

test('returns user keyboard shortcuts preferences via API', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk()
        ->assertJsonStructure([
            'shortcuts' => [
                'newProject' => ['key', 'modifiers', 'description', 'enabled'],
                'settings',
                'themeCustomizer',
                'logoGallery',
                'keyboardShortcuts',
                'help',
                'escape',
            ],
        ]);
});

test('returns shortcuts with proper structure', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data)->toHaveKey('shortcuts');
});

test('returns default shortcuts structure', function (): void {
    $user = User::factory()->create();
    UserKeyboardShortcut::findOrCreateForUser($user->id);

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data['shortcuts']['newProject']['key'])->toBe('n')
        ->and($data['shortcuts']['newProject']['modifiers'])->toContain('ctrl')
        ->and($data['shortcuts'])->toHaveKey('keyboardShortcuts');
});

test('returns disabled shortcuts state correctly', function (): void {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['disabled_shortcuts' => ['newProject', 'themeCustomizer']]);

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data['shortcuts']['newProject']['enabled'])->toBeFalse()
        ->and($data['shortcuts']['themeCustomizer']['enabled'])->toBeFalse()
        ->and($data['shortcuts']['logoGallery']['enabled'])->toBeTrue();
});

test('creates shortcuts record if it does not exist', function (): void {
    $user = User::factory()->create();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeTrue();
});

test('returns default shortcuts for new user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data['shortcuts'])->toHaveKeys(['newProject', 'settings', 'themeCustomizer', 'logoGallery', 'keyboardShortcuts', 'help', 'escape'])
        ->and($data['shortcuts']['newProject']['key'])->toBe('n')
        ->and($data['shortcuts']['newProject']['enabled'])->toBeTrue()
        ->and($data['shortcuts']['keyboardShortcuts']['key'])->toBe('k');
});

test('respects accept header', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts', ['Accept' => 'application/json'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json');
});
