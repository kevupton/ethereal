<?php namespace Kevupton\Ethereal\Providers;

use Illuminate\Database\Eloquent\Model;
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

        Model::saving(function ($model) {
            if (!$model->validateModel || !method_exists($model, 'validate')) return;
            $model->validate();
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