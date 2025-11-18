<?php

declare(strict_types=1);

use App\Jobs\CleanupExpiredShares;
use App\Jobs\CleanupOldExports;
use App\Jobs\CleanupOldFilesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic file cleanup job
Schedule::job(new CleanupOldFilesJob(30, true))
    ->dailyAt('02:00')
    ->name('cleanup-old-files')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule domain cache cleanup
Schedule::command('domain:clear-expired-cache')
    ->dailyAt('03:00')
    ->name('clear-expired-domain-cache')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule expired shares cleanup
Schedule::job(new CleanupExpiredShares)
    ->daily()
    ->at('01:00')
    ->name('cleanup-expired-shares')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule old exports cleanup
Schedule::job(new CleanupOldExports)
    ->daily()
    ->at('01:30')
    ->name('cleanup-old-exports')
    ->withoutOverlapping()
    ->onOneServer();
