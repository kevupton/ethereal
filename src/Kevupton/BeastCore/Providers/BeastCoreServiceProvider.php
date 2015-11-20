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

        foreach (get_class_methods(CustomValidator::class) as $key) {
            if (preg_match('/validate[a-zA-Z]+/', $key)) {
                $snake = snake_case(str_replace('validate', '', $key));
                \Validator::extend($snake, CustomValidator::class . '@' . $key,
                    CustomValidator::$msgs[$snake]);
            } else if (preg_match('/replace[a-zA-Z]+/', $key)) {
                $snake = snake_case(str_replace('replace', '', $key));
                \Validator::replacer($snake, CustomValidator::class . '@' .$key);
            }

        }
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