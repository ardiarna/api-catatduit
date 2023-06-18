<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Repositories\AdjustRepository', 'App\Repositories\Elo\AdjustImplement');
        $this->app->bind('App\Repositories\UserRepository', 'App\Repositories\Elo\UserImplement');
    }
}
