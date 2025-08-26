<?php

declare(strict_types=1);

use App\Services\ComponentMappingService;

test('can identify flux ui free components in blade templates', function (): void {
    $service = new ComponentMappingService;

    $bladeContent = '<flux:button>Test</flux:button><flux:input wire:model="test" />';

    $components = $service->identifyFluxComponents($bladeContent);

    expect($components)->toHaveCount(2);
    expect($components[0])->toMatchArray([
        'name' => 'button',
        'attributes' => [],
        'content' => 'Test',
        'is_self_closing' => false,
    ]);
    expect($components[1])->toMatchArray([
        'name' => 'input',
        'attributes' => ['wire:model' => 'test'],
        'content' => '',
        'is_self_closing' => true,
    ]);
});

test('can map free components to pro variants', function (): void {
    $service = new ComponentMappingService;

    $mapping = $service->getComponentMapping();

    expect($mapping)->toBeArray();
    expect($mapping)->toHaveKey('button');
    expect($mapping)->toHaveKey('input');
    expect($mapping)->toHaveKey('select');
    expect($mapping)->toHaveKey('table');

    // Button should have pro variants
    expect($mapping['button'])->toMatchArray([
        'pro_variant' => 'button',
        'upgrade_priority' => 'high',
        'new_attributes' => ['variant', 'size', 'loading', 'disabled'],
        'deprecated_attributes' => [],
    ]);
});

test('can determine component upgrade priority', function (): void {
    $service = new ComponentMappingService;

    expect($service->getUpgradePriority('button'))->toBe('high');
    expect($service->getUpgradePriority('input'))->toBe('high');
    expect($service->getUpgradePriority('table'))->toBe('medium');
    expect($service->getUpgradePriority('separator'))->toBe('low');
});

test('can find component dependencies', function (): void {
    $service = new ComponentMappingService;

    $dependencies = $service->getComponentDependencies('table');

    expect($dependencies)->toContain('pagination');
    expect($dependencies)->toContain('button');
});

test('can scan directory for flux components', function (): void {
    $service = new ComponentMappingService;

    // Create temporary test files
    $tempDir = sys_get_temp_dir().'/flux_test_'.uniqid();
    mkdir($tempDir);

    file_put_contents($tempDir.'/test.blade.php', '<flux:button>Click</flux:button>');
    file_put_contents($tempDir.'/other.blade.php', '<flux:input wire:model="name" />');

    $results = $service->scanDirectoryForComponents($tempDir);

    expect($results)->toHaveCount(2);

    // Find files by their content rather than order
    $testFile = collect($results)->first(fn ($r) => str_contains((string) $r['file'], 'test.blade.php'));
    $otherFile = collect($results)->first(fn ($r) => str_contains((string) $r['file'], 'other.blade.php'));

    expect($testFile)->not->toBeNull();
    expect($testFile['components'])->toHaveCount(1);
    expect($testFile['components'][0]['name'])->toBe('button');

    expect($otherFile)->not->toBeNull();
    expect($otherFile['components'])->toHaveCount(1);
    expect($otherFile['components'][0]['name'])->toBe('input');

    // Cleanup
    unlink($tempDir.'/test.blade.php');
    unlink($tempDir.'/other.blade.php');
    rmdir($tempDir);
});

test('can generate upgrade suggestions', function (): void {
    $service = new ComponentMappingService;

    $suggestions = $service->generateUpgradeSuggestions('button', ['type' => 'submit']);

    expect($suggestions)->toBeArray();
    expect($suggestions['recommended_attributes'])->toContain('variant="primary"');
    expect($suggestions['notes'])->toContain('Consider using variant instead of type');
});

test('can handle flux icon components', function (): void {
    $service = new ComponentMappingService;

    $bladeContent = '<flux:icon.chevron-down class="w-4 h-4" />';

    $components = $service->identifyFluxComponents($bladeContent);

    expect($components)->toHaveCount(1);
    expect($components[0]['name'])->toBe('icon');
    expect($components[0]['full_name'])->toBe('icon.chevron-down');
    expect($components[0]['attributes'])->toHaveKey('class');
});

test('can generate comprehensive audit report', function (): void {
    $service = new ComponentMappingService;

    // Create temporary test directory with multiple files
    $tempDir = sys_get_temp_dir().'/flux_audit_'.uniqid();
    mkdir($tempDir);

    file_put_contents($tempDir.'/form.blade.php',
        '<flux:button variant="primary">Submit</flux:button><flux:input wire:model="name" />');
    file_put_contents($tempDir.'/layout.blade.php',
        '<flux:card><flux:heading>Title</flux:heading></flux:card>');

    $results = $service->scanDirectoryForComponents($tempDir);

    expect($results)->toHaveCount(2);

    // Cleanup
    unlink($tempDir.'/form.blade.php');
    unlink($tempDir.'/layout.blade.php');
    rmdir($tempDir);
});
