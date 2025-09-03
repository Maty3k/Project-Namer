<?php

declare(strict_types=1);

use App\Livewire\ThemeCustomizer;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(ThemeCustomizer::class)
        ->assertStatus(200);
});
