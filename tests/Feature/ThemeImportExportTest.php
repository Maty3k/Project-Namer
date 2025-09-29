<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('theme import and export cycle works correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a test theme JSON
    $themeData = [
        'theme_name' => 'Test Theme',
        'primary_color' => '#ff6b6b',
        'accent_color' => '#4ecdc4',
        'background_color' => '#f7fff7',
        'text_color' => '#2d3436',
        'is_dark_mode' => false,
    ];

    $json = json_encode($themeData);
    $file = UploadedFile::fake()->createWithContent('theme.json', $json);

    // Import the theme
    $component = Livewire::test(ThemeCustomizer::class)
        ->set('themeFile', $file)
        ->call('importTheme')
        ->assertDispatched('theme-imported')
        ->assertSet('primaryColor', '#ff6b6b')
        ->assertSet('accentColor', '#4ecdc4')
        ->assertSet('backgroundColor', '#f7fff7')
        ->assertSet('textColor', '#2d3436')
        ->assertSet('themeName', 'Test Theme')
        ->assertSet('isDarkMode', false);

    // Export the theme and verify it matches
    $component->call('exportTheme')
        ->assertDispatched('download-theme');
});

test('theme import handles invalid JSON gracefully', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invalidJson = 'not valid json';
    $file = UploadedFile::fake()->createWithContent('theme.json', $invalidJson);

    Livewire::test(ThemeCustomizer::class)
        ->set('themeFile', $file)
        ->call('importTheme')
        ->assertDispatched('theme-error', 'Invalid theme file format. Please upload a valid JSON theme file.');
});

test('theme import validates required fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Missing required fields
    $incompleteTheme = [
        'theme_name' => 'Incomplete',
        'primary_color' => '#ff6b6b',
        // Missing background_color and text_color
    ];

    $json = json_encode($incompleteTheme);
    $file = UploadedFile::fake()->createWithContent('theme.json', $json);

    Livewire::test(ThemeCustomizer::class)
        ->set('themeFile', $file)
        ->call('importTheme')
        ->assertDispatched('theme-error', 'Invalid or missing background_color in theme file');
});

test('theme import validates color format', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invalidColorTheme = [
        'theme_name' => 'Invalid Colors',
        'primary_color' => 'red', // Invalid format
        'background_color' => '#ffffff',
        'text_color' => '#111827',
    ];

    $json = json_encode($invalidColorTheme);
    $file = UploadedFile::fake()->createWithContent('theme.json', $json);

    Livewire::test(ThemeCustomizer::class)
        ->set('themeFile', $file)
        ->call('importTheme')
        ->assertDispatched('theme-error', 'Invalid or missing primary_color in theme file');
});

test('theme export generates valid JSON', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ThemeCustomizer::class)
        ->set('themeName', 'Export Test')
        ->set('primaryColor', '#123456')
        ->set('accentColor', '#654321')
        ->set('backgroundColor', '#ffffff')
        ->set('textColor', '#000000')
        ->set('isDarkMode', true)
        ->call('exportTheme')
        ->assertDispatched('download-theme');
});
