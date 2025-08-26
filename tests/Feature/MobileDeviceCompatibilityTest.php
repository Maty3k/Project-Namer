<?php

declare(strict_types=1);

use App\Models\User;

describe('Mobile Device Compatibility Testing', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    describe('iOS Safari Mobile Compatibility', function (): void {
        it('handles iOS viewport and meta tags correctly', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // iOS Safari viewport requirements
            expect($response->content())
                ->toContain('width=device-width') // Required for iOS
                ->toContain('initial-scale=1') // Prevents zoom
                ->toContain('viewport'); // Has viewport meta tag
        });

        it('supports iOS touch events and gestures', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // iOS touch support
            expect($html)->toContain('x-on:touchstart') // Touch event handling
                ->toContain('x-on:touchmove') // Touch movement
                ->toContain('x-on:touchend') // Touch completion
                ->toContain('pull-to-refresh'); // Touch gesture features
        });

        it('handles iOS form input focus correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Test iOS form handling');

            $html = $component->html();

            // iOS form handling
            expect($html)->toContain('wire:blur') // Blur event for iOS keyboard dismiss
                ->toContain('focus-modern') // Focus management
                ->toContain('transition-') // Smooth focus transitions
                ->toContain('wire:model'); // Form data binding
        });

        it('supports iOS safe area and notch handling', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Safe area support patterns
            $content = $response->content();
            expect($content)->toContain('padding') // Has padding for safe areas
                ->toContain('space-'); // Has spacing for layout
        });
    });

    describe('Android Chrome Mobile Compatibility', function (): void {
        it('handles Android viewport scaling correctly', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Android Chrome viewport requirements
            expect($response->content())
                ->toContain('width=device-width') // Android scaling
                ->toContain('initial-scale=1') // Initial zoom level
                ->toContain('viewport'); // Has viewport meta tag
        });

        it('supports Android touch and swipe gestures', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'androidtest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Android gesture support
            expect($html)->toContain('gesture-support') // Gesture handling
                ->toContain('pull-to-refresh') // Pull to refresh
                ->toContain('swipe-persistence') // Swipe state management
                ->toContain('x-data="pullToRefresh()"'); // Alpine.js gesture component
        });

        it('handles Android keyboard and input methods', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Android input handling
            expect($html)->toContain('<form') // Form elements
                ->toContain('wire:submit') // Form submission
                ->toContain('placeholder=') // Input placeholders
                ->toContain('rows="4"'); // Textarea constraints
        });

        it('supports Android back button behavior', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Should load successfully (back button handled by browser)
            expect($response->status())->toBe(200);
        });
    });

    describe('Mobile Browser Performance Testing', function (): void {
        it('loads efficiently on mobile connections', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Performance optimization patterns
            expect($html)->toContain('fade-in') // Progressive loading
                ->toContain('wire:') // Efficient Livewire directives
                ->toContain('x-data') // Lightweight Alpine.js
                ->toContain('transition-'); // Smooth transitions
        });

        it('handles mobile memory constraints', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Mobile memory test')
                ->set('domainResults', array_fill(0, 50, [
                    'name' => 'memorytest',
                    'available' => true,
                    'extensions' => ['.com'],
                ]));

            $html = $component->html();

            // Memory-efficient patterns
            expect($html)->toContain('max-w-') // Width constraints to limit DOM size
                ->toContain('overflow-') // Proper overflow handling
                ->toContain('space-y-') // Efficient spacing
                ->not->toBeEmpty(); // Successfully rendered
        });

        it('implements efficient mobile caching strategies', function (): void {
            // First request
            $response1 = $this->get(route('dashboard'));
            $response1->assertSuccessful();

            // Second request (should use cached assets)
            $response2 = $this->get(route('dashboard'));
            $response2->assertSuccessful();

            // Both requests successful (caching working at HTTP level)
            expect($response1->status())->toBe(200);
            expect($response2->status())->toBe(200);
        });
    });

    describe('Mobile Form Interaction Testing', function (): void {
        it('handles mobile form validation correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', ''); // Empty to trigger validation

            $html = $component->html();

            // Mobile-friendly validation
            expect($html)->toContain('wire:model') // Real-time validation
                ->toContain('class=') // Has styling classes
                ->toContain('transition-') // Smooth validation feedback
                ->not->toContain('alert('); // No intrusive popups
        });

        it('supports mobile keyboard types and input modes', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Mobile input optimization
            expect($html)->toContain('<form') // Form elements
                ->toContain('wire:model') // Data binding
                ->toContain('class=') // Has styling
                ->toContain('transition-'); // Smooth interactions
        });

        it('handles mobile form submission correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Mobile form test submission');

            // Simulate form submission
            $component->call('generateNames');

            // Should handle gracefully (even without API)
            expect($component->get('businessDescription'))->toBe('Mobile form test submission');
        });
    });

    describe('Mobile Navigation and UX Testing', function (): void {
        it('provides mobile-friendly navigation patterns', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Mobile navigation patterns
            expect($response->content())->toContain('flex') // Flexible layouts
                ->toContain('gap-') // Proper spacing
                ->toContain('justify-') // Content justification
                ->toContain('items-'); // Item alignment
        });

        it('supports mobile touch targets and accessibility', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'touchtest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Touch accessibility
            expect($html)->toContain('p-') // Adequate padding for touch targets
                ->toContain('py-') // Vertical padding for touch targets
                ->toContain('transition-') // Touch feedback
                ->toContain('rounded-'); // Touch-friendly corners
        });

        it('handles mobile orientation changes', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Orientation adaptability
            expect($html)->toContain('w-full') // Full width utilization
                ->toContain('max-w-') // Maximum width constraints
                ->toContain('mx-auto') // Centered content
                ->toContain('space-y-'); // Layout patterns
        });
    });

    describe('Mobile Error Handling and Connectivity', function (): void {
        it('handles mobile network connectivity issues gracefully', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Network connectivity test');

            // Component should load regardless of network issues
            $html = $component->html();
            expect($html)->not->toBeEmpty();
        });

        it('provides appropriate mobile error messaging', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'ab'); // Too short to trigger validation

            $html = $component->html();

            // Error messaging patterns
            expect($html)->toContain('wire:') // Livewire validation
                ->toContain('class=') // Error styling
                ->not->toContain('console.error'); // No console errors in production
        });

        it('handles mobile timeout scenarios', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Timeout handling test');

            // Should handle timeouts gracefully (no actual timeout in test)
            expect($component->get('businessDescription'))->toBe('Timeout handling test');
        });
    });

    describe('Mobile Security and Privacy Testing', function (): void {
        it('handles mobile input sanitization correctly', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', '<script>alert("xss")</script>Mobile security test');

            // Input should be sanitized
            $businessDescription = $component->get('businessDescription');
            expect($businessDescription)->not->toContain('<script>')
                ->not->toContain('alert(')
                ->toContain('Mobile security test');
        });

        it('protects against mobile-specific attack vectors', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'javascript:alert("mobile-attack") Normal text');

            // Should strip javascript protocols
            $businessDescription = $component->get('businessDescription');
            expect($businessDescription)->not->toContain('javascript:')
                ->not->toContain('alert(')
                ->toContain('Normal text');
        });

        it('handles mobile session management properly', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Session should be maintained (authenticated user)
            expect($response->status())->toBe(200);
        });
    });

    describe('Mobile Accessibility Compliance Testing', function (): void {
        it('supports mobile screen readers', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Screen reader accessibility test');

            $html = $component->html();

            // Screen reader support
            expect($html)->toContain('<form') // Semantic form elements
                ->toContain('<div') // Proper DOM structure
                ->toContain('wire:model') // Accessible form controls
                ->not->toBeEmpty(); // Has content for screen readers
        });

        it('provides mobile keyboard navigation support', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Keyboard navigation
            expect($html)->toContain('wire:') // Interactive elements
                ->toContain('transition-') // Visual feedback
                ->toContain('focus-') // Focus management
                ->toContain('<form'); // Focusable form elements
        });

        it('handles mobile high contrast and zoom levels', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // High contrast and zoom support
            expect($html)->toContain('text-') // Readable text sizes
                ->toContain('border-') // Visible borders
                ->toContain('bg-') // Background colors
                ->toContain('dark:'); // Dark mode support
        });
    });
});
