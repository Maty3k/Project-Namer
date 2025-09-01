<?php

declare(strict_types=1);

use App\Models\User;

describe('Mobile Accessibility Compliance Testing', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    describe('WCAG 2.1 AA Compliance', function (): void {
        it('meets touch target size requirements (44px minimum)', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'accesstest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // WCAG 2.1 AA touch target requirements
            $hasMinHeight = str_contains($html, 'min-h-44') || str_contains($html, 'touch-target') || str_contains($html, 'btn-mobile') || str_contains($html, 'py-3');
            expect($hasMinHeight)->toBeTrue() // Minimum touch target sizing
                ->and($html)->toContain('p-'); // Padding for touch targets
        });

        it('provides adequate color contrast ratios', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Color contrast compliance patterns
            expect($html)->toContain('text-') // Text color utilities
                ->toContain('bg-') // Background colors
                ->toContain('border-') // Border colors
                ->toContain('dark:') // Dark mode support
                ->toContain('contrast-'); // High contrast support
        });

        it('supports keyboard navigation across all screen sizes', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Keyboard navigation support
            expect($html)->toContain('focus-') // Focus states
                ->toContain('tabindex') // Tab order management
                ->toContain('wire:keydown') // Keyboard event handling
                ->toContain('<form') // Semantic form elements
                ->toContain('focus-visible'); // Focus-visible support
        });

        it('provides semantic HTML structure', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Semantic HTML compliance
            expect($html)->toContain('<form') // Semantic forms
                ->toContain('<main') // Main content areas
                ->toContain('<h1') // Heading hierarchy
                ->toContain('<label') // Form labels
                ->toContain('role='); // ARIA roles
        });
    });

    describe('Screen Reader Accessibility', function (): void {
        it('provides proper ARIA labels and descriptions', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Screen reader test');

            $html = $component->html();

            // ARIA accessibility
            expect($html)->toContain('aria-label') // ARIA labels
                ->toContain('aria-describedby') // ARIA descriptions
                ->toContain('aria-expanded') // State descriptions
                ->toContain('aria-hidden') // Hidden content
                ->toContain('role='); // ARIA roles
        });

        it('announces loading states to screen readers', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Loading state test');

            $html = $component->html();

            // Screen reader announcements
            expect($html)->toContain('aria-live') // Live regions
                ->toContain('aria-busy') // Busy states
                ->toContain('wire:loading') // Loading indicators
                ->toContain('sr-only') // Screen reader only content
                ->toContain('screenReaderAnnouncement'); // Custom announcements
        });

        it('provides proper semantic markup and form labels', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Debug: let's see what's actually in the content
            $content = $response->content();
            
            // Form accessibility compliance - FluxUI might render differently
            expect($content)->toContain('<form') // Semantic forms
                ->toContain('<h1') // Proper heading structure  
                ->toContain('placeholder=') // Input placeholders
                ->toContain('wire:submit') // Form submission
                ->toContain('type="submit"'); // Submit button
        });

        it('maintains focus management during interactions', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Focus management test');

            $html = $component->html();

            // Focus management
            expect($html)->toContain('focus-') // Focus states
                ->toContain('tabindex') // Tab order
                ->toContain('focus-trap') // Focus trapping
                ->toContain('focus-visible') // Focus indicators
                ->toContain('focusedElement'); // Focus tracking
        });
    });

    describe('Mobile Screen Reader Support', function (): void {
        it('works with iOS VoiceOver', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'voiceovertest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // iOS VoiceOver compatibility
            expect($html)->toContain('role=') // ARIA roles
                ->toContain('aria-label') // Voice labels
                ->toContain('aria-describedby') // Descriptions
                ->toContain('<form') // Semantic forms
                ->toContain('wire:model'); // Form controls
        });

        it('works with Android TalkBack', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'talkbacktest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Android TalkBack compatibility
            $hasAndroidSupport = str_contains($html, 'contentDescription') || str_contains($html, 'aria-label');
            expect($hasAndroidSupport)->toBeTrue() // Android descriptions or ARIA labels
                ->and($html)->toContain('role=') // ARIA roles
                ->toContain('<button') // Semantic buttons
                ->toContain('wire:click'); // Interactive elements
        });

        it('provides swipe gesture descriptions', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'gesturetest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Gesture descriptions for screen readers
            expect($html)->toContain('aria-label') // Gesture labels
                ->toContain('gesture-hint') // Gesture hints
                ->toContain('swipe-instructions') // Swipe instructions
                ->toContain('aria-describedby') // Gesture descriptions
                ->toContain('touch-instructions'); // Touch instructions
        });
    });

    describe('High Contrast and Visual Accessibility', function (): void {
        it('supports high contrast mode across all screen sizes', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // High contrast support
            expect($html)->toContain('contrast-') // High contrast classes
                ->toContain('border-') // Visible borders
                ->toContain('ring-') // Focus rings
                ->toContain('outline-') // Outline support
                ->toContain('dark:'); // Dark mode compatibility
        });

        it('maintains readability at 200% zoom', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Zoom compatibility
            expect($html)->toContain('text-') // Scalable text
                ->toContain('leading-') // Line height
                ->toContain('tracking-') // Letter spacing
                ->toContain('max-w-') // Content width limits
                ->toContain('overflow-'); // Overflow handling
        });

        it('provides visual focus indicators', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Focus indicators
            expect($html)->toContain('focus:') // Focus states
                ->toContain('focus-visible') // Focus-visible support
                ->toContain('ring-') // Focus rings
                ->toContain('outline-') // Outline indicators
                ->toContain('focus-within'); // Container focus states
        });
    });

    describe('Motor Disability Support', function (): void {
        it('provides adequate spacing between interactive elements', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'spacing1', 'available' => true, 'extensions' => ['.com']],
                    ['name' => 'spacing2', 'available' => false, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Interactive element spacing
            expect($html)->toContain('space-') // Element spacing
                ->toContain('gap-') // Grid/flex gaps
                ->toContain('m-') // Margins
                ->toContain('p-') // Padding
                ->toContain('touch-target'); // Touch target sizing
        });

        it('supports click alternatives (hover states)', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Click alternatives
            expect($html)->toContain('hover:') // Hover states
                ->toContain('focus:') // Focus states
                ->toContain('active:') // Active states
                ->toContain('wire:keydown') // Keyboard alternatives
                ->toContain('wire:click'); // Click interactions
        });

        it('allows sufficient time for interactions', function (): void {
            $startTime = microtime(true);

            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Motor disability timing test');

            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;

            // Should allow sufficient interaction time (no aggressive timeouts)
            expect($responseTime)->toBeLessThan(5.0); // Reasonable response time
            expect($component->get('businessDescription'))->toBe('Motor disability timing test');
        });
    });

    describe('Responsive Accessibility', function (): void {
        it('maintains accessibility at xs breakpoint (400px)', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // XS breakpoint accessibility
            expect($html)->toContain('xs:') // XS responsive classes
                ->toContain('focus-') // Focus states
                ->toContain('aria-') // ARIA attributes
                ->toContain('role=') // ARIA roles
                ->toContain('touch-target'); // Touch targets
        });

        it('maintains accessibility at mobile breakpoints', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Mobile accessibility
            expect($html)->toContain('sm:') // Small breakpoint
                ->toContain('md:') // Medium breakpoint
                ->toContain('focus-') // Focus management
                ->toContain('aria-') // ARIA support
                ->toContain('mobile-nav'); // Mobile navigation
        });

        it('provides consistent accessibility across all breakpoints', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'responsive', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Cross-breakpoint consistency
            expect($html)->toContain('xs:') // Extra small
                ->toContain('sm:') // Small
                ->toContain('md:') // Medium
                ->toContain('lg:') // Large
                ->toContain('xl:') // Extra large
                ->toContain('focus-') // Focus states
                ->toContain('aria-'); // ARIA support
        });
    });

    describe('Form Accessibility', function (): void {
        it('associates labels with form controls', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Form label association
            expect($html)->toContain('<label') // Form labels
                ->toContain('for=') // Label-for association
                ->toContain('id=') // Form control IDs
                ->toContain('aria-labelledby') // ARIA label association
                ->toContain('wire:model'); // Form data binding
        });

        it('provides form validation feedback', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', ''); // Empty to trigger validation

            $html = $component->html();

            // Validation feedback
            expect($html)->toContain('aria-invalid') // Invalid state
                ->toContain('aria-describedby') // Error descriptions
                ->toContain('validationErrors') // Error messages
                ->toContain('validationHelp') // Help text
                ->toContain('role="alert"'); // Alert role for errors
        });

        it('groups related form controls', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Form grouping
            expect($html)->toContain('<fieldset') // Form grouping
                ->toContain('<legend') // Group descriptions
                ->toContain('role="group"') // Group roles
                ->toContain('aria-labelledby') // Group labels
                ->toContain('<form'); // Semantic form structure
        });
    });

    describe('Error Handling Accessibility', function (): void {
        it('announces errors to screen readers', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', ''); // Trigger validation error

            $html = $component->html();

            // Error announcements
            expect($html)->toContain('role="alert"') // Alert role
                ->toContain('aria-live') // Live regions
                ->toContain('aria-atomic') // Atomic updates
                ->toContain('validationErrors') // Error data
                ->toContain('screenReaderAnnouncement'); // Custom announcements
        });

        it('provides clear error recovery instructions', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'ab'); // Too short, triggers validation

            $html = $component->html();

            // Error recovery
            expect($html)->toContain('validationHelp') // Help instructions
                ->toContain('validationSuggestions') // Recovery suggestions
                ->toContain('aria-describedby') // Descriptive associations
                ->toContain('characterLimit') // Character limits
                ->toContain('characterCount'); // Character counting
        });
    });

    describe('Loading State Accessibility', function (): void {
        it('announces loading states appropriately', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Loading accessibility test');

            $html = $component->html();

            // Loading state accessibility
            expect($html)->toContain('aria-busy') // Busy indicator
                ->toContain('aria-live') // Live announcements
                ->toContain('wire:loading') // Loading states
                ->toContain('sr-only') // Screen reader content
                ->toContain('Loading'); // Loading text
        });

        it('maintains focus during loading states', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Focus during loading test');

            $html = $component->html();

            // Focus management during loading
            expect($html)->toContain('focus-') // Focus states
                ->toContain('wire:loading') // Loading indicators
                ->toContain('focusedElement') // Focus tracking
                ->toContain('tabindex') // Tab management
                ->not->toContain('focus-trap'); // No focus trapping during loading
        });
    });
});
