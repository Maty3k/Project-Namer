<?php

declare(strict_types=1);

use App\Models\GeneratedLogo;
use App\Models\LogoColorVariant;
use App\Models\LogoGeneration;
use App\Services\FileManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    $this->service = app(FileManagementService::class);
});

describe('File Storage Structure', function (): void {
    it('creates proper directory structure for logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        $directories = $this->service->createDirectoryStructure($logoGeneration->id);

        expect($directories)->toHaveKeys(['originals', 'customized', 'temp']);
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/originals"))->toBeTrue();
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/customized"))->toBeTrue();
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/temp"))->toBeTrue();
    });

    it('stores original logo files in correct directory', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create();

        $file = UploadedFile::fake()->image('logo.png', 512, 512);

        $result = $this->service->storeOriginalLogo($generatedLogo, $file);

        expect($result)->toHaveKeys(['success', 'file_path', 'file_size']);
        expect($result['success'])->toBeTrue();
        expect($result['file_path'])->toStartWith("logos/{$logoGeneration->id}/originals/");
        expect(Storage::disk('public')->exists($result['file_path']))->toBeTrue();
    });

    it('stores customized logo files with color scheme identifier', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create();
        $colorVariant = LogoColorVariant::factory()->for($generatedLogo)->create(['color_scheme' => 'ocean_blue']);

        $file = UploadedFile::fake()->image('logo-blue.svg', 512, 512);

        $result = $this->service->storeCustomizedLogo($colorVariant, $file);

        expect($result)->toHaveKeys(['success', 'file_path', 'file_size']);
        expect($result['success'])->toBeTrue();
        expect($result['file_path'])->toStartWith("logos/{$logoGeneration->id}/customized/");
        expect($result['file_path'])->toContain('ocean_blue');
        expect(Storage::disk('public')->exists($result['file_path']))->toBeTrue();
    });

    it('creates temporary files for processing', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        $file = UploadedFile::fake()->image('temp.png', 256, 256);

        $result = $this->service->storeTempFile($logoGeneration->id, $file, 'processing');

        expect($result)->toHaveKeys(['success', 'file_path', 'temp_id']);
        expect($result['success'])->toBeTrue();
        expect($result['file_path'])->toStartWith("logos/{$logoGeneration->id}/temp/");
        expect(Storage::disk('public')->exists($result['file_path']))->toBeTrue();
    });
});

describe('File Naming Conventions', function (): void {
    it('generates standard filename for original logos', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['business_name' => 'My Cool Startup']);
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create([
            'style' => 'minimalist',
            'variation_number' => 2,
        ]);

        $filename = $this->service->generateOriginalFilename($generatedLogo, 'png');

        expect($filename)->toMatch('/^my-cool-startup_minimalist_v2_[a-z0-9]{8}\.png$/');
    });

    it('generates filename for customized logos with color scheme', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['business_name' => 'Tech Company']);
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create([
            'style' => 'corporate',
            'variation_number' => 1,
        ]);
        $colorVariant = LogoColorVariant::factory()->for($generatedLogo)->create(['color_scheme' => 'royal_purple']);

        $filename = $this->service->generateCustomizedFilename($colorVariant, 'svg');

        expect($filename)->toMatch('/^tech-company_corporate_v1_royal_purple_[a-z0-9]{8}\.svg$/');
    });

    it('handles long business names by truncating appropriately', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['business_name' => 'This Is A Very Long Business Name That Should Be Truncated']);
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create([
            'style' => 'modern',
            'variation_number' => 3,
        ]);

        $filename = $this->service->generateOriginalFilename($generatedLogo, 'png');

        // Should be truncated to reasonable length
        expect(strlen($filename))->toBeLessThan(80);
        expect($filename)->toContain('this-is-a-very-long-business');
        expect($filename)->toContain('_modern_v3_');
    });

    it('sanitizes special characters in business names', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['business_name' => 'Café & Bistro (Premium)']);
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create([
            'style' => 'playful',
            'variation_number' => 1,
        ]);

        $filename = $this->service->generateOriginalFilename($generatedLogo, 'png');

        expect($filename)->toStartWith('cafe-bistro-premium_playful_v1_');
        expect($filename)->not->toContain('&');
        expect($filename)->not->toContain('(');
        expect($filename)->not->toContain(')');
        expect($filename)->not->toContain(' ');
    });
});

describe('File Organization', function (): void {
    it('organizes files by business name and creation date', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['business_name' => 'Test Business']);

        $organization = $this->service->getFileOrganization($logoGeneration->id);

        expect($organization)->toHaveKeys(['base_path', 'date_prefix', 'business_slug']);
        expect($organization['base_path'])->toBe("logos/{$logoGeneration->id}");
        expect($organization['business_slug'])->toBe('test-business');
    });

    it('lists all files for a logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create();

        // Create some test files
        Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/test1.png", 'test content');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/customized/test1_blue.svg", 'test content');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/temp1.png", 'temp content');

        $files = $this->service->listAllFiles($logoGeneration->id);

        expect($files)->toHaveKeys(['originals', 'customized', 'temp']);
        expect($files['originals'])->toHaveCount(1);
        expect($files['customized'])->toHaveCount(1);
        expect($files['temp'])->toHaveCount(1);
    });

    it('calculates total storage usage for logo generation', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create test files of known sizes
        Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/logo1.png", str_repeat('a', 1024)); // 1KB
        Storage::disk('public')->put("logos/{$logoGeneration->id}/customized/logo1_blue.svg", str_repeat('b', 2048)); // 2KB
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/temp.png", str_repeat('c', 512)); // 512B

        $usage = $this->service->calculateStorageUsage($logoGeneration->id);

        expect($usage)->toHaveKeys(['total_bytes', 'originals_bytes', 'customized_bytes', 'temp_bytes', 'formatted_size']);
        expect($usage['total_bytes'])->toBe(3584); // 1024 + 2048 + 512
        expect($usage['originals_bytes'])->toBe(1024);
        expect($usage['customized_bytes'])->toBe(2048);
        expect($usage['temp_bytes'])->toBe(512);
        expect($usage['formatted_size'])->toBe('3.5 KB');
    });
});

describe('File Cleanup', function (): void {
    it('cleans up temporary files older than specified age', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create temp files
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/old_temp.png", 'old content');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/new_temp.png", 'new content');

        // Mock old file by touching it with old timestamp
        $oldFilePath = Storage::disk('public')->path("logos/{$logoGeneration->id}/temp/old_temp.png");
        touch($oldFilePath, time() - (25 * 60 * 60)); // 25 hours ago

        $cleanedCount = $this->service->cleanupTempFiles($logoGeneration->id, 24); // 24 hours threshold

        expect($cleanedCount)->toBe(1);
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/temp/old_temp.png"))->toBeFalse();
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}/temp/new_temp.png"))->toBeTrue();
    });

    it('removes entire logo generation directory', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();

        // Create directory structure with files
        $this->service->createDirectoryStructure($logoGeneration->id);
        Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/logo.png", 'content');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/customized/logo_blue.svg", 'content');

        $result = $this->service->deleteLogoGeneration($logoGeneration->id);

        expect($result['success'])->toBeTrue();
        expect($result['deleted_files'])->toBeGreaterThan(0);
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}"))->toBeFalse();
    });

    it('cleans up failed generation files', function (): void {
        $logoGeneration = LogoGeneration::factory()->create(['status' => 'failed']);

        // Create some files
        Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/partial.png", 'partial content');
        Storage::disk('public')->put("logos/{$logoGeneration->id}/temp/processing.tmp", 'temp content');

        $result = $this->service->cleanupFailedGeneration($logoGeneration);

        expect($result['success'])->toBeTrue();
        expect(Storage::disk('public')->exists("logos/{$logoGeneration->id}"))->toBeFalse();
    });

    it('identifies and removes orphaned files', function (): void {
        // Create files for non-existent logo generation
        $nonExistentId = 99999;
        Storage::disk('public')->put("logos/{$nonExistentId}/originals/orphan.png", 'orphan content');

        $orphanedFiles = $this->service->findOrphanedFiles();
        $cleanedCount = $this->service->cleanupOrphanedFiles();

        expect($orphanedFiles)->toHaveCount(1);
        expect($cleanedCount)->toBe(1);
        expect(Storage::disk('public')->exists("logos/{$nonExistentId}/originals/orphan.png"))->toBeFalse();
    });
});

describe('File Security and Access Control', function (): void {
    it('validates file types for security', function (): void {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml'];
        $maliciousFile = UploadedFile::fake()->create('malware.exe', 1024, 'application/x-executable');

        $isValid = $this->service->validateFileType($maliciousFile, $allowedTypes);

        expect($isValid)->toBeFalse();
    });

    it('sanitizes uploaded file content', function (): void {
        $svgContent = '<?xml version="1.0"?><svg><script>alert("xss")</script><rect width="100" height="100"/></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $sanitized = $this->service->sanitizeSvgContent($file);

        expect($sanitized)->not->toContain('<script>');
        expect($sanitized)->not->toContain('alert');
        expect($sanitized)->toContain('<rect');
    });

    it('prevents path traversal attacks in filenames', function (): void {
        $maliciousName = '../../../etc/passwd';

        $safeName = $this->service->sanitizeFilename($maliciousName);

        expect($safeName)->not->toContain('../');
        expect($safeName)->not->toContain('/');
        expect($safeName)->toBe('etc-passwd');
    });

    it('enforces file size limits', function (): void {
        $largeFile = UploadedFile::fake()->create('large.png', 11 * 1024); // 11MB (over 10MB limit)

        $isValid = $this->service->validateFileSize($largeFile, 10 * 1024 * 1024); // 10MB limit

        expect($isValid)->toBeFalse();
    });

    it('validates image dimensions', function (): void {
        $tinyImage = UploadedFile::fake()->image('tiny.png', 10, 10); // Too small
        $validImage = UploadedFile::fake()->image('valid.png', 256, 256); // Just right

        expect($this->service->validateImageDimensions($tinyImage, 50, 50, 2048, 2048))->toBeFalse();
        expect($this->service->validateImageDimensions($validImage, 50, 50, 2048, 2048))->toBeTrue();
    });
});

describe('File Size Optimization', function (): void {
    it('compresses PNG images while maintaining quality', function (): void {
        $originalFile = UploadedFile::fake()->image('original.png', 256, 256);

        $result = $this->service->optimizePngImage($originalFile);

        expect($result)->toHaveKeys(['success', 'optimized_content', 'original_size', 'optimized_size', 'compression_ratio']);
        expect($result['success'])->toBeTrue();
        expect($result['optimized_size'])->toBeLessThanOrEqual($result['original_size']);
    });

    it('optimizes SVG files by removing unnecessary elements', function (): void {
        $svgContent = '<?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <!-- This is a comment -->
            <metadata>Some metadata</metadata>
            <rect width="50" height="50" fill="blue"/>
        </svg>';

        $originalFile = UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $result = $this->service->optimizeSvgImage($originalFile);

        expect($result['success'])->toBeTrue();
        expect($result['optimized_content'])->not->toContain('<!-- This is a comment -->');
        expect($result['optimized_content'])->not->toContain('<metadata>');
        expect($result['optimized_content'])->toContain('<rect');
        expect($result['optimized_size'])->toBeLessThan($result['original_size']);
    });

    it('creates progressive JPEG versions for web display', function (): void {
        $pngFile = UploadedFile::fake()->image('logo.png', 128, 128);

        $result = $this->service->createWebOptimizedVersion($pngFile, 'jpeg');

        expect($result)->toHaveKeys(['success', 'optimized_content', 'format', 'size_reduction']);
        expect($result['success'])->toBeTrue();
        expect($result['format'])->toBe('jpeg');
    });

    it('generates multiple size variants for responsive display', function (): void {
        $originalFile = UploadedFile::fake()->image('logo.png', 256, 256);
        $sizes = [64, 128, 192];

        $variants = $this->service->generateSizeVariants($originalFile, $sizes);

        expect($variants)->toHaveCount(3);
        foreach ($variants as $size => $variant) {
            expect($variant['success'])->toBeTrue();
            expect($variant['width'])->toBe($size);
            expect($variant['height'])->toBe($size);
        }
    });
});

describe('File Access and Download', function (): void {
    it('generates secure download URLs with expiration', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create();

        Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/logo.png", 'logo content');

        // Skip this test until route is defined
        $this->markTestSkipped('Route logos.download not yet defined');
    });

    it('creates ZIP archive for batch downloads', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $logos = GeneratedLogo::factory(3)->for($logoGeneration)->create();

        // Create test files
        foreach ($logos as $index => $logo) {
            Storage::disk('public')->put("logos/{$logoGeneration->id}/originals/logo{$index}.png", "logo content {$index}");
        }

        $result = $this->service->createBatchDownloadZip($logoGeneration->id);

        expect($result)->toHaveKeys(['success', 'zip_path', 'file_count', 'zip_size']);
        expect($result['success'])->toBeTrue();
        expect($result['file_count'])->toBe(3);
        expect(Storage::disk('public')->exists($result['zip_path']))->toBeTrue();
    });

    it('tracks download statistics', function (): void {
        $logoGeneration = LogoGeneration::factory()->create();
        $generatedLogo = GeneratedLogo::factory()->for($logoGeneration)->create();

        // Simulate downloads
        $this->service->trackDownload($generatedLogo, 'png', request()->ip());
        $this->service->trackDownload($generatedLogo, 'svg', request()->ip());

        $stats = $this->service->getDownloadStats($logoGeneration->id);

        expect($stats)->toHaveKeys(['total_downloads', 'format_breakdown', 'popular_logos']);
        expect($stats['total_downloads'])->toBe(2);
        expect($stats['format_breakdown'])->toHaveKeys(['png', 'svg']);
    });
});
