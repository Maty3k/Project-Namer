<?php

declare(strict_types=1);

use App\Models\LogoGeneration;
use App\Models\GeneratedLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GeneratedLogo Model', function (): void {
    beforeEach(function (): void {
        $this->logoGeneration = LogoGeneration::create([
            'session_id' => 'sess_123',
            'business_name' => 'TechFlow',
        ]);
    });

    it('can create a generated logo entry', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Create a minimalist logo for TechFlow',
            'original_file_path' => 'logos/sess_123/techflow-minimalist-1.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo)->toBeInstanceOf(GeneratedLogo::class)
            ->and($logo->style)->toBe('minimalist')
            ->and($logo->variation_number)->toBe(1)
            ->and($logo->image_width)->toBe(1024)
            ->and($logo->image_height)->toBe(1024);
    });

    it('belongs to a logo generation', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Create a minimalist logo for TechFlow',
            'original_file_path' => 'logos/sess_123/techflow-minimalist-1.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo->logoGeneration)->toBeInstanceOf(LogoGeneration::class)
            ->and($logo->logoGeneration->id)->toBe($this->logoGeneration->id);
    });

    it('validates style enum values', function (): void {
        expect(fn () => GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'invalid_style',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('has correct fillable attributes', function (): void {
        $fillable = (new GeneratedLogo)->getFillable();

        expect($fillable)->toContain('logo_generation_id')
            ->and($fillable)->toContain('style')
            ->and($fillable)->toContain('variation_number')
            ->and($fillable)->toContain('prompt_used')
            ->and($fillable)->toContain('original_file_path')
            ->and($fillable)->toContain('file_size')
            ->and($fillable)->toContain('image_width')
            ->and($fillable)->toContain('image_height')
            ->and($fillable)->toContain('generation_time_ms')
            ->and($fillable)->toContain('api_image_url');
    });

    it('can scope by style', function (): void {
        GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test1.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'modern',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test2.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        $minimalist = GeneratedLogo::ofStyle('minimalist')->get();
        $modern = GeneratedLogo::ofStyle('modern')->get();

        expect($minimalist)->toHaveCount(1)
            ->and($modern)->toHaveCount(1);
    });

    it('can get file extension from path', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'logos/sess_123/techflow-minimalist-1.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo->getFileExtension())->toBe('svg');
    });

    it('can get formatted file size', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo->getFormattedFileSize())->toBe('43.9 KB');
    });

    it('can generate download filename', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        $filename = $logo->generateDownloadFilename();

        expect($filename)->toBe('techflow-minimalist-1.svg');
    });

    it('can generate download filename with color scheme', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        $filename = $logo->generateDownloadFilename('ocean_blue', 'png');

        expect($filename)->toBe('techflow-minimalist-1-ocean_blue.png');
    });

    it('can check if file exists', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'nonexistent.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo->fileExists())->toBeFalse();
    });

    it('requires all necessary fields', function (): void {
        expect(fn () => GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            // missing required fields
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('has default image dimensions', function (): void {
        $logo = GeneratedLogo::create([
            'logo_generation_id' => $this->logoGeneration->id,
            'style' => 'minimalist',
            'variation_number' => 1,
            'prompt_used' => 'Test prompt',
            'original_file_path' => 'test.svg',
            'file_size' => 45000,
            'generation_time_ms' => 2500,
        ]);

        expect($logo->image_width)->toBe(1024)
            ->and($logo->image_height)->toBe(1024);
    });
});