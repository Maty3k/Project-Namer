<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

describe('Theme CSS Files', function () {
    it('has all 18 predefined theme CSS files', function () {
        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        foreach ($expectedThemes as $theme) {
            $filePath = public_path("css/themes/{$theme}.css");
            expect(File::exists($filePath))
                ->toBeTrue("Theme file {$theme}.css should exist");
        }
    });

    it('contains required CSS variables in each theme file', function () {
        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        $requiredVariables = [
            '--color-primary',
            '--color-secondary',
            '--color-accent',
            '--color-background',
            '--color-surface',
            '--color-text-primary',
            '--color-text-secondary',
            '--color-border',
        ];

        foreach ($expectedThemes as $theme) {
            $filePath = public_path("css/themes/{$theme}.css");

            if (File::exists($filePath)) {
                $content = File::get($filePath);

                foreach ($requiredVariables as $variable) {
                    expect(str_contains($content, $variable))
                        ->toBeTrue("Theme {$theme}.css should contain {$variable}");
                }
            }
        }
    });

    it('has both light and dark mode selectors in each theme file', function () {
        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        foreach ($expectedThemes as $theme) {
            $filePath = public_path("css/themes/{$theme}.css");

            if (File::exists($filePath)) {
                $content = File::get($filePath);

                // Check for :root selector (light mode)
                expect(str_contains($content, ':root {'))
                    ->toBeTrue("Theme {$theme}.css should contain :root selector");

                // Check for :root.dark selector (dark mode)
                expect(str_contains($content, ':root.dark {'))
                    ->toBeTrue("Theme {$theme}.css should contain :root.dark selector");
            }
        }
    });

    it('has CSS files in public directory for web accessibility', function () {
        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        foreach ($expectedThemes as $theme) {
            $filePath = public_path("css/themes/{$theme}.css");

            // Verify file exists in public directory (web-accessible)
            expect(File::exists($filePath))
                ->toBeTrue("Theme {$theme}.css should exist in public directory");

            // Verify file is readable
            expect(File::isReadable($filePath))
                ->toBeTrue("Theme {$theme}.css should be readable");
        }
    });

    it('validates CSS variable format with hex color values', function () {
        $expectedThemes = [
            'default', 'dark', 'ocean', 'sunset', 'forest',
            'cosmic-violet', 'coral-reef', 'midnight-teal',
            'summer', 'winter', 'halloween', 'spring', 'autumn',
            'neon-cyber', 'electric-blue', 'hot-pink', 'lava-red',
            'lime-punch', 'gold-rush', 'matrix-green',
        ];

        foreach ($expectedThemes as $theme) {
            $filePath = public_path("css/themes/{$theme}.css");

            if (File::exists($filePath)) {
                $content = File::get($filePath);

                // Check that CSS variables are properly formatted
                // Should match pattern: --color-name: #hexvalue;
                $pattern = '/--color-[a-z-]+:\s*#[0-9a-fA-F]{6};/';

                expect(preg_match($pattern, $content))
                    ->toBeGreaterThan(0, "Theme {$theme}.css should have properly formatted CSS variables");
            }
        }
    });
});
