<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\PublicShareController;
use App\Livewire\Appearance;
use App\Livewire\KeyboardShortcuts;
use App\Livewire\ProjectPage;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactorAuthenticationPage;
use Illuminate\Support\Facades\Route;

// Favicon is served by Nginx directly from public/favicon.ico
// Note: Herd/Valet returns 404 status for all static files by design,
// but the file content IS served correctly. This is expected development behavior.
// In production with proper Nginx config, this will return 200 status.

Route::get('/', fn () => view('welcome'))->name('home');

// Debug route for testing name generation
Route::get('/test-generation', function () {
    $fallbackService = app(\App\Services\FallbackNameService::class);
    $names = $fallbackService->generateNames('innovative tech startup', 'creative', 5);

    return response()->json([
        'service' => 'working',
        'names' => $names,
        'count' => count($names),
    ]);
})->name('test-generation');

// Public share routes
Route::get('/share/{uuid}', [PublicShareController::class, 'show'])
    ->name('public-share.show');

Route::post('/share/{uuid}/authenticate', [PublicShareController::class, 'authenticate'])
    ->name('public-share.authenticate');

// Public mood board sharing
Route::get('/share/mood-board/{token}', [PublicShareController::class, 'showMoodBoard'])
    ->name('public.mood-boards.show');

// Public export download
Route::get('/downloads/{uuid}', [ExportController::class, 'publicDownload'])
    ->name('public-download');

Route::get('dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('project/{uuid}', ProjectPage::class)
    ->middleware(['auth', 'verified'])
    ->name('project.show');

Route::get('project/{uuid}/gallery', App\Livewire\PhotoGallery::class)
    ->middleware(['auth', 'verified'])
    ->name('project.gallery');

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/two-factor-authentication', TwoFactorAuthenticationPage::class)->name('settings.two-factor-authentication');

    // Standalone features (not part of settings)
    Route::get('appearance', Appearance::class)->name('appearance');
    Route::get('keyboard-shortcuts', KeyboardShortcuts::class)->name('keyboard-shortcuts');

    // Share management
    Route::get('shares', fn () => view('shares.index'))->name('shares.index');
});

require __DIR__.'/auth.php';
