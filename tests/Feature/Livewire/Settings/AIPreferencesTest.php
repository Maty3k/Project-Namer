<?php

use App\Livewire\Settings\AIPreferences;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(AIPreferences::class)
        ->assertStatus(200);
});
