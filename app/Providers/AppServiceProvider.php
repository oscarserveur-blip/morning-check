<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (app()->environment('production')) {
            $appUrl = config('app.url');
            if (!empty($appUrl)) {
                URL::forceRootUrl($appUrl);
            }
            if (str_starts_with((string) $appUrl, 'https://') || (bool) env('APP_FORCE_HTTPS', false)) {
                URL::forceScheme('https');
            }
        }
    }
}
