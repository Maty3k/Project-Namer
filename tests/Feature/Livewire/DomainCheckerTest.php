<?php

use App\Livewire\DomainChecker;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(DomainChecker::class)
        ->assertStatus(200);
});
