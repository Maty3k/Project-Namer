<?php

declare(strict_types=1);

use Livewire\Volt\Volt;

it('can render', function (): void {
    $component = Volt::test('export-generator');

    $component->assertSee('');
});
