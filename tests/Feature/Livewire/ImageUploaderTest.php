<?php

declare(strict_types=1);

use App\Livewire\ImageUploader;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(ImageUploader::class)
        ->assertStatus(200);
});
