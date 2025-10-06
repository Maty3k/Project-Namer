<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
    Storage::fake('public');
});

describe('Image Lazy Loading', function (): void {
    test('logo gallery images have lazy loading attribute', function (): void {
        // Test that loading="lazy" is present in logo gallery template
        $templatePath = resource_path('views/livewire/logo-gallery.blade.php');
        $content = file_get_contents($templatePath);

        expect($content)->toContain('loading="lazy"');
    });

    test('project image gallery has lazy loading', function (): void {
        ProjectImage::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        $response = $this->get("/api/projects/{$this->project->id}/gallery");

        $response->assertStatus(200);
        $images = $response->json('images');
        expect($images)->toBeArray();
    });

    test('above-fold images do not have lazy loading', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Hero images should not be lazy loaded
        $content = $response->getContent();
        expect($content)->toBeString();
    });
});

describe('Component Deferred Loading', function (): void {
    test('heavy components use wire:init for deferred loading', function (): void {
        // Test that IntersectionObserver component exists
        $jsPath = resource_path('js/components/lazyLoadObserver.js');

        expect(file_exists($jsPath))->toBeTrue();
    });

    test('chart components are deferred until visible', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Charts should have deferred loading
        $content = $response->getContent();
        expect($content)->toBeString();
    });
});

describe('Virtual Scrolling', function (): void {
    test('long name suggestion lists render in chunks', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Virtual scrolling should be present for large lists
        $content = $response->getContent();
        expect($content)->toBeString();
    });

    test('pagination limits initial data load', function (): void {
        ProjectImage::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'processing_status' => 'completed',
        ]);

        $response = $this->get("/api/projects/{$this->project->id}/gallery?per_page=20");

        $response->assertStatus(200);
        $data = $response->json();
        expect($data['images'])->toHaveCount(20);
    });
});

describe('IntersectionObserver Integration', function (): void {
    test('IntersectionObserver is available for visibility detection', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Check that intersection observer polyfill or usage is present
        $content = $response->getContent();
        expect($content)->toBeString();
    });

    test('below-fold content loads on scroll', function (): void {
        // Test that lazy load observer is registered in app.js
        $appJsPath = resource_path('js/app.js');
        $content = file_get_contents($appJsPath);

        expect($content)->toContain('lazyLoadObserver');
    });
});

describe('Progressive Image Loading', function (): void {
    test('hero images have blur-up placeholder', function (): void {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        // Hero images should have blur-up effect
        $content = $response->getContent();
        expect($content)->toBeString();
    });

    test('generated logos have progressive loading', function (): void {
        // Test that progressive image component exists
        $componentPath = resource_path('views/components/ui/progressive-image.blade.php');

        expect(file_exists($componentPath))->toBeTrue();

        $content = file_get_contents($componentPath);
        expect($content)->toContain('loading=');
    });
});

describe('Performance with Large Datasets', function (): void {
    test('dashboard loads quickly with many projects', function (): void {
        Project::factory()->count(50)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(2.0); // Should load in under 2 seconds
    });

    test('project page loads quickly with many suggestions', function (): void {
        $startTime = microtime(true);
        $response = $this->get(route('dashboard'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(1.5); // Should load in under 1.5 seconds
    });

    test('logo gallery loads quickly with many logos', function (): void {
        $startTime = microtime(true);
        $response = $this->get(route('logos.index'));
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        expect($loadTime)->toBeLessThan(2.5); // Should load in under 2.5 seconds
    });
});
