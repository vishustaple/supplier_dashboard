<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class mPDFServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('mpdf', function ($app) {
            return new \Mpdf\Mpdf();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
