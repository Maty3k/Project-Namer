<?php

declare(strict_types=1);

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
