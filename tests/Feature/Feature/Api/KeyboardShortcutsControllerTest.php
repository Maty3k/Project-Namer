<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserKeyboardShortcut;

test('requires authentication to access API endpoint', function () {
    $this->getJson('/api/keyboard-shortcuts')
        ->assertUnauthorized();
});

test('returns user keyboard shortcuts preferences via API', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk()
        ->assertJsonStructure([
            'enabled',
            'shortcuts' => [
                'newProject' => ['key', 'modifiers', 'description', 'enabled'],
                'settings',
                'themeCustomizer',
                'logoGallery',
                'help',
                'escape',
            ],
        ]);
});

test('returns correct enabled state', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->update(['enabled' => false]);

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk()
        ->assertJson([
            'enabled' => false,
        ]);
});

test('returns custom shortcuts when set', function () {
    $user = User::factory()->create();
    $shortcuts = UserKeyboardShortcut::findOrCreateForUser($user->id);
    $shortcuts->updateShortcut('newProject', [
        'key' => 'm',
        'modifiers' => ['ctrl'],
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data['shortcuts']['newProject']['key'])->toBe('m')
        ->and($data['shortcuts']['newProject']['modifiers'])->toContain('ctrl');
});

test('returns disabled shortcuts state correctly', function () {
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

test('creates shortcuts record if it does not exist', function () {
    $user = User::factory()->create();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    expect(UserKeyboardShortcut::where('user_id', $user->id)->exists())->toBeTrue();
});

test('returns default shortcuts for new user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts')
        ->assertOk();

    $data = $response->json();
    expect($data['enabled'])->toBeTrue()
        ->and($data['shortcuts'])->toHaveKeys(['newProject', 'settings', 'themeCustomizer', 'logoGallery', 'help', 'escape'])
        ->and($data['shortcuts']['newProject']['key'])->toBe('n')
        ->and($data['shortcuts']['newProject']['enabled'])->toBeTrue();
});

test('respects accept header', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/keyboard-shortcuts', ['Accept' => 'application/json'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json');
});
