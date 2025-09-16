<?php

declare(strict_types=1);

use App\Livewire\ImageUploader;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

it('renders successfully', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Livewire::test(ImageUploader::class, ['project' => $project])
        ->assertStatus(200);
});

it('can handle multiple file uploads', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
    $file2 = UploadedFile::fake()->image('test2.jpg', 200, 200);
    $file3 = UploadedFile::fake()->image('test3.png', 150, 150);

    $component = Livewire::test(ImageUploader::class, ['project' => $project]);

    // Upload multiple files at once
    $component->set('images', [$file1, $file2, $file3]);

    // Check that all files are in the images array
    expect($component->get('images'))->toHaveCount(3);
});

it('validates file types and sizes', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $validFile = UploadedFile::fake()->image('valid.jpg', 100, 100);
    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    $component = Livewire::test(ImageUploader::class, ['project' => $project]);

    $component->set('images', [$validFile, $invalidFile]);

    // Should have validation errors for the PDF file
    $component->assertHasErrors(['images.1']);

    // Should only have the valid file in the array
    expect($component->get('images'))->toHaveCount(1);
});
