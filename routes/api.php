<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ErrorExplanationController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\LogoDownloadController;
use App\Http\Controllers\Api\LogoGenerationController;
use App\Http\Controllers\Api\ShareController;
use Illuminate\Support\Facades\Cache;
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

        // Retry failed logo generation
        Route::post('{logoGeneration}/retry', [LogoGenerationController::class, 'retry'])
            ->middleware('throttle.logos')
            ->name('api.logos.retry');

        // Complete partial logo generation
        Route::post('{logoGeneration}/complete', [LogoGenerationController::class, 'complete'])
            ->middleware('throttle.logos')
            ->name('api.logos.complete');

        // Download individual logo files - moderate rate limiting
        Route::get('{logoGeneration}/download/{generatedLogo}', [LogoDownloadController::class, 'download'])
            ->middleware('throttle.downloads')
            ->name('api.logos.download');

        // Download all logos as ZIP - moderate rate limiting
        Route::get('{logoGeneration}/download-batch', [LogoDownloadController::class, 'downloadBatch'])
            ->middleware('throttle.downloads')
            ->name('api.logos.download-batch');
    });

    // Sharing API Routes
    Route::prefix('shares')->middleware('auth')->group(function (): void {
        // List user's shares with filtering and pagination
        Route::get('/', [ShareController::class, 'index'])
            ->name('api.shares.index');

        // Create a new share
        Route::post('/', [ShareController::class, 'store'])
            ->middleware('throttle.shares')
            ->name('api.shares.store');

        // Show specific share
        Route::get('{share}', [ShareController::class, 'show'])
            ->name('api.shares.show');

        // Update share
        Route::put('{share}', [ShareController::class, 'update'])
            ->name('api.shares.update');

        // Deactivate share
        Route::delete('{share}', [ShareController::class, 'destroy'])
            ->name('api.shares.destroy');

        // Get share analytics
        Route::get('{share}/analytics', [ShareController::class, 'analytics'])
            ->name('api.shares.analytics');

        // Get social media metadata
        Route::get('{share}/metadata', [ShareController::class, 'metadata'])
            ->name('api.shares.metadata');
    });

    // Export API Routes
    Route::prefix('exports')->group(function (): void {
        // Authenticated export routes
        Route::middleware('auth')->group(function (): void {
            // List user's exports with filtering and pagination
            Route::get('/', [ExportController::class, 'index'])
                ->name('api.exports.index');

            // Create a new export
            Route::post('/', [ExportController::class, 'store'])
                ->middleware('throttle.exports')
                ->name('api.exports.store');

            // Show specific export
            Route::get('{export}', [ExportController::class, 'show'])
                ->name('api.exports.show');

            // Delete export
            Route::delete('{export}', [ExportController::class, 'destroy'])
                ->name('api.exports.destroy');

            // Get export analytics
            Route::get('analytics', [ExportController::class, 'analytics'])
                ->name('api.exports.analytics');

            // Authenticated download
            Route::get('{uuid}/download', [ExportController::class, 'download'])
                ->name('api.exports.download');

            // Cleanup expired exports (admin/system)
            Route::delete('cleanup', [ExportController::class, 'cleanup'])
                ->name('api.exports.cleanup');
        });
    });

    // Error explanation endpoints
    Route::get('error-explanations/{code}', [ErrorExplanationController::class, 'show'])
        ->name('api.error-explanations.show');

    // Logo styles endpoint with graceful degradation
    Route::get('logo-styles', function () {
        // Check cache first for graceful degradation
        $styles = Cache::get('logo_styles');

        if ($styles) {
            return response()->json([
                'styles' => $styles,
                'from_cache' => true,
                'message' => 'Showing cached options. Live updates temporarily unavailable.',
            ]);
        }

        return response()->json([
            'styles' => ['minimalist', 'modern', 'playful', 'corporate'],
            'from_cache' => false,
        ]);
    })->name('api.logo-styles');
});
