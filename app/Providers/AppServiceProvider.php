<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Export;
use App\Models\LogoGeneration;
use App\Models\Share;
use App\Policies\ExportPolicy;
use App\Policies\LogoGenerationPolicy;
use App\Policies\SharePolicy;
use App\Services\OpenAILogoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register OpenAI Logo Service with API key from config
        $this->app->singleton(fn ($app): \App\Services\OpenAILogoService => new OpenAILogoService(
            apiKey: config('services.openai.api_key')
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Export::class, ExportPolicy::class);
        Gate::policy(LogoGeneration::class, LogoGenerationPolicy::class);
        Gate::policy(Share::class, SharePolicy::class);
    }
}
