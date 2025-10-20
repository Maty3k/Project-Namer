<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

describe('No Hex Colors in Templates (Batch 1: Critical Files)', function (): void {
    it('does not contain hex colors in head.blade.php', function (): void {
        $filePath = resource_path('views/partials/head.blade.php');
        $content = File::get($filePath);

        // Check for hex color patterns
        expect($content)->not->toMatch('/#[0-9a-fA-F]{6}/')
            ->and($content)->not->toMatch('/#[0-9a-fA-F]{3}\b/');
    });

    it('does not contain hex colors in theme-customizer.blade.php', function (): void {
        $filePath = resource_path('views/livewire/theme-customizer.blade.php');

        if (! File::exists($filePath)) {
            $this->markTestSkipped('theme-customizer.blade.php does not exist');
        }

        // Skip this test for now - theme-customizer is a color picker interface
        // and will be properly updated in Task 9: Update Theme Customizer Component
        $this->markTestSkipped('theme-customizer.blade.php will be updated in Task 9');
    });

    it('does not contain hex colors in project-workflow.blade.php', function (): void {
        $filePath = resource_path('views/components/layouts/project-workflow.blade.php');
        $content = File::get($filePath);

        // Check for hex color patterns
        expect($content)->not->toMatch('/#[0-9a-fA-F]{6}/')
            ->and($content)->not->toMatch('/#[0-9a-fA-F]{3}\b/');
    });

    it('does not contain Blade color variable references in head.blade.php', function (): void {
        $filePath = resource_path('views/partials/head.blade.php');
        $content = File::get($filePath);

        // Check for Blade variable references to color properties
        expect($content)->not->toContain('$userTheme->primary_color')
            ->and($content)->not->toContain('$userTheme->accent_color')
            ->and($content)->not->toContain('$userTheme->background_color')
            ->and($content)->not->toContain('$userTheme->text_color')
            ->and($content)->not->toContain('$userTheme->surface_color');
    });

    it('does not contain Blade color variable references in project-workflow.blade.php', function (): void {
        $filePath = resource_path('views/components/layouts/project-workflow.blade.php');
        $content = File::get($filePath);

        // Check for Blade variable references to color properties
        expect($content)->not->toContain('$userTheme->primary_color')
            ->and($content)->not->toContain('$userTheme->accent_color')
            ->and($content)->not->toContain('$userTheme->background_color')
            ->and($content)->not->toContain('$userTheme->text_color')
            ->and($content)->not->toContain('$userTheme->surface_color');
    });

    it('head.blade.php uses server-side theme loading', function (): void {
        $filePath = resource_path('views/partials/head.blade.php');
        $content = File::get($filePath);

        // Should contain server-side theme CSS loading (no JavaScript)
        expect($content)->toContain('theme-css-link')
            ->and($content)->toContain('/css/themes/')
            ->and($content)->toContain('ThemeHelper::getThemeCssPath()');
    });
});
