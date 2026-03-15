<?php

namespace Frex\CrudGenerator;

use Frex\CrudGenerator\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Class FrexCrudServiceProvider.
 */
class FrexCrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/config/frex-crud.php' => config_path('frex-crud.php'),
        ], 'frex-crud-config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/frex-crud.php', 'frex-crud');
    }
}
