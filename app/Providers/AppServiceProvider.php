<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Export;
use App\Models\Share;
use App\Policies\ExportPolicy;
use App\Policies\SharePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Export::class, ExportPolicy::class);
        Gate::policy(Share::class, SharePolicy::class);
    }
}
