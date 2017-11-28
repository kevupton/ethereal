<?php namespace Kevupton\Ethereal\Providers;

use Illuminate\Support\ServiceProvider;
use Kevupton\Ethereal\Utils\JsonResponse;

class EtherealServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('eth.json', function () {
            return new JsonResponse();
        });
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