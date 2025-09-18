<?php
//Providers/AppServiceProvider.php
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

        // Register CacheService as singleton
        $this->app->singleton(\App\Services\CacheService::class, function ($app) {
            return new \App\Services\CacheService();
        });

        // Register CalorieCalculatorService as singleton
        $this->app->singleton(\App\Services\CalorieCalculatorService::class, function ($app) {
            return new \App\Services\CalorieCalculatorService();
        });

        // Register StreakCalculatorService as singleton
        $this->app->singleton(\App\Services\StreakCalculatorService::class, function ($app) {
            return new \App\Services\StreakCalculatorService();
        });

        // Register StatisticsService as singleton (depends on StreakCalculatorService)
        $this->app->singleton(\App\Services\StatisticsService::class, function ($app) {
            return new \App\Services\StatisticsService(
                $app->make(\App\Services\StreakCalculatorService::class)
            );
        });

        // Register WorkoutService as singleton (depends on CalorieCalculatorService and StatisticsService)
        $this->app->singleton(\App\Services\WorkoutService::class, function ($app) {
            return new \App\Services\WorkoutService(
                $app->make(\App\Services\CalorieCalculatorService::class),
                $app->make(\App\Services\StatisticsService::class)
            );
        });
    }

    public function boot()
    {
        Schema::defaultStringLength(191);
        
        if (config('app.force_https')) {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}