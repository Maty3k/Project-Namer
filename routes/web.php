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

// Public share routes
Route::get('/share/{uuid}', [PublicShareController::class, 'show'])
    ->name('public-share.show');

Route::post('/share/{uuid}/authenticate', [PublicShareController::class, 'authenticate'])
    ->name('public-share.authenticate');

// Public export download
Route::get('/downloads/{uuid}', [ExportController::class, 'publicDownload'])
    ->name('public-download');

Route::get('dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('project/{uuid}', ProjectPage::class)
    ->middleware(['auth', 'verified'])
    ->name('project.show');

Route::get('logo-gallery/{logoGeneration}', App\Livewire\LogoGallery::class)
    ->middleware(['auth', 'verified'])
    ->name('logo-gallery');

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
