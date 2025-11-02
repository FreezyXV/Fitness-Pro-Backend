<?php

namespace App\Providers;

use App\Repositories\Contracts\WorkoutRepositoryInterface;
use App\Repositories\WorkoutRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind WorkoutRepository
        $this->app->bind(
            WorkoutRepositoryInterface::class,
            WorkoutRepository::class
        );

        // Add more repository bindings here as you create them
        // Example:
        // $this->app->bind(
        //     ExerciseRepositoryInterface::class,
        //     ExerciseRepository::class
        // );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
