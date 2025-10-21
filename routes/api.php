<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AIGenerationController;
use App\Http\Controllers\Api\DomainCheckController;
use App\Http\Controllers\Api\ErrorExplanationController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\KeyboardShortcutsController;
use App\Http\Controllers\Api\LogoGenerationController;
use App\Http\Controllers\Api\MoodBoardController;
use App\Http\Controllers\Api\PhotoGalleryController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\SuggestionDomainsController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\UserPreferencesController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function (): void {
    // AI Generation API Routes - Simple auth without CSRF
    Route::prefix('ai')->middleware(['auth'])->group(function (): void {
        // Generate names using AI models
        Route::post('generate-names', [AIGenerationController::class, 'generateNames'])
            ->middleware('throttle:10,1')
            ->name('api.ai.generate-names');

        // Get generation status and results
        Route::get('generation/{sessionId}', [AIGenerationController::class, 'show'])
            ->name('api.ai.generation.show');

        // Cancel generation
        Route::post('cancel-generation/{sessionId}', [AIGenerationController::class, 'cancel'])
            ->name('api.ai.cancel-generation');

        // Get available AI models
        Route::get('models', [AIGenerationController::class, 'models'])
            ->name('api.ai.models');

        // User preferences management
        Route::get('preferences', [UserPreferencesController::class, 'show'])
            ->name('api.ai.preferences.show');

        Route::put('preferences', [UserPreferencesController::class, 'update'])
            ->name('api.ai.preferences.update');

        // Generation history
        Route::get('history', [UserPreferencesController::class, 'history'])
            ->name('api.ai.history');
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

    // Image Upload API Routes
    Route::prefix('projects')->middleware(['auth'])->group(function (): void {
        // Upload images to project
        Route::post('{project}/images', [ImageUploadController::class, 'store'])
            ->middleware('throttle:30,1') // 30 uploads per minute
            ->name('api.projects.images.store');

        // Delete project image
        Route::delete('{project}/images/{image}', [ImageUploadController::class, 'destroy'])
            ->name('api.projects.images.destroy');

        // Gallery API Routes
        Route::get('{project}/gallery', [PhotoGalleryController::class, 'index'])
            ->middleware('throttle:120,1') // 120 requests per minute for gallery browsing
            ->name('api.projects.gallery.index');

        // Bulk actions must come before {uuid} routes to avoid route conflicts
        Route::delete('{project}/gallery/bulk', [PhotoGalleryController::class, 'bulkAction'])
            ->middleware('throttle:20,1') // 20 bulk operations per minute
            ->name('api.projects.gallery.bulk-delete');

        Route::put('{project}/gallery/bulk', [PhotoGalleryController::class, 'bulkAction'])
            ->middleware('throttle:20,1') // 20 bulk operations per minute
            ->name('api.projects.gallery.bulk-update');

        Route::get('{project}/gallery/{uuid}', [PhotoGalleryController::class, 'show'])
            ->name('api.projects.gallery.show');

        Route::put('{project}/gallery/{uuid}', [PhotoGalleryController::class, 'update'])
            ->name('api.projects.gallery.update');

        // Mood Board API Routes
        Route::get('{project}/mood-boards', [MoodBoardController::class, 'index'])
            ->middleware('throttle:60,1') // 60 requests per minute
            ->name('api.projects.mood-boards.index');

        Route::post('{project}/mood-boards', [MoodBoardController::class, 'store'])
            ->middleware('throttle:10,1') // 10 creations per minute
            ->name('api.projects.mood-boards.store');

        Route::get('{project}/mood-boards/{uuid}', [MoodBoardController::class, 'show'])
            ->name('api.projects.mood-boards.show');

        Route::put('{project}/mood-boards/{uuid}', [MoodBoardController::class, 'update'])
            ->middleware('throttle:30,1') // 30 updates per minute
            ->name('api.projects.mood-boards.update');

        Route::delete('{project}/mood-boards/{uuid}', [MoodBoardController::class, 'destroy'])
            ->middleware('throttle:10,1') // 10 deletions per minute
            ->name('api.projects.mood-boards.destroy');

        // Mood board image management
        Route::post('{project}/mood-boards/{uuid}/images', [MoodBoardController::class, 'addImages'])
            ->middleware('throttle:20,1') // 20 image additions per minute
            ->name('api.projects.mood-boards.images.add');

        Route::delete('{project}/mood-boards/{uuid}/images', [MoodBoardController::class, 'removeImages'])
            ->middleware('throttle:20,1') // 20 image removals per minute
            ->name('api.projects.mood-boards.images.remove');

        // Mood board sharing
        Route::post('{project}/mood-boards/{uuid}/share', [MoodBoardController::class, 'share'])
            ->middleware('throttle:5,1') // 5 share creations per minute
            ->name('api.projects.mood-boards.share');

        Route::delete('{project}/mood-boards/{uuid}/share', [MoodBoardController::class, 'unshare'])
            ->middleware('throttle:10,1') // 10 share revocations per minute
            ->name('api.projects.mood-boards.unshare');

        // Mood board export
        Route::post('{project}/mood-boards/{uuid}/export', [MoodBoardController::class, 'export'])
            ->middleware('throttle:5,1') // 5 exports per minute
            ->name('api.projects.mood-boards.export');
    });

    // Theme API Routes - Authenticated with rate limiting
    Route::prefix('themes')->middleware(['auth'])->group(function (): void {
        // Get current user theme preferences
        Route::get('preferences', [ThemeController::class, 'getPreferences'])
            ->name('api.themes.preferences');

        // Update user theme preferences
        Route::put('preferences', [ThemeController::class, 'updatePreferences'])
            ->middleware('throttle:30,1') // 30 updates per minute
            ->name('api.themes.preferences.update');

        // Get predefined theme collection
        Route::get('presets', [ThemeController::class, 'getPresets'])
            ->name('api.themes.presets');

        // Generate custom CSS for theme
        Route::post('generate-css', [ThemeController::class, 'generateCss'])
            ->middleware('throttle:60,1') // 60 CSS generations per minute
            ->name('api.themes.generate-css');

        // Validate accessibility of color combinations
        Route::post('validate-accessibility', [ThemeController::class, 'validateAccessibility'])
            ->middleware('throttle:60,1') // 60 validations per minute
            ->name('api.themes.validate-accessibility');

        // Import theme from file
        Route::post('import', [ThemeController::class, 'importTheme'])
            ->middleware('throttle:10,1') // 10 imports per minute
            ->name('api.themes.import');

        // Export current theme as file
        Route::get('export', [ThemeController::class, 'exportTheme'])
            ->name('api.themes.export');
    });

    // Error explanation endpoints
    Route::get('error-explanations/{code}', [ErrorExplanationController::class, 'show'])
        ->name('api.error-explanations.show');

    // Keyboard shortcuts API endpoint
    Route::get('keyboard-shortcuts', [KeyboardShortcutsController::class, 'index'])
        ->middleware(['auth'])
        ->name('api.keyboard-shortcuts');

    // Suggestion domains API endpoint - uses web middleware for session auth
    Route::get('suggestions/{suggestion}/domains', [SuggestionDomainsController::class, 'show'])
        ->middleware(['web', 'auth'])
        ->name('api.suggestions.domains');

    // Domain checking API endpoint - uses web middleware for session auth
    Route::post('suggestions/{suggestion}/check-domains', [DomainCheckController::class, 'check'])
        ->middleware(['web', 'auth'])
        ->name('api.suggestions.check-domains');

    // Logo Generation API Routes - uses web middleware for session auth
    Route::prefix('logos')->middleware(['web', 'auth'])->group(function (): void {
        // Generate logos for a business idea
        Route::post('generate', [LogoGenerationController::class, 'generate'])
            ->middleware('throttle:10,1') // 10 requests per minute
            ->name('api.logos.generate');

        // Get logo generation status and results
        Route::get('{logoGeneration}', [LogoGenerationController::class, 'show'])
            ->name('api.logos.show');
    });
});
