<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserThemePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Dynamic Theme Loading', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        \App\Helpers\ThemeHelper::clearUserThemeCache();
    });
    it('loads default theme CSS file for unauthenticated users', function (): void {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('<link rel="stylesheet" href="/css/themes/default.css"', false);
    });

    it('loads user-specific theme CSS file for authenticated users', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'ocean',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('<link rel="stylesheet" href="/css/themes/ocean.css"', false);
    });

    it('applies dark class to html element when dark mode is enabled', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(5)->create();

        $user = User::factory()->create([
            'current_theme' => 'dark',
            'prefers_dark_mode' => true,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'dark',
            'is_dark_mode' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('<html lang="en" class="dark">', false);
    });

    it('does not apply dark class when light mode is enabled', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'default',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $html = $response->getContent();
        $response->assertStatus(200);

        // Should not have dark class on html element
        expect($html)->not->toContain('<html lang="en" class="dark">');
    });

    it('does not contain inline style blocks with hex colors', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'sunset',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($user)->get('/');

        $html = $response->getContent();

        // Should not contain hex color patterns in style tags
        expect($html)->not->toContain('background_color')
            ->and($html)->not->toContain('primary_color')
            ->and($html)->not->toContain('text_color');

        // Should not have inline style blocks with CSS variable definitions using hex
        expect($html)->not->toMatch('/<style>.*--theme-bg:\s*#[0-9a-fA-F]{6}/s');
    });

    it('loads correct theme when user changes theme preference', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(5)->create();

        $user = User::factory()->create([
            'current_theme' => 'forest',
            'prefers_dark_mode' => false,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'forest',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('<link rel="stylesheet" href="/css/themes/forest.css"', false);

        // Update theme preference and user model
        $user->update(['current_theme' => 'cosmic-violet', 'prefers_dark_mode' => true]);
        UserThemePreference::where('user_id', $user->id)->update([
            'theme_name' => 'cosmic-violet',
            'is_dark_mode' => true,
        ]);

        // Clear theme cache
        \App\Helpers\ThemeHelper::clearUserThemeCache();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('<link rel="stylesheet" href="/css/themes/cosmic-violet.css"', false)
            ->assertSee('<html lang="en" class="dark">', false);
    });

    it('includes Alpine.js dark mode toggle functionality', function (): void {
        $user = User::factory()->create();
        $preference = UserThemePreference::factory()->create([
            'user_id' => $user->id,
            'theme_name' => 'default',
            'is_dark_mode' => false,
        ]);

        $response = $this->actingAs($user)->get('/');

        $html = $response->getContent();

        // Should have Alpine.js dark mode state
        expect($html)->toContain('x-data')
            ->and($html)->toContain('darkMode');
    });

    it('persists dark mode preference across page loads', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(5)->create();

        $user = User::factory()->create([
            'current_theme' => 'dark',
            'prefers_dark_mode' => true,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'dark',
            'is_dark_mode' => true,
        ]);

        // First page load
        $response1 = $this->actingAs($user)->get('/dashboard');
        $response1->assertStatus(200)
            ->assertSee('<html lang="en" class="dark">', false);

        // Second page load (should still have dark mode)
        $response2 = $this->actingAs($user)->get('/dashboard');
        $response2->assertStatus(200)
            ->assertSee('<html lang="en" class="dark">', false);
    });

    it('handles themes with special characters in names', function (): void {
        // Create dummy users to avoid static cache collision
        User::factory()->count(5)->create();

        $user = User::factory()->create([
            'current_theme' => 'neon-cyber',
            'prefers_dark_mode' => true,
        ]);

        UserThemePreference::where('user_id', $user->id)->delete();
        UserThemePreference::create([
            'user_id' => $user->id,
            'theme_name' => 'neon-cyber',
            'is_dark_mode' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertSee('<link rel="stylesheet" href="/css/themes/neon-cyber.css"', false);
    });
});
