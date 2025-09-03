<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->withoutVite();
});

describe('Theme API Endpoints', function (): void {
    test('can get current user theme preferences', function (): void {
        $theme = UserThemePreference::factory()->create([
            'user_id' => $this->user->id,
            'primary_color' => '#3b82f6',
            'accent_color' => '#10b981',
            'background_color' => '#f8fafc',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/preferences');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'theme' => [
                    'id',
                    'primary_color',
                    'accent_color',
                    'background_color',
                    'text_color',
                    'theme_name',
                    'is_dark_mode',
                ],
            ]);

        expect($response->json('theme.primary_color'))->toBe('#3b82f6');
    });

    test('returns default theme when no preferences exist', function (): void {
        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/preferences');

        $response->assertSuccessful();
        expect($response->json('theme'))->not->toBeNull();
        expect($response->json('theme.theme_name'))->toBe('default');
    });

    test('can update user theme preferences', function (): void {
        $themeData = [
            'primary_color' => '#8b5cf6',
            'accent_color' => '#f59e0b',
            'background_color' => '#1f2937',
            'text_color' => '#f9fafb',
            'theme_name' => 'purple_dark',
            'is_dark_mode' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/themes/preferences', $themeData);

        $response->assertSuccessful();

        $preference = UserThemePreference::where('user_id', $this->user->id)->first();
        expect($preference->primary_color)->toBe('#8b5cf6');
        expect($preference->theme_name)->toBe('purple_dark');
        expect($preference->is_dark_mode)->toBeTrue();
    });

    test('validates color hex codes', function (): void {
        $response = $this->actingAs($this->user)
            ->putJson('/api/themes/preferences', [
                'primary_color' => 'invalid-color',
                'accent_color' => 'invalid-color',
                'background_color' => '#12345',  // too short
                'text_color' => 'nothex',
                'theme_name' => 'test'
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['primary_color', 'accent_color', 'background_color', 'text_color']);
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
                        'primary_color',
                        'accent_color',
                        'background_color',
                        'text_color',
                        'is_dark_mode',
                        'preview_url',
                    ],
                ],
            ]);

        expect($response->json('themes'))->toHaveCount(10); // 5 standard + 5 seasonal themes
    });

    test('can generate custom CSS for theme', function (): void {
        $themeData = [
            'primary_color' => '#3b82f6',
            'accent_color' => '#10b981',
            'background_color' => '#ffffff',
            'text_color' => '#111827',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/themes/generate-css', $themeData);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'css',
                'custom_properties',
                'accessibility_score',
            ]);

        expect($response->json('css'))->toContain(':root');
        expect($response->json('css'))->toContain('--color-primary');
    });

    test('validates accessibility of color combinations', function (): void {
        $poorContrastTheme = [
            'primary_color' => '#ffff00', // Yellow
            'background_color' => '#ffffff', // White - poor contrast
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/themes/validate-accessibility', $poorContrastTheme);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'accessibility_score',
                'contrast_ratio',
                'wcag_level',
                'warnings',
                'suggestions',
            ]);

        expect($response->json('accessibility_score'))->toBeLessThan(0.7);
        expect($response->json('warnings'))->not->toBeEmpty();
    });

    test('can import theme from file', function (): void {
        $themeJson = json_encode([
            'theme_name' => 'imported_theme',
            'primary_color' => '#6366f1',
            'accent_color' => '#ec4899',
            'background_color' => '#f1f5f9',
            'text_color' => '#0f172a',
            'is_dark_mode' => false,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('theme.json', $themeJson);

        $response = $this->actingAs($this->user)
            ->postJson('/api/themes/import', [
                'theme_file' => $file,
            ]);

        $response->assertSuccessful();

        $preference = UserThemePreference::where('user_id', $this->user->id)->first();
        expect($preference->theme_name)->toBe('imported_theme');
    });

    test('can export current theme as file', function (): void {
        UserThemePreference::factory()->create([
            'user_id' => $this->user->id,
            'theme_name' => 'my_custom_theme',
            'primary_color' => '#6366f1',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/themes/export');

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition');

        $exportData = json_decode((string) $response->getContent(), true);
        expect($exportData['theme_name'])->toBe('my_custom_theme');
    });

    test('requires authentication for all theme operations', function (): void {
        $response = $this->getJson('/api/themes/preferences');
        $response->assertUnauthorized();

        $response = $this->putJson('/api/themes/preferences', []);
        $response->assertUnauthorized();
    });
});
