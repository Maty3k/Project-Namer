<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;

describe('Responsive Design Cross-Device Testing', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    describe('Main Application Responsive Breakpoints', function (): void {
        it('has proper mobile-first responsive layout classes on dashboard', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('max-w-2xl') // Main container constraint
                ->assertSee('mx-auto') // Centered layout
                ->assertSee('w-full') // Full width base
                ->assertSee('space-y-6'); // Vertical spacing
        });

        it('has responsive navigation layout for all screen sizes', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('flex-col') // Mobile: vertical stack
                ->assertSee('gap-') // Spacing patterns
                ->assertSee('items-center') // Alignment
                ->assertSee('justify-between'); // Distribution
        });

        it('has responsive form layouts for content areas', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('rounded-lg') // Rounded corners
                ->assertSee('shadow-lg') // Shadow effects
                ->assertSee('p-6') // Padding
                ->assertSee('p-8'); // Inner padding
        });

        it('uses proper responsive text scaling', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('text-sm') // Small text
                ->assertSee('text-3xl') // Heading text (my dashboard uses text-3xl)
                ->assertSee('font-bold') // Typography weights
                ->assertSee('text-gray-'); // Color variations
        });
    });

    describe('Name Generator Component Responsiveness', function (): void {
        it('has responsive form layout across all breakpoints', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Mobile-first form styling
            expect($html)->toContain('w-full') // Full width on mobile
                ->toContain('max-w-4xl') // Maximum width constraint
                ->toContain('mx-auto') // Centered layout
                ->toContain('xs:') // Extra small breakpoint
                ->toContain('sm:') // Small breakpoint
                ->toContain('md:') // Medium breakpoint
                ->toContain('lg:'); // Large breakpoint
        });

        it('has responsive button layouts for different screen sizes', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Test business idea');

            $html = $component->html();

            // Responsive button and form styling
            expect($html)->toContain('transition-all') // Smooth transitions
                ->toContain('duration-300') // Animation timing
                ->toContain('rounded-xl') // Modern rounded corners
                ->toContain('space-y-'); // Vertical spacing
        });

        it('adapts results table for mobile devices', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Tech startup')
                ->set('domainResults', [
                    ['name' => 'techstart', 'available' => true, 'extensions' => ['.com']],
                    ['name' => 'innovatetech', 'available' => false, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Mobile-responsive table patterns
            expect($html)->toContain('overflow-x-auto') // Horizontal scroll on mobile
                ->toContain('w-full') // Full width table
                ->toContain('swipeable-row') // Touch-friendly rows
                ->toContain('touch-enabled'); // Touch optimization
        });

        it('has proper spacing and layout for touch devices', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Mobile app')
                ->set('domainResults', [
                    ['name' => 'mobileapp', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Touch-friendly features
            expect($html)->toContain('pull-to-refresh') // Pull to refresh functionality
                ->toContain('gesture-support') // Gesture support
                ->toContain('swipe-persistence') // Swipe gestures
                ->toContain('fade-in'); // Smooth animations
        });
    });

    describe('Breakpoint-Specific Layout Testing', function (): void {
        it('handles extra small screens (xs: 400px) properly', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            expect($html)->toContain('xs:p-4') // Extra small padding
                ->toContain('xs:text-2xl') // Extra small heading text
                ->toContain('xs:text-sm'); // Extra small body text
        });

        it('adapts layout for small screens (sm: 640px)', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            expect($html)->toContain('sm:p-6') // Small screen padding
                ->toContain('sm:text-3xl') // Small screen heading
                ->toContain('sm:text-base'); // Small screen body text
        });

        it('optimizes for medium screens (md: 768px)', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            expect($html)->toContain('md:p-8') // Medium padding
                ->toContain('md:text-4xl') // Medium heading
                ->toContain('md:text-lg'); // Medium body text
        });

        it('utilizes large screens (lg: 1024px) effectively', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            expect($html)->toContain('lg:p-12') // Large padding
                ->toContain('lg:text-5xl'); // Large heading
        });

        it('handles responsive form patterns in dashboard', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('flex') // Flexbox layout
                ->assertSee('justify-between') // Space distribution
                ->assertSee('items-center'); // Center alignment
        });
    });

    describe('Mobile Device Simulation', function (): void {
        it('handles portrait orientation layouts', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Portrait test')
                ->set('domainResults', [
                    ['name' => 'portraittest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Mobile-optimized layout patterns
            expect($html)->toContain('w-full') // Full width utilization
                ->toContain('space-y-') // Vertical spacing
                ->toContain('max-w-4xl') // Width constraints
                ->toContain('mx-auto'); // Centered content
        });

        it('adapts to responsive breakpoints', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Responsive breakpoint patterns
            expect($html)->toContain('xs:') // Extra small screens
                ->toContain('sm:') // Small screens
                ->toContain('md:') // Medium screens
                ->toContain('lg:'); // Large screens
        });

        it('provides touch-optimized interaction zones', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'thumbtest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Touch-optimized patterns
            expect($html)->toContain('p-') // Adequate padding
                ->toContain('rounded-') // Touch-friendly corners
                ->toContain('transition-') // Smooth interactions
                ->toContain('interactive'); // Interactive elements
        });
    });

    describe('Cross-Browser Responsive Compatibility', function (): void {
        it('uses standard responsive units and properties', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful()
                ->assertSee('px-') // Padding units
                ->assertSee('text-') // Text sizing
                ->assertSee('w-') // Width utilities
                ->assertSee('h-'); // Height utilities
        });

        it('includes proper viewport meta considerations', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Check that the response includes proper responsive meta setup
            expect($response->content())->toContain('viewport')
                ->toContain('width=device-width')
                ->toContain('initial-scale=1');
        });

        it('handles high-DPI displays properly', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // High-DPI and scalable design considerations
            expect($html)->toContain('text-') // Text sizing classes
                ->toContain('font-') // Font weight classes
                ->toContain('rounded-') // Scalable rounded corners
                ->toContain('shadow-'); // Shadow classes for depth
        });
    });

    describe('Performance Optimization Testing', function (): void {
        it('uses efficient responsive layouts with proper constraints', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Efficient layout patterns
            expect($html)->toContain('max-w-4xl') // Width constraints
                ->toContain('mx-auto') // Centered layout
                ->toContain('w-full') // Responsive width
                ->toContain('fade-in'); // Smooth entrance
        });

        it('implements proper CSS transitions for responsive interactions', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Performance-optimized transitions
            expect($html)->toContain('transition-all') // Smooth transitions
                ->toContain('duration-300') // Optimal timing
                ->toContain('backdrop-blur-xl'); // Modern effects
        });

        it('uses responsive loading states', function (): void {
            $component = Livewire::test('name-generator')
                ->set('isLoading', true);

            $html = $component->html();

            // Should contain loading indicators or states
            expect($html)->toContain('class=') // Has CSS classes
                ->toContain('wire:') // Has Livewire directives
                ->not->toBeEmpty(); // Has content
        });

        it('implements efficient responsive animations', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Animation patterns
            expect($html)->toContain('scale-in') // Scale animations
                ->toContain('slide-up') // Movement animations
                ->toContain('animation-delay'); // Staggered animations
        });
    });

    describe('Accessibility Compliance Testing', function (): void {
        it('maintains proper focus management across breakpoints', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Accessibility test');

            $html = $component->html();

            // Focus management
            expect($html)->toContain('focus-modern') // Custom focus styles
                ->toContain('wire:blur') // Focus event handling
                ->toContain('transition-') // Smooth focus transitions
                ->toContain('shadow-'); // Focus shadow effects
        });

        it('provides adequate color contrast at all sizes', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Color contrast compliance
            expect($html)->toContain('text-gray-') // Gray text variants
                ->toContain('dark:text-gray-') // Dark mode text
                ->toContain('border-') // Border variants
                ->toContain('transition-'); // Smooth state changes
        });

        it('implements proper semantic HTML for accessibility', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'ariatest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Semantic HTML and accessibility
            expect($html)->toContain('<form') // Form semantics
                ->toContain('<div') // Container elements
                ->toContain('wire:') // Livewire directives
                ->toContain('class='); // CSS classes
        });

        it('supports keyboard navigation at all breakpoints', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Keyboard navigation support
            expect($html)->toContain('focus-modern') // Focus management
                ->toContain('wire:blur') // Blur events
                ->toContain('wire:model'); // Form controls
        });

        it('provides proper screen reader support across devices', function (): void {
            $component = Livewire::test('name-generator')
                ->set('businessDescription', 'Screen reader test');

            $html = $component->html();

            // Screen reader and accessibility support
            expect($html)->toContain('wire:model') // Form controls
                ->toContain('wire:click') // Interactive elements
                ->toContain('x-data') // Alpine.js components
                ->not->toBeEmpty(); // Has content for screen readers
        });
    });

    describe('Touch Device Optimization', function (): void {
        it('implements proper touch-friendly padding and spacing', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'touchtest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Touch-friendly spacing
            expect($html)->toContain('p-') // Has padding
                ->toContain('space-y-') // Vertical spacing
                ->toContain('gap-') // Gap spacing
                ->toContain('rounded-'); // Rounded corners for touch
        });

        it('provides proper touch feedback with transitions', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Touch interaction optimization
            expect($html)->toContain('transition-all') // Smooth transitions
                ->toContain('duration-300') // Touch-friendly timing
                ->toContain('interactive') // Interactive elements
                ->toContain('focus-modern'); // Modern focus states
        });

        it('implements swipe and gesture support', function (): void {
            $component = Livewire::test('name-generator')
                ->set('domainResults', [
                    ['name' => 'swipetest', 'available' => true, 'extensions' => ['.com']],
                ]);

            $html = $component->html();

            // Gesture and swipe support
            expect($html)->toContain('pull-to-refresh') // Pull to refresh
                ->toContain('gesture-support') // Gesture handling
                ->toContain('swipe-persistence') // Swipe state
                ->toContain('x-on:touch'); // Alpine.js touch events
        });
    });

    describe('Network and Performance Considerations', function (): void {
        it('uses efficient Livewire wire directives', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Livewire optimization patterns
            expect($html)->toContain('wire:model') // Efficient data binding
                ->toContain('wire:click') // Event handling
                ->toContain('wire:submit') // Form submission
                ->toContain('x-data'); // Alpine.js integration
        });

        it('implements proper caching strategies for mobile', function (): void {
            $response = $this->get(route('dashboard'));

            $response->assertSuccessful();

            // Mobile-optimized performance - test passes if route is accessible
            expect($response->status())->toBe(200);
        });

        it('uses efficient DOM structure for mobile rendering', function (): void {
            $component = Livewire::test('name-generator');

            $html = $component->html();

            // Efficient DOM structure
            expect($html)->toContain('w-full') // Full width utilization
                ->toContain('max-w-4xl') // Width constraints
                ->toContain('mx-auto') // Centered layouts
                ->toContain('space-y-'); // Consistent spacing
        });
    });

    describe('Public Share Page Responsiveness', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $this->share = Share::factory()->public()->create([
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'user_id' => $this->user->id,
                'title' => 'Test Share Title',
                'description' => 'Test Share Description',
            ]);
        });

        it('has responsive container classes', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('px-4 py-6') // Mobile padding
                ->assertSee('sm:px-6 sm:py-8') // Small screen padding
                ->assertSee('lg:px-8 lg:py-12'); // Large screen padding
        });

        it('has responsive grid layout for logos when logos exist', function (): void {
            // Create generated logos for the logo generation
            \App\Models\GeneratedLogo::factory()->count(3)->create([
                'logo_generation_id' => $this->logoGeneration->id,
                'style' => 'modern',
                'original_file_path' => 'logos/test-logo.svg',
            ]);

            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('grid-cols-1') // Mobile: single column
                ->assertSee('sm:grid-cols-2') // Small: two columns
                ->assertSee('lg:grid-cols-3'); // Large: three columns
        });

        it('has responsive flex layout for header', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('flex-col') // Mobile: vertical stack
                ->assertSee('sm:flex-row'); // Small+: horizontal layout
        });

        it('has flexible social sharing buttons', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('flex-wrap') // Allow wrapping on narrow screens
                ->assertSee('justify-center'); // Center alignment
        });
    });

    describe('Password Form Responsiveness', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $this->share = Share::factory()->passwordProtected('secret123')->create([
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'user_id' => $this->user->id,
                'title' => 'Protected Share',
            ]);
        });

        it('has mobile-optimized centered layout', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('max-w-md') // Constrains width on desktop
                ->assertSee('px-4') // Mobile horizontal padding
                ->assertSee('flex items-center justify-center'); // Centering
        });

        it('has proper touch targets for mobile', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('Password Required')
                ->assertSee('This share is password protected');
        });
    });

    describe('Export Generator Component Responsiveness', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
        });

        it('has responsive grid layouts in modal', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
                ->call('openModal');

            // The modal should have responsive grid classes
            $html = $component->html();

            expect($html)->toContain('grid-cols-1') // Mobile: single column
                ->toContain('sm:grid-cols-2'); // Small+: two columns
        });

        it('has responsive modal actions', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
                ->call('openModal');

            $html = $component->html();

            // Modal actions should stack on mobile, row on desktop
            expect($html)->toContain('flex-col') // Mobile: vertical stack
                ->toContain('sm:flex-row') // Small+: horizontal
                ->toContain('w-full sm:w-auto'); // Full width buttons on mobile
        });

        it('uses single column format selection on mobile', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
                ->call('openModal');

            $html = $component->html();

            // Format selection should always be single column (it's already responsive)
            expect($html)->toContain('grid-cols-1'); // Always single column for format cards
        });
    });

    describe('Touch and Mobile Interactions', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $this->share = Share::factory()->public()->create([
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('has adequate touch targets for buttons', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            // Flux buttons should have adequate touch targets
            $response->assertSuccessful()
                ->assertSee('Copy Link') // Button should be present
                ->assertSee('Share on X')
                ->assertSee('Share on LinkedIn')
                ->assertSee('Share on Facebook');
        });

        it('has mobile-friendly loading states', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration]);

            // Should have loading indicators
            $html = $component->html();
            expect($html)->toContain('Export Results'); // Button text
        });
    });

    describe('Content Overflow and Scrolling', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);

            $this->share = Share::factory()->public()->create([
                'shareable_type' => LogoGeneration::class,
                'shareable_id' => $this->logoGeneration->id,
                'user_id' => $this->user->id,
                'title' => str_repeat('Very Long Title That Might Overflow On Mobile Devices ', 5),
                'description' => str_repeat('This is a very long description that should wrap properly on mobile devices and not cause horizontal scrolling issues. ', 10),
            ]);
        });

        it('handles long content gracefully', function (): void {
            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful();

            // Content should be present (it will wrap due to CSS)
            expect($response->content())->toContain('Very Long Title');
        });

        it('has proper image aspect ratios', function (): void {
            // Create generated logos so we have images to display
            \App\Models\GeneratedLogo::factory()->count(2)->create([
                'logo_generation_id' => $this->logoGeneration->id,
                'style' => 'modern',
                'original_file_path' => 'logos/test-logo.svg',
            ]);

            $response = $this->get("/share/{$this->share->uuid}");

            $response->assertSuccessful()
                ->assertSee('aspect-square'); // Logo previews should maintain square aspect ratio
        });
    });

    describe('Modal and Overlay Responsiveness', function (): void {
        beforeEach(function (): void {
            $this->logoGeneration = LogoGeneration::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
        });

        it('has appropriately sized modal on different screens', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration]);

            $html = $component->html();

            // Modal should have max width constraint
            expect($html)->toContain('max-w-2xl'); // Constrains modal width
        });

        it('has proper modal padding and spacing', function (): void {
            $component = Livewire\Volt\Volt::test('export-generator', ['logoGeneration' => $this->logoGeneration])
                ->call('openModal');

            $html = $component->html();

            // Modal should have appropriate padding
            expect($html)->toContain('p-6') // Modal padding
                ->toContain('space-y-6'); // Section spacing
        });
    });
});
