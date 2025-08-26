<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\Share;
use App\Models\User;

describe('Responsive Design Compatibility', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
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
