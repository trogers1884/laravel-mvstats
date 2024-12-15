<?php

namespace Trogers1884\LaravelMvstats;

use Illuminate\Support\ServiceProvider;
use Trogers1884\LaravelMvstats\Console\Commands\ResetMaterializedViewStats;

class LaravelMvstatsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ResetMaterializedViewStats::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Nothing to register yet
    }
}