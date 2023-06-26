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
        $this->app->bind('App\Repositories\AnggaranRepository', 'App\Repositories\Elo\AnggaranImplement');
        $this->app->bind('App\Repositories\BankRepository', 'App\Repositories\Elo\BankImplement');
        $this->app->bind('App\Repositories\KategoriRepository', 'App\Repositories\Elo\KategoriImplement');
        $this->app->bind('App\Repositories\PinjamanRepository', 'App\Repositories\Elo\PinjamanImplement');
        $this->app->bind('App\Repositories\PinjamanDetilRepository', 'App\Repositories\Elo\PinjamanDetilImplement');
        $this->app->bind('App\Repositories\PiutangRepository', 'App\Repositories\Elo\PiutangImplement');
        $this->app->bind('App\Repositories\PiutangDetilRepository', 'App\Repositories\Elo\PiutangDetilImplement');
        $this->app->bind('App\Repositories\RekeningRepository', 'App\Repositories\Elo\RekeningImplement');
        $this->app->bind('App\Repositories\TransaksiRepository', 'App\Repositories\Elo\TransaksiImplement');
        $this->app->bind('App\Repositories\TransaksiFotoRepository', 'App\Repositories\Elo\TransaksiFotoImplement');
        $this->app->bind('App\Repositories\TransferRepository', 'App\Repositories\Elo\TransferImplement');
        $this->app->bind('App\Repositories\UserRepository', 'App\Repositories\Elo\UserImplement');
    }
}
