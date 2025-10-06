<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

describe('Skeleton Components', function (): void {
    describe('Name Card Skeleton', function (): void {
        it('renders with correct structure', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)
                ->toContain('animate-pulse')
                ->toContain('bg-gray-200')
                ->toContain('dark:bg-gray-700')
                ->toContain('rounded');
        });

        it('has proper shimmer animation classes', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)->toContain('animate-pulse');
        });

        it('matches name card layout structure', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            // Should have elements matching actual name card structure
            expect($html)
                ->toContain('h-') // Height classes
                ->toContain('w-'); // Width classes
        });

        it('supports dark mode colors', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)
                ->toContain('bg-gray-200')
                ->toContain('dark:bg-gray-700');
        });
    });

    describe('Logo Card Skeleton', function (): void {
        it('renders with correct structure', function (): void {
            $html = View::make('components.skeleton-logo-card')->render();

            expect($html)
                ->toContain('animate-pulse')
                ->toContain('bg-gray-200')
                ->toContain('dark:bg-gray-700');
        });

        it('has square aspect ratio for logo placeholder', function (): void {
            $html = View::make('components.skeleton-logo-card')->render();

            // Logo cards should have square or specific aspect ratio
            expect($html)->toContain('aspect-');
        });

        it('supports dark mode', function (): void {
            $html = View::make('components.skeleton-logo-card')->render();

            expect($html)->toContain('dark:bg-gray-');
        });
    });

    describe('Session List Item Skeleton', function (): void {
        it('renders with correct structure', function (): void {
            $html = View::make('components.skeleton-session-item')->render();

            expect($html)
                ->toContain('animate-pulse')
                ->toContain('bg-gray-200')
                ->toContain('dark:bg-gray-700');
        });

        it('matches session item layout', function (): void {
            $html = View::make('components.skeleton-session-item')->render();

            // Should have multiple lines for title and preview
            expect($html)->toContain('mb-'); // Margin bottom for spacing
        });

        it('is compact for sidebar display', function (): void {
            $html = View::make('components.skeleton-session-item')->render();

            // Should have compact padding
            expect($html)->toContain('p-');
        });
    });

    describe('Project Card Skeleton', function (): void {
        it('renders with correct structure', function (): void {
            $html = View::make('components.skeleton-project-card')->render();

            expect($html)
                ->toContain('animate-pulse')
                ->toContain('bg-gray-200')
                ->toContain('dark:bg-gray-700')
                ->toContain('rounded');
        });

        it('has proper card border and shadow', function (): void {
            $html = View::make('components.skeleton-project-card')->render();

            expect($html)
                ->toContain('border')
                ->toContain('shadow');
        });

        it('matches project card dimensions', function (): void {
            $html = View::make('components.skeleton-project-card')->render();

            // Should have similar padding and structure to real card
            expect($html)
                ->toContain('p-')
                ->toContain('rounded');
        });
    });

    describe('Skeleton Accessibility', function (): void {
        it('includes aria-label for screen readers', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)->toContain('aria-label');
        });

        it('has aria-busy attribute during loading', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)->toContain('aria-busy="true"');
        });

        it('includes role attribute for loading state', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)->toContain('role="status"');
        });
    });

    describe('Skeleton Responsiveness', function (): void {
        it('adapts to container width', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            expect($html)->toContain('w-full');
        });

        it('maintains proper spacing on mobile', function (): void {
            $html = View::make('components.skeleton-name-card')->render();

            // Should have responsive padding/margin
            expect($html)->toMatch('/(p-|m-|gap-)/');
        });
    });

    describe('Multiple Skeletons Rendering', function (): void {
        it('can render multiple name card skeletons', function (): void {
            $skeletons = [];
            for ($i = 0; $i < 5; $i++) {
                $skeletons[] = View::make('components.skeleton-name-card')->render();
            }

            expect($skeletons)->toHaveCount(5);
            foreach ($skeletons as $skeleton) {
                expect($skeleton)->toContain('animate-pulse');
            }
        });

        it('renders consistently across multiple instances', function (): void {
            $skeleton1 = View::make('components.skeleton-project-card')->render();
            $skeleton2 = View::make('components.skeleton-project-card')->render();

            // Should render identical structure
            expect($skeleton1)->toBe($skeleton2);
        });
    });

    describe('Skeleton Performance', function (): void {
        it('renders quickly without heavy computation', function (): void {
            $startTime = microtime(true);

            for ($i = 0; $i < 100; $i++) {
                View::make('components.skeleton-name-card')->render();
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // 100 skeletons should render in under 100ms
            expect($executionTime)->toBeLessThan(0.1);
        });
    });
});
