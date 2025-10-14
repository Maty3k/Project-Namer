<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('Simplified UserThemePreference Model', function (): void {
    it('has getThemeCssPath method that returns correct path format', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
        ]);

        expect($preference->getThemeCssPath())
            ->toBe('/css/themes/ocean.css');
    });

    it('returns default theme with correct structure', function (): void {
        $defaultTheme = UserThemePreference::getDefaultTheme();

        expect($defaultTheme)->toBeArray()
            ->and($defaultTheme)->toHaveKeys(['theme_name', 'is_dark_mode', 'border_radius', 'font_size', 'compact_mode'])
            ->and($defaultTheme['theme_name'])->toBe('default')
            ->and($defaultTheme['is_dark_mode'])->toBeFalse()
            ->and($defaultTheme['border_radius'])->toBe('medium')
            ->and($defaultTheme['font_size'])->toBe('medium')
            ->and($defaultTheme['compact_mode'])->toBeFalse();
    });

    it('casts is_dark_mode as boolean', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'is_dark_mode' => true,
        ]);

        expect($preference->is_dark_mode)->toBeTrue()
            ->and($preference->is_dark_mode)->toBeBool();
    });

    it('casts compact_mode as boolean', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'compact_mode' => true,
        ]);

        expect($preference->compact_mode)->toBeTrue()
            ->and($preference->compact_mode)->toBeBool();
    });

    it('belongs to user', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($preference->user)->toBeInstanceOf(User::class)
            ->and($preference->user->id)->toBe($user->id);
    });

    it('factory creates records with valid predefined theme names', function (): void {
        $validThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($validThemes)->toContain($preference->theme_name);
    });

    it('does not have color-related columns', function (): void {
        $colorColumns = [
            'primary_color',
            'secondary_color',
            'accent_color',
            'background_color',
            'surface_color',
            'text_primary_color',
            'text_secondary_color',
            'dark_background_color',
            'dark_surface_color',
            'dark_text_primary_color',
            'dark_text_secondary_color',
            'text_color',
        ];

        foreach ($colorColumns as $column) {
            expect(Schema::hasColumn('user_theme_preferences', $column))
                ->toBeFalse("Column {$column} should not exist in simplified schema");
        }
    });

    it('does not have is_custom_theme column', function (): void {
        expect(Schema::hasColumn('user_theme_preferences', 'is_custom_theme'))
            ->toBeFalse('Column is_custom_theme should not exist in simplified schema');
    });

    it('does not have theme_config column', function (): void {
        expect(Schema::hasColumn('user_theme_preferences', 'theme_config'))
            ->toBeFalse('Column theme_config should not exist in simplified schema');
    });

    it('has required simplified columns', function (): void {
        expect(Schema::hasColumn('user_theme_preferences', 'theme_name'))->toBeTrue()
            ->and(Schema::hasColumn('user_theme_preferences', 'is_dark_mode'))->toBeTrue()
            ->and(Schema::hasColumn('user_theme_preferences', 'border_radius'))->toBeTrue()
            ->and(Schema::hasColumn('user_theme_preferences', 'font_size'))->toBeTrue()
            ->and(Schema::hasColumn('user_theme_preferences', 'compact_mode'))->toBeTrue();
    });

    it('validates theme_name is one of predefined themes', function (): void {
        $user = User::factory()->create();

        // This should work with valid theme
        $validPreference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
        ]);

        expect($validPreference->theme_name)->toBe('ocean');

        // Test that factory only generates valid themes
        $randomPreference = UserThemePreference::factory()->create();
        $validThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        expect($validThemes)->toContain($randomPreference->theme_name);
    });

    it('has composite index on theme_name and is_dark_mode', function (): void {
        $indexes = Schema::getIndexes('user_theme_preferences');
        $indexColumns = array_map(fn ($index) => $index['columns'], $indexes);

        $hasCompositeIndex = false;
        foreach ($indexColumns as $columns) {
            if ($columns === ['theme_name', 'is_dark_mode']) {
                $hasCompositeIndex = true;
                break;
            }
        }

        expect($hasCompositeIndex)->toBeTrue('Should have composite index on theme_name and is_dark_mode');
    });
});
