<?php
//Providers/RouteServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // Important to keep parent boot call for route caching and macros
        parent::boot();

        // Ensure API routes are registered with correct middleware and prefix
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Web routes (optional)
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}