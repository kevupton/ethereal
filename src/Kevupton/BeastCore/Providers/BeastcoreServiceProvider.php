<?php namespace Kevupton\BeastCore\Providers;

use Illuminate\Support\ServiceProvider;
use Kevupton\BeastCore\CustomValidator;

class BeastCoreServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        \Validator::resolver(function($translator, $data, $rules, $messages)
        {
            return new CustomValidator($translator, $data, $rules, $messages);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
//
    }
}