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

    // Sharing API Routes - CSRF protected state-changing operations
    Route::prefix('shares')->middleware(['auth', 'web'])->group(function (): void {
        // List user's shares with filtering and pagination (read-only, no CSRF needed)
        Route::get('/', [ShareController::class, 'index'])
            ->withoutMiddleware('web')
            ->name('api.shares.index');

        // Create a new share (state-changing, requires CSRF)
        Route::post('/', [ShareController::class, 'store'])
            ->middleware('throttle.shares')
            ->name('api.shares.store');

        // Show specific share (read-only, no CSRF needed)
        Route::get('{share}', [ShareController::class, 'show'])
            ->withoutMiddleware('web')
            ->name('api.shares.show');

        // Update share (state-changing, requires CSRF)
        Route::put('{share}', [ShareController::class, 'update'])
            ->name('api.shares.update');

        // Deactivate share (state-changing, requires CSRF)
        Route::delete('{share}', [ShareController::class, 'destroy'])
            ->name('api.shares.destroy');

        // Get share analytics (read-only, no CSRF needed)
        Route::get('{share}/analytics', [ShareController::class, 'analytics'])
            ->withoutMiddleware('web')
            ->name('api.shares.analytics');

        // Get social media metadata (read-only, no CSRF needed)
        Route::get('{share}/metadata', [ShareController::class, 'metadata'])
            ->withoutMiddleware('web')
            ->name('api.shares.metadata');
    });

    // Export API Routes - CSRF protected state-changing operations
    Route::prefix('exports')->group(function (): void {
        // Authenticated export routes with CSRF protection for state changes
        Route::middleware(['auth', 'web'])->group(function (): void {
            // List user's exports with filtering and pagination (read-only, no CSRF needed)
            Route::get('/', [ExportController::class, 'index'])
                ->withoutMiddleware('web')
                ->name('api.exports.index');

            // Create a new export (state-changing, requires CSRF)
            Route::post('/', [ExportController::class, 'store'])
                ->middleware('throttle.exports')
                ->name('api.exports.store');

            // Get export analytics (read-only, no CSRF needed)
            Route::get('analytics', [ExportController::class, 'analytics'])
                ->withoutMiddleware('web')
                ->name('api.exports.analytics');

            // Cleanup expired exports (state-changing, requires CSRF)
            Route::delete('cleanup', [ExportController::class, 'cleanup'])
                ->name('api.exports.cleanup');

            // Show specific export (read-only, no CSRF needed)
            Route::get('{export}', [ExportController::class, 'show'])
                ->withoutMiddleware('web')
                ->name('api.exports.show');

            // Delete export (state-changing, requires CSRF)
            Route::delete('{export}', [ExportController::class, 'destroy'])
                ->name('api.exports.destroy');

            // Authenticated download (read-only, no CSRF needed)
            Route::get('{uuid}/download', [ExportController::class, 'download'])
                ->withoutMiddleware('web')
                ->name('api.exports.download');
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
