<?php

declare(strict_types=1);

use App\Livewire\DomainChecker;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(DomainChecker::class)
        ->assertStatus(200);
});
