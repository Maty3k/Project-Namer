<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LogoDownloadController;
use App\Http\Controllers\Api\LogoGenerationController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function (): void {
    // Logo Generation API Routes
    Route::prefix('logos')->group(function (): void {
        // Generate logos for a business idea - more restrictive rate limiting
        Route::post('generate', [LogoGenerationController::class, 'generate'])
            ->middleware('throttle.logos')
            ->name('api.logos.generate');

        // Get generation status and progress
        Route::get('{logoGeneration}/status', [LogoGenerationController::class, 'status'])
            ->name('api.logos.status');

        // Get generated logos with color scheme information
        Route::get('{logoGeneration}', [LogoGenerationController::class, 'show'])
            ->name('api.logos.show');

        // Apply color customization to logos
        Route::post('{logoGeneration}/customize', [LogoGenerationController::class, 'customize'])
            ->middleware('throttle.logos')
            ->name('api.logos.customize');

        // Download individual logo files - moderate rate limiting
        Route::get('{logoGeneration}/download/{generatedLogo}', [LogoDownloadController::class, 'download'])
            ->middleware('throttle.downloads')
            ->name('api.logos.download');

        // Download all logos as ZIP - moderate rate limiting
        Route::get('{logoGeneration}/download-batch', [LogoDownloadController::class, 'downloadBatch'])
            ->middleware('throttle.downloads')
            ->name('api.logos.download-batch');
    });
});
