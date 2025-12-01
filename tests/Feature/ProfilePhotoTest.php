<?php

declare(strict_types=1);

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('user can upload profile photo', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();

    actingAs($user);

    $file = UploadedFile::fake()->image('profile.jpg');

    Livewire::test(Profile::class)
        ->set('profilePhoto', $file)
        ->call('updateProfilePhoto')
        ->assertDispatched('profile-photo-updated');

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();
    expect($user->profilePhotoUrl())->toContain('storage/profile-photos');

    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('user can delete profile photo', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();

    actingAs($user);

    $file = UploadedFile::fake()->image('profile.jpg');

    Livewire::test(Profile::class)
        ->set('profilePhoto', $file)
        ->call('updateProfilePhoto');

    $user->refresh();
    $oldPath = $user->profile_photo_path;

    Livewire::test(Profile::class)
        ->call('deleteProfilePhoto')
        ->assertDispatched('profile-photo-deleted');

    $user->refresh();

    expect($user->profile_photo_path)->toBeNull();
    expect($user->profilePhotoUrl())->toBeNull();

    Storage::disk('public')->assertMissing($oldPath);
});

test('profile photo validates correctly', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Profile::class)
        ->call('updateProfilePhoto')
        ->assertHasErrors(['profilePhoto' => 'required']);
});

test('uploading new photo deletes old photo', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();

    actingAs($user);

    $firstFile = UploadedFile::fake()->image('profile1.jpg');

    Livewire::test(Profile::class)
        ->set('profilePhoto', $firstFile)
        ->call('updateProfilePhoto');

    $user->refresh();
    $firstPath = $user->profile_photo_path;

    Storage::disk('public')->assertExists($firstPath);

    $secondFile = UploadedFile::fake()->image('profile2.jpg');

    Livewire::test(Profile::class)
        ->set('profilePhoto', $secondFile)
        ->call('updateProfilePhoto');

    $user->refresh();

    expect($user->profile_photo_path)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

test('user initials are shown when no profile photo exists', function (): void {
    $user = User::factory()->create(['name' => 'John Doe']);

    actingAs($user);

    expect($user->profilePhotoUrl())->toBeNull();
    expect($user->initials())->toBe('JD');
});

test('profile photo url returns correct path', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'profile_photo_path' => 'profile-photos/test.jpg',
    ]);

    actingAs($user);

    $url = $user->profilePhotoUrl();

    expect($url)->toContain('storage/profile-photos/test.jpg');
});
