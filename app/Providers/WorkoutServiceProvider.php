<?php
//Providers/WorkoutServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CalorieCalculatorService;
use App\Services\WorkoutService;
use App\Services\StatisticsService;
use App\Services\StreakCalculatorService;

class WorkoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalorieCalculatorService::class, function ($app) {
            return new CalorieCalculatorService();
        });

        $this->app->singleton(StreakCalculatorService::class, function ($app) {
            return new StreakCalculatorService();
        });

        $this->app->singleton(StatisticsService::class, function ($app) {
            return new StatisticsService(
                $app->make(StreakCalculatorService::class)
            );
        });

        $this->app->singleton(WorkoutService::class, function ($app) {
            return new WorkoutService(
                $app->make(CalorieCalculatorService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
