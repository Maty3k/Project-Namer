<?php

declare(strict_types=1);

use App\Models\UploadedLogo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
});

describe('UploadedLogo Model', function (): void {
    it('can create an uploaded logo with valid data', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'session_id' => 'test-session-123',
            'original_name' => 'company-logo.png',
            'file_path' => 'logos/uploaded/company-logo.png',
            'mime_type' => 'image/png',
            'file_size' => 125000,
        ]);

        expect($uploadedLogo)->toBeInstanceOf(UploadedLogo::class);
        expect($uploadedLogo->session_id)->toBe('test-session-123');
        expect($uploadedLogo->original_name)->toBe('company-logo.png');
        expect($uploadedLogo->mime_type)->toBe('image/png');
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $uploadedLogo = UploadedLogo::factory()->create(['user_id' => $user->id]);

        expect($uploadedLogo->user)->toBeInstanceOf(User::class);
        expect($uploadedLogo->user->id)->toBe($user->id);
    });

    it('can have null user for guest uploads', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create(['user_id' => null]);

        expect($uploadedLogo->user)->toBeNull();
    });

    it('can scope by session', function (): void {
        $sessionId = 'test-session-123';

        UploadedLogo::factory()->count(3)->create(['session_id' => $sessionId]);
        UploadedLogo::factory()->count(2)->create(['session_id' => 'other-session']);

        $sessionLogos = UploadedLogo::forSession($sessionId)->get();

        expect($sessionLogos->count())->toBe(3);
        expect($sessionLogos->every(fn ($logo) => $logo->session_id === $sessionId))->toBeTrue();
    });

    it('can scope by user', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UploadedLogo::factory()->count(2)->create(['user_id' => $user1->id]);
        UploadedLogo::factory()->count(3)->create(['user_id' => $user2->id]);

        $userLogos = UploadedLogo::forUser($user1->id)->get();

        expect($userLogos->count())->toBe(2);
        expect($userLogos->every(fn ($logo) => $logo->user_id === $user1->id))->toBeTrue();
    });

    it('can scope by category', function (): void {
        UploadedLogo::factory()->count(2)->create(['category' => 'brand']);
        UploadedLogo::factory()->count(3)->create(['category' => 'icon']);
        UploadedLogo::factory()->create(['category' => null]);

        $brandLogos = UploadedLogo::ofCategory('brand')->get();

        expect($brandLogos->count())->toBe(2);
        expect($brandLogos->every(fn ($logo) => $logo->category === 'brand'))->toBeTrue();
    });

    it('can scope by mime type', function (): void {
        UploadedLogo::factory()->count(2)->create(['mime_type' => 'image/png']);
        UploadedLogo::factory()->count(3)->create(['mime_type' => 'image/svg+xml']);

        $pngLogos = UploadedLogo::ofMimeType('image/png')->get();

        expect($pngLogos->count())->toBe(2);
        expect($pngLogos->every(fn ($logo) => $logo->mime_type === 'image/png'))->toBeTrue();
    });

    it('can get file extension from original name', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'original_name' => 'my-company-logo.png',
        ]);

        expect($uploadedLogo->getFileExtension())->toBe('png');
    });

    it('can format file size', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create(['file_size' => 1024]);
        expect($uploadedLogo->getFormattedFileSize())->toBe('1 KB');

        $uploadedLogo = UploadedLogo::factory()->create(['file_size' => 1048576]);
        expect($uploadedLogo->getFormattedFileSize())->toBe('1 MB');

        $uploadedLogo = UploadedLogo::factory()->create(['file_size' => 512]);
        expect($uploadedLogo->getFormattedFileSize())->toBe('512 B');
    });

    it('can check if file exists', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'file_path' => 'logos/uploaded/test-logo.png',
        ]);

        // File doesn't exist initially
        expect($uploadedLogo->fileExists())->toBeFalse();

        // Create the file
        Storage::disk('public')->put('logos/uploaded/test-logo.png', 'fake content');

        expect($uploadedLogo->fileExists())->toBeTrue();
    });

    it('can get file URL', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'file_path' => 'logos/uploaded/test-logo.png',
        ]);

        $url = $uploadedLogo->getFileUrl();

        expect($url)->toBeString();
        expect($url)->toContain('test-logo.png');
    });

    it('can generate download filename', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'original_name' => 'My Company Logo.png',
        ]);

        $filename = $uploadedLogo->generateDownloadFilename();

        expect($filename)->toBe('my-company-logo.png');
    });

    it('can generate download filename with custom format', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'original_name' => 'company-logo.png',
        ]);

        $filename = $uploadedLogo->generateDownloadFilename('svg');

        expect($filename)->toBe('company-logo.svg');
    });

    it('can detect SVG files', function (): void {
        $svgLogo = UploadedLogo::factory()->create(['mime_type' => 'image/svg+xml']);
        $pngLogo = UploadedLogo::factory()->create(['mime_type' => 'image/png']);

        expect($svgLogo->isSvg())->toBeTrue();
        expect($pngLogo->isSvg())->toBeFalse();
    });

    it('can detect raster images', function (): void {
        $pngLogo = UploadedLogo::factory()->create(['mime_type' => 'image/png']);
        $jpegLogo = UploadedLogo::factory()->create(['mime_type' => 'image/jpeg']);
        $svgLogo = UploadedLogo::factory()->create(['mime_type' => 'image/svg+xml']);

        expect($pngLogo->isRasterImage())->toBeTrue();
        expect($jpegLogo->isRasterImage())->toBeTrue();
        expect($svgLogo->isRasterImage())->toBeFalse();
    });

    it('can get display name', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'original_name' => 'my-awesome-company-logo.png',
        ]);

        expect($uploadedLogo->getDisplayName())->toBe('my-awesome-company-logo');
    });

    it('casts integer fields properly', function (): void {
        $user = User::factory()->create();
        $uploadedLogo = UploadedLogo::factory()->create([
            'file_size' => '125000',
            'image_width' => '800',
            'image_height' => '600',
            'user_id' => (string) $user->id,
        ]);

        expect($uploadedLogo->file_size)->toBeInt();
        expect($uploadedLogo->image_width)->toBeInt();
        expect($uploadedLogo->image_height)->toBeInt();
        expect($uploadedLogo->user_id)->toBeInt();
    });

    it('handles null values for optional fields', function (): void {
        $uploadedLogo = UploadedLogo::factory()->create([
            'user_id' => null,
            'image_width' => null,
            'image_height' => null,
            'category' => null,
            'description' => null,
        ]);

        expect($uploadedLogo->user_id)->toBeNull();
        expect($uploadedLogo->image_width)->toBeNull();
        expect($uploadedLogo->image_height)->toBeNull();
        expect($uploadedLogo->category)->toBeNull();
        expect($uploadedLogo->description)->toBeNull();
    });
});
