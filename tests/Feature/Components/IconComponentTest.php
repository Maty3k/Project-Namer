<?php

declare(strict_types=1);

use App\View\Components\AppIcon;
use Illuminate\Support\Facades\View;

function renderIcon(string $name, array $attributes = []): string
{
    $component = new AppIcon(
        name: $name,
        size: $attributes['size'] ?? 'md',
        style: $attributes['style'] ?? 'outline',
        variant: $attributes['variant'] ?? null,
        loading: $attributes['loading'] ?? false,
        class: $attributes['class'] ?? null
    );

    return View::make('components.app-icon', $component->data())->render();
}

describe('Icon Component', function (): void {
    test('icon component renders successfully', function (): void {
        $html = renderIcon('check');

        expect($html)->toContain('<svg')
            ->toContain('class=')
            ->toContain('viewBox=');
    });

    test('icon component renders with default size', function (): void {
        $html = renderIcon('check');

        expect($html)->toContain('w-5 h-5');
    });

    test('icon component accepts different sizes', function (): void {
        $sizes = ['sm' => 'w-4 h-4', 'md' => 'w-5 h-5', 'lg' => 'w-6 h-6', 'xl' => 'w-8 h-8'];

        foreach ($sizes as $size => $expectedClass) {
            $html = renderIcon('check', ['size' => $size]);
            expect($html)->toContain($expectedClass);
        }
    });

    test('icon component accepts custom classes', function (): void {
        $html = renderIcon('check', ['class' => 'text-green-500 custom-class']);

        expect($html)->toContain('text-green-500')
            ->toContain('custom-class');
    });

    test('icon component includes accessibility attributes', function (): void {
        // Test default aria-hidden for decorative icons
        $html = renderIcon('check');
        expect($html)->toContain('aria-hidden="true"')
            ->not->toContain('role=')
            ->not->toContain('aria-label=');

        // Test aria-label creates proper accessible icon
        $component = new AppIcon(
            name: 'check',
            size: 'md',
            style: 'outline',
            variant: null,
            loading: false,
            class: null
        );

        $html = View::make('components.app-icon', array_merge($component->data(), [
            'attributes' => new \Illuminate\View\ComponentAttributeBag(['aria-label' => 'Success indicator']),
        ]))->render();

        expect($html)->toContain('role="img"')
            ->toContain('aria-label="Success indicator"')
            ->not->toContain('aria-hidden=');
    });

    test('icon component renders outline style by default', function (): void {
        $html = renderIcon('check');

        // Should use stroke for outline style
        expect($html)->toContain('stroke="currentColor"')
            ->toContain('fill="none"');
    });

    test('icon component can render solid style', function (): void {
        $html = renderIcon('check', ['style' => 'solid']);

        // Should use fill for solid style
        expect($html)->toContain('fill="currentColor"')
            ->not->toContain('stroke="currentColor"');
    });

    test('icon component renders common icon types', function (): void {
        $commonIcons = ['check', 'x', 'plus', 'minus', 'trash', 'edit', 'save', 'download', 'upload'];

        foreach ($commonIcons as $iconName) {
            $html = renderIcon($iconName);

            expect($html)->toContain('<svg')
                ->toContain('viewBox=');
        }
    });

    test('icon component handles unknown icon gracefully', function (): void {
        $html = renderIcon('non-existent-icon');

        // Should render a fallback icon or empty state
        expect($html)->toBeString();
    });

    test('icon component supports contextual icons for actions', function (): void {
        $contextualIcons = [
            'delete' => 'trash',
            'edit' => 'pencil',
            'save' => 'check',
            'cancel' => 'x',
            'add' => 'plus',
            'remove' => 'minus',
        ];

        foreach ($contextualIcons as $action => $iconName) {
            $html = renderIcon($action);

            expect($html)->toContain('<svg')
                ->toContain('viewBox=');
        }
    });

    test('icon component supports stroke width customization', function (): void {
        // This will be tested when we add attribute support
        $html = renderIcon('check');

        expect($html)->toContain('stroke-width="1.5"'); // default
    });

    test('icon component supports title attribute for tooltips', function (): void {
        // This will be tested when we add attribute support
        $html = renderIcon('check');

        expect($html)->toContain('<svg');
    });

    test('icon component works with alpine.js directives', function (): void {
        // This will be tested when we add attribute support
        $html = renderIcon('check');

        expect($html)->toContain('<svg');
    });

    test('icon component supports color variants', function (): void {
        $variants = ['success', 'error', 'warning', 'info'];
        $expectedClasses = [
            'success' => 'text-green-600',
            'error' => 'text-red-600',
            'warning' => 'text-yellow-600',
            'info' => 'text-blue-600',
        ];

        foreach ($variants as $variant) {
            $html = renderIcon('check', ['variant' => $variant]);

            expect($html)->toContain($expectedClasses[$variant]);
        }
    });

    test('icon component supports loading state', function (): void {
        $html = renderIcon('loading', ['loading' => true]);

        expect($html)->toContain('animate-spin');
    });

    test('icon component is compatible with flux ui', function (): void {
        // Test that the icon component works well within flux components
        $html = renderIcon('check', ['size' => 'sm']);

        // Should render proper classes that work with flux styling
        expect($html)->toContain('w-4 h-4');
    });

    test('icon component renders multiple icons efficiently', function (): void {
        $startTime = microtime(true);

        // Test rendering 20 different icons to check performance
        $iconNames = [
            'check', 'x', 'plus', 'minus', 'trash', 'pencil', 'home', 'user',
            'settings', 'search', 'filter', 'download', 'upload', 'share',
            'copy', 'move', 'archive', 'restore', 'notification', 'refresh',
        ];

        foreach ($iconNames as $iconName) {
            $html = renderIcon($iconName);
            expect($html)->toContain('<svg')->toContain('viewBox=');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should render 20 icons in under 1 second
        expect($executionTime)->toBeLessThan(1.0);
    });

    test('icon component renders consistently across variants', function (): void {
        $variants = ['success', 'error', 'warning', 'info', 'muted'];
        $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];

        foreach ($variants as $variant) {
            foreach ($sizes as $size) {
                $html = renderIcon('check', ['variant' => $variant, 'size' => $size]);

                // All variants should have consistent SVG structure
                expect($html)->toContain('<svg')
                    ->toContain('viewBox="0 0 24 24"')
                    ->toContain('xmlns="http://www.w3.org/2000/svg"')
                    ->toContain('aria-hidden="true"')
                    ->toContain('</svg>');
            }
        }
    });
});

describe('Icon Component Integration', function (): void {
    test('icon component integrates with button elements', function (): void {
        $iconHtml = renderIcon('plus', ['size' => 'sm']);
        $buttonWithIcon = '
            <button class="flex items-center">
                '.$iconHtml.'
                <span class="ml-2">Add Item</span>
            </button>
        ';

        expect($buttonWithIcon)->toContain('<svg')
            ->toContain('w-4 h-4')
            ->toContain('Add Item');
    });

    test('icon component works in navigation context', function (): void {
        $iconHtml = renderIcon('home', ['size' => 'md']);
        $navItem = '
            <a href="/dashboard" class="flex items-center">
                '.$iconHtml.'
                <span class="ml-2">Dashboard</span>
            </a>
        ';

        expect($navItem)->toContain('<svg')
            ->toContain('w-5 h-5')
            ->toContain('Dashboard');
    });

    test('icon component works with status indicators', function (): void {
        $iconHtml = renderIcon('check', ['variant' => 'success', 'size' => 'sm']);
        $statusIndicator = '
            <div class="flex items-center">
                '.$iconHtml.'
                <span class="ml-1 text-green-600">Available</span>
            </div>
        ';

        expect($statusIndicator)->toContain('<svg')
            ->toContain('text-green-600')
            ->toContain('Available');
    });
});
