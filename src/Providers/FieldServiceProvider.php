<?php

namespace NovaAttachMany\Providers;

use Illuminate\Support\Facades\Route;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('nova-attach-many', __DIR__.'/../../dist/js/field.js');
            Nova::style('nova-attach-many', __DIR__.'/../../dist/css/field.css');
        });

        $this->app->booted(function () {
            Route::middleware(['nova'])
                ->prefix('nova-vendor/nova-attach-many')
                ->group(__DIR__.'/../../routes/api.php');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
