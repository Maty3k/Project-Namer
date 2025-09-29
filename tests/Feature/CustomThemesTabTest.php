<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use App\Models\CustomTheme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('custom themes tab appears when user has imported themes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a custom theme for the user
    CustomTheme::create([
        'user_id' => $user->id,
        'theme_name' => 'My Custom Theme',
        'primary_color' => '#ff0000',
        'accent_color' => '#00ff00',
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'is_dark_mode' => false,
        'is_imported' => true,
    ]);

    Livewire::test(ThemeCustomizer::class)
        ->assertSee('Custom')
        ->assertSee('My Custom Theme');
});

test('custom themes tab does not appear when user has no imported themes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // When no custom themes, the custom category button should not be in the available categories
    $component = Livewire::test(ThemeCustomizer::class);

    expect($component->get('availableCategories'))->not->toContain('custom');
});

test('importing a theme saves it to custom themes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $themeData = [
        'theme_name' => 'Imported Theme',
        'primary_color' => '#123456',
        'accent_color' => '#654321',
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'is_dark_mode' => false,
    ];

    $json = json_encode($themeData);
    $file = UploadedFile::fake()->createWithContent('theme.json', $json);

    Livewire::test(ThemeCustomizer::class)
        ->set('themeFile', $file)
        ->call('importTheme')
        ->assertDispatched('theme-imported');

    // Verify the theme was saved to custom themes
    $customTheme = CustomTheme::where('user_id', $user->id)
        ->where('theme_name', 'Imported Theme')
        ->first();

    expect($customTheme)->not->toBeNull();
    expect($customTheme->primary_color)->toBe('#123456');
    expect($customTheme->is_imported)->toBeTrue();
});

test('user can apply custom imported theme', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customTheme = CustomTheme::create([
        'user_id' => $user->id,
        'theme_name' => 'Apply This Theme',
        'primary_color' => '#aabbcc',
        'accent_color' => '#ddeeff',
        'background_color' => '#ffffff',
        'text_color' => '#112233',
        'is_dark_mode' => true,
        'is_imported' => true,
    ]);

    Livewire::test(ThemeCustomizer::class)
        ->call('applyCustomTheme', $customTheme->id)
        ->assertSet('themeName', 'Apply This Theme')
        ->assertSet('primaryColor', '#aabbcc')
        ->assertSet('accentColor', '#ddeeff')
        ->assertSet('backgroundColor', '#ffffff')
        ->assertSet('textColor', '#112233')
        ->assertSet('isDarkMode', true)
        ->assertDispatched('theme-updated');
});

test('user can delete custom imported theme', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customTheme = CustomTheme::create([
        'user_id' => $user->id,
        'theme_name' => 'Delete Me',
        'primary_color' => '#ff0000',
        'accent_color' => '#00ff00',
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'is_dark_mode' => false,
        'is_imported' => true,
    ]);

    Livewire::test(ThemeCustomizer::class)
        ->call('deleteCustomTheme', $customTheme->id)
        ->assertDispatched('theme-deleted', 'Custom theme deleted successfully');

    // Verify the theme was deleted
    expect(CustomTheme::find($customTheme->id))->toBeNull();
});

test('user cannot apply another users custom theme', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $customTheme = CustomTheme::create([
        'user_id' => $user1->id,
        'theme_name' => 'Private Theme',
        'primary_color' => '#ff0000',
        'accent_color' => '#00ff00',
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'is_dark_mode' => false,
        'is_imported' => true,
    ]);

    $this->actingAs($user2);

    Livewire::test(ThemeCustomizer::class)
        ->call('applyCustomTheme', $customTheme->id)
        ->assertDispatched('theme-error', 'Custom theme not found');
});
