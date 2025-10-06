<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Core Components FluxUI Alignment', function (): void {
    test('app layout uses FluxUI color classes', function (): void {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);

        // Should not contain old custom color variables
        $html = $response->getContent();
        expect($html)->not->toContain('--color-primary-')
            ->and($html)->not->toContain('bg-primary-')
            ->and($html)->not->toContain('text-primary-');

        // Should use FluxUI standard classes
        expect($html)->toContain('dark:');
    });

    test('mobile user menu renders without custom colors', function (): void {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/dashboard');
        $html = $response->getContent();

        // Should not use deprecated color classes in mobile menu
        expect($html)->not->toContain('hover:bg-red-50 dark:hover:bg-red-900/20');
    });

    test('desktop user menu renders without custom colors', function (): void {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/dashboard');
        $html = $response->getContent();

        // Should not use deprecated color classes in desktop menu
        expect($html)->not->toContain('hover:bg-red-50 dark:hover:bg-red-900/20');
    });

    test('session skeleton uses FluxUI zinc classes', function (): void {
        $html = Blade::render('<x-session-skeleton />');

        // Should use zinc (FluxUI default) instead of hardcoded colors
        expect($html)->not->toContain('bg-gray-100')
            ->and($html)->not->toContain('bg-gray-200')
            ->and($html)->not->toContain('bg-gray-300');
    });

    test('ai generation progress uses accent colors', function (): void {
        $html = Blade::render('<x-ai-generation-progress :progress="50" />');

        // Should not use hardcoded blue/green colors
        expect($html)->not->toContain('bg-blue-500')
            ->and($html)->not->toContain('bg-green-500')
            ->and($html)->not->toContain('text-blue-600');
    });

    test('mobile bottom bar uses FluxUI classes', function (): void {
        $html = Blade::render('<x-mobile-bottom-bar />');

        // Should not use hardcoded colors
        expect($html)->not->toContain('bg-gray-800')
            ->and($html)->not->toContain('bg-gray-900')
            ->and($html)->not->toContain('border-gray-700');
    });

    test('sidebar bottom menu uses FluxUI classes', function (): void {
        $html = Blade::render('<x-sidebar-bottom-menu />');

        // Should not use hardcoded colors
        expect($html)->not->toContain('bg-gray-800')
            ->and($html)->not->toContain('bg-gray-900')
            ->and($html)->not->toContain('border-gray-700');
    });

    test('components support dark mode properly', function (): void {
        $user = User::factory()->create();

        $components = [
            ['<x-mobile-user-menu :user="$user" />', ['user' => $user]],
            ['<x-desktop-user-menu :user="$user" />', ['user' => $user]],
            ['<x-session-skeleton />', []],
            ['<x-mobile-bottom-bar />', []],
            ['<x-sidebar-bottom-menu />', []],
        ];

        foreach ($components as [$component, $data]) {
            $html = Blade::render($component, $data);
            // All components should have dark mode support
            expect($html)->toContain('dark:');
        }
    });
});
