<?php namespace Kevupton\Ethereal\Providers;

use Illuminate\Support\ServiceProvider;
use Kevupton\LaravelJsonResponse\Providers\LaravelJsonResponseProvider;

class EtherealServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->register(LaravelJsonResponseProvider::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}