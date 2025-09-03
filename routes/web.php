<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\PublicShareController;
use App\Livewire\ProjectPage;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactorAuthenticationPage;
use Illuminate\Support\Facades\Route;

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

Route::get('logos', App\Livewire\LogoGalleryIndex::class)
    ->middleware(['auth', 'verified'])
    ->name('logos.index');

Route::get('logo-gallery/{logoGeneration}', App\Livewire\LogoGallery::class)
    ->middleware(['auth', 'verified'])
    ->name('logo-gallery');

Route::get('project/{uuid}/gallery', App\Livewire\PhotoGallery::class)
    ->middleware(['auth', 'verified'])
    ->name('project.gallery');

Route::get('themes', App\Livewire\ThemeCustomizer::class)
    ->middleware(['auth', 'verified'])
    ->name('themes.customizer');

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/two-factor-authentication', TwoFactorAuthenticationPage::class)->name('settings.two-factor-authentication');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Share management
    Route::get('shares', fn () => view('shares.index'))->name('shares.index');
});

require __DIR__.'/auth.php';
