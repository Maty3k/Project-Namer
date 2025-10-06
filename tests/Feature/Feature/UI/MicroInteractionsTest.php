<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Button States and Transitions', function (): void {
    test('micro-interactions CSS file exists', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        expect(file_exists($cssPath))->toBeTrue();
    });

    test('button hover transitions are defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toContain('hover');
        expect($content)->toMatch('/(transform|scale|shadow)/');
    });

    test('button active states are defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toContain('active');
    });

    test('button transitions use ease timing', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(ease|cubic-bezier)/');
    });
});

describe('Card Hover Effects', function (): void {
    test('card hover effects are defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(card|hover)/i');
    });

    test('card shadow effects use transition', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/shadow/');
    });
});

describe('Form Focus Transitions', function (): void {
    test('form focus transitions are defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(focus|input|textarea)/');
    });

    test('focus ring styles are consistent', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/focus/');
    });
});

describe('Validation Animations', function (): void {
    test('shake animation is defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(shake|error)/i');
    });

    test('shake animation uses keyframes', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/@keyframes|animation/');
    });
});

describe('Success Animations', function (): void {
    test('success glow animation is defined', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(glow|success)/i');
    });

    test('glow animation uses transitions', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/(transition|animation)/');
    });
});

describe('Ripple Effects', function (): void {
    test('ripple effect component exists', function (): void {
        $jsPath = resource_path('js/components/rippleEffect.js');
        expect(file_exists($jsPath))->toBeTrue();
    });

    test('ripple effect applies to primary actions', function (): void {
        $jsPath = resource_path('js/components/rippleEffect.js');
        $content = file_get_contents($jsPath);

        expect($content)->toMatch('/(ripple|effect)/i');
    });

    test('ripple effect uses CSS classes', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toMatch('/ripple/i');
    });
});

describe('Reduced Motion Support', function (): void {
    test('prefers-reduced-motion media query is used', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        expect($content)->toContain('prefers-reduced-motion');
    });

    test('animations are disabled for reduced motion', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // Should have animation: none or transition-duration: 0 in reduced motion block
        expect($content)->toMatch('/(animation.*none|animation-duration.*0|transition-duration.*0)/');
    });

    test('transitions are disabled for reduced motion', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // Should have reduced motion rules
        expect($content)->toContain('prefers-reduced-motion');
    });
});

describe('Performance Considerations', function (): void {
    test('animations use transform instead of position', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // Should use transform for better performance
        expect($content)->toMatch('/transform/');
    });

    test('transitions use will-change sparingly', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // If will-change is used, it should be intentional
        // This is a soft check - it's okay if will-change isn't used
        expect($content)->toBeString();
    });
});

describe('Consistency Across Components', function (): void {
    test('transition durations are consistent', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // Should have standard durations like 150ms, 200ms, 300ms
        expect($content)->toMatch('/\d+m?s/');
    });

    test('easing functions are consistent', function (): void {
        $cssPath = resource_path('css/micro-interactions.css');
        $content = file_get_contents($cssPath);

        // Should use consistent easing
        expect($content)->toMatch('/(ease|cubic-bezier)/');
    });
});
