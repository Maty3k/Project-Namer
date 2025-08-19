<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('LogoColorVariant Model', function (): void {
    beforeEach(function (): void {
        $this->logoGeneration = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);

        $this->generatedLogo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Create a minimalist logo for TechFlow',
            'original_file_path' => 'logos/sess_123/techflow-minimalist-1.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);
    });

    it('can create a logo color variant', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'logos/sess_123/customized/techflow-minimalist-1-ocean_blue.svg',
            'file_size' => 47000,
        ]);

        expect($variant)->toBeInstanceOf(LogoColorVariant::class)
            ->and($variant->color_scheme)->toBe('ocean_blue')
            ->and($variant->file_size)->toBe(47000);
    });

    it('belongs to a generated logo', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'logos/sess_123/customized/techflow-minimalist-1-ocean_blue.svg',
            'file_size' => 47000,
        ]);

        expect($variant->generatedLogo)->toBeInstanceOf(GeneratedLogo::class)
            ->and($variant->generatedLogo->id)->toBe($this->generatedLogo->id);
    });

    it('validates color scheme enum values', function (): void {
        expect(fn () => LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'invalid_scheme',
            'file_path' => 'test.svg',
            'file_size' => 47000,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('enforces unique constraint on logo and color scheme', function (): void {
        LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test1.svg',
            'file_size' => 47000,
        ]);

        expect(fn () => LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test2.svg',
            'file_size' => 48000,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('has correct fillable attributes', function (): void {
        $fillable = (new LogoColorVariant)->getFillable();

        expect($fillable)->toContain('generated_logo_id')
            ->and($fillable)->toContain('color_scheme')
            ->and($fillable)->toContain('file_path')
            ->and($fillable)->toContain('file_size');
    });

    it('can scope by color scheme', function (): void {
        LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test1.svg',
            'file_size' => 47000,
        ]);

        LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'forest_green',
            'file_path' => 'test2.svg',
            'file_size' => 48000,
        ]);

        $oceanBlue = LogoColorVariant::withColorScheme('ocean_blue')->get();
        $forestGreen = LogoColorVariant::withColorScheme('forest_green')->get();

        expect($oceanBlue)->toHaveCount(1)
            ->and($forestGreen)->toHaveCount(1);
    });

    it('can get formatted file size', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test.svg',
            'file_size' => 47000,
        ]);

        expect($variant->getFormattedFileSize())->toBe('45.9 KB');
    });

    it('can get file extension from path', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'logos/sess_123/customized/techflow-minimalist-1-ocean_blue.svg',
            'file_size' => 47000,
        ]);

        expect($variant->getFileExtension())->toBe('svg');
    });

    it('can generate download filename', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'logos/sess_123/customized/techflow-minimalist-1-ocean_blue.svg',
            'file_size' => 47000,
        ]);

        $filename = $variant->generateDownloadFilename();

        expect($filename)->toBe('techflow-minimalist-1-ocean_blue.svg');
    });

    it('can generate download filename with different format', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'logos/sess_123/customized/techflow-minimalist-1-ocean_blue.svg',
            'file_size' => 47000,
        ]);

        $filename = $variant->generateDownloadFilename('png');

        expect($filename)->toBe('techflow-minimalist-1-ocean_blue.png');
    });

    it('can check if file exists', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'nonexistent.svg',
            'file_size' => 47000,
        ]);

        expect($variant->fileExists())->toBeFalse();
    });

    it('can get color scheme display name', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test.svg',
            'file_size' => 47000,
        ]);

        expect($variant->getColorSchemeDisplayName())->toBe('Ocean Blue');
    });

    it('requires all necessary fields', function (): void {
        expect(fn () => LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            // missing file_path and file_size
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can find existing variant by logo and color scheme', function (): void {
        $variant = LogoColorVariant::create([
            'generated_logo_id' => $this->generatedLogo->id,
            'color_scheme' => 'ocean_blue',
            'file_path' => 'test.svg',
            'file_size' => 47000,
        ]);

        $found = LogoColorVariant::findByLogoAndScheme($this->generatedLogo->id, 'ocean_blue');
        $notFound = LogoColorVariant::findByLogoAndScheme($this->generatedLogo->id, 'forest_green');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($variant->id)
            ->and($notFound)->toBeNull();
    });
});