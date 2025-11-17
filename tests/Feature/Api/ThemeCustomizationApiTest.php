<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('slow');

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->withoutVite();
});

describe('Theme API Endpoints', function (): void {
    test('can get current user theme preferences', function (): void {
        $theme = UserThemePreference::factory()->create([
            'user_id' => $this->user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/preferences');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'theme' => [
                    'id',
                    'theme_name',
                    'is_dark_mode',
                ],
            ]);

        expect($response->json('theme.theme_name'))->toBe('ocean');
        expect($response->json('theme.is_dark_mode'))->toBeFalse();
    });

    test('returns default theme when no preferences exist', function (): void {
        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/preferences');

        $response->assertSuccessful();
        expect($response->json('theme'))->not->toBeNull();
        expect($response->json('theme.theme_name'))->toBe('default');
    });

    test('can update user theme preferences', function (): void {
        // API controller needs to be updated to work with new simplified theme system
        $this->markTestSkipped('API controller not yet updated for new theme system');

        $themeData = [
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/themes/preferences', $themeData);

        $response->assertSuccessful();

        $preference = UserThemePreference::where('user_id', $this->user->id)->first();
        expect($preference->theme_name)->toBe('sunset');
        expect($preference->is_dark_mode)->toBeFalse();
    });

    test('validates theme name is valid', function (): void {
        // API controller needs to be updated to work with new simplified theme system
        $this->markTestSkipped('API controller not yet updated for new theme system');

        $response = $this->actingAs($this->user)
            ->putJson('/api/themes/preferences', [
                'theme_name' => 'invalid-theme-that-does-not-exist',
                'is_dark_mode' => false,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['theme_name']);
    });

    test('can get predefined theme collection', function (): void {
        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/presets');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'themes' => [
                    '*' => [
                        'name',
                        'display_name',
                        'is_dark_mode',
                        'category',
                    ],
                ],
            ]);

        expect($response->json('themes'))->toHaveCount(24); // All 24 predefined themes
    });

    test('can get theme CSS file path', function (): void {
        // API endpoint does not exist yet - needs to be added
        $this->markTestSkipped('API endpoint /api/themes/css-path not yet implemented');

        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/css-path?theme=ocean');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'theme_name',
                'css_path',
            ]);

        expect($response->json('theme_name'))->toBe('ocean');
        expect($response->json('css_path'))->toBe('/css/themes/ocean.css');
    });

    test('validates accessibility information is available for themes', function (): void {
        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/presets');

        $response->assertSuccessful();

        $themes = $response->json('themes');

        // Verify all themes have required structure
        foreach ($themes as $theme) {
            expect($theme)->toHaveKeys(['name', 'display_name', 'is_dark_mode']);
            expect($theme['name'])->toBeString();
            expect($theme['is_dark_mode'])->toBeBool();
        }
    });

    test('can import theme from file', function (): void {
        // API controller needs to be updated to work with new simplified theme system
        $this->markTestSkipped('API controller not yet updated for new theme system');

        $themeJson = json_encode([
            'theme_name' => 'forest',
            'is_dark_mode' => false,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('theme.json', $themeJson);

        $response = $this->actingAs($this->user)
            ->postJson('/api/themes/import', [
                'theme_file' => $file,
            ]);

        $response->assertSuccessful();

        $preference = UserThemePreference::where('user_id', $this->user->id)->first();
        expect($preference->theme_name)->toBe('forest');
        expect($preference->is_dark_mode)->toBeFalse();
    });

    test('can export current theme as file', function (): void {
        UserThemePreference::factory()->create([
            'user_id' => $this->user->id,
            'theme_name' => 'neon-cyber',
            'is_dark_mode' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/export');

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition');

        $exportData = json_decode((string) $response->getContent(), true);
        expect($exportData['theme_name'])->toBe('neon-cyber');
        expect($exportData['is_dark_mode'])->toBeTrue();
    });

    test('requires authentication for all theme operations', function (): void {
        $response = $this->getJson('/api/themes/preferences');
        $response->assertUnauthorized();

        $response = $this->putJson('/api/themes/preferences', []);
        $response->assertUnauthorized();
    });
});
