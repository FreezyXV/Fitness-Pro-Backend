<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    public function boot()
    {
        Schema::defaultStringLength(191);
        
        if (config('app.force_https')) {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}